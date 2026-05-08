# Tavan Backend — Agent Quick-Start

**Read this before touching any backend code.**

---

## What This Is

Laravel 13 REST API + Filament 4 admin panel powering the Tavan mobile app (React Native / Expo).
- **API:** `api.tavan.store/api/v1`
- **Admin:** `admin.tavan.store`
- **Mobile app:** `/Volumes/SSD/tavan-mobile`

---

## Tech Stack

| Thing | What |
|-------|------|
| Framework | Laravel 13 |
| Auth | Sanctum (personal access tokens) |
| DB | MySQL 8 (ULIDs as primary keys) |
| Images | Cloudflare R2 (S3-compatible) |
| Real-time | Laravel Reverb (WebSocket, Pusher protocol) |
| Admin | Filament 4 |
| API docs | Scramble (auto-generated OpenAPI at `/docs/api`) |
| Local dev | Laravel Sail (Docker) |
| Queue/cache | Redis |

---

## Local Dev Commands

```bash
./vendor/bin/sail up -d          # start MySQL + Redis + Reverb
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail artisan test
./vendor/bin/sail down
```

URLs: `http://localhost/api/v1` · `http://localhost/telescope` · `http://localhost/docs/api` · `http://localhost/admin`

---

## Project Structure

```
app/
  Http/
    Controllers/Api/   # One controller per resource (thin — parse, call service, return resource)
    Middleware/         # auth:sanctum + any custom middleware
    Requests/           # Form request validation (one per action)
    Resources/          # API resources — define exact response shapes
  Models/               # Eloquent models (all use HasUlids)
  Services/             # Business logic (ConversationService, OrderService, etc.)
  Policies/             # Authorization — called via $this->authorize() in controllers
  Events/               # Broadcast events for Reverb
  Filament/             # Filament resources/pages for admin panel

database/
  migrations/           # All table definitions (source of truth for schema)
  seeders/              # Dev seed data

routes/
  api.php               # All API routes — versioned under prefix('v1')
```

---

## Models & Primary Keys

All models use `HasUlids` — primary keys are ULIDs (sortable, URL-safe, not guessable).

| Model | Table | Notes |
|-------|-------|-------|
| `User` | `users` | Auth + profile; implements `FilamentUser` |
| `Product` | `products` | Statuses: `draft`, `active`, `reserved`, `sold` |
| `ProductImage` | `product_images` | `sort_order` assigned sequentially |
| `Brand` | `brands` | Admin-managed |
| `BrandSuggestion` | `brand_suggestions` | User-submitted suggestions |
| `Category` | `categories` | Admin-managed |
| `ShippingOption` | `shipping_options` | Admin-managed |
| `Conversation` | `conversations` | Unique per user pair |
| `Message` | `messages` | `type`: `text`, `system_offer`, `system_trade`, `system_order`, `system_inquiry`, `system_status` |
| `Offer` | `offers` | Statuses: `pending`, `accepted`, `declined`, `countered` |
| `Trade` | `trades` | Statuses: `pending`, `accepted`, `declined`, `countered` |
| `Order` | `orders` | Statuses: `pending`, `accepted`, `shipped`, `delivered`, `completed`, `declined` |
| `Review` | `reviews` | One per role per order (max 2 per order) |
| `UserPreference` | `user_preferences` | Feed/size preferences (hasOne on User) |
| `UserAddress` | `user_addresses` | Multiple per user |
| `UserBlock` | `user_blocks` | User blocking |
| `UserReport` | `user_reports` | User reporting |
| `PushToken` | `push_tokens` | Expo push tokens |
| `BlogPost` | `blog_posts` | Editorial content |
| `SupportInquiry` | `support_inquiries` | Contact form |
| `WishlistItem` | `wishlist_items` | User ↔ Product |

---

## Auth Flow

1. Mobile `POST /auth/login` → `LocalAuthProvider` validates email+password → issues Sanctum token
2. Mobile sends `Authorization: Bearer <token>` on every request
3. `auth:sanctum` middleware verifies token, injects `$request->user()`
4. Social login: mobile sends id_token → `SocialAuthController` via Laravel Socialite
5. OTP password reset: `forgotPassword` → `verifyResetOtp` → `resetPassword`

---

## Response Shapes (never change without updating mobile)

```json
// Single resource
{ "data": { ... } }

// Paginated list
{ "data": [...], "meta": { "current_page": 1, "last_page": 5, "per_page": 20, "total": 100 } }

// Error
{ "message": "...", "errors": { "field": ["..."] } }
```

Response shapes are enforced by API Resources in `app/Http/Resources/`.  
**Never change a response key name without also updating `src/api/*.service.js` in the mobile app.**

---

## Key Business Rules

- **One conversation per user pair** — `conversations` has a unique constraint on `(participant_one_id, participant_two_id)`. `POST /conversations` returns existing if found.
- **Accepting an offer auto-creates an order** — `OfferService::accept()` creates the `orders` row and sends a `system_order` message.
- **System messages** — offers/trades/orders create a `messages` row with `type` + `payload = {"offerId": "..."}`. Mobile fetches the entity separately.
- **Product status flow** — `draft` → `active` (via publish) → `reserved` (optional) → `sold`. Only `active` products appear in feeds.
- **Review constraint** — one review per role per order; max 2 per order. `rating` and `total_reviews` are cached on the `users` table and recalculated via `ReviewService`.
- **Image upload** — sequential multipart uploads; `sort_order` assigned via `MAX(sort_order) + 1` per product.
- **Brands/Categories** — admin-managed via Filament; never hardcode in mobile.

---

## Services

| Service | Responsibility |
|---------|---------------|
| `ConversationService` | find/create conversation, send message, create system messages, mark read |
| `OrderService` | create order, status transitions, order number generation |
| `ReviewService` | create review, recalculate user rating |
| `ImageService` | upload to R2, delete, reorder |
| `PushNotificationService` | send via Expo Push API |
| `Auth/LocalAuthProvider` | email+password auth |
| `Auth/LogSmsProvider` | OTP via log (dev); swap for real SMS provider |

---

## Adding a New Endpoint (checklist)

1. **Migration** (if new table/column): `sail artisan make:migration`
2. **Model**: `sail artisan make:model` — add `HasUlids`, fillable, relations
3. **Form Request**: `sail artisan make:request` — validation rules
4. **API Resource**: `sail artisan make:resource` — define response shape
5. **Service** (if business logic): add to `app/Services/`
6. **Policy**: `sail artisan make:policy` — authorize owner-only actions
7. **Controller**: thin — `$this->authorize()`, call service, return resource
8. **Route**: add to `routes/api.php` under the correct middleware group
9. **Update mobile**: update `src/api/*.service.js` if response shape changed

---

## Where to Look for Things

| Question | Location |
|----------|---------|
| All API routes | `routes/api.php` |
| Response shapes | `app/Http/Resources/` |
| Validation rules | `app/Http/Requests/` |
| Business logic | `app/Services/` |
| DB schema | `database/migrations/` |
| Auth abstraction | `app/Services/Auth/` |
| Broadcast events | `app/Events/` |
| Admin panel | `app/Filament/` |
| Full endpoint reference | `docs/API.md` |
| Architecture decisions | `docs/ARCHITECTURE.md` |
| Auto-generated OpenAPI | `http://localhost/docs/api` |

# Tavan Backend — CLAUDE.md

## Project Overview

Tavan is a Bosnian secondhand fashion marketplace (think Vinted for Bosnia). This is the Laravel 13 REST API + Filament admin panel that powers the Tavan mobile app.

- **Mobile app**: `/Users/macbookpro/Desktop/tavan-mobile` (React Native / Expo)
- **Backend**: this directory — Laravel 13, MySQL 8, Laravel Sail (Docker)
- **Language/region**: Bosnia and Herzegovina; prices in KM (Convertible Mark); UI text in Bosnian/Croatian

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 13 |
| Database | MySQL 8 |
| Auth | Laravel Sanctum (token-based) |
| Image storage | Cloudflare R2 (S3-compatible via Laravel filesystem) |
| Real-time | Laravel Reverb (WebSockets, Pusher protocol) |
| Admin panel | Filament 4 |
| API docs | Scramble (auto-generated OpenAPI, no annotations) |
| Local dev | Laravel Sail (Docker) |
| Hosting | Laravel Forge + Laravel VPS |
| Queue/cache | Redis |

## Domain Setup

- `tavan.store` — landing page (separate, untouched)
- `api.tavan.store` — this Laravel API
- `admin.tavan.store` — Filament admin panel
- DNS managed via GoDaddy

## Local Development

```bash
# Start all services (MySQL, Redis, Reverb)
./vendor/bin/sail up -d

# Run migrations
./vendor/bin/sail artisan migrate

# Run migrations + seed
./vendor/bin/sail artisan migrate:fresh --seed

# Run tests
./vendor/bin/sail artisan test

# Tail logs
./vendor/bin/sail artisan telescope

# Stop services
./vendor/bin/sail down
```

API runs at: http://localhost/api  
Telescope (debug dashboard): http://localhost/telescope  
API docs (Scramble): http://localhost/docs/api  
Filament admin: http://localhost/admin  

## Authentication Flow

Laravel owns auth end-to-end. Firebase is not used.

1. Mobile calls `POST /api/v1/auth/register` or `/login` with credentials
2. Laravel verifies via `AuthProviderInterface` (currently `LocalAuthProvider`)
3. Laravel issues a **Sanctum personal access token**
4. Mobile stores that token and sends `Authorization: Bearer <sanctum_token>` on every request
5. Phone OTP: mobile calls `/auth/phone/send-otp` → receives SMS code → verifies via `/auth/phone/verify-otp`
6. Google/Apple social login: mobile gets id_token from Expo packages → sends to `/auth/social/{provider}` (Phase 2b)

**Auth abstraction**: `AuthProviderInterface` + `SmsProviderInterface` allow swapping providers without touching mobile.
- `LocalAuthProvider` — current implementation (email/password + Sanctum)
- `LogSmsProvider` — dev SMS driver (logs OTP to `storage/logs/laravel.log`)
- To add Twilio: implement `SmsProviderInterface`, swap binding in `AppServiceProvider`

## Architecture

```
Mobile App (React Native)
    │
    │ HTTP requests (Bearer: Sanctum token)
    ▼
Laravel REST API (api.tavan.store)
    │
    ├── Sanctum (token verification)
    ├── MySQL (business data)
    ├── Cloudflare R2 (images)
    └── Laravel Reverb (WebSocket events)

Filament Admin (admin.tavan.store)
    └── Same MySQL database, separate auth (email/password)
```

## Project Structure

```
app/
  Http/
    Controllers/Api/     # API controllers (one per resource)
    Middleware/          # Auth middleware
    Requests/            # Form request validation
    Resources/           # API resources (response shaping)
  Models/                # Eloquent models
  Services/              # Business logic (OfferService, OrderService, etc.)

database/
  migrations/            # All table definitions
  seeders/               # Development seed data

routes/
  api.php                # All API routes (versioned: /api/v1/...)
  web.php                # Minimal (health check only)

docs/
  API.md                 # Complete endpoint reference
  DATABASE_SCHEMA.md     # Table definitions and relationships
  ARCHITECTURE.md        # System design decisions
```

## API Response Contract

All responses must match the shape the mobile app expects (defined by mock server).  
**Never change response shapes without updating the mobile `/src/api/*.service.js` files.**

Standard success response:
```json
{ "data": { ... } }
```

Paginated response:
```json
{
  "data": [ ... ],
  "meta": { "current_page": 1, "last_page": 5, "per_page": 20, "total": 100 }
}
```

Error response:
```json
{ "message": "...", "errors": { "field": ["..."] } }
```

## Key Business Rules

- **One conversation per user pair** (not per product). Multiple offers/trades/orders live in the same conversation thread.
- **Single user table** — buyers and sellers are the same entity. `rating` and `total_reviews` are cached on the user record.
- **System messages** — offers, trades, orders create `messages` rows with a `type` field (`system_offer`, `system_trade`, `system_order`, `system_inquiry`, `system_status`) and a `payload` JSON column with the entity ID.
- **Order from offer** — when a seller accepts an offer, an order is automatically created with `offer_id` set.
- **Review constraint** — one review per role per order (buyer can review seller, seller can review buyer; max 2 reviews per order).
- **Product status flow**: `draft` → `active` → `sold`. Only `active` products appear in feeds/search.
- **Brands + Categories are admin-managed** — mobile app reads these from the API; never hardcoded in mobile.

## Development Phases

- [x] Phase 1 — Project scaffold, Docker, DB migrations (Laravel 13, Sail, MySQL, Redis)
- [x] Phase 2 — Auth (Sanctum, LocalAuthProvider, PhoneVerificationService, UserResource, routes wired)
- [x] Phase 3 — All DB migrations (16 tables + personal_access_tokens)
- [x] Phase 4 — Core API endpoints (catalog, users, products, wishlist — 25 routes)
- [x] Phase 5 — Transactions (offers, trades, orders, reviews — 44 routes total)
- [x] Phase 6 — Messaging (conversations, messages, Reverb WebSockets — 49 routes total)
- [x] Phase 7 — Image upload (Cloudflare R2 via S3-compatible driver)
- [ ] Phase 8 — Filament admin panel (brands, categories, users, products)
- [ ] Phase 9 — Push notifications
- [ ] Phase 10 — Production deployment (Forge + Laravel VPS)

## Installed Packages

- `filament/filament` ^4.0 — admin panel at /admin
- `laravel/sanctum` ^4.3 — API token issuance (`personal_access_tokens` table)
- `laravel/reverb` ^1.10 — WebSocket server
- `laravel/socialite` ^5.27 — Google + Apple social login (Phase 2b)
- `laravel/telescope` ^5.20 — local debug dashboard at /telescope
- `dedoc/scramble` ^0.13.20 — auto-generates OpenAPI docs at /docs/api (no annotations needed)

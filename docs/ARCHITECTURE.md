# Tavan Backend — Architecture & Design Decisions

## System Overview

```
Mobile App (React Native / Expo)
    │
    │  HTTPS  Authorization: Bearer <sanctum_token>
    ▼
┌─────────────────────────────────────────────────┐
│              api.tavan.store                     │
│              Laravel 13 REST API                 │
│                                                  │
│  auth:sanctum middleware                         │
│    └── verifies Sanctum personal access token   │
│    └── resolves User model                       │
│                                                  │
│  Controllers → Services → Models                │
│                                                  │
│  Events → Laravel Reverb (WebSocket)            │
└──────────┬──────────────────────┬───────────────┘
           │                      │
        MySQL 8              Cloudflare R2
     (business data)            (images)
           │
     Redis (cache + queues)

┌─────────────────────────────────────────────────┐
│              admin.tavan.store                   │
│              Filament 4 Admin Panel              │
│              Same MySQL database                 │
│              Separate session auth               │
└─────────────────────────────────────────────────┘
```

---

## Key Design Decisions

### 1. Sanctum Token Auth

Laravel owns auth end-to-end. Mobile sends email+password → Laravel issues a Sanctum personal access token. Mobile stores the token and sends `Authorization: Bearer <token>` on every request.

Auth is abstracted via `AuthProviderInterface` + `SmsProviderInterface`:
- `LocalAuthProvider` — current implementation (email/password + Sanctum)
- `LogSmsProvider` — dev SMS driver (OTPs logged to `storage/logs/laravel.log`)
- Social login via Laravel Socialite: `POST /auth/social/google` and `/auth/social/apple`
- Password reset via OTP: `forgotPassword` → `verifyResetOtp` → `resetPassword`

### 2. One Conversation Per User Pair

Conversations are unique on `(participant_one_id, participant_two_id)`. All offers, trades, and orders between two users appear as system messages in the same thread. This matches the mobile UX where there's one chat per person, not per product.

### 3. System Messages for Transactions

When an offer/trade/order is created, two things happen:
1. The entity is created in its own table (`offers`, `trades`, `orders`)
2. A `messages` row is inserted with `type = system_offer|system_trade|system_order|system_inquiry|system_status` and `payload = { "offerId": "..." }`

The mobile app reads the payload, fetches the entity, and renders the appropriate card in the chat timeline.

### 4. Cached Counters

`users.rating`, `users.total_reviews`, and `products.likes_count` are cached counts/averages updated via model observers. Avoids expensive aggregation queries on every request.

### 5. Admin-Managed Catalog

Brands are stored in MySQL and managed via Filament. The mobile app fetches them from `/api/v1/brands`.

Categories and shipping/delivery options are defined statically in the mobile client (`src/constants/`) and are not served from the API. This keeps them fast (no network round-trip) and avoids the overhead of admin wiring for data that rarely changes.

### 6. Response Shape Contract

The mobile app's `src/api/*.service.js` files define the exact shape expected from each endpoint. Laravel API Resources (`app/Http/Resources/`) enforce these shapes. **Never change a response shape without updating both sides.**

Standard success:
```json
{ "data": { ... } }
```
Paginated:
```json
{ "data": [...], "meta": { "current_page": 1, "last_page": 5, "per_page": 20, "total": 100 } }
```
Error:
```json
{ "message": "...", "errors": { "field": ["..."] } }
```

### 7. Image Storage via Cloudflare R2

Images uploaded via multipart to Cloudflare R2 (S3-compatible, zero egress fees). Laravel uses the `s3` filesystem driver pointed at R2's endpoint. CDN delivery is automatic via Cloudflare.

Sequential uploads from the mobile app preserve `sort_order` (assigned via `MAX + 1` per product).

### 8. Real-time via Laravel Reverb

Self-hosted WebSocket server using the Pusher protocol. Events broadcast on private channels per user. Mobile subscribes using `@pusher/pusher-websocket-react-native`. No third-party WebSocket service needed.

### 9. Thin Controllers, Service Layer for Business Logic

Controllers parse request, call service, return resource. Business logic lives in `app/Services/`:
- `ConversationService` — message sending, system message creation, read marking
- `OrderService` — create, status transitions, order number generation
- `ReviewService` — create, rating recalculation on user
- `ImageService` — upload, delete, reorder
- `PushNotificationService` — send via Expo Push API
- `UserDeletionService` — cancel active orders on deletion request; full anonymization (purge job + admin force-delete)
- `ViewCountService` — debounced increment of `products.view_count` and `users.profile_view_count`
- `app/Services/Auth/` — auth providers (LocalAuthProvider, SmsProvider, etc.)

### 10. 30-Day Account Deletion Grace Period

When a user deletes their account, `deletion_requested_at` is set to `now()` and all active orders are immediately cancelled (the other party is notified via system message). The account and all its products are hidden from public feeds and search instantly — no products visible, seller profile returns 404.

If the user tries to log in during the grace period, the API returns `423` with `account_pending_deletion`, a `deletionDate`, and a short-lived `recoveryToken`. The mobile app shows a recovery screen; the user can call `DELETE /users/me/deletion` (authenticated with the recovery token) to clear `deletion_requested_at` and resume normally.

After 30 days, the `users:purge-pending-deletions` Artisan command (scheduled daily) runs `UserDeletionService::anonymize()` which locks conversations, deletes R2 assets, removes non-sold products, and overwrites all PII. The user row is kept to preserve order/review history for the other party (same pattern as Vinted/Depop; anonymized data is outside GDPR scope).

### 11. Authorization via Policies

All authenticated resource routes use Laravel policies. Users can only modify their own resources. Policy checks happen in the controller via `$this->authorize()`.

---

## Security

- All API routes (except catalog, products, user profiles, blog, search) require a valid Sanctum token
- Policy-based authorization — users cannot modify other users' resources
- File uploads validated for type (images only) and size
- Rate limiting on auth and public endpoints
- CORS configured for mobile app origins only
- User blocking and reporting system (`user_blocks`, `user_reports` tables)

---

## Deployment

- **Local**: Laravel Sail (Docker Compose with MySQL + Redis + Reverb)
- **Production**: Laravel Forge on a VPS
  - Nginx (Forge-managed)
  - SSL via Let's Encrypt (Forge-managed)
  - MySQL 8
  - Redis
  - Supervisor for queue workers and Reverb
  - Zero-downtime deploys via Forge

## Future Improvements

### Phone Number Format Validation

Currently `phone` is stored and verified as a free-form string (max 32 chars). There is no validation that it is a valid phone number (e.g. E.164 format like `+38761123456`).

Recommended approach:
- Add `libphonenumber` via [`propaganistas/laravel-phone`](https://github.com/Propaganistas/Laravel-Phone) to validate and normalise numbers on input.
- Store numbers in E.164 format to prevent duplicates caused by formatting differences (e.g. `061123456` vs `+38761123456` being treated as different values when they are the same number).
- Apply the `phone` rule in `SendPhoneOtpRequest` and `VerifyPhoneOtpRequest`.
- Consider normalising existing rows with a one-off migration before enforcing the format.
- **Domains**: `api.tavan.store` (API), `admin.tavan.store` (Filament)

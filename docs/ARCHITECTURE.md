# Architecture & Design Decisions

## System Overview

```
Mobile App (React Native / Expo)
    │
    │  HTTPS  Bearer: Firebase ID Token
    ▼
┌─────────────────────────────────────────┐
│         api.tavan.store                  │
│         Laravel 11 REST API             │
│                                         │
│  FirebaseAuthMiddleware                 │
│    └── verifies token via Admin SDK     │
│    └── resolves User from firebase_uid  │
│                                         │
│  Controllers → Services → Models        │
│                                         │
│  Events → Laravel Reverb (WebSockets)   │
└──────────┬─────────────────┬────────────┘
           │                 │
     MySQL 8            Cloudflare R2
   (business data)       (images)
           │
    Redis (cache + queues)

┌─────────────────────────────────────────┐
│         admin.tavan.store               │
│         Filament 3 Admin Panel          │
│         Same MySQL database             │
│         Separate session auth           │
└─────────────────────────────────────────┘
```

## Key Design Decisions

### 1. Firebase Auth (no passwords stored)
Users authenticate with Firebase on the mobile side. Laravel never sees passwords. The `FirebaseAuthMiddleware` verifies the Firebase ID Token on every request, resolves the `firebase_uid` → MySQL user record. On first login, a user record is auto-created.

Why: Firebase handles email verification, phone auth, and future social logins (Google, Apple). Zero password management on our side.

### 2. ULIDs instead of auto-increment IDs
All primary keys use ULIDs (Universally Unique Lexicographically Sortable Identifiers). They are:
- Sortable by time (unlike UUIDs)
- URL-safe
- Not guessable (unlike integers)
- Globally unique (safe to merge databases)

### 3. One Conversation Per User Pair
Conversations are unique on `(participant_one_id, participant_two_id)`. All offers, trades, and orders between two users appear as system messages in the same thread. This matches the mobile UX where there's one chat per person, not per product.

### 4. System Messages for Transactions
When an offer/trade/order is created, two things happen:
1. The entity is created in its own table (offers, trades, orders)
2. A `message` row is inserted with `type = system_offer|system_trade|system_order` and `payload = { "offerId": "..." }`

The mobile app reads the payload, fetches the entity, and renders the appropriate card in the chat timeline.

### 5. Cached Counters
`users.rating`, `users.total_reviews`, and `products.likes` are cached counts/averages updated via model observers. This avoids expensive aggregation queries on every request.

### 6. Admin-Managed Catalog
Brands, categories, and shipping options are stored in MySQL and managed via Filament. The mobile app fetches them from `/api/v1/catalog/*` endpoints. No app release needed to add a new brand or category.

### 7. Response Shape Contract
The mobile app's `/src/api/*.service.js` files define the exact shape expected from each endpoint, derived from the mock server. Laravel API Resources (`app/Http/Resources/`) enforce these shapes. **Never change a response shape without updating both sides.**

### 8. Image Storage via Cloudflare R2
Images are uploaded to Cloudflare R2 (S3-compatible). Laravel uses the `s3` filesystem driver pointed at R2's endpoint. Zero egress fees vs AWS S3. CDN delivery is automatic via Cloudflare.

### 9. Real-time via Laravel Reverb
Laravel Reverb is a self-hosted WebSocket server using the Pusher protocol. Events are broadcast on private channels per user. Mobile subscribes using `@pusher/pusher-websocket-react-native`. No third-party WebSocket service needed.

### 10. Service Layer for Business Logic
Controllers are thin (parse request, call service, return resource). Business logic lives in `app/Services/`:
- `OfferService` — create, accept, decline, counter
- `OrderService` — create, status transitions, order number generation
- `TradeService` — create, respond
- `FeedService` — personalized product feed

This keeps controllers testable and logic reusable.

## Security

- All API routes (except catalog + search) require a valid Firebase token
- Users can only modify their own resources (policy-based authorization)
- File uploads validated for type (images only) and size
- Rate limiting on auth and public endpoints
- CORS configured for mobile app origins only

## Deployment

- **Local**: Laravel Sail (Docker Compose with MySQL + Redis + Reverb)
- **Production**: Laravel Forge manages a DigitalOcean droplet
  - Nginx (Forge-managed)
  - SSL via Let's Encrypt (Forge-managed)
  - MySQL 8 on same server (can move to managed DB later)
  - Redis on same server
  - Supervisor for queue workers and Reverb
  - Zero-downtime deploys via Forge

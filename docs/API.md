# Tavan API Reference

**Base URL:** `https://api.tavan.store/api/v1`  
**Local:** `http://localhost/api/v1`

All authenticated routes require: `Authorization: Bearer <sanctum_token>`

Auto-generated OpenAPI docs (Scramble): `http://localhost/docs/api`

---

## Auth

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/auth/register` | No | Register with email + password |
| POST | `/auth/login` | No | Login with email + password |
| POST | `/auth/social/google` | No | Social login (Google id_token) |
| POST | `/auth/social/apple` | No | Social login (Apple identityToken) |
| POST | `/auth/email/verify` | No | Verify email OTP (issued at registration) |
| POST | `/auth/email/resend` | No | Resend email verification OTP (60 s cooldown) |
| POST | `/auth/forgot-password` | No | Request password reset OTP to email |
| POST | `/auth/verify-reset-otp` | No | Verify reset OTP, receive short-lived reset token |
| POST | `/auth/reset-password` | No | Reset password using reset token |
| POST | `/auth/phone/send-otp` | No | Send phone verification OTP |
| POST | `/auth/phone/verify-otp` | Yes | Verify phone OTP |
| GET | `/auth/me` | Yes | Get current user |
| POST | `/auth/logout` | Yes | Revoke Sanctum token |
| POST | `/auth/change-password` | Yes | Change password |

### POST /auth/register
```json
{ "name": "...", "username": "...", "email": "...", "password": "...", "password_confirmation": "..." }
```
Response: `{ "data": { "token": "...", "user": { ... } } }`

### POST /auth/login
```json
{ "email": "...", "password": "..." }
```
Response: `{ "data": { "token": "...", "user": { ... } } }`

**423 Locked** — account is in the 30-day deletion grace period:
```json
{
  "error": "account_pending_deletion",
  "deletionDate": "2026-06-20T14:32:00.000000Z",
  "recoveryToken": "1|abc..."
}
```
Mobile shows a recovery screen. `recoveryToken` is a Sanctum token scoped only to `DELETE /users/me/deletion` — use it as the Bearer token to cancel deletion. A full session token is re-issued in the cancel-deletion response.

### POST /auth/social/{provider}
Google: `{ "idToken": "..." }`  
Apple: `{ "identityToken": "...", "givenName": "...", "familyName": "..." }`  
Response: `{ "data": { "token": "...", "user": { ... } } }`

---

## Users

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/users` | No | Search users |
| GET | `/users/{username}` | No | Get public profile |
| GET | `/users/{username}/products` | No | Get user's products |
| GET | `/users/{username}/reviews` | No | Get user's reviews |
| PATCH | `/users/me` | Yes | Update own profile |
| DELETE | `/users/me` | Yes | Request account deletion (starts 30-day grace period) |
| DELETE | `/users/me/deletion` | Yes (recovery token) | Cancel pending deletion and re-issue session token |
| POST | `/users/me/avatar` | Yes | Upload avatar (multipart) |
| GET | `/users/me/preferences` | Yes | Get feed preferences |
| PATCH | `/users/me/preferences` | Yes | Save feed preferences (accepts `vintageOnly: bool`) |
| GET | `/users/me/notifications` | Yes | Get notification preference |
| PATCH | `/users/me/notifications` | Yes | Set notification preference |
| GET | `/users/me/addresses` | Yes | List shipping addresses |
| POST | `/users/me/addresses` | Yes | Add address |
| PATCH | `/users/me/addresses/{address}` | Yes | Update address |
| DELETE | `/users/me/addresses/{address}` | Yes | Delete address |
| GET | `/users/me/blocks` | Yes | List blocked users |
| POST | `/users/{user}/block` | Yes | Block user |
| DELETE | `/users/{user}/block` | Yes | Unblock user |
| POST | `/users/{user}/report` | Yes | Report user |

---

## Products

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/products` | No | List/filter products (paginated) |
| GET | `/products/{product}` | No | Get single product (with seller) |
| POST | `/products` | Yes | Create product (draft) |
| PATCH | `/products/{product}` | Yes (owner) | Update product |
| DELETE | `/products/{product}` | Yes (owner) | Delete product |
| POST | `/products/{product}/publish` | Yes (owner) | Publish draft → active |
| POST | `/products/{product}/images` | Yes (owner) | Upload image (multipart) |
| DELETE | `/products/{product}/images/{image}` | Yes (owner) | Delete image |
| PATCH | `/products/{product}/images/reorder` | Yes (owner) | Reorder images |
| POST | `/products/{product}/report` | Yes | Report a product |
| POST | `/products/{product}/vintage` | Yes (owner) | Submit vintage badge application |

### GET /products query params
- `category` — root category key (`women`/`men`)
- `subcategory` — subcategory key
- `brand_id` — brand UUID
- `condition` — condition key
- `size` — size value
- `color` — color key
- `material` — material key
- `price_min` / `price_max`
- `sort` — `newest` (default) | `price_asc` | `price_desc`
- `page`, `per_page`
- `seller_id` — filter by seller
- `query` — text search
- `vintageOnly` — `true` to return only vintage-approved products

### POST /products/{product}/vintage
Submits a Vintage badge application for a product the authenticated user owns. If the seller has `is_vintage_seller = true`, the badge is approved immediately. Otherwise it enters the admin review queue (`pending`). A product can only have one active application (returns 422 if already `pending` or `approved`). A rejected product cannot re-apply.

```json
{ "era": "90s", "notes": "Originalni Levi's...", "provenance": "Kupljeno u Italiji" }
```

`era` values: `50s` | `60s` | `70s` | `80s` | `90s` | `y2k`

Returns the updated product resource.

### Product response — vintage fields
```json
{
  "vintage_status": "approved",
  "vintage_reject_reason": null,
  "vintage": {
    "era": "90s",
    "notes": "Originalni Levi's 501...",
    "provenance": "Kupljeno u Italiji"
  }
}
```
`vintage` is `null` unless `vintage_status === "approved"`. `vintage_status` is always present (`null` = no claim submitted).

---

## Wishlist

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/wishlist` | Yes | Get wishlisted product IDs |
| POST | `/wishlist/{product}/toggle` | Yes | Toggle wishlist (on/off) |
| POST | `/wishlist/{product}` | Yes | Add to wishlist |
| DELETE | `/wishlist/{product}` | Yes | Remove from wishlist |

---

## Catalog

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/brands` | No | List all brands |

> **Note:** Categories and shipping/delivery options are defined statically on the mobile client and are not served from the API.

---

## Offers

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/offers` | Yes | Create offer |
| GET | `/offers/{offer}` | Yes | Get offer |
| POST | `/offers/{offer}/accept` | Yes (seller) | Accept offer (auto-creates order) |
| POST | `/offers/{offer}/decline` | Yes (seller) | Decline offer |
| POST | `/offers/{offer}/counter` | Yes (seller) | Counter offer |

### POST /offers
```json
{ "product_id": "...", "offered_price": 25.00, "conversation_id": "..." }
```

---

## Trades

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/trades` | Yes | Create trade proposal |
| GET | `/trades/{trade}` | Yes | Get trade |
| POST | `/trades/{trade}/accept` | Yes (seller) | Accept trade |
| POST | `/trades/{trade}/decline` | Yes (seller) | Decline trade |
| POST | `/trades/{trade}/counter` | Yes (seller) | Counter trade |

---

## Orders

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/orders` | Yes | List orders (buyer or seller) |
| POST | `/orders` | Yes | Create order (direct buy) |
| GET | `/orders/{order}` | Yes | Get order (with product) |
| POST | `/orders/{order}/accept` | Yes (seller) | Accept order |
| POST | `/orders/{order}/ship` | Yes (seller) | Mark shipped |
| POST | `/orders/{order}/deliver` | Yes (seller) | Mark delivered |
| POST | `/orders/{order}/complete` | Yes (buyer) | Mark completed |
| POST | `/orders/{order}/decline` | Yes (seller) | Decline order |

### GET /orders query params
- `role` — `buyer` | `seller`
- `status` — filter by status
- `page`, `per_page`

Order statuses: `pending` | `accepted` | `shipped` | `delivered` | `completed` | `declined` | `cancelled`

`cancelled` is set automatically when either party initiates account deletion while the order is still active (`pending`, `accepted`, or `shipped`). The other party receives a system message in the conversation.

---

## Reviews

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/users/{username}/reviews` | No | List user's reviews |
| GET | `/reviews/{review}` | Yes | Get single review |
| POST | `/orders/{order}/reviews` | Yes | Create review for order |

---

## Announcements

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/announcements` | No | List active announcements (guests see target_group=all only) |
| GET | `/announcements/unread-count` | Yes | Count of unread announcements for current user |
| POST | `/announcements/{announcement}/read` | Yes | Mark announcement as read |

---

## Conversations & Messages

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/conversations` | Yes | List conversations |
| GET | `/conversations/unread` | Yes | Unread count |
| POST | `/conversations` | Yes | Find or create conversation |
| POST | `/conversations/support` | Yes | Find or create support conversation with the system user |
| GET | `/conversations/{conversation}` | Yes | Get conversation with messages |
| GET | `/conversations/{conversation}/info` | Yes | Get conversation metadata |
| POST | `/conversations/{conversation}/messages` | Yes | Send message |
| POST | `/conversations/{conversation}/read` | Yes | Mark as read |

### POST /conversations
```json
{ "user_id": "...", "product_id": "..." }
```
Returns existing conversation if one already exists between the two users.

### POST /conversations/{conversation}/messages
```json
{ "type": "text", "body": "..." }
```
System message types (`system_offer`, `system_trade`, etc.) are created automatically by their respective service actions — not sent by the mobile app directly.

---

## Push Notifications

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/push-tokens` | Yes | Register push token |
| DELETE | `/push-tokens` | Yes | Remove push token |
| POST | `/push-tokens/badge/reset` | Yes | Reset app badge count to zero |

### POST /push-tokens
```json
{ "token": "ExponentPushToken[...]", "platform": "ios" }
```

---

## Tracking

Public routes for marketing analytics. Gated by `X-App-Key` header (same as all `/api/v1/` routes), rate-limited to 60 req/min.

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/tracking/share-view` | No | Record a share-link view (product or profile) |
| GET | `/tracking/campaign/{id}` | No | Get campaign metadata by ID (used by the share landing page) |
| POST | `/tracking/campaign-event` | No | Record a campaign event (link_click, app_install) |

---

## Misc

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/support` | No | Submit support inquiry (anonymous or authenticated) |
| POST | `/brand-suggestions` | Yes | Suggest a new brand |
| GET | `/posts` | No | Blog posts list |
| GET | `/posts/slugs` | No | All blog post slugs |
| GET | `/posts/{slug}` | No | Single blog post |

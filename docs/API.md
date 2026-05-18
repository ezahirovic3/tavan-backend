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
| POST | `/auth/forgot-password` | No | Request OTP to email |
| POST | `/auth/verify-reset-otp` | No | Verify OTP, get reset token |
| POST | `/auth/reset-password` | No | Reset password with token |
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
| DELETE | `/users/me` | Yes | Delete account |
| POST | `/users/me/avatar` | Yes | Upload avatar (multipart) |
| GET | `/users/me/preferences` | Yes | Get feed preferences |
| PATCH | `/users/me/preferences` | Yes | Save feed preferences |
| GET | `/users/me/notifications` | Yes | Get notification pref |
| PATCH | `/users/me/notifications` | Yes | Set notification pref |
| GET | `/users/me/addresses` | Yes | List shipping addresses |
| POST | `/users/me/addresses` | Yes | Add address |
| PATCH | `/users/me/addresses/{address}` | Yes | Update address |
| DELETE | `/users/me/addresses/{address}` | Yes | Delete address |
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

---

## Reviews

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/users/{username}/reviews` | No | List user's reviews |
| GET | `/reviews/{review}` | Yes | Get single review |
| POST | `/orders/{order}/reviews` | Yes | Create review for order |

---

## Conversations & Messages

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/conversations` | Yes | List conversations |
| GET | `/conversations/unread` | Yes | Unread count |
| POST | `/conversations` | Yes | Find or create conversation |
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

### POST /push-tokens
```json
{ "token": "ExponentPushToken[...]", "platform": "ios" }
```

---

## Misc

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/support` | Yes | Submit support inquiry |
| POST | `/brand-suggestions` | Yes | Suggest a new brand |
| GET | `/posts` | No | Blog posts list |
| GET | `/posts/slugs` | No | All blog post slugs |
| GET | `/posts/{slug}` | No | Single blog post |

# API Reference

Base URL: `https://api.tavan.store/api/v1`  
Local: `http://localhost/api/v1`

All authenticated routes require: `Authorization: Bearer <firebase_id_token>`

---

## Auth

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/auth/login` | No | Verify Firebase token, create user if new, return user |
| GET | `/auth/me` | Yes | Get current authenticated user |
| DELETE | `/auth/logout` | Yes | Revoke push token for this device |

### POST /auth/login
Body:
```json
{ "firebase_token": "..." }
```
Response: `{ "data": { user } }`

---

## Users

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/users/{id}` | Yes | Get user profile |
| PATCH | `/users/{id}` | Yes (own) | Update profile (name, bio, avatar, location, phone) |
| GET | `/users/{id}/preferences` | Yes (own) | Get feed preferences |
| PUT | `/users/{id}/preferences` | Yes (own) | Save feed preferences |
| GET | `/users/{id}/addresses` | Yes (own) | List shipping addresses |
| POST | `/users/{id}/addresses` | Yes (own) | Create address |
| PATCH | `/users/{id}/addresses/{addressId}` | Yes (own) | Update address |
| DELETE | `/users/{id}/addresses/{addressId}` | Yes (own) | Delete address |

---

## Products

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/products` | No | List products (paginated, filtered) |
| POST | `/products` | Yes | Create product |
| GET | `/products/{id}` | No | Get single product |
| PATCH | `/products/{id}` | Yes (owner) | Update product |
| PATCH | `/products/{id}/status` | Yes (owner) | Update product status (draft/active/sold) |
| DELETE | `/products/{id}` | Yes (owner) | Delete product |
| POST | `/products/{id}/images` | Yes (owner) | Upload product images |
| DELETE | `/products/{id}/images/{imageId}` | Yes (owner) | Delete product image |

### GET /products query params
- `category` — root category key (women/men)
- `subcategory` — subcategory key
- `brand_id` — brand UUID
- `condition` — condition key
- `size` — size value
- `color` — color key
- `min_price`, `max_price` — decimal
- `location` — city name
- `allows_trades` — boolean
- `allows_offers` — boolean
- `sort` — `newest` | `price_asc` | `price_desc`
- `page`, `per_page`

---

## Feed

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/feed` | Yes | Personalized feed based on preferences |
| GET | `/feed/suggested` | Yes | Suggested products (no preferences needed) |

---

## Search

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/search/products` | No | Search products by query |
| GET | `/search/users` | No | Search users by username/name |

Query params: `q` (search term), `page`, `per_page`

---

## Catalog (admin-managed, read-only for mobile)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/catalog/brands` | No | List active brands |
| GET | `/catalog/categories` | No | Category tree |
| GET | `/catalog/shipping-options` | No | Shipping options |

---

## Wishlist

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/wishlist` | Yes | Get current user's wishlist |
| POST | `/wishlist/{productId}/toggle` | Yes | Add or remove product from wishlist |

---

## Conversations

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/conversations` | Yes | List user's conversations |
| POST | `/conversations` | Yes | Find or create conversation with another user |
| GET | `/conversations/{id}` | Yes | Get single conversation |
| POST | `/conversations/{id}/read` | Yes | Mark conversation as read |

### POST /conversations body
```json
{ "other_user_id": "...", "product_id": "..." }
```

---

## Messages

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/conversations/{id}/messages` | Yes | Get messages (paginated) |
| POST | `/conversations/{id}/messages` | Yes | Send text message |
| GET | `/messages/unread-count` | Yes | Get total unread count |

### POST message body
```json
{ "body": "Hello!" }
```

---

## Offers

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/offers/{id}` | Yes | Get single offer |
| POST | `/offers` | Yes | Create offer (buyer) |
| POST | `/offers/{id}/accept` | Yes (seller) | Accept offer → auto-creates order |
| POST | `/offers/{id}/decline` | Yes (seller) | Decline offer |
| POST | `/offers/{id}/counter` | Yes (seller) | Counter offer |
| DELETE | `/offers/{id}` | Yes (buyer) | Cancel offer |

### POST /offers body
```json
{
  "product_id": "...",
  "offered_price": 25.00,
  "message": "Would you take 25?"
}
```

---

## Trades

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/trades/{id}` | Yes | Get single trade |
| POST | `/trades` | Yes | Create trade proposal (buyer) |
| POST | `/trades/{id}/accept` | Yes (seller) | Accept trade |
| POST | `/trades/{id}/decline` | Yes (seller) | Decline trade |

### POST /trades body
```json
{
  "product_id": "...",
  "offered_product_id": "...",
  "message": "Want to trade?"
}
```

---

## Orders

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/orders` | Yes | List user's orders (as buyer or seller) |
| GET | `/orders/{id}` | Yes | Get single order |
| POST | `/orders` | Yes | Create order (direct purchase) |
| PATCH | `/orders/{id}/status` | Yes (seller) | Update order status |

### POST /orders body
```json
{
  "product_id": "...",
  "offer_id": null,
  "shipping_name": "...",
  "shipping_street": "...",
  "shipping_city": "...",
  "shipping_phone": "...",
  "payment_method": "cash_on_delivery",
  "delivery_method": "standard"
}
```

### PATCH /orders/{id}/status body
```json
{ "status": "accepted" }
```
Valid transitions: `pending → accepted | declined`, `accepted → shipped`, `shipped → delivered`, `delivered → completed`

---

## Reviews

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/users/{id}/reviews` | No | Get reviews for a user |
| POST | `/orders/{id}/review` | Yes | Leave review for an order |

### POST review body
```json
{ "rating": 5, "comment": "Odlično pakovanje!" }
```

---

## Support

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/support` | No | Submit support inquiry |

---

## Push Notifications

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/push-tokens` | Yes | Register Expo push token |
| DELETE | `/push-tokens/{token}` | Yes | Unregister token (logout) |

---

## WebSocket Events (Laravel Reverb)

Channels are private, authenticated via `Authorization` header.

| Channel | Event | Triggered by |
|---------|-------|-------------|
| `private-user.{userId}` | `MessageSent` | New message in any conversation |
| `private-user.{userId}` | `OfferUpdated` | Offer status change |
| `private-user.{userId}` | `TradeUpdated` | Trade status change |
| `private-user.{userId}` | `OrderUpdated` | Order status change |
| `private-conversation.{conversationId}` | `MessageSent` | New message (for open chat screens) |

# Tavan Backend — Roadmap & Pending Changes

> Last updated: 2026-07-02

This document covers features and schema changes that have been discussed and decided but not yet implemented. It is a living record of architectural decisions and pending work.

---

## 1. order_items Refactor ✅ (shipped — migration 2026-07-02, `OrderItem` model + resource live)

### Problem

The `orders` table currently holds a single `product_id` FK — one order, one product. This blocks multi-item (bundle) orders and is the wrong long-term data model.

### Decision

**Option A — `order_items` table** (chosen). Orders become headers; line items live in a separate `order_items` table. This is the correct relational design and unblocks bundle buy.

Option B (bundle_id FK on orders) was considered and rejected — it patches around the problem architecturally.

### New table: `order_items`

```
id               ulid, PK
order_id         FK → orders.id, cascade on delete
product_id       FK → products.id, nullable, nullify on delete
price            decimal(10,2)  — product price at time of order
created_at / updated_at
```

No per-item `discount` column. The only discount source today is an offer, which is
order-level (and a future Vinted-style bundle offer would also be order-level), so
`discount` stays on `orders`. Add a per-item column when something actually produces one.

### Backward compatibility — `product()` as HasOneThrough (not an accessor)

An accessor (`getProductAttribute`) was originally proposed and **does not work**:
`OrderResource` uses `$this->whenLoaded('product')`, which checks the model's loaded
relations — an accessor would silently drop the `product` key from every API response.

Instead, keep `product()` as a real Eloquent relation resolved through `order_items`:

```php
public function product(): HasOneThrough
{
    return $this->hasOneThrough(
        Product::class, OrderItem::class,
        'order_id', 'id', 'id', 'product_id'
    )->oldest('order_items.id');
}
```

Because it is a real relation: all `->with('product.images')` / `->load('product')`
call sites, `whenLoaded('product')` in `OrderResource`, and Filament's
`TextColumn::make('product.title')->searchable()` + all `product.*` infolist entries
keep working **unchanged**. Only write paths need edits.

### Migration strategy

Single deploy, one migration: create `order_items`, backfill in `up()` with a loop over
`orders WHERE product_id IS NOT NULL` (works identically on prod/staging/dev — not a
manual prod-only step), then drop `orders.product_id`. Note `orders.product_id` is
already nullable with `nullOnDelete` (2026-05-11 migration), so backfill must skip nulls.
`down()` restores the column and re-derives it from the first item.

### Blast radius — every file that needs to change

**Models**
- `Order.php` — remove `product_id` from `$fillable`; change `product()` from BelongsTo to HasOneThrough (above); add `items()` HasMany
- `app/Models/OrderItem.php` — new

**Services**
- `OrderService.php` — 2 live creation paths (`createDirect`, `createFromTrade`): remove `product_id` from `Order::create()`, add an `OrderItem` row after each. **`createFromOffer()` is dead code — no callers** (offer acceptance only flips offer status; the buyer then places a direct order with `offer_id`, and `createDirect` applies the discount). Delete it.
- `UserDeletionService.php` — `->with('product')` → `->with('items.product')`; iterate `$order->items` when releasing reserved products

**Controllers**
- `OrderController.php`:
  - `complete()` / `decline()` — iterate `$order->items` and update each product (null-safe: `product_id` is nullable). This also fixes a pre-existing bug: the current non-null-safe `$order->product->update()` 500s if the product was deleted.
  - Eager loads: keep `product` (HasOneThrough), **add** `items.product.images` so the new `items` array is in responses from day one.

**Resources**
- `OrderResource.php` — add `'items' => OrderItemResource::collection($this->whenLoaded('items'))`. `'product'` key unchanged (still `whenLoaded('product')`).
- `app/Http/Resources/OrderItemResource.php` — new

**Filament**
- No changes needed — `product.title` column (incl. `searchable()`) and `product.*` infolist entries work through the HasOneThrough relation.

**Tests & factories**
- `tests/Feature/Transactions/OrderTest.php` — 15 tests post `product_id` and assert `orders.product_id`; update to assert `order_items` rows. Add multi-item coverage: complete → all products `sold`, decline → all back to `active`.
- `database/factories/OrderFactory.php` — remove `product_id`; add `forProduct()` helper creating an item
- `database/factories/OrderItemFactory.php` — new

**New files summary**
- `app/Models/OrderItem.php`
- `app/Http/Resources/OrderItemResource.php`
- `database/factories/OrderItemFactory.php`
- `database/migrations/xxxx_create_order_items_table.php`

### API backward compatibility

The mobile app reads `order.product` in at least: `app/(main)/order/[orderId].js`,
`src/components/pages/add/ordersSection.js`, `src/components/pages/chat/cards/OrderCard.js`,
`app/(main)/settings/orders.js`, `src/components/pages/profile/profileTabContent.js`,
`src/hooks/useConversation.js`. Chat order cards resolve via `GET /orders/{id}` (payload
only carries `orderId`), so they go through the same `OrderResource`.

**`product` stays in the `OrderResource` response** via the HasOneThrough relation —
zero mobile changes required initially. Responses additionally expose `items[]`; the
bundle-buy mobile UI consumes it later (old orders render via `product` fallback).

---

## 2. Bundle Buy (Backend: DONE — mobile UI: DONE, live)

### Concept

Buyer selects multiple items from the same seller and places them as a single order. Like Vinted bundles.

- Buyer-initiated (seller-created bundles are a separate, later feature — see §4)
- All items must be from the same seller
- Single shipping fee for the whole order
- `order_items` schema is a prerequisite

### Backend implementation (shipped)

`POST /api/v1/orders` now accepts `product_ids: []` (1–20 items) alongside the legacy
`product_id` (old mobile versions keep working unchanged). All purchase validation
(availability, ownership, same-seller) moved from the controller into
`OrderService::createDirect`, inside the transaction with `lockForUpdate` on the
products — bundles are reserved atomically or rejected as a whole (422 names the
unavailable items). Offers are validated properly now (must be the buyer's own accepted
offer for the ordered product; single-item orders only). Push notification says
"N artikala" for bundles.

### Decisions made ahead of implementation

- **Shipping fee rule**: free only if *all* items are free-shipping; otherwise charge the
  **max** of the individual per-item shipping costs (one parcel, largest item drives the
  price — Vinted-style). No shipping data needed on `order_items`.
- **Atomic reservation**: order creation must `lockForUpdate()` all products inside the
  transaction and re-check `status === 'active'` there (today the check lives in the
  controller, outside the transaction). If any item became unavailable, return 422
  naming the unavailable items.
- **Offers × bundles**: deferred. `offers.product_id` is single-product; bundle offers
  need their own design later.
- **Push/system-message copy**: bundle orders say "N artikala" instead of a single title.

### Mobile UX: shipped (bundle selection from seller profile, multi-item checkout, order screens rendering `items[]`).

---

## 3. Courier Integration — EuroExpress (Status: On Hold)

A meeting with EuroExpress is scheduled. Their tech team may be present to explain their system/API.

**Do not design or implement anything in this area until after the meeting.**

Known unknowns:
- How their booking API works (REST? SOAP? proprietary?)
- Whether tracking is per-shipment or per-item
- What fields they require at booking time
- How webhook/status callbacks work (if at all)

Likely fields needed on `orders` (post-meeting):
- `courier_provider` — string (e.g. `euroexpress`)
- `tracking_number` — string, nullable
- `shipment_reference` — string, nullable (their internal ref)
- `estimated_delivery_at` — datetime, nullable

Likely new status states (to confirm with courier):
- Currently: `pending → accepted → shipped → delivered → completed`
- Possible additions: `picked_up`, `in_transit`, `out_for_delivery`

Status flow changes and courier fields are a **separate migration** from order_items — no dependency between them.

---

## 4. Seller-Created Bundles (Status: Future / Gated)

Sellers can group existing active listings into a named bundle at a set price. Gated behind a subscription plan.

**Blocked by:** subscription/paywall implementation (requires Monri integration, pending obrt registration).

---

## 5. Subscription / Paywall (Status: Future)

One tier to start. Unlocks:
- Seller-created bundles
- Promoted/featured listings ("Izdvojeni")

**Blocked by:** Monri card payment integration, which requires obrt registration first.

---

## 6. Card Payments — Monri (Status: Blocked)

C2C card payments are not currently supported in Bosnia. Requires obrt (sole trader) registration to contact Monri. No timeline yet.

---

---

## 7. Release train work (July 2026 — product roadmap in `tavan-landing/ROADMAP.md`)

> What/when/why lives in the landing repo's ROADMAP.md (single source of truth).
> Sibling implementation doc: `tavan-mobile/docs/roadmap.md`.

### 1.3.0 (juli) — Stilovi + founding bedž ✅ (implemented 2026-07-11)

- [x] `App\Enums\ProductStyle` — 14 curated styles (Depop model), MAX_PER_PRODUCT = 3;
      single source for validation, Filament options, search intents
- [x] Migration: `products.styles` JSON array (nullable) + cast on `Product`
- [x] Validation: `StoreProductRequest` / `UpdateProductRequest` — array, max 3,
      `Rule::in(ProductStyle::values())`
- [x] `ProductResource`: expose `styles` (defaults to `[]`)
- [x] `ProductController@index`: `styles` filter param (whereJsonContains, any-of)
- [x] Search: STYLE_INTENTS in `ProductSearchService` + `detectStyleIntent()`.
      `pokrivene` → modest. `vintage`/`retro` → retro style **plus**
      `vintage_status = approved` OR-branch (badge and style both surface)
- [x] `user_preferences.styles` (scope added mid-build): column + validation +
      personalized-feed OR-branch in the `$applyPreferences` closure
- [x] `users.is_founding_seller` boolean + Filament toggle/column/filter + API `UserResource`
- [x] Filament ProductForm styles multi-select + ProductInfolist badges
- [x] Tests: `tests/Feature/Products/ProductStyleTest.php` (12 tests — validation,
      filter, search intents, preferences, founding flag)

### 1.4.0 (sredina augusta) — Follows + notifications

- [ ] `follows` table (follower_id, followed_id, unique pair) + model + cascades
- [ ] Endpoints: `POST/DELETE users/{user}/follow`, `GET users/me/following`,
      follower/following counts on `UserResource` (withCount, mind N+1 on lists)
- [ ] Throttle follow/unfollow
- [ ] Home rail feed: `GET products?followed=true` — recent items from followed
      sellers, no preference filtering, excludes future `drop`-state items
- [ ] Notification fan-out (queued): new-listing digest per seller (30 min quiet /
      60 min max delay, quiet hours 22–08, daily cap; "novi artikal" vs "X novih
      artikala"); `drop_scheduled` immediate (1.5.0 consumes)
- [ ] Blocks: blocked users can't follow; block severs follows both ways

### 1.5.0 (sep–okt) — Drops — **depends on §1 order_items refactor**

The reservation basket produces one order with N items → `order_items` must ship
first. Bundle-buy backend (§2, done) provides the atomic `lockForUpdate` reservation
pattern and `product_ids[]` checkout the basket reuses.

- [ ] `drops` table (seller, scheduled_at, tease media, description, status:
      scheduled/live/ended/cancelled) + `drop_id` on products + product status `drop`
- [ ] Visibility: `drop` items excluded from `Product::active()`, feeds, **and search**
      until T0+24h; purchasable only via drop endpoints during the window
- [ ] Server-enforced buy-only for drop items (offers/trades rejected, not just hidden)
- [ ] Scheduler: T0 flips to live; T0+24h releases unsold to `active`;
      cancellation notifies interested users (queued)
- [ ] `drop_interests` table; interested count exposed to the seller
- [ ] Reservation endpoint: lock item for user (TTL ~5 min, per-user cap per drop);
      basket checkout → ONE order via existing multi-item path
- [ ] Review-flag edge: seller flagged after scheduling → drop suspended
- [ ] Filament: Drops resource (oversight, cancel/suspend)
- [ ] Baseline metric (can ship earlier): same-buyer-same-seller orders within 15 min

### 2.0.0 (novembar) — Djeca i bebe

- [ ] `kids` root_category wherever root values are validated
- [ ] Kids category branch (clothing + shoes ONLY)
- [ ] Search `ROOT_INTENTS` += `djeca`, `djecije`, `dječije`, `kids` → `kids`

---

## Implementation Order

```
[DONE]        order_items refactor (§1) — prerequisite for 1.5.0 drops basket
[DONE 07-11]  1.3.0 — styles + founding badge (backend + mobile)
[DONE]        bundle buy mobile UX (backend + mobile both live)
[Next]        1.4.0 — follows + notification fan-out (mid-Aug)
[After meet]  courier integration — EuroExpress fields + status flow (any open release)
[Sep–Oct]     1.5.0 — drops (needs order_items + reuses bundle-buy reservation)
[November]    2.0.0 — kids root + categories
[Future]      subscriptions → seller bundles → promoted listings → drop quotas
[Future]      Monri card payments (obrt prerequisite) → unlocks credits lane from product roadmap
```

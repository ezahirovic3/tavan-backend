# Tavan — Marketing & Analytics

This document covers the full analytics and marketing plan across all three parts of the ecosystem:
backend (Laravel), mobile (React Native / Expo), and landing page (Next.js).

Work is split into three phases based on what's valuable at each stage of growth.

---

## Phase 1 — Launch (build before or shortly after going live)

### 1.1 Product & Profile View Counts

**Why:** Internal metric for identifying high-impact sellers and products. Foundation for seller
analytics later. Needed before launch so data starts accumulating from day one.

**What to build:**

- Add `view_count` integer column (default 0) to `products` table
- Add `profile_view_count` integer column (default 0) to `users` table
- Increment `products.view_count` on every `GET /api/v1/products/{id}` call (authenticated or not)
- Increment `users.profile_view_count` on every `GET /api/v1/users/{username}` call
- Use `DB::statement('UPDATE ... SET view_count = view_count + 1')` — not Eloquent — to avoid
  race conditions and skip model events/activity log noise
- Expose both counts in `ProductResource` and `UserResource` responses
- Add sortable columns in Filament `ProductResource` and `UserResource` tables

**Not yet exposed to sellers in the mobile app — internal admin use only in Phase 1.**

---

### 1.2 Share View Tracking

**Why:** Every product and profile shared outside the app lands on the existing
`/open/product/[productId]` or `/open/profile/[userId]` pages. These pages already detect
platform and handle the redirect. We just need to log each visit.

**What already exists:**
- `/open/product/[productId]/page.tsx` — renders `AppRedirect` with a product deep link
- `/open/profile/[userId]/page.tsx` — renders `AppRedirect` with a profile deep link
- `AppRedirect` component — already detects `ios` / `android` / `desktop` and knows whether
  it sent the user to the store or opened the app

**What to build — Backend:**

New table: `share_views`

| Column | Type | Notes |
|--------|------|-------|
| `id` | ULID PK | |
| `entity_type` | enum `product`, `profile` | |
| `entity_id` | string (ULID) | product or user ID |
| `platform` | enum `ios`, `android`, `desktop` | detected by landing page |
| `outcome` | enum `app_opened`, `store_redirect`, `unknown` | detected by landing page |
| `referrer` | string nullable | raw `Referer` header — tells you Instagram, WhatsApp, etc. |
| `referrer_platform` | enum nullable | parsed from referrer: `instagram`, `whatsapp`, `facebook`, `twitter`, `direct`, `other` |
| `created_at` | timestamp | no `updated_at` needed |

New endpoint: `POST /api/v1/tracking/share-view` (public, no auth required)

```json
{
  "entity_type": "product",
  "entity_id": "01JXXXX",
  "platform": "ios",
  "outcome": "app_opened",
  "referrer": "https://www.instagram.com/"
}
```

The backend parses the referrer into `referrer_platform` and stores the row.

**What to build — Landing page:**

In `AppRedirect`, after the redirect outcome is determined (app opened vs store redirect),
fire a `POST` to the backend tracking endpoint with the entity details, platform, and outcome.
The entity type and ID are already available — they come through `deepLink` prop.

Parse `document.referrer` client-side to send along with the request. No user-identifiable
data is collected.

**Filament analytics panel:**

Under a new "Analitika" section — table of share views filterable by entity type, referrer
platform, date range. Simple charts: shares by day, top shared products, top shared profiles,
breakdown by platform (iOS vs Android vs desktop).

---

### 1.3 Campaign Management

**Why:** Track paid acquisition efforts, log spend, measure how many users each campaign drove.

**Data model:**

Table: `campaigns`

| Column | Type | Notes |
|--------|------|-------|
| `id` | ULID PK | also used as the public campaign code |
| `name` | string | e.g. "Instagram Juni 2026" |
| `description` | text nullable | notes on the campaign |
| `channel` | enum | `instagram`, `facebook`, `tiktok`, `flyer`, `influencer`, `other` |
| `status` | enum | `active`, `paused`, `completed` |
| `starts_at` | date nullable | |
| `ends_at` | date nullable | |
| `created_at` / `updated_at` | timestamps | |

Table: `campaign_expenses`

| Column | Type | Notes |
|--------|------|-------|
| `id` | ULID PK | |
| `campaign_id` | FK → campaigns | |
| `amount` | decimal(10,2) | in KM |
| `note` | string nullable | e.g. "Instagram boost post 3" |
| `spent_at` | date | |
| `created_at` | timestamp | |

Table: `campaign_events`

| Column | Type | Notes |
|--------|------|-------|
| `id` | ULID PK | |
| `campaign_id` | FK → campaigns | |
| `type` | enum | `link_click`, `app_install` |
| `platform` | enum nullable | `ios`, `android`, `desktop` |
| `outcome` | enum nullable | `app_opened`, `store_redirect` |
| `created_at` | timestamp | |

**Campaign link format:**

Each campaign gets a unique link: `tavan.store/go/[campaignId]`

This page on the landing site works exactly like the existing `/open/` pages — tries to open
the app, falls back to the store. On load it fires `POST /api/v1/tracking/campaign-event` with
`{ campaign_id, type: "link_click", platform, outcome }`.

**Install attribution (Phase 1 — approximate):**

Without Branch.io, installs are attributed by counting new `users` rows created within 48 hours
of a campaign link click from the same general time window. This is an approximation, displayed
in admin as "estimated installs" to make the approximation explicit.

**Filament admin panel — "Marketing" section:**

- Campaign list with total spend, estimated installs, cost-per-install
- Campaign detail page: expense timeline, clicks-by-day chart, channel breakdown
- "Novi trošak" button to log a new expense entry inline

---

## Phase 2 — Post-launch (once real users are in the app)

### 2.1 Seller Analytics (in-app)

Sellers get a read-only analytics screen for their own listings. Similar to what Instagram
shows business accounts — views, wishlist adds, share taps.

**What to build:**

New endpoint: `GET /api/v1/me/analytics`

Returns:
```json
{
  "total_product_views": 1240,
  "total_profile_views": 380,
  "total_wishlist_adds": 94,
  "top_products": [
    { "product_id": "...", "title": "...", "view_count": 210, "wishlist_count": 12 }
  ],
  "views_by_day": [ { "date": "2026-06-01", "count": 45 }, ... ]
}
```

`views_by_day` is built from `share_views` + direct `view_count` increments, filtered to the
requesting seller's content.

Mobile: new "Analitika" tab in the seller's profile or listings management screen.

---

### 2.2 Deferred Deep Link Attribution (Branch.io)

**Why:** When a user clicks a campaign link, gets sent to the App Store, installs the app,
and opens it — the campaign ID is lost. Branch.io solves this by fingerprinting the user on
the web and matching them when the app first opens.

**When to add:** When you're running multiple simultaneous campaigns and need precise
install-to-campaign attribution. Not needed until you feel the gap in your data.

**Integration points:**

- Landing page: add Branch.io web SDK to the `/go/[campaignId]` page. Pass the campaign ID
  as a Branch link parameter.
- Mobile app: add `react-native-branch` SDK. On first app open, Branch fires a callback with
  the matched campaign ID. App calls `POST /api/v1/tracking/campaign-event` with
  `{ campaign_id, type: "app_install" }`.
- Backend: `campaign_events` table already handles this — no schema changes needed.
- Admin: replace "estimated installs" label with real attribution once Branch is active.

Branch.io free tier covers up to 10,000 monthly active users — sufficient until meaningful scale.

---

### 2.3 Wishlist Add Tracking

Adding a product to a wishlist is a strong buying signal. Track it alongside view counts.

- Add `wishlist_add_count` to `products` table (increment on `POST /wishlist`, decrement on
  `DELETE /wishlist`)
- Expose in `ProductResource` and seller analytics endpoint
- Shown in Filament product table alongside `view_count`

---

## Phase 3 — Scale (thousands of active users)

### 3.1 Promoted Listings (In-App Advertising)

Sellers pay to boost their listing in the feed for a defined number of impressions or days.

**Concepts:**
- `promotions` table: `product_id`, `seller_id`, `budget_km`, `impressions_bought`,
  `impressions_served`, `starts_at`, `ends_at`, `status`
- Feed endpoint weights promoted listings into results when `impressions_served < impressions_bought`
- Admin panel manages pricing tiers and approves promotions
- Sellers create promotions from their listing detail screen in the app

### 3.2 Campaign Source Attribution on Share Links

When a user who arrived via a paid campaign shares a product, their share link can carry the
original campaign ID as a query param (`?c=CAMP_ID`). This closes the loop: you can see how
much organic sharing was driven by each paid campaign.

Implementation: store `acquired_via_campaign_id` on the `users` table at registration (set
from the Branch.io callback or campaign event on first open). When that user's share link is
generated in the app, append `?c={acquired_via_campaign_id}` to the URL.

---

## Summary Table

| Feature | Phase | Where |
|---------|-------|-------|
| Product view count | 1 | Backend + Filament |
| Profile view count | 1 | Backend + Filament |
| Share view tracking | 1 | Backend + Landing + Filament |
| Campaign management + expenses | 1 | Backend + Landing + Filament |
| Approximate install attribution | 1 | Backend + Filament |
| Seller analytics screen | 2 | Backend + Mobile |
| Wishlist add tracking | 2 | Backend |
| Branch.io deferred deep links | 2 | Landing + Mobile + Backend |
| Promoted listings | 3 | Backend + Mobile + Filament |
| Campaign source on share links | 3 | Mobile + Backend |

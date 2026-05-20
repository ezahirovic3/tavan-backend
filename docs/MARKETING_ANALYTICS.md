# Tavan — Marketing & Analytics

## Status

Phase 1 is complete. All tracking infrastructure is live.

| Feature | Status | Notes |
|---|---|---|
| Product view counts | ✅ Done | `products.view_count`, Redis dedup (1/viewer/day), exposed in API + Filament |
| Profile view counts | ✅ Done | `users.profile_view_count`, same dedup logic |
| Share view tracking | ✅ Done | `share_views` table, landing page `/open/` pages wired, grouped Filament view |
| Campaign management | ✅ Done | `campaigns`, `campaign_expenses`, `campaign_events` tables, Filament CRUD |
| Campaign link clicks | ✅ Done | `POST /api/v1/tracking/campaign-event`, `/go/[campaignId]` on landing |
| Mobile campaign attribution | ✅ Done | `tavan://campaign/{id}` deep link → stored → sent on register → `users.acquired_via_campaign_id` |

---

## Seller Analytics — Future Feature (Paid Plans)

### Vision

Sellers on a paid plan get an analytics screen in the app — similar to what Instagram shows business accounts. They can see how their listings and profile are performing without needing access to the admin panel.

This feature is intentionally held back from free accounts. It is a primary incentive for upgrading to a paid plan.

### What sellers would see

**Profile overview**
- Total profile views (last 7 / 30 days)
- Profile views over time — bar chart by day

**Listings performance**
- Per-product: view count, wishlist adds, number of offers received
- Sortable list: top products by views, top products by wishlist adds
- Views over time for a selected product

**Acquisition insight** (if acquired via campaign)
- "You joined via [campaign channel]" — simple attribution label, no raw IDs exposed

### Data that's already collected (no new backend work needed)

- `products.view_count` — already incremented on every product open
- `users.profile_view_count` — already incremented on every profile open
- `users.acquired_via_campaign_id` — set at registration if user came via a campaign link

### What still needs to be built when this ships

**Backend**
- `products.wishlist_add_count` — increment on `POST /wishlist`, decrement on `DELETE /wishlist` (currently not tracked)
- `GET /api/v1/me/analytics` — aggregates the above for the authenticated seller only

**Mobile**
- Analytics screen in the seller's profile or listings management area
- Gated behind plan check: only rendered if `user.plan === 'business'` (or equivalent)

**Billing / Plans** (prerequisite — must exist before this ships)
- User plan field (`users.plan`: `free`, `business`, or similar)
- Payment flow for upgrading
- Plan enforcement middleware or policy

### Endpoint shape (draft)

```
GET /api/v1/me/analytics
Authorization: Bearer {token}
```

```json
{
  "profile_views": 380,
  "profile_views_by_day": [
    { "date": "2026-06-01", "count": 45 }
  ],
  "total_product_views": 1240,
  "total_wishlist_adds": 94,
  "top_products": [
    {
      "product_id": "...",
      "title": "...",
      "view_count": 210,
      "wishlist_add_count": 12,
      "offer_count": 3
    }
  ]
}
```

### When to build

Pick this up after:
1. Paid plans / billing are defined and implemented
2. The app has enough active sellers that analytics data is meaningful
3. `wishlist_add_count` tracking is added (small backend task, ~1 hour)

---

## Deferred: Branch.io Deep Link Attribution

Dropped for now. Current setup gives accurate link click counts per campaign which is sufficient at this scale.

Add Branch.io when running multiple simultaneous paid campaigns and precise install-to-campaign attribution is needed for budget decisions. The `campaign_events` table already has `type: app_install` support — no schema changes needed when this is picked up.

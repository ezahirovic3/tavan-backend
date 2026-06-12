# Badges & Gamification — Future Plan

Planned post-launch, once there is a real user base to differentiate.

---

## Founding Seller Badge

The **first 50 manually approved sellers** earn a permanent Founding Seller badge.

### Data model

```
users
  is_founding_seller    boolean   default false
  founding_seller_no    smallint  nullable   (1–50, order of approval)
```

Awarded inside a guarded transaction at seller approval time — lock + count check so
the total never exceeds 50 even under concurrent approvals.

### Surface

- **Mobile:** badge on profile header and next to seller name on listings
- **API:** expose `is_founding_seller` and `founding_seller_no` on the public `UserResource`
- **Filament:** founding-seller status visible on user record; remaining-slots counter useful
  during the award window

### Open question

Decide before awarding whether the badge is purely cosmetic or carries economic perks
(reduced commission, priority placement, early feature access). Cosmetic-only is the safe
default; perks require unit-economics sign-off once payments are live.

---

## Broader Gamification (TBD)

Other badge/milestone ideas to revisit once user behaviour data is available:

- **Top Seller** — based on completed orders / rating threshold
- **Fast Responder** — reply time below a target window
- **Verified** — phone + identity verified (if ID verification is added later)
- Seasonal or campaign badges

Keep the schema generic (`user_badges` join table) rather than per-badge boolean columns
once more than two or three badge types exist.

# Future: Admin ↔ User In-App Messaging

## Overview

A future phase will introduce a direct messaging channel between Tavan admins and regular users, surfaced inside the existing conversations UI. This will replace ad-hoc email/phone contact and enable structured, auditable communication around listings, reports, and account actions.

## Motivation

- **Listing rejections** — when an admin rejects a `pending_review` product (returns it to `draft`), the seller needs to know *why*. Today there is no in-app mechanism to deliver that reason.
- **Report outcomes** — users who file product or user reports should receive a resolution update.
- **Account restrictions** — if a seller's `listings_require_review` flag is re-enabled (due to policy violations), they should be notified in-app with context.
- **General support escalation** — support inquiries submitted via the landing page or in-app help screen that need a back-and-forth response.

## Proposed Design

### Conversation thread

Reuse the existing `conversations` + `messages` tables. Create a special system user (e.g., `username = "tavan"`, `role = "admin"`) that acts as the sender for all admin-originated messages. This keeps the mobile UI unchanged — the user sees a conversation with "Tavan" just like any other thread.

### System message types (extend existing `type` enum)

| type | payload | Trigger |
|---|---|---|
| `system_listing_rejected` | `{ product_id, reason }` | Admin clicks "Odbij" on a product |
| `system_listing_approved` | `{ product_id }` | Admin clicks "Odobri" on a product |
| `system_account_restricted` | `{ reason }` | Admin re-enables `listings_require_review` |
| `system_report_resolved` | `{ report_id, outcome }` | Admin resolves a user/product report |

### Filament integration

- "Odbij oglas" action should open a modal asking for a rejection reason before saving.
- The reason is stored in the system message payload and displayed inline in the mobile conversation view.
- Admin can also open a free-form message compose panel from the User or Product record page to send arbitrary messages.

### Mobile UI

- Conversation list shows "Tavan" thread at the top (pinned).
- System messages render as styled info cards (not chat bubbles), similar to existing `system_offer` / `system_order` cards.
- Push notification is sent on every admin message.

## Status

**Not started.** Tracked here as a reminder.

Related files to touch when implementing:
- `database/migrations/` — possibly add `admin_id` or rely on the system user approach
- `app/Models/Conversation.php` + `Message.php`
- `app/Services/MessagingService.php`
- `app/Filament/Resources/ProductResource.php` — rejection reason modal
- `app/Filament/Resources/UserResource.php` — compose message action
- `src/components/pages/chat/` (mobile) — system message card variants

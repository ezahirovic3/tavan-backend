# Trust & Safety — Future Improvements

Two complementary features that together ensure every active user is verified and bad actors
can't simply re-register to evade consequences.

---

## 1. Mandatory Phone Verification

### Current state
- Phone OTP infrastructure already exists (`PhoneVerificationService`, `SmsProviderInterface`,
  `/auth/phone/send-otp`, `/auth/phone/verify-otp`)
- Phone is collected during registration but verification is **optional**
- Email is the only required verified identifier today
- `LogSmsProvider` is the only SMS driver — dev-only (logs OTP to disk, never sends a real SMS)

### SMS provider
**Twilio Verify** is the leading candidate for production OTP delivery (still under investigation).
Verify is a purpose-built OTP service: it owns code generation, expiry, and attempt limiting on
Twilio's side, so we don't store OTP codes in our database. Single `check` API call replaces the
verify-and-expire logic we'd otherwise write. Bosnia (+387) is a supported destination.

### Goal
Make a verified phone number a hard requirement before a user can perform any meaningful action
inside the app (list a product, message a seller, make an offer, place an order).

### Approach

**Backend**
- Add middleware `RequiresPhoneVerification` — checks `phone_verified_at` is set on the
  authenticated user; returns `403` with `code: phone_unverified` if not
- Apply to all write routes: products, conversations, offers, trades, orders
- Read routes (browsing, search, public profiles) remain open — user can explore before verifying
- The existing OTP endpoints stay unchanged; just the enforcement gate is new

**Mobile**
- On `403 phone_unverified`, intercept globally in the API client and redirect to a
  "Verifikuj broj telefona" screen (non-dismissable flow)
- After successful verification, resume whatever the user was trying to do
- Onboarding flow: prompt for phone + OTP immediately after profile setup is complete
  so most users are verified before they ever hit the gate

### What this gives us
- Every user who transacts has a real, unique phone number tied to their account
- Combined with the ban system below, evasion requires both a new phone number and a new device

---

## 2. Ban System

### Current state
- `UserReportResource` has a working Dismiss, Restrict (auto-review flag), and a
  placeholder Ban action that does nothing
- `ViewUser` has no ban action
- No `banned_until` column on users, no device tracking

### Goal
Allow admins to ban users for a fixed duration or permanently, with device-level fingerprinting
to prevent trivial re-registration.

---

### 2a. Ban Durations

Store a single nullable `banned_until` datetime on the user:

| Value | Meaning |
|-------|---------|
| `NULL` | Not banned |
| future datetime | Temporarily banned until that moment |
| `2099-01-01` | Permanent (sentinel date — treated as "forever") |

Temp bans self-expire — no cron job needed. `isBanned()` is just `banned_until > now()`.

**Admin options when banning:**
- 7 days — minor harassment, spam
- 30 days — repeated violations, suspicious behaviour
- Permanent — fraud, scams, repeated temp bans

Admin can lift any ban early. A `ban_reason` (nullable string) is stored alongside for
internal record-keeping.

---

### 2b. Device Fingerprinting

**The problem:** a banned user deletes the app and re-registers with a new email.
Phone OTP alone partially solves this (they need a new SIM) but device fingerprinting
adds a second independent barrier.

**How it works:**

*Mobile*
- On first launch, generate a UUID and write it to `expo-secure-store`
  (`keychainAccessible: AFTER_FIRST_UNLOCK_THIS_DEVICE_ONLY` on iOS so it survives
  reinstalls on the same device)
- Send as `X-Device-ID` header on every request, including unauthenticated ones
  (registration, login)
- Also collect `expo-device` fingerprint metadata (platform, OS version, model name)
  and send as `X-Device-Meta` (JSON) — used for matching confidence, not as the primary key

*Backend — new table: `banned_devices`*
```
id              ulid  PK
device_id       string  (indexed — the UUID from SecureStore)
banned_at       timestamp
reason          string nullable
```

No user FK — intentional. The record must survive account deletion/anonymisation.

*On ban:*
- Insert all `device_id` values seen from that user (from their push tokens + any
  request logs) into `banned_devices`
- Revoke all Sanctum tokens for the user immediately

*On registration:*
- Middleware checks `X-Device-ID` against `banned_devices` before the User is created
- If found → `403` with neutral message: *"Registracija nije moguća s ovog uređaja."*
- No indication of why — don't tell the bad actor what signal tripped

*On login:*
- Same check — prevents a banned user logging into a different account from the same device

---

### 2c. What the ban does to the account

| Action | Effect |
|--------|--------|
| All Sanctum tokens | Revoked immediately — user is logged out |
| Active & reserved listings | Hidden (status → `draft`) — preserved for audit, invisible in feeds |
| Sold listings | Untouched — order history integrity |
| Conversations | `allow_replies` → `false` — history preserved, no new messages |
| Login attempts | `403` with *"Tvoj račun je suspendiran."* — no login until `banned_until` passes |
| API requests with old token | `401` Unauthenticated — mobile handles this as a forced logout |

Temp bans: once `banned_until` is in the past the account is automatically active again —
no admin action needed.

---

### 2d. What other users see

Banned user's public profile returns `404` (same as a blocked user). The mobile screen
shows *"Profil nije dostupan"* — no explicit "banned" label. Moderation decisions stay
internal.

---

### 2e. Limitations (be honest about these)

Device fingerprinting is a **soft barrier**, not a hard one:
- Factory reset or a new phone bypasses device ID entirely
- iOS SecureStore can be cleared with a full uninstall on older OS versions

This is fine. The goal is to stop **casual** ban evasion (the 95% case), not a determined
adversary. The combination of device ban + mandatory phone OTP means evasion requires:
1. A new device (or full reset), **and**
2. A new phone number

At that point the effort exceeds the motivation for most bad actors on a marketplace.

---

### 2f. Admin surface

**UserReportResource** (replaces the placeholder):
- Ban action: dropdown for duration (7d / 30d / permanent), textarea for `ban_reason`,
  confirmation modal, updates report status to `banned`

**ViewUser** (header actions):
- "Baniraj" button — same duration picker
- "Skini ban" button — visible only when `isBanned()`, clears `banned_until`
- BANNED badge visible in the hero section when active

**ListUsers** (table):
- Banned column with badge and remaining time (`jos 4 dana` / `Permanentno`)
- Filter: show only banned users

---

## Sequencing

These two features are independent but **phone OTP enforcement should ship first** —
it gives every existing user a grace period to verify before the ban system goes live,
so banning + device-locking a verified user feels fair and defensible.

Suggested order:
1. Phone OTP enforcement (backend middleware + mobile intercept flow)
2. Ban system (migration + admin actions + device fingerprinting)

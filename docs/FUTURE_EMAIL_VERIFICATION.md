# Future: Email Verification + Resend + React Email

## Overview

Manual email/password accounts currently receive a Sanctum token immediately on registration with no email verification step. This document covers the full plan to enforce email verification before token issuance, migrate email delivery to Resend, and build branded transactional email templates with React Email.

Social login (Google, Apple) is unaffected — those emails are pre-verified by the provider and `email_verified_at` will be set automatically on account creation.

---

## Prerequisites (manual steps)

1. Sign up at resend.com
2. Add and verify `tavan.store` as a sending domain
3. Create an API key
4. Add to backend `.env`:

```
MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxxxxxx
MAIL_FROM_ADDRESS=noreply@tavan.store
MAIL_FROM_NAME=Tavan
```

---

## Backend

### Dependencies
```bash
composer require resend/laravel
```

### Migration
New `email_verification_tokens` table:

| column | type | notes |
|---|---|---|
| `email` | string | primary lookup key |
| `token` | string | bcrypt-hashed 6-digit OTP |
| `sent_at` | timestamp | used to enforce 60s resend cooldown |
| `created_at` | timestamp | used to enforce 15min OTP expiry |

### Auth flow changes

**`LocalAuthProvider::register()`**
- Creates account as before
- Generates 6-digit OTP, stores hashed in `email_verification_tokens`
- Sends `EmailVerificationOtpNotification`
- Returns `{ status: "verification_required", email }` — **no token**

**`LocalAuthProvider::login()`**
- Checks `email_verified_at` after password verification
- If null → returns `403` with `{ code: "email_not_verified", email }`
- Mobile catches this code and routes to the verify screen
- Does not auto-resend OTP on login — user taps resend manually

### New controller methods on `AuthController`

**`verifyEmail()`**
- Looks up token record by email
- Checks 15min expiry on `created_at` — returns 422 if expired
- Checks OTP hash — returns 422 if wrong
- Sets `email_verified_at = now()`, deletes token record
- Issues and returns Sanctum token — first and only time token is granted

**`resendEmailVerification()`**
- Checks `sent_at` — returns 429 with seconds remaining if < 60s ago
- Generates fresh OTP, replaces existing token record, updates `sent_at`
- Sends `EmailVerificationOtpNotification`

### New routes (public, no `auth:sanctum`)
```
POST api/v1/auth/email/verify
POST api/v1/auth/email/resend
```

Both behind `throttle:5,10` middleware (5 requests per 10 minutes per IP).

### Notifications

**New `EmailVerificationOtpNotification`** — same shape as existing `PasswordResetOtpNotification`.

Both notifications:
- Add `implements ShouldQueue` so API responses don't wait on email delivery
- Initially use plain `MailMessage` (works immediately)
- Swap to Resend template IDs once React Email templates are built and published

### `SocialAuthController`

In `findOrCreateUser()`:
- On **create**: set `email_verified_at = now()`
- On **provider link** (existing account found by email): set `email_verified_at = now()` if not already set

---

## Email Templates (React Email)

New `emails/` directory in the landing repo (`/Volumes/SSD/tavan-landing/emails/`):

```
emails/
  components/
    Header.tsx       — logo + brand header bar
    Footer.tsx       — links, unsubscribe notice, address
    OtpBox.tsx       — large styled 6-digit code display block
  EmailVerification.tsx
  PasswordReset.tsx
```

Both `EmailVerification` and `PasswordReset` share `Header`, `Footer`, and `OtpBox`. The only differences are the subject line and body copy.

**Build flow:**
1. `cd emails && npx react-email dev` — live browser preview
2. Build and publish templates to Resend dashboard
3. Grab template IDs from Resend
4. Update notifications to call Resend with `{ template_id, to, variables: { otp, name } }` instead of `MailMessage`

---

## Mobile

### `authService.js`
```js
verifyEmail(email, otp)     → POST auth/email/verify
resendEmailVerification(email) → POST auth/email/resend
```

### `authContext.js`
- `_signUp()` — on success, does not store token; returns `{ email }` so screen can navigate to verify
- `_signIn()` — on `403` with `code: "email_not_verified"`, throws typed error for screen to catch

### New screen: `(auth)/verifyEmail.js`
- Reuses `OtpInput` component from `verifyResetCode.js`
- Receives `email` as route param
- 60-second countdown on resend button — disabled while counting, shows `"Pošalji ponovo za 0:42"`
- On verify success → store token → navigate to profile setup (`/(main)/settings/editProfile?mode=setup`)
- On resend → reset OTP input, restart 60s countdown
- On `429` from backend → show "Sačekaj malo" and keep button disabled, sync timer to `seconds_remaining` from response

### `signUp.js`
- On success → `router.replace("/(auth)/verifyEmail", { email })`
- Remove the current success flash message (verification is not done yet)

### `signIn.js`
- Catch `email_not_verified` error → `router.replace("/(auth)/verifyEmail", { email })`

---

## Rate limiting summary

| Mechanism | Where | What it prevents |
|---|---|---|
| 60s UI countdown | Mobile | Accidental spam from normal users |
| 60s `sent_at` backend check | `AuthController` | API calls that bypass the UI |
| `throttle:5,10` middleware | Route | Brute-force / scripted attacks |
| 15min OTP expiry | `AuthController` | Stale codes being used later |

---

## Implementation order

1. **Prerequisites** — Resend account, domain verification, API key in `.env`
2. **Backend** — migration, auth changes, new endpoints, notifications (plain `MailMessage` first)
3. **Mobile** — service calls, context changes, new `verifyEmail.js` screen
4. **React Email templates** — build, preview, publish to Resend
5. **Swap** plain `MailMessage` in notifications for Resend template IDs

Steps 2 and 3 are fully buildable before Resend is set up. Steps 4 and 5 are the polish pass.

---

## Status

**Not started.** Tracked here as a reminder.

Related files to touch when implementing:
- `database/migrations/` — `email_verification_tokens` table
- `app/Services/Auth/LocalAuthProvider.php`
- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Controllers/Api/SocialAuthController.php`
- `app/Notifications/EmailVerificationOtpNotification.php` (new)
- `app/Notifications/PasswordResetOtpNotification.php` (add ShouldQueue)
- `routes/api.php`
- `/Volumes/SSD/tavan-landing/emails/` (new directory)
- `/Volumes/SSD/tavan-mobile/src/api/authService.js`
- `/Volumes/SSD/tavan-mobile/src/contexts/authContext.js`
- `/Volumes/SSD/tavan-mobile/app/(auth)/verifyEmail.js` (new)
- `/Volumes/SSD/tavan-mobile/app/(auth)/signUp.js`
- `/Volumes/SSD/tavan-mobile/app/(auth)/signIn.js`

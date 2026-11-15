# API Phase 6 — Public site, Google, Resend, admin billing

## Public

| GET | `/api/public/bootstrap` | Plans, features, `google_auth_enabled` for landing page |

## Google OAuth

| GET | `/api/auth/google/url` | JSON `{ "url": "..." }` — redirect browser to Google |
| GET | `/api/auth/google/callback` | Google redirect target; sends user to `FRONTEND_URL/auth/google/callback?token=...` |

Env: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` (must match Google Cloud console).

## Email (Resend)

Set `MAIL_MAILER=resend`, `RESEND_API_KEY`, `MAIL_FROM_*`, `MAIL_ENABLED=true`, `MAIL_ADMIN_ADDRESS` (notified on new pending subscription).

Mailables: welcome (register/Google), admin pending notice, subscription activated.

## Admin (super-admin)

| PUT | `/api/admin/tenants/{tenant}/subscription` | Body: `plan_slug`, `status` (`manual_pending` \| `active` \| `suspended`), optional `notes` |
| GET/PATCH | `/api/admin/plans` | Manage catalog pricing |
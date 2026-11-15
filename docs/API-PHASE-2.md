# API — Phase 2 (Meta OAuth & Social Accounts)

Prerequisites: Phase 1 auth + `X-Tenant-Id` header.

## Environment (backend `.env`)

```env
META_APP_ID=your-app-id
META_APP_SECRET=your-app-secret
META_REDIRECT_URI=http://127.0.0.1:8000/api/meta/oauth/callback
FRONTEND_URL=http://localhost:3000
```

In [Meta for Developers](https://developers.facebook.com/), add the redirect URI and enable Facebook Login for Business.

## OAuth flow

1. `GET /api/meta/oauth/url` (auth + tenant) → `{ authorize_url, state }`
2. User signs in on Meta
3. Meta redirects to `GET /api/meta/oauth/callback` (public)
4. API discovers Pages + linked Instagram accounts, stores encrypted tokens
5. Browser redirects to `{FRONTEND_URL}/social-accounts?connected=N`

Optional query on step 1: `?brand_id=` to pre-link new accounts to a brand.

## Social accounts (tenant-scoped)

| Method | Path | Role |
|--------|------|------|
| GET | `/api/social-accounts` | owner, admin, editor |
| GET | `/api/social-accounts/{id}` | members (same tenant) |
| PATCH | `/api/social-accounts/{id}` | owner, admin (`brand_id` only) |
| DELETE | `/api/social-accounts/{id}` | owner, admin (disconnect) |

Tokens are **never** returned in JSON responses.

## Platforms

- `facebook_page`
- `instagram_professional` (via Page-linked Instagram Business account)

## Next phase (MVP P0)

Content items, approval workflow, publishing adapters, scheduled jobs.
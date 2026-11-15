# SocialPilot Marketing (social-media-marketing)

Multi-tenant social media marketing SaaS — **Phase 1** implements Laravel tenancy, auth, roles, and brand CRUD, plus a Next.js dashboard connected to the real API.

## Stack

| Layer | Path | Tech |
|-------|------|------|
| API | `backend/` | Laravel 13, Sanctum, MySQL/SQLite |
| UI | `frontend/` | Next.js App Router, TypeScript, Tailwind |
| Docs | `docs/` | API & architecture notes |

Architecture blueprint (interactive): [SocialPilot Marketing — Futuristic Architecture Blueprint](https://cursor.com/canvas/store-7a2b7c15-1bf9-4e1b-b431-88b92d1969d1/1c07c63b-e84a-4634-ac76-1f342107e60b)

## Local development

### Backend

```bash
cd backend
cp .env.example .env   # if needed
composer install
php artisan key:generate    # required — missing APP_KEY causes 500 on register
touch database/database.sqlite   # only if DB_CONNECTION=sqlite (default)
php artisan migrate
php artisan serve
```

**Verify API before registering:** open [http://127.0.0.1:8000/api/health](http://127.0.0.1:8000/api/health) — should return `"status":"ok"`. If not, follow the `hint` in the JSON response.

### Register returns 500?

| Cause | Fix |
|-------|-----|
| No `APP_KEY` | `php artisan key:generate` |
| DB not migrated | `php artisan migrate` |
| SQLite file missing | `touch backend/database/database.sqlite` |
| MySQL misconfigured | Use SQLite for local dev, or create DB and set `DB_*` in `.env` |
| `vendor/` missing | `composer install` |

The `WebSocket … HMR … Back-Forward Cache` message in the browser is harmless (Next.js hot reload); ignore it.

Run tests:

```bash
php artisan test
```

### Frontend

```bash
cd frontend
cp .env.example .env.local
npm install
npm run dev
```

Set `NEXT_PUBLIC_API_URL` to your Laravel host (default `http://127.0.0.1:8000`).

## Phase 3 — Content, AI & approvals

- Content model + approval audit log
- Provider-agnostic AI caption generation (`AI_API_KEY`)
- UI: Content studio, Approvals queue, Calendar list view
- Docs: [docs/API-PHASE-3.md](docs/API-PHASE-3.md)

## Phase 2 — Meta OAuth

- `social_accounts` table with encrypted tokens
- Official Meta OAuth (Facebook Pages + Instagram Professional discovery)
- `/social-accounts` dashboard + link accounts to brands
- Docs: [docs/API-PHASE-2.md](docs/API-PHASE-2.md)

## Phase 1 scope (implemented)

- Multi-tenant tables: `tenants`, `tenant_user`, `brands`
- Sanctum API auth (register, login, logout, me)
- Tenant header isolation (`X-Tenant-Id`)
- Laravel policies + feature tests for cross-tenant denial
- Next.js: login/register, dashboard shell, brand list/create/detail

## Not in Phase 1

Meta OAuth, AI, publishing, billing, calendar, approvals — see PRD §19.

API details: [docs/API-PHASE-1.md](docs/API-PHASE-1.md)
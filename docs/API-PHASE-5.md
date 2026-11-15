# API Phase 5 — Billing & Admin

## Tenant (auth + `X-Tenant-Id`)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/billing` | Subscription actuelle + gateway + instructions manuelles |
| GET | `/api/billing/plans` | Plans publics |
| POST | `/api/billing/request-plan` | Body `{ "plan_slug": "pro" }` — owner/admin uniquement |

## Super-admin (`is_super_admin`, pas de tenant header requis)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/admin/dashboard` | Compteurs plateforme |
| GET | `/api/admin/tenants` | Liste paginée |
| GET | `/api/admin/tenants/{id}` | Détail |
| GET | `/api/admin/users` | Utilisateurs |
| PATCH | `/api/admin/users/{id}/super-admin` | Body `{ "is_super_admin": true }` |
| GET | `/api/admin/subscriptions/pending` | Abonnements en attente de paiement |
| POST | `/api/admin/subscriptions/{id}/activate` | Body `{ "notes": "..." }` |
| POST | `/api/admin/subscriptions/{id}/suspend` | Suspendre |

## Inscription

`POST /api/auth/register` crée une subscription `manual_pending` sur le plan `DEFAULT_PLAN_SLUG` (défaut `starter`).

## Configuration

Voir `backend/config/billing.php` et `docs/MVP.md`.
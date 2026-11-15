# API — Phase 1 (Tenancy & Brands)

Base URL: `http://127.0.0.1:8000` (local)

Authentication: `Authorization: Bearer {token}` (Sanctum personal access token)

Tenant scoping (brand routes): `X-Tenant-Id: {tenant_id}`

## Auth

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/auth/register` | Register user + default organization (owner) |
| POST | `/api/auth/login` | Login, returns token |
| POST | `/api/auth/logout` | Revoke current token |
| GET | `/api/auth/me` | Current user + tenant memberships |

### Register body

```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "secret",
  "password_confirmation": "secret",
  "organization_name": "Acme Marketing"
}
```

## Tenants

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/tenants` | List organizations for current user |
| POST | `/api/tenants` | Create organization (caller becomes owner) |
| GET | `/api/tenants/{id}` | View organization (member only) |

## Brands (tenant-scoped)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/brands` | List brands for `X-Tenant-Id` |
| POST | `/api/brands` | Create brand |
| GET | `/api/brands/{id}` | Show brand (same tenant) |
| PATCH | `/api/brands/{id}` | Update brand (`archived: true` to archive) |
| DELETE | `/api/brands/{id}` | Delete (owner/admin only) |

## Roles

Tenant roles: `owner`, `admin`, `editor`.

- Editors: create/read/update brands; cannot delete.
- Owners/admins: full brand CRUD.
- `users.is_super_admin`: bypass for platform admin (future `/admin` UI).
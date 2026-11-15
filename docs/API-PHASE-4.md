# API — Phase 4 (Publishing engine)

Only **approved** or **scheduled** content can publish. Tokens stay on the server.

## Endpoints

| Method | Path | Description |
|--------|------|-------------|
| POST | `/api/contents/{id}/publish` | Queue publish job (owner/admin) |
| GET | `/api/publishing-logs` | `?content_id=`, `?status=` |

## Scheduler

```bash
php artisan schedule:work   # local
# or cron: * * * * * php artisan schedule:run

php artisan content:publish-due   # manual — queues due scheduled posts
```

Requires a queue worker for jobs:

```bash
php artisan queue:work
```

## Platforms

- **Facebook Page** — text post (`/{page-id}/feed`)
- **Instagram** — single image via `image_url` in `media_assets` (public HTTPS URL)

## Idempotency

Each publish uses key `content:{id}:v{version}`. Successful logs block duplicate publishes for the same version.

## Ops checklist

1. Meta app permissions for publish scopes
2. Brand linked social account (`/social-accounts`)
3. Content approved → publish or schedule
4. Redis/database queue configured (`QUEUE_CONNECTION`)
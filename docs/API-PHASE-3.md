# API — Phase 3 (Content, AI, Approvals)

Tenant header required. AI uses OpenAI-compatible chat API (`AI_API_KEY`, `AI_BASE_URL`, `AI_MODEL`).

## Content CRUD

| Method | Path |
|--------|------|
| GET | `/api/contents` — `?status=`, `?pending_only=1`, `?brand_id=` |
| POST | `/api/contents` |
| GET/PATCH/DELETE | `/api/contents/{id}` |

## Workflow actions

| Method | Path | Who |
|--------|------|-----|
| POST | `/api/contents/{id}/submit` | editor+ |
| POST | `/api/contents/{id}/approve` | owner, admin |
| POST | `/api/contents/{id}/reject` | owner, admin |
| POST | `/api/contents/{id}/request-changes` | owner, admin |
| POST | `/api/contents/{id}/schedule` | owner, admin — body: `scheduled_at` (approved only) |
| POST | `/api/contents/{id}/generate-caption` | editor+ — sets `pending_approval` |

Statuses: `draft` → `pending_approval` → `approved` → `scheduled` (publish phase later).

## AI environment

```env
AI_API_KEY=sk-...
AI_BASE_URL=https://api.openai.com/v1   # or compatible provider
AI_MODEL=gpt-4o-mini
```

Unapproved content is never eligible for publishing (next phase).
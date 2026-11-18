# Roadmap — weekly planning & automation

Your target workflow:

1. User fills a **short weekly brief** (like a sheet): brand, goals, posts/reels/stories per day, platforms, times, with/without captions.
2. **AI drafts** the full week (copy + structure; later images/video).
3. User **reviews and confirms** (batch approval).
4. System **schedules and publishes** automatically within **plan limits**.

## What exists today (this repo)

| Piece | Status |
|-------|--------|
| Plan limits `posts_per_day`, `reels_per_week`, `stories_per_day` | Seeded per Starter / Pro / Agency; **editable in `/admin/plans`** |
| Usage vs limits on **Dashboard** | `GET /api/dashboard/stats` |
| Enforcement on schedule / publish / AI generate / new Meta connect | When `BILLING_ENFORCE_ACTIVE=true` |
| **Week plan** + **Phase 2 AI week** | `POST /week-plans/{id}/generate` (queue), `approve-all`, `/planner` UI |
| Image generation | Hook only (`IMAGE_PROVIDER=none`); wire API in Phase 3 |
| Calendar week grid | Still list view — grid polish optional |

## Phase 2 — AI week builder (**shipped on branch `cursor/phase-2-week-ai-f52a`**)

- `GenerateWeekPlanJob` + `WeekPlanGenerationService`
- Groq topics per slot + optional format-aware captions
- `POST /week-plans/{id}/approve-all` schedules approved posts
- Requires `php artisan queue:work`

## Phase 3 — Images & media

- Storage (S3 / R2), `media_assets` upload API.
- Optional image API (OpenAI, Flux, etc.) behind feature flag.
- For reels: script + storyboard only until video pipeline exists.

## Phase 4 — Hands-off automation

- “Activate week plan” → auto-approve optional (per brand setting).
- Queue: generate missing copy → attach media → schedule → `content:publish-due`.
- Notifications (Resend): plan ready for review, publish failures.

## Admin

All numeric limits live in **`subscription_plans.limits`** JSON. Super-admin edits via **Admin → Plans** (price + limits). Re-seed optional: `php artisan db:seed --class=SubscriptionPlanSeeder` merges by slug.

## Default plan caps (editable)

| Plan | Posts / day | Reels / week | Stories / day | Social accounts |
|------|-------------|--------------|---------------|-----------------|
| Starter | 1 | 1 | 1 | 4 |
| Pro | 2 | 2 | 2 | 20 |
| Agency | 5 | 5 | 5 | Unlimited |
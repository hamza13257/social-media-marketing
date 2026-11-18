"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { useAuth } from "@/context/auth-context";
import { api, ApiError } from "@/lib/api";
import type { DashboardStats } from "@/types/api";
import { UsageMeter } from "@/components/dashboard/usage-meter";

export default function DashboardPage() {
  const { user, tenants, activeTenantId } = useAuth();
  const active = tenants.find((t) => String(t.id) === activeTenantId);
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      const res = await api.dashboardStats();
      setStats(res.data);
      setError(null);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load stats.");
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const limits = stats?.limits ?? {};
  const usage = stats?.usage ?? {};

  return (
    <div className="mx-auto max-w-5xl">
      <h1 className="text-3xl font-semibold text-white">Dashboard</h1>
      <p className="mt-2 text-slate-400">
        Plan usage, publishing caps, and quick links. Weekly AI automation roadmap:{" "}
        <span className="text-slate-300">docs/ROADMAP-WEEKLY-AUTOMATION.md</span>
      </p>

      <div className="mt-8 grid gap-4 md:grid-cols-3">
        <Panel title="Signed in as" value={user?.name ?? "—"} />
        <Panel title="Organization" value={active?.name ?? "—"} />
        <Panel
          title="Subscription"
          value={
            stats?.plan_name
              ? `${stats.plan_name} (${stats.subscription_status ?? "—"})`
              : "—"
          }
        />
      </div>

      {error ? <p className="mt-6 text-sm text-rose-400">{error}</p> : null}

      {stats ? (
        <>
          <section className="mt-8">
            <h2 className="text-lg font-medium text-white">Publishing limits (today / this week)</h2>
            <p className="mt-1 text-xs text-slate-500">
              Counts include scheduled and published slots. Upgrade on{" "}
              <Link href="/billing" className="text-blue-300 hover:text-blue-200">
                Billing
              </Link>
              .
            </p>
            <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
              <UsageMeter
                label="Feed posts (today)"
                used={usage.posts_today ?? 0}
                limit={limits.posts_per_day}
                hint="Single image, carousel, article"
              />
              <UsageMeter
                label="Reels (this week)"
                used={usage.reels_this_week ?? 0}
                limit={limits.reels_per_week}
              />
              <UsageMeter
                label="Stories (today)"
                used={usage.stories_today ?? 0}
                limit={limits.stories_per_day}
              />
              <UsageMeter
                label="AI generations (month)"
                used={usage.ai_generations_month ?? 0}
                limit={limits.ai_generations_month}
              />
              <UsageMeter
                label="Brands"
                used={usage.brands ?? 0}
                limit={limits.brands}
              />
              <UsageMeter
                label="Social accounts"
                used={usage.social_accounts ?? 0}
                limit={limits.social_accounts}
              />
            </div>
          </section>

          <section className="mt-8 rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
            <h2 className="text-lg font-medium text-white">Content pipeline</h2>
            <div className="mt-4 flex flex-wrap gap-4 text-sm">
              <Stat label="Scheduled upcoming" value={stats.content.upcoming_scheduled} />
              <Stat
                label="Pending approval"
                value={Number(stats.content.by_status?.pending_approval ?? 0)}
              />
              <Stat
                label="Published"
                value={Number(stats.content.by_status?.published ?? 0)}
              />
            </div>
          </section>
        </>
      ) : null}

      <section className="mt-8 rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
        <h2 className="text-lg font-medium text-white">Next steps</h2>
        <ul className="mt-4 space-y-2 text-sm">
          <li>
            <Link href="/planner" className="text-blue-300 hover:text-blue-200">
              Weekly planner
            </Link>
            — save your brief for the week (AI fill coming in phase 2)
          </li>
          <li>
            <Link href="/content-studio" className="text-blue-300 hover:text-blue-200">
              Content studio
            </Link>
          </li>
          <li>
            <Link href="/calendar" className="text-blue-300 hover:text-blue-200">
              Calendar
            </Link>
          </li>
        </ul>
        {user?.is_super_admin ? (
          <p className="mt-4 text-sm text-violet-300">
            <Link href="/admin/plans">Admin → Plans</Link> — edit limits per subscription
          </p>
        ) : null}
      </section>
    </div>
  );
}

function Panel({ title, value }: { title: string; value: string }) {
  return (
    <div className="rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 to-slate-950 p-5">
      <p className="text-xs uppercase tracking-wide text-blue-400">{title}</p>
      <p className="mt-2 text-xl font-semibold text-white">{value}</p>
    </div>
  );
}

function Stat({ label, value }: { label: string; value: number }) {
  return (
    <div className="rounded-lg border border-slate-800 bg-slate-950/60 px-4 py-3">
      <p className="text-xs text-slate-500">{label}</p>
      <p className="text-2xl font-semibold text-white">{value}</p>
    </div>
  );
}
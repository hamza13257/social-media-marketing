"use client";

import { useCallback, useEffect, useState } from "react";
import { ApiError, api } from "@/lib/api";
import type { BillingInfo, SubscriptionPlan } from "@/types/api";
import { Button } from "@/components/ui/button";

function formatLimit(value: number | null | undefined): string {
  if (value === null || value === undefined) {
    return "Unlimited";
  }
  return String(value);
}

export default function BillingPage() {
  const [info, setInfo] = useState<BillingInfo | null>(null);
  const [plans, setPlans] = useState<SubscriptionPlan[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  const load = useCallback(async () => {
    setError(null);
    try {
      const [sub, planList] = await Promise.all([
        api.billing(),
        api.billingPlans(),
      ]);
      setInfo(sub);
      setPlans(planList.data);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Could not load billing.");
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  async function requestPlan(slug: string) {
    setBusy(true);
    setError(null);
    try {
      await api.requestPlan(slug);
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Request failed.");
    } finally {
      setBusy(false);
    }
  }

  const sub = info?.data;
  const status = sub?.status ?? "unknown";

  return (
    <div className="mx-auto max-w-3xl space-y-8">
      <header>
        <h1 className="text-2xl font-semibold text-white">Billing</h1>
        <p className="mt-1 text-sm text-slate-400">
          Manual payment: choose a plan, pay off-platform, then we activate your
          workspace.
        </p>
      </header>

      {error && (
        <p className="rounded-lg border border-red-900/50 bg-red-950/40 px-4 py-3 text-sm text-red-200">
          {error}
        </p>
      )}

      <section className="rounded-xl border border-slate-800 bg-slate-950/60 p-6">
        <h2 className="text-sm font-medium uppercase tracking-wide text-slate-500">
          Current subscription
        </h2>
        {sub ? (
          <dl className="mt-4 grid gap-3 text-sm text-slate-200">
              <div className="flex justify-between gap-4">
                <dt className="text-slate-500">Plan</dt>
                <dd>{sub.plan?.name ?? "—"}</dd>
              </div>
              <div className="flex justify-between gap-4">
                <dt className="text-slate-500">Status</dt>
                <dd className="capitalize">{status.replace(/_/g, " ")}</dd>
              </div>
              <div className="flex justify-between gap-4">
                <dt className="text-slate-500">Gateway</dt>
                <dd className="capitalize">{info?.gateway ?? "—"}</dd>
              </div>
              {sub.activated_at && (
                <div className="flex justify-between gap-4">
                  <dt className="text-slate-500">Activated</dt>
                  <dd>{new Date(sub.activated_at).toLocaleString()}</dd>
                </div>
              )}
            </dl>
        ) : (
          <p className="mt-4 text-sm text-slate-400">No subscription record yet.</p>
        )}

        {status === "manual_pending" && info?.manual_instructions && (
          <p className="mt-4 rounded-lg bg-blue-950/40 px-4 py-3 text-sm text-blue-100">
            {info.manual_instructions}
          </p>
        )}
      </section>

      <section className="space-y-4">
        <h2 className="text-sm font-medium uppercase tracking-wide text-slate-500">
          Plans
        </h2>
        <div className="grid gap-4 md:grid-cols-3">
          {plans.map((plan) => (
            <article
              key={plan.id}
              className="flex flex-col rounded-xl border border-slate-800 bg-slate-950/60 p-5"
            >
              <h3 className="font-semibold text-white">{plan.name}</h3>
              <p className="mt-1 text-xs text-slate-500">{plan.description}</p>
              <p className="mt-4 text-lg font-medium text-blue-300">
                ${(plan.price_cents / 100).toFixed(0)}
                <span className="text-xs text-slate-500">/mo</span>
              </p>
              <ul className="mt-3 space-y-1 text-xs text-slate-500">
                <li>{formatLimit(plan.limits?.posts_per_day)} posts / day</li>
                <li>{formatLimit(plan.limits?.reels_per_week)} reels / week</li>
                <li>{formatLimit(plan.limits?.social_accounts)} social accounts</li>
              </ul>
              <Button
                className="mt-auto pt-6"
                disabled={busy || sub?.plan?.slug === plan.slug}
                onClick={() => requestPlan(plan.slug)}
              >
                {sub?.plan?.slug === plan.slug ? "Current" : "Select plan"}
              </Button>
            </article>
          ))}
        </div>
      </section>
    </div>
  );
}
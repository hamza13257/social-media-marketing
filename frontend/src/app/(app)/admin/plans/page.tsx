"use client";

import { useCallback, useEffect, useState } from "react";
import { ApiError, api } from "@/lib/api";
import type { SubscriptionPlan } from "@/types/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

const LIMIT_KEYS: { key: string; label: string; hint?: string }[] = [
  { key: "posts_per_day", label: "Posts / day" },
  { key: "reels_per_week", label: "Reels / week" },
  { key: "stories_per_day", label: "Stories / day" },
  { key: "ai_generations_month", label: "AI generations / month" },
  { key: "brands", label: "Brands" },
  { key: "social_accounts", label: "Social accounts" },
  { key: "team_members", label: "Team members" },
  { key: "storage_mb", label: "Storage (MB)" },
];

export default function AdminPlansPage() {
  const [plans, setPlans] = useState<SubscriptionPlan[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [busySlug, setBusySlug] = useState<string | null>(null);

  const load = useCallback(async () => {
    setError(null);
    try {
      const res = await api.adminPlans();
      setPlans(res.data);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Failed to load plans.");
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  async function save(plan: SubscriptionPlan, price: string, limits: Record<string, string>) {
    setBusySlug(plan.slug);
    setError(null);
    try {
      const cents = Math.round(parseFloat(price) * 100);
      const parsedLimits: Record<string, number | null> = {};
      for (const { key } of LIMIT_KEYS) {
        const raw = limits[key]?.trim() ?? "";
        if (raw === "" || raw.toLowerCase() === "unlimited") {
          parsedLimits[key] = null;
        } else {
          parsedLimits[key] = parseInt(raw, 10);
        }
      }
      await api.adminUpdatePlan(plan.id, { price_cents: cents, limits: parsedLimits });
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Update failed.");
    } finally {
      setBusySlug(null);
    }
  }

  return (
    <div className="space-y-4">
      {error && <p className="text-sm text-red-300">{error}</p>}
      <p className="text-sm text-slate-400">
        Edit pricing and all plan limits. Use <code className="text-slate-300">unlimited</code> or leave empty
        for no cap.
      </p>
      <div className="grid gap-4 lg:grid-cols-3">
        {plans.map((plan) => (
          <PlanEditor
            key={plan.id}
            plan={plan}
            busy={busySlug === plan.slug}
            onSave={save}
          />
        ))}
      </div>
    </div>
  );
}

function PlanEditor({
  plan,
  busy,
  onSave,
}: {
  plan: SubscriptionPlan;
  busy: boolean;
  onSave: (plan: SubscriptionPlan, price: string, limits: Record<string, string>) => void;
}) {
  const [price, setPrice] = useState(String(plan.price_cents / 100));
  const [limits, setLimits] = useState<Record<string, string>>(() =>
    initLimits(plan.limits ?? {}),
  );

  useEffect(() => {
    setPrice(String(plan.price_cents / 100));
    setLimits(initLimits(plan.limits ?? {}));
  }, [plan]);

  return (
    <article className="rounded-xl border border-slate-800 bg-slate-950/60 p-5">
      <h3 className="font-semibold text-white">{plan.name}</h3>
      <p className="text-xs text-slate-500">{plan.slug}</p>
      <p className="mt-2 text-sm text-slate-400">{plan.description}</p>
      <div className="mt-4">
        <label className="text-xs text-slate-500">Price (USD / month)</label>
        <Input
          className="mt-1"
          value={price}
          onChange={(e) => setPrice(e.target.value)}
          type="number"
          min={0}
          step="0.01"
        />
      </div>
      <div className="mt-4 space-y-2">
        <p className="text-xs font-medium text-slate-500">Limits</p>
        {LIMIT_KEYS.map(({ key, label }) => (
          <div key={key}>
            <label className="text-[11px] text-slate-500">{label}</label>
            <Input
              className="mt-0.5"
              value={limits[key] ?? ""}
              onChange={(e) => setLimits((prev) => ({ ...prev, [key]: e.target.value }))}
              placeholder="unlimited"
            />
          </div>
        ))}
      </div>
      <Button className="mt-4 w-full" disabled={busy} onClick={() => onSave(plan, price, limits)}>
        Save
      </Button>
    </article>
  );
}

function initLimits(limits: Record<string, number | null>): Record<string, string> {
  const out: Record<string, string> = {};
  for (const { key } of LIMIT_KEYS) {
    const v = limits[key];
    out[key] = v === null || v === undefined ? "" : String(v);
  }
  return out;
}
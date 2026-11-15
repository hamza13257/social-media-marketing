"use client";

import { useCallback, useEffect, useState } from "react";
import { ApiError, api } from "@/lib/api";
import type { SubscriptionPlan } from "@/types/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

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

  async function save(plan: SubscriptionPlan, price: string) {
    setBusySlug(plan.slug);
    setError(null);
    try {
      const cents = Math.round(parseFloat(price) * 100);
      await api.adminUpdatePlan(plan.id, { price_cents: cents });
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
        Edit public pricing shown on the client home page (`/`).
      </p>
      <div className="grid gap-4 md:grid-cols-3">
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
  onSave: (plan: SubscriptionPlan, price: string) => void;
}) {
  const [price, setPrice] = useState(String(plan.price_cents / 100));

  useEffect(() => {
    setPrice(String(plan.price_cents / 100));
  }, [plan.price_cents]);

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
      <Button className="mt-4 w-full" disabled={busy} onClick={() => onSave(plan, price)}>
        Save
      </Button>
    </article>
  );
}
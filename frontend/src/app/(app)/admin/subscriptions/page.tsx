"use client";

import { useCallback, useEffect, useState } from "react";
import { ApiError, api } from "@/lib/api";
import type { SubscriptionRecord } from "@/types/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

export default function AdminSubscriptionsPage() {
  const [items, setItems] = useState<SubscriptionRecord[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [notes, setNotes] = useState<Record<number, string>>({});
  const [busyId, setBusyId] = useState<number | null>(null);

  const load = useCallback(async () => {
    setError(null);
    try {
      const res = await api.adminPendingSubscriptions();
      setItems(res.data);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Failed to load queue.");
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  async function activate(id: number) {
    const note = notes[id]?.trim();
    if (!note) {
      setError("Payment notes are required before activation.");
      return;
    }
    setBusyId(id);
    setError(null);
    try {
      await api.adminActivateSubscription(id, note);
      await load();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Activation failed.");
    } finally {
      setBusyId(null);
    }
  }

  if (items.length === 0 && !error) {
    return <p className="text-sm text-slate-400">No pending manual payments.</p>;
  }

  return (
    <div className="space-y-4">
      {error && <p className="text-sm text-red-300">{error}</p>}
      {items.map((item) => (
        <div
          key={item.id}
          className="rounded-xl border border-slate-800 bg-slate-950/60 p-5"
        >
          <p className="font-medium text-white">
            {item.tenant?.name ?? `Tenant #${item.tenant_id}`} — {item.plan?.name}
          </p>
          <p className="mt-1 text-xs text-slate-500">Subscription #{item.id}</p>
          <div className="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
            <div className="flex-1">
              <label className="text-xs text-slate-500">Payment notes</label>
              <Input
                className="mt-1"
                value={notes[item.id] ?? ""}
                onChange={(e) =>
                  setNotes((n) => ({ ...n, [item.id]: e.target.value }))
                }
                placeholder="Transfer reference, date, amount…"
              />
            </div>
            <Button
              disabled={busyId === item.id}
              onClick={() => activate(item.id)}
            >
              Activate
            </Button>
          </div>
        </div>
      ))}
    </div>
  );
}
"use client";

import { useCallback, useEffect, useState } from "react";
import { ApiError, api } from "@/lib/api";
import type { AdminTenantRow, SubscriptionPlan } from "@/types/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

export default function AdminTenantsPage() {
  const [rows, setRows] = useState<AdminTenantRow[]>([]);
  const [plans, setPlans] = useState<SubscriptionPlan[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [openId, setOpenId] = useState<number | null>(null);

  const load = useCallback(async () => {
    setError(null);
    try {
      const [tenants, planList] = await Promise.all([
        api.adminTenants(),
        api.adminPlans(),
      ]);
      setRows(tenants.data);
      setPlans(planList.data);
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Failed to load tenants.");
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  return (
    <div className="space-y-4">
      {error && <p className="text-sm text-red-300">{error}</p>}
      <div className="overflow-x-auto rounded-xl border border-slate-800">
        <table className="min-w-full text-left text-sm text-slate-200">
          <thead className="bg-slate-900/80 text-xs uppercase text-slate-500">
            <tr>
              <th className="px-4 py-3">Organization</th>
              <th className="px-4 py-3">Plan</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Users</th>
              <th className="px-4 py-3">Brands</th>
              <th className="px-4 py-3" />
            </tr>
          </thead>
          <tbody>
            {rows.map((row) => (
              <tr key={row.id} className="border-t border-slate-800">
                <td className="px-4 py-3 font-medium text-white">{row.name}</td>
                <td className="px-4 py-3">{row.subscription?.plan?.name ?? "—"}</td>
                <td className="px-4 py-3 capitalize">
                  {row.subscription?.status?.replace(/_/g, " ") ?? "—"}
                </td>
                <td className="px-4 py-3">{row.users_count ?? "—"}</td>
                <td className="px-4 py-3">{row.brands_count ?? "—"}</td>
                <td className="px-4 py-3 text-right">
                  <Button variant="ghost" onClick={() => setOpenId(row.id)}>
                    Manage sub
                  </Button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {openId !== null && (
        <ManageSubscriptionPanel
          tenant={rows.find((r) => r.id === openId)!}
          plans={plans}
          onClose={() => setOpenId(null)}
          onSaved={load}
        />
      )}
    </div>
  );
}

function ManageSubscriptionPanel({
  tenant,
  plans,
  onClose,
  onSaved,
}: {
  tenant: AdminTenantRow;
  plans: SubscriptionPlan[];
  onClose: () => void;
  onSaved: () => Promise<void>;
}) {
  const [planSlug, setPlanSlug] = useState(tenant.subscription?.plan?.slug ?? "starter");
  const [status, setStatus] = useState(tenant.subscription?.status ?? "manual_pending");
  const [notes, setNotes] = useState("");
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function save() {
    setBusy(true);
    setError(null);
    try {
      await api.adminUpsertTenantSubscription(tenant.id, {
        plan_slug: planSlug,
        status,
        notes: notes || undefined,
      });
      await onSaved();
      onClose();
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Save failed.");
    } finally {
      setBusy(false);
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
      <div className="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-950 p-6 shadow-xl">
        <h2 className="text-lg font-semibold text-white">{tenant.name}</h2>
        <p className="text-xs text-slate-500">Assign plan or mark payment pending / active.</p>
        <div className="mt-4 space-y-3">
          <div>
            <label className="text-xs text-slate-500">Plan</label>
            <select
              className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
              value={planSlug}
              onChange={(e) => setPlanSlug(e.target.value)}
            >
              {plans.map((p) => (
                <option key={p.id} value={p.slug}>
                  {p.name}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="text-xs text-slate-500">Status</label>
            <select
              className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm"
              value={status}
              onChange={(e) => setStatus(e.target.value)}
            >
              <option value="manual_pending">manual_pending</option>
              <option value="active">active</option>
              <option value="suspended">suspended</option>
            </select>
          </div>
          <div>
            <label className="text-xs text-slate-500">Payment notes</label>
            <Input
              className="mt-1"
              value={notes}
              onChange={(e) => setNotes(e.target.value)}
              placeholder="Optional for activation"
            />
          </div>
        </div>
        {error && <p className="mt-3 text-sm text-red-300">{error}</p>}
        <div className="mt-6 flex justify-end gap-2">
          <Button variant="ghost" onClick={onClose}>
            Cancel
          </Button>
          <Button disabled={busy} onClick={save}>
            Save
          </Button>
        </div>
      </div>
    </div>
  );
}
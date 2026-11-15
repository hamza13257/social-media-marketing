"use client";

import { useEffect, useState } from "react";
import { ApiError, api } from "@/lib/api";
import type { AdminTenantRow } from "@/types/api";

export default function AdminTenantsPage() {
  const [rows, setRows] = useState<AdminTenantRow[]>([]);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api
      .adminTenants()
      .then((res) => setRows(res.data))
      .catch((e) =>
        setError(e instanceof ApiError ? e.message : "Failed to load tenants."),
      );
  }, []);

  if (error) {
    return <p className="text-sm text-red-300">{error}</p>;
  }

  return (
    <div className="overflow-x-auto rounded-xl border border-slate-800">
      <table className="min-w-full text-left text-sm text-slate-200">
        <thead className="bg-slate-900/80 text-xs uppercase text-slate-500">
          <tr>
            <th className="px-4 py-3">Organization</th>
            <th className="px-4 py-3">Plan</th>
            <th className="px-4 py-3">Status</th>
            <th className="px-4 py-3">Users</th>
            <th className="px-4 py-3">Brands</th>
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
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
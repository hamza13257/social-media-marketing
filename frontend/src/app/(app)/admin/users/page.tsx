"use client";

import { useEffect, useState } from "react";
import { ApiError, api } from "@/lib/api";
import type { AdminUserRow } from "@/types/api";
import { Button } from "@/components/ui/button";

export default function AdminUsersPage() {
  const [rows, setRows] = useState<AdminUserRow[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [busyId, setBusyId] = useState<number | null>(null);

  useEffect(() => {
    api
      .adminUsers()
      .then((res) => setRows(res.data))
      .catch((e) =>
        setError(e instanceof ApiError ? e.message : "Failed to load users."),
      );
  }, []);

  async function toggleSuperAdmin(user: AdminUserRow) {
    setBusyId(user.id);
    setError(null);
    try {
      await api.adminSetSuperAdmin(user.id, !user.is_super_admin);
      setRows((prev) =>
        prev.map((r) =>
          r.id === user.id ? { ...r, is_super_admin: !user.is_super_admin } : r,
        ),
      );
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Update failed.");
    } finally {
      setBusyId(null);
    }
  }

  return (
    <div className="space-y-4">
      {error && <p className="text-sm text-red-300">{error}</p>}
      <div className="overflow-x-auto rounded-xl border border-slate-800">
        <table className="min-w-full text-left text-sm text-slate-200">
          <thead className="bg-slate-900/80 text-xs uppercase text-slate-500">
            <tr>
              <th className="px-4 py-3">Name</th>
              <th className="px-4 py-3">Email</th>
              <th className="px-4 py-3">Super admin</th>
              <th className="px-4 py-3" />
            </tr>
          </thead>
          <tbody>
            {rows.map((row) => (
              <tr key={row.id} className="border-t border-slate-800">
                <td className="px-4 py-3 text-white">{row.name}</td>
                <td className="px-4 py-3">{row.email}</td>
                <td className="px-4 py-3">
                  {row.is_super_admin ? "Yes" : "No"}
                </td>
                <td className="px-4 py-3 text-right">
                  <Button
                    variant="ghost"
                    disabled={busyId === row.id}
                    onClick={() => toggleSuperAdmin(row)}
                  >
                    {row.is_super_admin ? "Revoke" : "Grant"}
                  </Button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
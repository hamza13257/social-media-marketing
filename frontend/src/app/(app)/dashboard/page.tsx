"use client";

import { useAuth } from "@/context/auth-context";

export default function DashboardPage() {
  const { user, tenants, activeTenantId } = useAuth();
  const active = tenants.find((t) => String(t.id) === activeTenantId);

  return (
    <div className="mx-auto max-w-5xl">
      <h1 className="text-3xl font-semibold text-white">Dashboard</h1>
      <p className="mt-2 text-slate-400">
        Premium marketing control center — Phase 1 foundation.
      </p>

      <div className="mt-8 grid gap-4 md:grid-cols-3">
        <Panel title="Signed in as" value={user?.name ?? "—"} />
        <Panel title="Organization" value={active?.name ?? "—"} />
        <Panel title="Your role" value={active?.role ?? "—"} />
      </div>

      <div className="mt-8 rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
        <h2 className="text-lg font-medium text-white">Next up (MVP P0)</h2>
        <ul className="mt-4 list-disc space-y-2 pl-5 text-sm text-slate-400">
          <li>Meta OAuth for Instagram & Facebook</li>
          <li>AI caption generation and content approval</li>
          <li>Calendar scheduling and publishing jobs</li>
        </ul>
      </div>
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
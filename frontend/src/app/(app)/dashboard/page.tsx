"use client";

import Link from "next/link";
import { useAuth } from "@/context/auth-context";

const steps = [
  { href: "/billing", label: "Billing", hint: "Active subscription (or admin activation)" },
  { href: "/brands", label: "Brands", hint: "Create a brand" },
  { href: "/social-accounts", label: "Social accounts", hint: "Connect Meta when available" },
  { href: "/content-studio", label: "Content studio", hint: "Draft → approve → publish" },
];

export default function DashboardPage() {
  const { user, tenants, activeTenantId } = useAuth();
  const active = tenants.find((t) => String(t.id) === activeTenantId);

  return (
    <div className="mx-auto max-w-5xl">
      <h1 className="text-3xl font-semibold text-white">Dashboard</h1>
      <p className="mt-2 text-slate-400">MVP workflow — publishing is on branch <code className="text-slate-500">main</code>.</p>

      <div className="mt-8 grid gap-4 md:grid-cols-3">
        <Panel title="Signed in as" value={user?.name ?? "—"} />
        <Panel title="Organization" value={active?.name ?? "—"} />
        <Panel title="Your role" value={active?.role ?? "—"} />
      </div>

      <div className="mt-8 rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
        <h2 className="text-lg font-medium text-white">Publication (lancer en local)</h2>
        <pre className="mt-4 overflow-x-auto rounded-lg bg-slate-950 p-4 text-xs text-slate-400">
{`php artisan serve
php artisan queue:work
php artisan schedule:work   # optional — scheduled posts`}
        </pre>
        <p className="mt-4 text-sm text-slate-500">
          See <span className="text-slate-300">docs/MVP-DEMARRAGE.md</span> in the repo.
        </p>
      </div>

      <div className="mt-8 rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
        <h2 className="text-lg font-medium text-white">Checklist</h2>
        <ul className="mt-4 space-y-3">
          {steps.map((step) => (
            <li key={step.href}>
              <Link href={step.href} className="font-medium text-blue-300 hover:text-blue-200">
                {step.label}
              </Link>
              <p className="text-xs text-slate-500">{step.hint}</p>
            </li>
          ))}
        </ul>
        {user?.is_super_admin ? (
          <p className="mt-4 text-sm text-violet-300">
            <Link href="/admin">Platform admin</Link> — activate subscriptions
          </p>
        ) : null}
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
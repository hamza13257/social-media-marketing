"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useAuth } from "@/context/auth-context";

const nav = [
  { href: "/dashboard", label: "Overview" },
  { href: "/brands", label: "Brands" },
  { href: "/social-accounts", label: "Social accounts" },
  { href: "/content-studio", label: "Content studio" },
  { href: "/approvals", label: "Approvals" },
  { href: "/calendar", label: "Calendar" },
];

export function Sidebar() {
  const pathname = usePathname();
  const { user, tenants, activeTenantId, setActiveTenantId, signOut } = useAuth();

  return (
  <aside className="flex h-full w-64 shrink-0 flex-col border-r border-slate-800 bg-slate-950/90 p-4">
    <div className="mb-8 px-2">
      <p className="text-xs font-semibold uppercase tracking-widest text-blue-400">
        SocialPilot
      </p>
      <h1 className="mt-1 text-lg font-semibold text-white">Marketing HQ</h1>
    </div>

    <nav className="space-y-1">
      {nav.map((item) => {
        const active =
          pathname === item.href || pathname.startsWith(`${item.href}/`);
        return (
          <Link
            key={item.href}
            href={item.href}
            className={`block rounded-lg px-3 py-2 text-sm font-medium transition ${
              active
                ? "bg-blue-600/20 text-blue-200"
                : "text-slate-300 hover:bg-slate-900 hover:text-white"
            }`}
          >
            {item.label}
          </Link>
        );
      })}
    </nav>

    <div className="mt-auto space-y-4 border-t border-slate-800 pt-4">
      <div>
        <label className="text-xs text-slate-500">Organization</label>
        <select
          className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-2 py-2 text-sm text-slate-200"
          value={activeTenantId ?? ""}
          onChange={(e) => setActiveTenantId(e.target.value)}
        >
          {tenants.map((t) => (
            <option key={t.id} value={t.id}>
              {t.name}
            </option>
          ))}
        </select>
      </div>
      <div className="px-2">
        <p className="truncate text-sm font-medium text-white">{user?.name}</p>
        <p className="truncate text-xs text-slate-500">{user?.email}</p>
      </div>
      <button
        type="button"
        onClick={() => signOut()}
        className="w-full rounded-lg px-3 py-2 text-left text-sm text-slate-400 hover:bg-slate-900 hover:text-white"
      >
        Sign out
      </button>
    </div>
  </aside>
  );
}
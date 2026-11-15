"use client";

import { useEffect, useState } from "react";
import { ApiError, api } from "@/lib/api";
import type { AdminDashboard } from "@/types/api";

export default function AdminDashboardPage() {
  const [data, setData] = useState<AdminDashboard | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    api
      .adminDashboard()
      .then((res) => setData(res.data))
      .catch((e) =>
        setError(e instanceof ApiError ? e.message : "Failed to load dashboard."),
      );
  }, []);

  if (error) {
    return <p className="text-sm text-red-300">{error}</p>;
  }

  if (!data) {
    return <p className="text-sm text-slate-400">Loading…</p>;
  }

  const cards = [
    { label: "Users", value: data.users },
    { label: "Organizations", value: data.tenants },
    { label: "Active subscriptions", value: data.subscriptions_active },
    { label: "Pending manual payments", value: data.subscriptions_pending },
    { label: "Payment gateway", value: data.payment_gateway },
  ];

  return (
    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      {cards.map((card) => (
        <div
          key={card.label}
          className="rounded-xl border border-slate-800 bg-slate-950/60 p-5"
        >
          <p className="text-xs uppercase tracking-wide text-slate-500">
            {card.label}
          </p>
          <p className="mt-2 text-2xl font-semibold text-white">{card.value}</p>
        </div>
      ))}
    </div>
  );
}
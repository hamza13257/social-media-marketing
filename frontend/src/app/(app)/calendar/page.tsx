"use client";

import { useCallback, useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import type { ContentItem } from "@/types/api";

export default function CalendarPage() {
  const [items, setItems] = useState<ContentItem[]>([]);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      const scheduled = await api.listContents({ status: "scheduled" });
      const approved = await api.listContents({ status: "approved" });
      setItems([...scheduled.data, ...approved.data]);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Load failed.");
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  const byDate = items.reduce<Record<string, ContentItem[]>>((acc, item) => {
    const key = item.scheduled_at
      ? new Date(item.scheduled_at).toLocaleString()
      : "Unscheduled (approved)";
    acc[key] = acc[key] ?? [];
    acc[key].push(item);
    return acc;
  }, {});

  return (
    <div className="mx-auto max-w-4xl">
      <h1 className="text-3xl font-semibold text-white">Calendar</h1>
      <p className="mt-2 text-slate-400">Scheduled and approved content (list view — drag & drop later).</p>
      {error ? <p className="mt-4 text-sm text-rose-400">{error}</p> : null}
      <div className="mt-8 space-y-6">
          {Object.entries(byDate).map(([date, group]) => (
            <section key={date}>
              <h2 className="text-sm font-semibold uppercase tracking-wide text-blue-400">{date}</h2>
              <ul className="mt-2 space-y-2">
                {group.map((item) => (
                  <li
                    key={item.id}
                    className="rounded-lg border border-slate-800 bg-slate-900/40 px-4 py-3 text-sm"
                  >
                    <span className="font-medium text-white">{item.topic}</span>
                    <span className="text-slate-500"> · {item.status}</span>
                  </li>
                ))}
              </ul>
            </section>
          ))}
          {items.length === 0 ? (
            <p className="text-slate-500">No approved or scheduled items yet.</p>
          ) : null}
      </div>
    </div>
  );
}
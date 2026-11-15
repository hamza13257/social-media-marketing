"use client";

import { useCallback, useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import type { ContentItem } from "@/types/api";
import { Button } from "@/components/ui/button";

export default function ApprovalsPage() {
  const [items, setItems] = useState<ContentItem[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await api.listContents({ pending_only: true });
      setItems(res.data);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Load failed.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  async function act(
    id: number,
    action: "approve" | "reject" | "request-changes",
  ) {
    try {
      if (action === "approve") await api.approveContent(id);
      if (action === "reject") await api.rejectContent(id, "Rejected");
      if (action === "request-changes") await api.requestContentChanges(id, "Changes needed");
      load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Action failed.");
    }
  }

  return (
    <div className="mx-auto max-w-4xl">
      <h1 className="text-3xl font-semibold text-white">Approvals</h1>
      <p className="mt-2 text-slate-400">Human review before scheduling or publish.</p>

      {error ? <p className="mt-6 text-sm text-rose-400">{error}</p> : null}

      {loading ? (
        <p className="mt-8 text-slate-500">Loading queue…</p>
      ) : items.length === 0 ? (
        <p className="mt-8 text-slate-500">No content awaiting approval.</p>
      ) : (
        <ul className="mt-8 space-y-4">
          {items.map((item) => (
            <li
              key={item.id}
              className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5"
            >
              <h2 className="text-lg font-semibold text-white">{item.topic}</h2>
              <p className="text-xs text-slate-500">
                {item.brand?.name} · {item.platform}
              </p>
              {item.caption ? (
                <p className="mt-3 whitespace-pre-wrap text-sm text-slate-300">{item.caption}</p>
              ) : null}
              <div className="mt-4 flex flex-wrap gap-2">
                <Button onClick={() => act(item.id, "approve")}>Approve</Button>
                <Button variant="ghost" onClick={() => act(item.id, "request-changes")}>
                  Request changes
                </Button>
                <Button variant="danger" onClick={() => act(item.id, "reject")}>
                  Reject
                </Button>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
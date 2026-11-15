"use client";

import { useCallback, useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { api, ApiError } from "@/lib/api";
import type { ContentItem } from "@/types/api";
import { Button } from "@/components/ui/button";
import { Textarea } from "@/components/ui/textarea";

export default function ContentDetailPage() {
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const id = Number(params.id);
  const [content, setContent] = useState<ContentItem | null>(null);
  const [caption, setCaption] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const res = await api.getContent(id);
      setContent(res.data);
      setCaption(res.data.caption ?? "");
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Not found.");
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    if (!Number.isNaN(id)) load();
  }, [id, load]);

  async function saveCaption() {
    try {
      const res = await api.updateContent(id, { caption });
      setContent(res.data);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Save failed.");
    }
  }

  async function submit() {
    try {
      await api.submitContent(id);
      load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Submit failed.");
    }
  }

  if (loading) return <p className="text-slate-500">Loading…</p>;
  if (!content) return <p className="text-rose-400">{error}</p>;

  return (
    <div className="mx-auto max-w-2xl">
      <button
        type="button"
        className="text-sm text-slate-500 hover:text-white"
        onClick={() => router.push("/content-studio")}
      >
        ← Back
      </button>
      <h1 className="mt-4 text-3xl font-semibold text-white">{content.topic}</h1>
      <p className="mt-1 text-sm text-slate-500">
        Status: <span className="text-blue-300">{content.status}</span> · v
        {content.version}
      </p>

      <div className="mt-8 space-y-4">
        <label className="text-xs text-slate-400">Caption</label>
        <Textarea value={caption} onChange={(e) => setCaption(e.target.value)} rows={8} />
        <div className="flex flex-wrap gap-2">
          <Button onClick={saveCaption}>Save</Button>
          {content.status === "draft" || content.status === "changes_requested" ? (
            <Button variant="ghost" onClick={submit}>
              Submit for approval
            </Button>
          ) : null}
        </div>
      </div>
      {error ? <p className="mt-4 text-sm text-rose-400">{error}</p> : null}
    </div>
  );
}
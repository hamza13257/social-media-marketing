"use client";

import { useCallback, useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { api, ApiError } from "@/lib/api";
import type { ContentItem, PublishingLog, SocialAccount } from "@/types/api";
import { Input } from "@/components/ui/input";
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
  const [logs, setLogs] = useState<PublishingLog[]>([]);
  const [scheduleAt, setScheduleAt] = useState("");
  const [imageUrl, setImageUrl] = useState("");
  const [socialAccounts, setSocialAccounts] = useState<SocialAccount[]>([]);
  const [socialAccountId, setSocialAccountId] = useState<string>("");

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [res, logRes] = await Promise.all([
        api.getContent(id),
        api.listPublishingLogs(id),
      ]);
      setContent(res.data);
      setCaption(res.data.caption ?? "");
      const asset = res.data.media_assets?.find((a) => a.type === "image");
      setImageUrl(asset?.url ?? "");
      setLogs(logRes.data);
      const accounts = await api.listSocialAccounts({ brand_id: res.data.brand_id });
      setSocialAccounts(accounts.data);
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

  async function saveImageUrl() {
    const body = imageUrl.trim()
      ? { media_assets: [{ type: "image", url: imageUrl.trim() }] }
      : { media_assets: [] };
    try {
      const res = await api.updateContent(id, body);
      setContent(res.data);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Image URL save failed.");
    }
  }

  async function publishNow() {
    try {
      const accountId = socialAccountId ? Number(socialAccountId) : undefined;
      await api.publishContent(id, accountId);
      setError(null);
      load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Publish failed.");
    }
  }

  async function schedule() {
    if (!scheduleAt) return;
    try {
      await api.scheduleContent(id, new Date(scheduleAt).toISOString());
      load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Schedule failed.");
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
        {content.external_post_id ? (
          <span className="ml-2 text-slate-600">· Post {content.external_post_id}</span>
        ) : null}
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

      <div className="mt-8 space-y-3 rounded-xl border border-slate-800 bg-slate-900/40 p-4">
        <p className="text-sm font-medium text-white">Media (Instagram)</p>
        <p className="text-xs text-slate-500">
          Public HTTPS image URL required for Instagram. Facebook text posts can skip this.
        </p>
        <Input
          className="mt-1"
          placeholder="https://example.com/image.jpg"
          value={imageUrl}
          onChange={(e) => setImageUrl(e.target.value)}
        />
        <Button variant="ghost" onClick={saveImageUrl}>
          Save image URL
        </Button>
      </div>

      {content.status === "approved" || content.status === "scheduled" ? (
        <div className="mt-8 space-y-3 rounded-xl border border-slate-800 bg-slate-900/40 p-4">
          <p className="text-sm font-medium text-white">Publishing</p>
          <p className="text-xs text-slate-500">
            Run <code className="text-slate-400">php artisan queue:work</code> so jobs complete. Link
            accounts under Social accounts.
          </p>
          {socialAccounts.length > 0 ? (
            <div>
              <label className="text-xs text-slate-400">Target account (optional)</label>
              <select
                className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200"
                value={socialAccountId}
                onChange={(e) => setSocialAccountId(e.target.value)}
              >
                <option value="">Auto (match platform)</option>
                {socialAccounts.map((a) => (
                  <option key={a.id} value={a.id}>
                    {a.platform_label} — {a.name}
                  </option>
                ))}
              </select>
            </div>
          ) : (
            <p className="text-xs text-amber-400">No social accounts for this brand yet.</p>
          )}
          <Button onClick={publishNow}>Publish now (queue)</Button>
          <div className="flex flex-wrap items-end gap-2">
            <div className="min-w-[200px] flex-1">
              <label className="text-xs text-slate-400">Schedule</label>
              <Input
                type="datetime-local"
                className="mt-1"
                value={scheduleAt}
                onChange={(e) => setScheduleAt(e.target.value)}
              />
            </div>
            <Button variant="ghost" onClick={schedule}>
              Save schedule
            </Button>
          </div>
        </div>
      ) : null}

      {logs.length > 0 ? (
        <div className="mt-8">
          <h2 className="text-sm font-semibold text-slate-300">Publishing logs</h2>
          <ul className="mt-2 space-y-2 text-xs text-slate-500">
            {logs.map((log) => (
              <li key={log.id} className="rounded border border-slate-800 px-3 py-2">
                {log.status} · {log.started_at ?? log.finished_at}
                {log.error_message ? ` — ${log.error_message}` : ""}
              </li>
            ))}
          </ul>
        </div>
      ) : null}

      {error ? <p className="mt-4 text-sm text-rose-400">{error}</p> : null}
    </div>
  );
}
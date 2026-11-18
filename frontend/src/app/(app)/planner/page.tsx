"use client";

import Link from "next/link";
import { FormEvent, useCallback, useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import type { Brand, ContentItem, WeekPlanRow } from "@/types/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";

function mondayOfCurrentWeek(): string {
  const d = new Date();
  const day = d.getDay();
  const diff = d.getDate() - day + (day === 0 ? -6 : 1);
  d.setDate(diff);
  return d.toISOString().slice(0, 10);
}

export default function PlannerPage() {
  const [brands, setBrands] = useState<Brand[]>([]);
  const [plans, setPlans] = useState<WeekPlanRow[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [selectedPlanId, setSelectedPlanId] = useState<number | null>(null);
  const [preview, setPreview] = useState<ContentItem[]>([]);
  const [busyPlanId, setBusyPlanId] = useState<number | null>(null);

  const [weekStart, setWeekStart] = useState(mondayOfCurrentWeek());
  const [brandId, setBrandId] = useState("");
  const [brief, setBrief] = useState("");
  const [postsPerDay, setPostsPerDay] = useState("1");
  const [reelsPerWeek, setReelsPerWeek] = useState("1");
  const [storiesPerDay, setStoriesPerDay] = useState("0");
  const [generateCaptions, setGenerateCaptions] = useState(true);

  const load = useCallback(async () => {
    try {
      const [brandRes, planRes] = await Promise.all([api.listBrands(), api.listWeekPlans()]);
      setBrands(brandRes.data);
      setPlans(planRes.data);
      if (!brandId && brandRes.data[0]) {
        setBrandId(String(brandRes.data[0].id));
      }
    } catch (e) {
      setError(e instanceof ApiError ? e.message : "Load failed.");
    }
  }, [brandId]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    if (!selectedPlanId) {
      return;
    }

    const plan = plans.find((p) => p.id === selectedPlanId);
    if (plan?.status === "generating" || plan?.status === "queued") {
      const timer = setInterval(() => {
        load().then(() => refreshPlan(selectedPlanId));
      }, 4000);

      return () => clearInterval(timer);
    }
  }, [selectedPlanId, plans, load]);

  async function refreshPlan(id: number) {
    try {
      const res = await api.getWeekPlan(id);
      setPreview(res.contents);
      setPlans((prev) =>
        prev.map((p) => (p.id === id ? { ...p, ...res.data, status: res.data.status } : p)),
      );
    } catch {
      /* ignore poll errors */
    }
  }

  async function onSubmit(e: FormEvent) {
    e.preventDefault();
    setSaving(true);
    setError(null);
    setMessage(null);
    try {
      const res = await api.createWeekPlan({
        brand_id: Number(brandId),
        week_start: weekStart,
        brief,
        rhythm: {
          posts_per_day: Number(postsPerDay),
          reels_per_week: Number(reelsPerWeek),
          stories_per_day: Number(storiesPerDay),
          platforms: ["instagram", "facebook"],
          generate_captions: generateCaptions,
        },
      });
      setMessage(res.message);
      setSelectedPlanId(res.data.id);
      await load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Save failed.");
    } finally {
      setSaving(false);
    }
  }

  async function runGenerate(id: number) {
    setBusyPlanId(id);
    setError(null);
    try {
      const res = await api.generateWeekPlan(id);
      setMessage(res.message);
      setSelectedPlanId(id);
      await load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Generate failed.");
    } finally {
      setBusyPlanId(null);
    }
  }

  async function runApproveAll(id: number) {
    setBusyPlanId(id);
    setError(null);
    try {
      const res = await api.approveWeekPlanAll(id);
      setMessage(res.message);
      await load();
      await refreshPlan(id);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Approve failed.");
    } finally {
      setBusyPlanId(null);
    }
  }

  return (
    <div className="mx-auto max-w-3xl">
      <h1 className="text-3xl font-semibold text-white">Weekly planner</h1>
      <p className="mt-2 text-sm text-slate-400">
        Save a brief, generate the week with Groq (queue required), review, then approve all. Image API
        comes in Phase 3 — set <code className="text-slate-300">IMAGE_API_KEY</code> when you have a free
        provider.
      </p>

      <form onSubmit={onSubmit} className="mt-8 space-y-4 rounded-2xl border border-slate-800 bg-slate-900/40 p-6">
        <div>
          <label className="text-xs text-slate-400">Brand</label>
          <select
            className="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200"
            value={brandId}
            onChange={(e) => setBrandId(e.target.value)}
            required
          >
            {brands.map((b) => (
              <option key={b.id} value={b.id}>
                {b.name}
              </option>
            ))}
          </select>
        </div>
        <div>
          <label className="text-xs text-slate-400">Week starting (Monday)</label>
          <Input className="mt-1" type="date" value={weekStart} onChange={(e) => setWeekStart(e.target.value)} required />
        </div>
        <div>
          <label className="text-xs text-slate-400">Brief</label>
          <Textarea
            className="mt-1"
            rows={5}
            value={brief}
            onChange={(e) => setBrief(e.target.value)}
            placeholder="Promo week, product launch, educational tips…"
            required
          />
        </div>
        <div className="grid gap-3 sm:grid-cols-3">
          <div>
            <label className="text-xs text-slate-400">Posts / day</label>
            <Input className="mt-1" type="number" min={0} value={postsPerDay} onChange={(e) => setPostsPerDay(e.target.value)} />
          </div>
          <div>
            <label className="text-xs text-slate-400">Reels / week</label>
            <Input className="mt-1" type="number" min={0} value={reelsPerWeek} onChange={(e) => setReelsPerWeek(e.target.value)} />
          </div>
          <div>
            <label className="text-xs text-slate-400">Stories / day</label>
            <Input className="mt-1" type="number" min={0} value={storiesPerDay} onChange={(e) => setStoriesPerDay(e.target.value)} />
          </div>
        </div>
        <label className="flex items-center gap-2 text-sm text-slate-300">
          <input
            type="checkbox"
            checked={generateCaptions}
            onChange={(e) => setGenerateCaptions(e.target.checked)}
          />
          Generate AI captions for each slot (uses Groq quota)
        </label>
        <Button type="submit" disabled={saving}>
          {saving ? "Saving…" : "Save week plan"}
        </Button>
        {message ? <p className="text-sm text-emerald-400">{message}</p> : null}
        {error ? <p className="text-sm text-rose-400">{error}</p> : null}
      </form>

      {plans.length > 0 ? (
        <div className="mt-10 space-y-4">
          <h2 className="text-sm font-semibold text-slate-300">Your week plans</h2>
          {plans.map((p) => (
            <article key={p.id} className="rounded-lg border border-slate-800 bg-slate-950/50 p-4">
              <div className="flex flex-wrap items-start justify-between gap-2">
                <div>
                  <p className="font-medium text-white">
                    {p.week_start} · {p.brand?.name ?? "Brand"}
                  </p>
                  <p className="text-xs text-slate-500">
                    {p.status}
                    {typeof p.contents_count === "number" ? ` · ${p.contents_count} posts` : ""}
                  </p>
                  {p.error_message ? <p className="mt-1 text-xs text-rose-400">{p.error_message}</p> : null}
                </div>
                <div className="flex flex-wrap gap-2">
                  <Button
                    variant="ghost"
                    disabled={busyPlanId === p.id || p.status === "generating" || p.status === "queued"}
                    onClick={() => runGenerate(p.id)}
                  >
                    Generate week
                  </Button>
                  <Button
                    variant="ghost"
                    disabled={busyPlanId === p.id}
                    onClick={() => {
                      setSelectedPlanId(p.id);
                      refreshPlan(p.id);
                    }}
                  >
                    Preview
                  </Button>
                  <Button
                    disabled={busyPlanId === p.id || p.status !== "ready_for_review"}
                    onClick={() => runApproveAll(p.id)}
                  >
                    Approve all & schedule
                  </Button>
                </div>
              </div>
            </article>
          ))}
        </div>
      ) : null}

      {preview.length > 0 ? (
        <div className="mt-8">
          <h2 className="text-sm font-semibold text-slate-300">Generated slots</h2>
          <ul className="mt-3 space-y-2 text-sm">
            {preview.map((c) => (
              <li key={c.id} className="rounded border border-slate-800 px-3 py-2 text-slate-300">
                <p className="text-white">{c.topic}</p>
                <p className="text-xs text-slate-500">
                  {c.scheduled_at} · {c.platform} · {c.format} · {c.status}
                </p>
                <Link href={`/content-studio/${c.id}`} className="text-xs text-blue-300 hover:text-blue-200">
                  Open in studio
                </Link>
              </li>
            ))}
          </ul>
          <p className="mt-3 text-xs text-slate-500">
            Run <code className="text-slate-400">php artisan queue:work</code> while generating.
          </p>
        </div>
      ) : null}
    </div>
  );
}
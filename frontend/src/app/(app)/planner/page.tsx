"use client";

import { FormEvent, useCallback, useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import type { Brand, WeekPlanRow } from "@/types/api";
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
      load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Save failed.");
    } finally {
      setSaving(false);
    }
  }

  return (
    <div className="mx-auto max-w-3xl">
      <h1 className="text-3xl font-semibold text-white">Weekly planner</h1>
      <p className="mt-2 text-sm text-slate-400">
        Describe your week in a few lines. Caps follow your subscription (see Dashboard). Phase 2 will
        auto-generate drafts, times, and captions for your approval — images still require your media or a
        future image API.
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
          <label className="text-xs text-slate-400">Brief (themes, promos, tone)</label>
          <Textarea
            className="mt-1"
            rows={5}
            value={brief}
            onChange={(e) => setBrief(e.target.value)}
            placeholder="E.g. Black Friday teasers Mon–Wed, behind-the-scenes reel Friday, 2 stories daily with polls…"
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
          Generate captions when automation is enabled (planned)
        </label>
        <Button type="submit" disabled={saving}>
          {saving ? "Saving…" : "Save week plan"}
        </Button>
        {message ? <p className="text-sm text-emerald-400">{message}</p> : null}
        {error ? <p className="text-sm text-rose-400">{error}</p> : null}
      </form>

      {plans.length > 0 ? (
        <div className="mt-10">
          <h2 className="text-sm font-semibold text-slate-300">Recent plans</h2>
          <ul className="mt-3 space-y-2 text-sm text-slate-400">
            {plans.map((p) => (
              <li key={p.id} className="rounded-lg border border-slate-800 px-3 py-2">
                {p.week_start} · {p.brand?.name ?? "Brand"} · {p.status}
              </li>
            ))}
          </ul>
        </div>
      ) : null}
    </div>
  );
}
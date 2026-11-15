"use client";

import Link from "next/link";
import { FormEvent, useCallback, useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import type { Brand, ContentItem } from "@/types/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

export default function ContentStudioPage() {
  const [items, setItems] = useState<ContentItem[]>([]);
  const [brands, setBrands] = useState<Brand[]>([]);
  const [aiConfigured, setAiConfigured] = useState(false);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [showForm, setShowForm] = useState(false);
  const [saving, setSaving] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    try {
      const [contents, brandList] = await Promise.all([
        api.listContents(),
        api.listBrands(),
      ]);
      setItems(contents.data);
      setAiConfigured(contents.meta.ai_configured);
      setBrands(brandList.data);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Load failed.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  async function onCreate(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setSaving(true);
    setError(null);
    const form = new FormData(event.currentTarget);
    try {
      await api.createContent({
        brand_id: Number(form.get("brand_id")),
        topic: String(form.get("topic")),
        objective: String(form.get("objective") || ""),
        platform: form.get("platform"),
        format: form.get("format"),
      });
      setShowForm(false);
      load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Create failed.");
    } finally {
      setSaving(false);
    }
  }

  async function runGenerate(id: number) {
    try {
      await api.generateCaption(id);
      load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "AI generation failed.");
    }
  }

  return (
    <div className="mx-auto max-w-5xl">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-semibold text-white">Content studio</h1>
          <p className="mt-2 text-slate-400">Create drafts and generate captions with AI.</p>
        </div>
        <Button onClick={() => setShowForm((v) => !v)}>
          {showForm ? "Close" : "New content"}
        </Button>
      </div>

      {!aiConfigured ? (
        <p className="mt-4 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
          Set <code className="text-amber-100">AI_API_KEY</code> in backend{" "}
          <code className="text-amber-100">.env</code> to enable caption generation
          (OpenAI-compatible API).
        </p>
      ) : null}

      {error ? <p className="mt-4 text-sm text-rose-400">{error}</p> : null}

      {showForm ? (
        <form
          onSubmit={onCreate}
          className="mt-6 space-y-4 rounded-2xl border border-slate-800 bg-slate-900/50 p-6"
        >
          <MotionCapField label="Brand">
            <select
              name="brand_id"
              required
              className="w-full rounded-lg border border-slate-700 bg-slate-900 px-2 py-2 text-sm text-slate-200"
            >
              {brands.map((b) => (
                <option key={b.id} value={b.id}>
                  {b.name}
                </option>
              ))}
            </select>
          </MotionCapField>
          <MotionCapField label="Topic">
            <Input name="topic" required />
          </MotionCapField>
          <MotionCapField label="Objective">
            <Input name="objective" />
          </MotionCapField>
          <div className="grid gap-4 md:grid-cols-2">
            <MotionCapField label="Platform">
              <select
                name="platform"
                className="w-full rounded-lg border border-slate-700 bg-slate-900 px-2 py-2 text-sm text-slate-200"
              >
                <option value="instagram">Instagram</option>
                <option value="facebook">Facebook</option>
              </select>
            </MotionCapField>
            <MotionCapField label="Format">
              <select
                name="format"
                className="w-full rounded-lg border border-slate-700 bg-slate-900 px-2 py-2 text-sm text-slate-200"
              >
                <option value="single_image">Single image</option>
                <option value="carousel">Carousel</option>
                <option value="reel">Reel</option>
                <option value="story">Story</option>
              </select>
            </MotionCapField>
          </div>
          <Button type="submit" loading={saving}>
            Create draft
          </Button>
        </form>
      ) : null}

      {loading ? (
        <p className="mt-8 text-slate-500">Loading…</p>
      ) : (
        <ul className="mt-8 space-y-3">
          {items.map((item) => (
            <li
              key={item.id}
              className="rounded-xl border border-slate-800 bg-slate-900/40 p-4"
            >
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                  <Link
                    href={`/content-studio/${item.id}`}
                    className="font-semibold text-white hover:text-blue-300"
                  >
                    {item.topic}
                  </Link>
                  <p className="text-xs text-slate-500">
                    {item.brand?.name} · {item.platform} · {item.status}
                  </p>
                </div>
                {item.status === "draft" && aiConfigured ? (
                  <Button variant="ghost" onClick={() => runGenerate(item.id)}>
                    Generate AI caption
                  </Button>
                ) : null}
              </div>
              {item.caption ? (
                <p className="mt-2 line-clamp-2 text-sm text-slate-400">{item.caption}</p>
              ) : null}
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}

function MotionCapField({
  label,
  children,
}: {
  label: string;
  children: React.ReactNode;
}) {
  return (
    <div>
      <label className="text-xs text-slate-400">{label}</label>
      <div className="mt-1">{children}</div>
    </div>
  );
}

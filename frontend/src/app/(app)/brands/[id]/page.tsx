"use client";

import { useCallback, useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { api, ApiError } from "@/lib/api";
import type { Brand } from "@/types/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";

export default function BrandDetailPage() {
  const params = useParams<{ id: string }>();
  const router = useRouter();
  const brandId = Number(params.id);
  const [brand, setBrand] = useState<Brand | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [saving, setSaving] = useState(false);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
  setError(null);
    try {
      const response = await api.getBrand(brandId);
      setBrand(response.data);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Brand not found.");
    } finally {
      setLoading(false);
    }
  }, [brandId]);

  useEffect(() => {
    if (!Number.isNaN(brandId)) {
      load();
    }
  }, [brandId, load]);

  async function onSave(event: React.FormEvent<HTMLFormElement>) {
    event.preventDefault();
    if (!brand) return;
    setSaving(true);
    setError(null);
    const form = new FormData(event.currentTarget);
    const colorsRaw = String(form.get("brand_colors") ?? "");
    const brand_colors = colorsRaw
      .split(",")
      .map((c) => c.trim())
      .filter(Boolean);

    try {
      const response = await api.updateBrand(brand.id, {
        name: String(form.get("name")),
        description: String(form.get("description") || ""),
        website_url: String(form.get("website_url") || "") || null,
        tone_of_voice: String(form.get("tone_of_voice") || "") || null,
        target_audience: String(form.get("target_audience") || "") || null,
        brand_colors,
      });
      setBrand(response.data);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Save failed.");
    } finally {
      setSaving(false);
    }
  }

  if (loading) {
    return <p className="text-slate-500">Loading brand…</p>;
  }

  if (!brand) {
    return <p className="text-rose-400">{error ?? "Brand unavailable."}</p>;
  }

  return (
    <div className="mx-auto max-w-2xl">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h1 className="text-3xl font-semibold text-white">{brand.name}</h1>
          <p className="mt-1 text-sm text-slate-500">Slug: {brand.slug}</p>
        </div>
        <Button
          variant="ghost"
          onClick={async () => {
            await api.deleteBrand(brand.id);
            router.push("/brands");
          }}
        >
          Delete
        </Button>
      </div>

      <form className="mt-8 space-y-4" onSubmit={onSave}>
        <div>
          <label className="text-xs text-slate-400">Name</label>
          <Input name="name" defaultValue={brand.name} className="mt-1" required />
        </div>
        <div>
          <label className="text-xs text-slate-400">Description</label>
          <Textarea
            name="description"
            defaultValue={brand.description ?? ""}
            className="mt-1"
          />
        </div>
        <div>
          <label className="text-xs text-slate-400">Website</label>
          <Input
            name="website_url"
            type="url"
            defaultValue={brand.website_url ?? ""}
            className="mt-1"
          />
        </div>
        <div>
          <label className="text-xs text-slate-400">Target audience</label>
          <Textarea
            name="target_audience"
            defaultValue={brand.target_audience ?? ""}
            className="mt-1"
            rows={3}
          />
        </div>
        <div>
          <label className="text-xs text-slate-400">Tone of voice</label>
          <Input
            name="tone_of_voice"
            defaultValue={brand.tone_of_voice ?? ""}
            className="mt-1"
          />
        </div>
        <div>
          <label className="text-xs text-slate-400">Brand colors</label>
          <Input
            name="brand_colors"
            defaultValue={brand.brand_colors?.join(", ") ?? ""}
            className="mt-1"
          />
        </div>
        {error ? <p className="text-sm text-rose-400">{error}</p> : null}
        <Button type="submit" loading={saving}>
          Save changes
        </Button>
      </form>
    </div>
  );
}
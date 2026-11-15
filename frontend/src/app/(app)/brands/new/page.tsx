"use client";

import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";
import { api, ApiError } from "@/lib/api";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";

export default function NewBrandPage() {
  const router = useRouter();
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setLoading(true);
    const form = new FormData(event.currentTarget);
    const colorsRaw = String(form.get("brand_colors") ?? "");
    const brand_colors = colorsRaw
      .split(",")
      .map((c) => c.trim())
      .filter(Boolean);

    try {
      const response = await api.createBrand({
        name: String(form.get("name")),
        description: String(form.get("description") || ""),
        website_url: String(form.get("website_url") || "") || null,
        tone_of_voice: String(form.get("tone_of_voice") || "") || null,
        target_audience: String(form.get("target_audience") || "") || null,
        brand_colors: brand_colors.length ? brand_colors : undefined,
      });
      router.push(`/brands/${response.data.id}`);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Could not create brand.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="mx-auto max-w-2xl">
      <h1 className="text-3xl font-semibold text-white">New brand</h1>
      <p className="mt-2 text-slate-400">
        Creates a tenant-scoped brand via the Laravel API.
      </p>
      <form className="mt-8 space-y-4" onSubmit={onSubmit}>
        <Field label="Brand name">
          <Input name="name" required />
        </Field>
        <Field label="Description">
          <Textarea name="description" />
        </Field>
        <Field label="Website URL">
          <Input name="website_url" type="url" placeholder="https://" />
        </Field>
        <Field label="Target audience">
          <Textarea name="target_audience" rows={3} />
        </Field>
        <Field label="Tone of voice">
          <Input name="tone_of_voice" placeholder="Professional, bold, educational…" />
        </Field>
        <Field label="Brand colors (comma-separated hex)">
          <Input name="brand_colors" placeholder="#1e3a8a, #3b82f6, #93c5fd" />
        </Field>
        {error ? <p className="text-sm text-rose-400">{error}</p> : null}
        <Button type="submit" loading={loading}>
          Create brand
        </Button>
      </form>
    </div>
  );
}

function Field({
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
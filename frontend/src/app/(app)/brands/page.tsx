"use client";

import Link from "next/link";
import { useCallback, useEffect, useState } from "react";
import { api, ApiError } from "@/lib/api";
import type { Brand } from "@/types/api";
import { Button } from "@/components/ui/button";

export default function BrandsPage() {
  const [brands, setBrands] = useState<Brand[]>([]);
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await api.listBrands();
      setBrands(response.data);
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Could not load brands.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  return (
    <div className="mx-auto max-w-5xl">
      <div className="flex flex-wrap items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-semibold text-white">Brands</h1>
          <p className="mt-2 text-slate-400">
            Manage marketing identities for your organization.
          </p>
        </div>
        <Link href="/brands/new">
          <Button>New brand</Button>
        </Link>
      </div>

      {error ? <p className="mt-6 text-sm text-rose-400">{error}</p> : null}

      {loading ? (
        <p className="mt-8 text-slate-500">Loading brands…</p>
      ) : brands.length === 0 ? (
        <div className="mt-8 rounded-2xl border border-dashed border-slate-700 p-10 text-center text-slate-400">
          No brands yet. Create your first brand to get started.
        </div>
      ) : (
        <BrandList brands={brands} onArchived={load} />
      )}
    </div>
  );
}

function BrandList({
  brands,
  onArchived,
}: {
  brands: Brand[];
  onArchived: () => void;
}) {
  return (
    <ul className="mt-8 grid gap-4 md:grid-cols-2">
      {brands.map((brand) => (
        <li
          key={brand.id}
          className="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 transition hover:border-blue-700/50"
        >
          <div className="flex items-start justify-between gap-3">
            <div>
              <Link
                href={`/brands/${brand.id}`}
                className="text-lg font-semibold text-white hover:text-blue-300"
              >
                {brand.name}
              </Link>
              <p className="mt-1 text-xs text-slate-500">/{brand.slug}</p>
            </div>
            {brand.archived_at ? (
              <span className="rounded-full bg-slate-800 px-2 py-1 text-xs text-slate-400">
                Archived
              </span>
            ) : null}
          </div>
          {brand.description ? (
            <p className="mt-3 line-clamp-2 text-sm text-slate-400">
              {brand.description}
            </p>
          ) : null}
          {brand.brand_colors?.length ? (
            <div className="mt-4 flex gap-2">
              {brand.brand_colors.map((color) => (
                <span
                  key={color}
                  className="h-6 w-6 rounded-full border border-slate-700"
                  style={{ backgroundColor: color }}
                  title={color}
                />
              ))}
            </div>
          ) : null}
          {!brand.archived_at ? (
            <button
              type="button"
              className="mt-4 text-xs text-slate-500 hover:text-rose-400"
              onClick={async () => {
                await api.updateBrand(brand.id, { archived: true });
                onArchived();
              }}
            >
              Archive
            </button>
          ) : null}
        </li>
      ))}
    </ul>
  );
}
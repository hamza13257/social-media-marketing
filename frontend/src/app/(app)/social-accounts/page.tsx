"use client";

import { useCallback, useEffect, useState } from "react";
import { useSearchParams } from "next/navigation";
import { api, ApiError } from "@/lib/api";
import type { Brand, SocialAccount } from "@/types/api";
import { Button } from "@/components/ui/button";

export default function SocialAccountsPage() {
  const searchParams = useSearchParams();
  const [accounts, setAccounts] = useState<SocialAccount[]>([]);
  const [brands, setBrands] = useState<Brand[]>([]);
  const [metaConfigured, setMetaConfigured] = useState(true);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [connecting, setConnecting] = useState(false);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const [accountsRes, brandsRes] = await Promise.all([
        api.listSocialAccounts(),
        api.listBrands(),
      ]);
      setAccounts(accountsRes.data);
      setMetaConfigured(accountsRes.meta.meta_configured);
      setBrands(brandsRes.data);
    } catch (err) {
      setError(
        err instanceof ApiError ? err.message : "Could not load social accounts.",
      );
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    const oauthError = searchParams.get("error");
    const connected = searchParams.get("connected");
    if (oauthError) {
      setNotice(null);
      setError(decodeURIComponent(oauthError));
    } else if (connected) {
      setError(null);
      setNotice(`Connected ${connected} account(s) from Meta.`);
      load();
    }
  }, [searchParams, load]);

  async function connectMeta() {
    setConnecting(true);
    setError(null);
    try {
      const { authorize_url } = await api.metaOAuthUrl();
      window.location.href = authorize_url;
    } catch (err) {
      setConnecting(false);
      setError(err instanceof ApiError ? err.message : "Could not start Meta OAuth.");
    }
  }

  async function assignBrand(accountId: number, brandId: string) {
    try {
      await api.updateSocialAccount(accountId, {
        brand_id: brandId ? Number(brandId) : null,
      });
      load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Update failed.");
    }
  }

  async function disconnect(id: number) {
    try {
      await api.disconnectSocialAccount(id);
      load();
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Disconnect failed.");
    }
  }

  return (
    <div className="mx-auto max-w-5xl">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 className="text-3xl font-semibold text-white">Social accounts</h1>
          <p className="mt-2 text-slate-400">
            Connect Instagram Professional and Facebook Pages via official Meta
            OAuth.
          </p>
        </div>
        <Button onClick={connectMeta} loading={connecting} disabled={!metaConfigured}>
          Connect Meta
        </Button>
      </div>

      {!metaConfigured ? (
        <p className="mt-6 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-200">
          Meta OAuth is not configured on the API server. Set{" "}
          <code className="text-amber-100">META_APP_ID</code> and{" "}
          <code className="text-amber-100">META_APP_SECRET</code> in{" "}
          <code className="text-amber-100">backend/.env</code>, then add the redirect
          URI in the Meta developer console:{" "}
          <code className="text-amber-100">
            http://127.0.0.1:8000/api/meta/oauth/callback
          </code>
        </p>
      ) : null}

      {notice ? <p className="mt-6 text-sm text-blue-300">{notice}</p> : null}
      {error ? <p className="mt-6 text-sm text-rose-400">{error}</p> : null}

      {loading ? (
        <p className="mt-8 text-slate-500">Loading accounts…</p>
      ) : accounts.length === 0 ? (
        <div className="mt-8 rounded-2xl border border-dashed border-slate-700 p-10 text-center text-slate-400">
          No social accounts connected yet.
        </div>
      ) : (
        <ul className="mt-8 space-y-4">
          {accounts.map((account) => (
            <li
              key={account.id}
              className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-800 bg-slate-900/50 p-5"
            >
              <div className="flex items-center gap-4">
                {account.profile_picture_url ? (
                  // eslint-disable-next-line @next/next/no-img-element
                  <img
                    src={account.profile_picture_url}
                    alt=""
                    className="h-12 w-12 rounded-full border border-slate-700 object-cover"
                  />
                ) : (
                  <div className="flex h-12 w-12 items-center justify-center rounded-full border border-slate-700 bg-slate-800 text-xs text-slate-400">
                    {account.platform === "instagram_professional" ? "IG" : "FB"}
                  </div>
                )}
                <div>
                  <p className="font-semibold text-white">{account.name}</p>
                  <p className="text-xs text-slate-500">
                    {account.platform_label}
                    {account.username ? ` · @${account.username}` : ""}
                  </p>
                  <p className="text-xs text-slate-600">
                    Status: {account.connection_status}
                    {account.last_error ? ` — ${account.last_error}` : ""}
                  </p>
                </div>
              </div>

              <div className="flex flex-wrap items-center gap-3">
                <select
                  className="rounded-lg border border-slate-700 bg-slate-900 px-2 py-2 text-sm text-slate-200"
                  value={account.brand_id ?? ""}
                  onChange={(e) => assignBrand(account.id, e.target.value)}
                  aria-label="Assign brand"
                >
                  <option value="">No brand</option>
                  {brands.map((brand) => (
                    <option key={brand.id} value={brand.id}>
                      {brand.name}
                    </option>
                  ))}
                </select>
                <Button variant="ghost" onClick={() => disconnect(account.id)}>
                  Disconnect
                </Button>
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
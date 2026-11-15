"use client";

import { useState } from "react";
import { api, ApiError } from "@/lib/api";
import { getApiBaseUrl } from "@/lib/session";

type Props = {
  className?: string;
  label?: string;
};

export function GoogleSignInButton({ className = "", label = "Continue with Google" }: Props) {
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function start() {
    setError(null);
    setLoading(true);
    try {
      const { url } = await api.googleAuthUrl();
      window.location.href = url;
    } catch (err) {
      const fallback = `${getApiBaseUrl()}/api/auth/google/callback`;
      setError(
        err instanceof ApiError
          ? err.message
          : `Google sign-in unavailable. Check GOOGLE_* in backend .env and redirect URI ${fallback}`,
      );
      setLoading(false);
    }
  }

  return (
    <div className={className}>
      <button
        type="button"
        disabled={loading}
        onClick={start}
        className="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-600 bg-white px-4 py-2.5 text-sm font-medium text-slate-900 hover:bg-slate-100 disabled:opacity-50"
      >
        <span className="text-lg">G</span>
        {loading ? "Redirecting…" : label}
      </button>
      {error ? <p className="mt-2 text-xs text-rose-400">{error}</p> : null}
    </div>
  );
}
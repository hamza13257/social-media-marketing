"use client";

import { useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { persistSession } from "@/lib/session";
import { useAuth } from "@/context/auth-context";

export function GoogleCallbackInner() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { refresh } = useAuth();
  const [message, setMessage] = useState("Completing Google sign-in…");

  useEffect(() => {
    const token = searchParams.get("token");
    const tenantId = searchParams.get("tenant_id");
    if (!token) {
      setMessage("Sign-in failed. Return to login and try again.");
      return;
    }
    persistSession(token, tenantId ?? "");
    refresh()
      .then(() => router.replace("/dashboard"))
      .catch(() => {
        setMessage("Session saved but profile could not be loaded.");
      });
  }, [searchParams, refresh, router]);

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-950 px-4">
      <p className="text-sm text-slate-400">{message}</p>
    </div>
  );
}
import { Suspense } from "react";
import { GoogleCallbackInner } from "./google-callback-inner";

export const instant = false;

export default function GoogleCallbackPage() {
  return (
    <Suspense
      fallback={
        <div className="flex min-h-screen items-center justify-center bg-slate-950 px-4">
          <p className="text-sm text-slate-400">Completing Google sign-in…</p>
        </div>
      }
    >
      <GoogleCallbackInner />
    </Suspense>
  );
}
import { Suspense } from "react";

export default function SocialAccountsLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <Suspense fallback={<p className="text-slate-500">Loading…</p>}>
      {children}
    </Suspense>
  );
}
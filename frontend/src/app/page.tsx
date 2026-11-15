import Link from "next/link";

export default function HomePage() {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-slate-950 px-6 text-center">
      <p className="text-sm font-semibold uppercase tracking-widest text-blue-400">
        SocialPilot Marketing
      </p>
      <h1 className="mt-4 max-w-2xl text-4xl font-semibold text-white md:text-5xl">
        Centralize brands, content, and publishing in one premium workspace.
      </h1>
      <p className="mt-4 max-w-xl text-slate-400">
        Phase 1 is live: multi-tenant auth and brand management wired to your
        Laravel API.
      </p>
      <div className="mt-8 flex flex-wrap justify-center gap-3">
        <Link
          href="/login"
          className="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-500"
        >
          Sign in
        </Link>
        <Link
          href="/register"
          className="rounded-lg border border-slate-700 px-5 py-2.5 text-sm font-medium text-slate-200 hover:bg-slate-900"
        >
          Create account
        </Link>
      </div>
    </div>
  );
}
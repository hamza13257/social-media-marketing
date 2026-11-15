"use client";

import Link from "next/link";
import { useEffect, useState } from "react";
import { api } from "@/lib/api";
import type { PublicBootstrap, SubscriptionPlan } from "@/types/api";
import { GoogleSignInButton } from "@/components/auth/google-sign-in-button";

export function LandingPage() {
  const [bootstrap, setBootstrap] = useState<PublicBootstrap | null>(null);

  useEffect(() => {
    api.publicBootstrap().then((res) => setBootstrap(res.data)).catch(() => null);
  }, []);

  const plans = bootstrap?.plans ?? [];

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100">
      <header className="border-b border-slate-800/80 bg-slate-950/90">
        <div className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
          <div>
            <p className="text-xs font-semibold uppercase tracking-widest text-blue-400">
              SocialPilot
            </p>
            <p className="text-sm text-slate-400">Marketing platform</p>
          </div>
          <nav className="flex items-center gap-3">
            <Link href="/login" className="text-sm text-slate-300 hover:text-white">
              Sign in
            </Link>
            <Link
              href="/register"
              className="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500"
            >
              Start free
            </Link>
          </nav>
        </div>
      </header>

      <section className="mx-auto max-w-6xl px-6 py-20 text-center">
        <p className="text-sm font-medium text-blue-300">Built for agencies & brand owners</p>
        <h1 className="mt-4 text-4xl font-semibold tracking-tight text-white md:text-6xl">
          Publish smarter on Meta with AI, approvals, and billing you control.
        </h1>
        <p className="mx-auto mt-6 max-w-2xl text-lg text-slate-400">
          {bootstrap?.tagline ??
            "One workspace for brands, content, scheduling, and Instagram & Facebook publishing."}
        </p>
        <div className="mt-10 flex flex-wrap justify-center gap-3">
          <Link
            href="/register"
            className="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-900/40 hover:bg-blue-500"
          >
            Create client account
          </Link>
          <Link
            href="/login"
            className="rounded-xl border border-slate-700 px-6 py-3 text-sm font-semibold text-slate-200 hover:bg-slate-900"
          >
            Client login
          </Link>
          {bootstrap?.google_auth_enabled ? (
            <GoogleSignInButton className="rounded-xl" />
          ) : null}
        </div>
      </section>

      <section className="border-y border-slate-800 bg-slate-900/30 py-16">
        <div className="mx-auto grid max-w-6xl gap-8 px-6 md:grid-cols-3">
          {(bootstrap?.features ?? defaultFeatures).map((feature) => (
            <div
              key={feature}
              className="rounded-2xl border border-slate-800 bg-slate-950/60 p-6 text-left"
            >
              <h3 className="font-medium text-white">{feature}</h3>
              <p className="mt-2 text-sm text-slate-500">
                Included in the MVP — ready for your Meta and Groq API keys.
              </p>
            </div>
          ))}
        </div>
      </section>

      <section id="pricing" className="mx-auto max-w-6xl px-6 py-20">
        <h2 className="text-center text-2xl font-semibold text-white">Pricing</h2>
        <p className="mt-2 text-center text-sm text-slate-500">
          Manual payment today — Stripe when you flip `PAYMENT_GATEWAY`.
        </p>
        <div className="mt-10 grid gap-6 md:grid-cols-3">
          {plans.length > 0
            ? plans.map((plan) => <PricingCard key={plan.id} plan={plan} />)
            : placeholderPlans.map((plan) => (
                <PricingCard key={plan.slug} plan={plan} placeholder />
              ))}
        </div>
      </section>

      <footer className="border-t border-slate-800 py-8 text-center text-xs text-slate-500">
        © 2026 {bootstrap?.app_name ?? "SocialPilot Marketing"} — Platform admin at{" "}
        <Link href="/admin" className="text-violet-400 hover:text-violet-300">
          /admin
        </Link>{" "}
        (super-admin only).
      </footer>
    </div>
  );
}

const defaultFeatures = [
  "Multi-brand workspace",
  "Meta Facebook & Instagram",
  "AI captions (Groq)",
];

function PricingCard({
  plan,
  placeholder,
}: {
  plan: SubscriptionPlan;
  placeholder?: boolean;
}) {
  return (
    <article className="flex flex-col rounded-2xl border border-slate-800 bg-slate-900/50 p-6">
      <h3 className="text-lg font-semibold text-white">{plan.name}</h3>
      <p className="mt-2 min-h-[3rem] text-sm text-slate-500">{plan.description}</p>
      <p className="mt-6 text-3xl font-semibold text-blue-300">
        ${(plan.price_cents / 100).toFixed(0)}
        <span className="text-sm font-normal text-slate-500">/mo</span>
      </p>
      <Link
        href="/register"
        className={`mt-8 block rounded-lg py-2.5 text-center text-sm font-medium ${
          placeholder
            ? "border border-slate-700 text-slate-400"
            : "bg-blue-600 text-white hover:bg-blue-500"
        }`}
      >
        Get started
      </Link>
    </article>
  );
}

const placeholderPlans: SubscriptionPlan[] = [
  {
    id: 0,
    slug: "starter",
    name: "Starter",
    description: "Personal brands",
    price_cents: 2900,
    currency: "USD",
  },
  {
    id: 0,
    slug: "pro",
    name: "Pro",
    description: "Growing teams",
    price_cents: 7900,
    currency: "USD",
  },
  {
    id: 0,
    slug: "agency",
    name: "Agency",
    description: "Higher limits",
    price_cents: 19900,
    currency: "USD",
  },
];
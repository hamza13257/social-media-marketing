"use client";

import Link from "next/link";
import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";
import { api, ApiError } from "@/lib/api";
import { persistSession } from "@/lib/session";
import { useAuth } from "@/context/auth-context";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

export default function RegisterPage() {
  const router = useRouter();
  const { refresh } = useAuth();
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function onSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setError(null);
    setLoading(true);
    const form = new FormData(event.currentTarget);
    try {
      const response = await api.register({
        name: String(form.get("name")),
        email: String(form.get("email")),
        password: String(form.get("password")),
        password_confirmation: String(form.get("password_confirmation")),
        organization_name: String(form.get("organization_name")),
      });
      if (response.tenant) {
        persistSession(response.token, response.tenant.id);
      }
      await refresh();
      router.push("/dashboard");
    } catch (err) {
      setError(err instanceof ApiError ? err.message : "Registration failed.");
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-950 px-4 py-10">
      <div className="w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900/60 p-8 shadow-xl shadow-blue-950/20">
        <h1 className="text-2xl font-semibold text-white">Create account</h1>
        <p className="mt-2 text-sm text-slate-400">
          Start with your first organization and brand workspace.
        </p>
        <form className="mt-8 space-y-4" onSubmit={onSubmit}>
          <div>
            <label className="text-xs text-slate-400">Your name</label>
            <Input name="name" required className="mt-1" />
          </div>
          <div>
            <label className="text-xs text-slate-400">Organization</label>
            <Input name="organization_name" required className="mt-1" />
          </div>
          <div>
            <label className="text-xs text-slate-400">Email</label>
            <Input name="email" type="email" required className="mt-1" />
          </div>
          <div>
            <label className="text-xs text-slate-400">Password</label>
            <Input name="password" type="password" required className="mt-1" />
          </div>
          <div>
            <label className="text-xs text-slate-400">Confirm password</label>
            <Input
              name="password_confirmation"
              type="password"
              required
              className="mt-1"
            />
          </div>
          {error ? <p className="text-sm text-rose-400">{error}</p> : null}
          <Button type="submit" className="w-full" loading={loading}>
            Register
          </Button>
        </form>
        <p className="mt-6 text-center text-sm text-slate-500">
          Already registered?{" "}
          <Link href="/login" className="text-blue-400 hover:text-blue-300">
            Sign in
          </Link>
        </p>
      </div>
    </div>
  );
}
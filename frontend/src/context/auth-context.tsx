"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
} from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";
import {
  clearSession,
  getTenantId,
  getToken,
  persistSession,
} from "@/lib/session";
import type { Tenant, User } from "@/types/api";

type AuthContextValue = {
  user: User | null;
  tenants: Tenant[];
  activeTenantId: string | null;
  loading: boolean;
  setActiveTenantId: (id: string) => void;
  refresh: () => Promise<void>;
  signOut: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [tenants, setTenants] = useState<Tenant[]>([]);
  const [activeTenantId, setActiveTenantIdState] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);

  const refresh = useCallback(async () => {
    const token = getToken();
    if (!token) {
      setUser(null);
      setTenants([]);
      setActiveTenantIdState(null);
      setLoading(false);
      return;
    }

    const me = await api.me();
    setUser(me.user);
    setTenants(me.tenants);

    const stored = getTenantId();
    const valid =
      stored && me.tenants.some((t) => String(t.id) === stored)
        ? stored
        : me.tenants[0]
          ? String(me.tenants[0].id)
          : null;

    if (valid) {
      persistSession(token, valid);
    }
    setActiveTenantIdState(valid);
    setLoading(false);
  }, []);

  useEffect(() => {
    refresh().catch(() => {
      clearSession();
      setLoading(false);
    });
  }, [refresh]);

  const setActiveTenantId = useCallback((id: string) => {
    const token = getToken();
    if (token) {
      persistSession(token, id);
    }
    setActiveTenantIdState(id);
  }, []);

  const signOut = useCallback(async () => {
    try {
      await api.logout();
    } catch {
      /* ignore */
    }
    clearSession();
    setUser(null);
    setTenants([]);
    setActiveTenantIdState(null);
    router.push("/login");
  }, [router]);

  const value = useMemo(
    () => ({
      user,
      tenants,
      activeTenantId,
      loading,
      setActiveTenantId,
      refresh,
      signOut,
    }),
    [user, tenants, activeTenantId, loading, setActiveTenantId, refresh, signOut],
  );

  return (
    <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error("useAuth must be used within AuthProvider");
  }
  return ctx;
}

export function completeAuthSession(
  token: string,
  tenant: Tenant | null,
): void {
  if (tenant) {
    persistSession(token, tenant.id);
  } else {
    persistSession(token, "");
  }
}
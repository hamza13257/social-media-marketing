const TOKEN_KEY = "smm_token";
const TENANT_KEY = "smm_tenant_id";
const COOKIE_TOKEN = "smm_token";

export function getApiBaseUrl(): string {
  return process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000";
}

export function getToken(): string | null {
  if (typeof window === "undefined") {
    return null;
  }
  return localStorage.getItem(TOKEN_KEY);
}

export function getTenantId(): string | null {
  if (typeof window === "undefined") {
    return null;
  }
  return localStorage.getItem(TENANT_KEY);
}

export function persistSession(token: string, tenantId: number | string): void {
  localStorage.setItem(TOKEN_KEY, token);
  localStorage.setItem(TENANT_KEY, String(tenantId));
  document.cookie = `${COOKIE_TOKEN}=${token}; path=/; max-age=${60 * 60 * 24 * 7}; SameSite=Lax`;
}

export function clearSession(): void {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(TENANT_KEY);
  document.cookie = `${COOKIE_TOKEN}=; path=/; max-age=0`;
}
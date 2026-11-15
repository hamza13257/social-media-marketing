import type {
  AuthResponse,
  Brand,
  MeResponse,
  Tenant,
} from "@/types/api";
import {
  clearSession,
  getApiBaseUrl,
  getTenantId,
  getToken,
} from "@/lib/session";

export class ApiError extends Error {
  constructor(
    message: string,
    public status: number,
    public payload?: unknown,
  ) {
    super(message);
    this.name = "ApiError";
  }
}

type RequestOptions = {
  method?: string;
  body?: unknown;
  tenantScoped?: boolean;
  token?: string | null;
};

async function request<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const headers: Record<string, string> = {
    Accept: "application/json",
    "Content-Type": "application/json",
  };

  const token = options.token ?? getToken();
  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  if (options.tenantScoped) {
    const tenantId = getTenantId();
    if (!tenantId) {
      throw new ApiError("No organization selected.", 400);
    }
    headers["X-Tenant-Id"] = tenantId;
  }

  const response = await fetch(`${getApiBaseUrl()}${path}`, {
    method: options.method ?? "GET",
    headers,
    body: options.body ? JSON.stringify(options.body) : undefined,
  });

  if (response.status === 204) {
    return undefined as T;
  }

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    if (response.status === 401) {
      clearSession();
    }
    const message =
      (payload as { message?: string })?.message ?? "Request failed.";
    throw new ApiError(message, response.status, payload);
  }

  return payload as T;
}

export const api = {
  register(body: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    organization_name: string;
  }) {
    return request<AuthResponse>("/api/auth/register", {
      method: "POST",
      body,
    });
  },

  login(body: { email: string; password: string }) {
    return request<AuthResponse>("/api/auth/login", { method: "POST", body });
  },

  logout() {
    return request<{ message: string }>("/api/auth/logout", { method: "POST" });
  },

  me() {
    return request<MeResponse>("/api/auth/me");
  },

  listTenants() {
    return request<{ data: Tenant[] }>("/api/tenants");
  },

  listBrands(includeArchived = false) {
    const query = includeArchived ? "?include_archived=1" : "";
    return request<{ data: Brand[] }>(`/api/brands${query}`, {
      tenantScoped: true,
    });
  },

  getBrand(id: number) {
    return request<{ data: Brand }>(`/api/brands/${id}`, { tenantScoped: true });
  },

  createBrand(body: Record<string, unknown>) {
    return request<{ data: Brand }>("/api/brands", {
      method: "POST",
      body,
      tenantScoped: true,
    });
  },

  updateBrand(id: number, body: Record<string, unknown>) {
    return request<{ data: Brand }>(`/api/brands/${id}`, {
      method: "PATCH",
      body,
      tenantScoped: true,
    });
  },

  deleteBrand(id: number) {
    return request<void>(`/api/brands/${id}`, {
      method: "DELETE",
      tenantScoped: true,
    });
  },
};
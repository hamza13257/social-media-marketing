import type {
  AuthResponse,
  Brand,
  ContentItem,
  ContentsResponse,
  MeResponse,
  MetaOAuthUrlResponse,
  SocialAccount,
  SocialAccountsResponse,
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
    const body = payload as {
      message?: string;
      errors?: Record<string, string[]>;
      exception?: string;
    };
    let message = body.message ?? "Request failed.";
    if (body.errors) {
      message = Object.values(body.errors).flat().join(" ");
    } else if (response.status >= 500) {
      message =
        body.message ??
        "Server error — check backend setup (php artisan key:generate && php artisan migrate).";
    }
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

  listSocialAccounts(params?: { brand_id?: number }) {
    const query = params?.brand_id ? `?brand_id=${params.brand_id}` : "";
    return request<SocialAccountsResponse>(`/api/social-accounts${query}`, {
      tenantScoped: true,
    });
  },

  updateSocialAccount(id: number, body: { brand_id: number | null }) {
    return request<{ data: SocialAccount }>(`/api/social-accounts/${id}`, {
      method: "PATCH",
      body,
      tenantScoped: true,
    });
  },

  disconnectSocialAccount(id: number) {
    return request<void>(`/api/social-accounts/${id}`, {
      method: "DELETE",
      tenantScoped: true,
    });
  },

  metaOAuthUrl(brandId?: number) {
    const query = brandId ? `?brand_id=${brandId}` : "";
    return request<MetaOAuthUrlResponse>(`/api/meta/oauth/url${query}`, {
      tenantScoped: true,
    });
  },

  listContents(params?: { status?: string; pending_only?: boolean; brand_id?: number }) {
    const search = new URLSearchParams();
    if (params?.status) search.set("status", params.status);
    if (params?.pending_only) search.set("pending_only", "1");
    if (params?.brand_id) search.set("brand_id", String(params.brand_id));
    const q = search.toString();
    return request<ContentsResponse>(`/api/contents${q ? `?${q}` : ""}`, {
      tenantScoped: true,
    });
  },

  createContent(body: Record<string, unknown>) {
    return request<{ data: ContentItem }>("/api/contents", {
      method: "POST",
      body,
      tenantScoped: true,
    });
  },

  getContent(id: number) {
    return request<{ data: ContentItem }>(`/api/contents/${id}`, {
      tenantScoped: true,
    });
  },

  updateContent(id: number, body: Record<string, unknown>) {
    return request<{ data: ContentItem }>(`/api/contents/${id}`, {
      method: "PATCH",
      body,
      tenantScoped: true,
    });
  },

  submitContent(id: number, comment?: string) {
    return request<{ data: ContentItem }>(`/api/contents/${id}/submit`, {
      method: "POST",
      body: { comment },
      tenantScoped: true,
    });
  },

  approveContent(id: number, comment?: string) {
    return request<{ data: ContentItem }>(`/api/contents/${id}/approve`, {
      method: "POST",
      body: { comment },
      tenantScoped: true,
    });
  },

  rejectContent(id: number, comment?: string) {
    return request<{ data: ContentItem }>(`/api/contents/${id}/reject`, {
      method: "POST",
      body: { comment },
      tenantScoped: true,
    });
  },

  requestContentChanges(id: number, comment?: string) {
    return request<{ data: ContentItem }>(`/api/contents/${id}/request-changes`, {
      method: "POST",
      body: { comment },
      tenantScoped: true,
    });
  },

  generateCaption(id: number) {
    return request<{ data: ContentItem; warnings?: string[] }>(
      `/api/contents/${id}/generate-caption`,
      { method: "POST", tenantScoped: true },
    );
  },

  scheduleContent(id: number, scheduledAt: string) {
    return request<{ data: ContentItem }>(`/api/contents/${id}/schedule`, {
      method: "POST",
      body: { scheduled_at: scheduledAt },
      tenantScoped: true,
    });
  },
};
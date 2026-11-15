export type User = {
  id: number;
  name: string;
  email: string;
  is_super_admin?: boolean;
};

export type Tenant = {
  id: number;
  name: string;
  slug: string;
  role?: string;
  created_at?: string;
};

export type Brand = {
  id: number;
  tenant_id: number;
  name: string;
  slug: string;
  description: string | null;
  website_url: string | null;
  brand_colors: string[] | null;
  target_audience: string | null;
  language: string;
  tone_of_voice: string | null;
  marketing_objectives: string[] | null;
  products_services: string[] | null;
  content_pillars: string[] | null;
  brand_guidelines: string | null;
  archived_at: string | null;
  created_at?: string;
  updated_at?: string;
};

export type AuthResponse = {
  token: string;
  token_type: string;
  user: User;
  tenant: Tenant | null;
};

export type MeResponse = {
  user: User;
  tenants: Tenant[];
};
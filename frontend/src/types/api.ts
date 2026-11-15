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

export type SocialAccount = {
  id: number;
  tenant_id: number;
  brand_id: number | null;
  platform: string;
  platform_label: string;
  external_id: string;
  name: string;
  username: string | null;
  profile_picture_url: string | null;
  connection_status: string;
  token_expires_at: string | null;
  last_error: string | null;
  connected_at: string | null;
  brand?: { id: number; name: string } | null;
};

export type SocialAccountsResponse = {
  data: SocialAccount[];
  meta: { meta_configured: boolean };
};

export type MetaOAuthUrlResponse = {
  authorize_url: string;
  state: string;
};

export type ContentItem = {
  id: number;
  brand_id: number;
  topic: string;
  objective: string | null;
  platform: string;
  format: string;
  language: string;
  caption: string | null;
  hashtags: string[] | null;
  call_to_action: string | null;
  scheduled_at: string | null;
  status: string;
  version: number;
  brand?: { id: number; name: string };
  author?: { id: number; name: string };
};

export type ContentsResponse = {
  data: ContentItem[];
  meta: { ai_configured: boolean };
};
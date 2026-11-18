export type User = {
  id: number;
  name: string;
  email: string;
  is_super_admin?: boolean;
  avatar_url?: string | null;
};

export type PublicBootstrap = {
  app_name: string;
  tagline: string;
  google_auth_enabled: boolean;
  payment_gateway: string;
  plans: SubscriptionPlan[];
  features: string[];
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

export type PostStructure = {
  platform?: string;
  format?: string;
  hook?: string | null;
  image_alt?: string | null;
  visual_suggestions?: string[];
  slides?: Array<{ title?: string; body?: string; visual_idea?: string }>;
  story_frames?: Array<{ text?: string; sticker_idea?: string }>;
  reel_script?: { hook?: string; scenes?: string[]; on_screen_text?: string[] } | null;
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
  media_assets?: { type: string; url: string }[] | null;
  post_structure?: PostStructure | null;
  scheduled_at: string | null;
  status: string;
  version: number;
  brand?: { id: number; name: string };
  author?: { id: number; name: string };
  social_account_id?: number | null;
  external_post_id?: string | null;
  published_at?: string | null;
};

export type PublishingLog = {
  id: number;
  content_id: number;
  platform: string;
  status: string;
  external_post_id: string | null;
  error_message: string | null;
  started_at: string | null;
  finished_at: string | null;
};

export type ContentsResponse = {
  data: ContentItem[];
  meta: { ai_configured: boolean };
};

export type SubscriptionPlan = {
  id: number;
  slug: string;
  name: string;
  description: string | null;
  price_cents: number;
  currency: string;
  limits?: Record<string, number | null>;
};

export type SubscriptionRecord = {
  id: number;
  tenant_id: number;
  status: string;
  payment_gateway: string;
  manual_payment_notes: string | null;
  activated_at: string | null;
  expires_at: string | null;
  plan?: SubscriptionPlan;
  tenant?: { id: number; name: string };
};

export type BillingInfo = {
  data: SubscriptionRecord | null;
  gateway: string;
  manual_instructions?: string;
};

export type AdminDashboard = {
  users: number;
  tenants: number;
  subscriptions_pending: number;
  subscriptions_active: number;
  payment_gateway: string;
};

export type AdminTenantRow = {
  id: number;
  name: string;
  slug: string;
  subscription?: SubscriptionRecord | null;
  users_count?: number;
  brands_count?: number;
};

export type AdminUserRow = {
  id: number;
  name: string;
  email: string;
  is_super_admin: boolean;
};
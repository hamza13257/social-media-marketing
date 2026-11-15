import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";

const protectedPrefixes = [
  "/dashboard",
  "/brands",
  "/social-accounts",
  "/content-studio",
  "/approvals",
  "/calendar",
];

export function middleware(request: NextRequest) {
  const token = request.cookies.get("smm_token")?.value;
  const path = request.nextUrl.pathname;
  const needsAuth = protectedPrefixes.some(
    (prefix) => path === prefix || path.startsWith(`${prefix}/`),
  );

  if (needsAuth && !token) {
    const login = new URL("/login", request.url);
    login.searchParams.set("next", path);
    return NextResponse.redirect(login);
  }

  if ((path === "/login" || path === "/register") && token) {
    return NextResponse.redirect(new URL("/dashboard", request.url));
  }

  return NextResponse.next();
}

export const config = {
  matcher: [
    "/dashboard/:path*",
    "/brands/:path*",
    "/social-accounts/:path*",
    "/content-studio/:path*",
    "/approvals/:path*",
    "/calendar/:path*",
    "/login",
    "/register",
  ],
};
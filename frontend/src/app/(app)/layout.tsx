import { DashboardShell } from "@/components/layout/dashboard-shell";

export const instant = false;

export default function AppLayout({ children }: { children: React.ReactNode }) {
  return <DashboardShell>{children}</DashboardShell>;
}
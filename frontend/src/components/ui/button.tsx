import { type ButtonHTMLAttributes } from "react";

type Props = ButtonHTMLAttributes<HTMLButtonElement> & {
  variant?: "primary" | "ghost" | "danger";
  loading?: boolean;
};

const variants: Record<NonNullable<Props["variant"]>, string> = {
  primary:
    "bg-blue-600 hover:bg-blue-500 text-white shadow-sm shadow-blue-900/30",
  ghost: "bg-transparent hover:bg-slate-800 text-slate-200 border border-slate-700",
  danger: "bg-rose-600 hover:bg-rose-500 text-white",
};

export function Button({
  className = "",
  variant = "primary",
  loading,
  disabled,
  children,
  ...props
}: Props) {
  return (
    <button
      className={`inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium transition disabled:opacity-50 ${variants[variant]} ${className}`}
      disabled={disabled || loading}
      {...props}
    >
      {loading ? "Please wait…" : children}
    </button>
  );
}
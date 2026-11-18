export function UsageMeter({
  label,
  used,
  limit,
  hint,
}: {
  label: string;
  used: number;
  limit: number | null | undefined;
  hint?: string;
}) {
  const max = limit ?? 0;
  const unlimited = limit === null || limit === undefined;
  const pct = unlimited || max === 0 ? 0 : Math.min(100, Math.round((used / max) * 100));

  return (
    <div className="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
      <div className="flex items-baseline justify-between gap-2">
        <p className="text-sm font-medium text-white">{label}</p>
        <p className="text-xs text-slate-400">
          {used}
          {unlimited ? " · unlimited" : ` / ${max}`}
        </p>
      </div>
      {hint ? <p className="mt-1 text-[11px] text-slate-500">{hint}</p> : null}
      {!unlimited && max > 0 ? (
        <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-800">
          <div
            className={`h-full rounded-full transition-all ${
              pct >= 100 ? "bg-rose-500" : pct >= 80 ? "bg-amber-500" : "bg-blue-500"
            }`}
            style={{ width: `${pct}%` }}
          />
        </div>
      ) : null}
    </div>
  );
}
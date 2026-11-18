import type { ContentItem } from "@/types/api";

export function PostPreview({ content }: { content: ContentItem }) {
  const structure = content.post_structure;
  const hashtagLine =
    content.hashtags && content.hashtags.length > 0
      ? content.hashtags.map((t) => (t.startsWith("#") ? t : `#${t}`)).join(" ")
      : "";

  return (
    <div className="rounded-xl border border-slate-700 bg-gradient-to-b from-slate-900 to-slate-950 p-4">
      <p className="text-xs uppercase tracking-wide text-blue-400">
        Post preview · {content.platform} · {content.format.replace(/_/g, " ")}
      </p>

      {structure?.hook ? (
        <p className="mt-3 text-sm font-semibold text-white">Hook: {structure.hook}</p>
      ) : null}

      {content.media_assets?.find((a) => a.type === "image")?.url ? (
        <div className="mt-3 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
          {/* eslint-disable-next-line @next/next/no-img-element */}
          <img
            src={content.media_assets.find((a) => a.type === "image")!.url}
            alt={structure?.image_alt ?? "Post visual"}
            className="max-h-64 w-full object-cover"
          />
        </div>
      ) : structure?.visual_suggestions?.length ? (
        <p className="mt-3 rounded-lg bg-slate-950 px-3 py-2 text-xs text-slate-400">
          Visual: {structure.visual_suggestions[0]}
        </p>
      ) : null}

      <div className="mt-4 whitespace-pre-wrap text-sm text-slate-200">
        {content.caption}
        {content.call_to_action ? `\n\n${content.call_to_action}` : ""}
        {hashtagLine ? `\n\n${hashtagLine}` : ""}
      </div>

      {Array.isArray(structure?.slides) && structure.slides.length > 0 ? (
        <ul className="mt-4 space-y-2 border-t border-slate-800 pt-4">
          <p className="text-xs font-medium text-slate-500">Carousel slides</p>
          {structure.slides.map((slide, i) => (
            <li key={i} className="rounded-lg border border-slate-800 bg-slate-950/80 p-3 text-xs text-slate-300">
              <p className="font-medium text-white">{slide.title}</p>
              <p className="mt-1">{slide.body}</p>
              {slide.visual_idea ? (
                <p className="mt-1 text-slate-500">Visual: {slide.visual_idea}</p>
              ) : null}
            </li>
          ))}
        </ul>
      ) : null}

      {structure?.reel_script ? (
        <div className="mt-4 border-t border-slate-800 pt-4 text-xs text-slate-400">
          <p className="font-medium text-slate-300">Reel script</p>
          {structure.reel_script.scenes?.map((scene: string, i: number) => (
            <p key={i} className="mt-1">
              {i + 1}. {scene}
            </p>
          ))}
        </div>
      ) : null}

      <p className="mt-4 text-[11px] text-slate-600">
        MVP publishes caption text to Meta. Images need a public URL; carousel/reel/video files are
        not auto-generated.
      </p>
    </div>
  );
}
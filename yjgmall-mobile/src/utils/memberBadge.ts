import { mediaUrl } from "@/utils/request";

const SRC: Record<string, string> = {
  silver: "/img/member/silver.png",
  gold: "/img/member/gold.png",
  diamond: "/img/member/diamond.svg",
};

export function memberBadgeUrl(grade?: string | null) {
  const src = SRC[String(grade || "")];
  return src ? mediaUrl(src) : "";
}

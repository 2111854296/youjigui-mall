export function isHtmlContent(s: string) {
  return /<[a-z][\s\S]*>/i.test(String(s || ""));
}

/** 纯文本转可给 rich-text 用的 HTML；已是 HTML 则原样返回 */
export function contentNodes(s?: string | null, empty = "暂无详情") {
  const t = String(s || "").trim();
  if (!t) return empty;
  if (isHtmlContent(t)) return t;
  return t
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\n/g, "<br/>");
}

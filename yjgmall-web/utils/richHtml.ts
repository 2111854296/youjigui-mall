/** Destoon 迁移正文：lazy.gif + original/data-src → 直接显示真图 */
export function unwrapDestoonLazyHtml(html: string): string {
  const raw = String(html || '')
  if (!raw || !/lazy|original=|data-src|data-original/i.test(raw)) return raw
  return raw.replace(/<img\b[^>]*>/gi, (tag) => {
    let real = ''
    for (const attr of ['original', 'data-original', 'data-src', 'data-lazy', 'data-url']) {
      const m = tag.match(new RegExp(`\\b${attr}\\s*=\\s*(["'])(.*?)\\1`, 'i'))
      const cand = (m?.[2] || '').trim()
      if (cand && !/lazy\.gif|blank\.gif|spacer|loading\.gif/i.test(cand)) {
        real = cand
        break
      }
    }
    if (!real) return tag
    if (/\bsrc\s*=\s*(["']).*?\1/i.test(tag)) {
      return tag.replace(/\bsrc\s*=\s*(["']).*?\1/i, `src="${real.replace(/"/g, '&quot;')}"`)
    }
    return tag.replace(/<img\b/i, `<img src="${real.replace(/"/g, '&quot;')}"`)
  })
}

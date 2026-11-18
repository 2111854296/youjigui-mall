/** 无图默认用站点 logo，禁止用带联系方式的演示商品图 */
export const SAFE_PLACEHOLDER = '/img/logo-yjg.png'

/** 演示商品图路径（含电话/广告字样），一律视为无效 */
export function isDemoProductImg(src?: string | null): boolean {
  const s = String(src || '').trim()
  if (!s) return true
  if (/\/img\/products\/p\d+\.(jpe?g|png|webp|gif)/i.test(s)) return true
  if (/placehold|via\.placeholder/i.test(s)) return true
  if (/\/img\/product\.svg/i.test(s)) return true
  return false
}

export function safeThumb(src?: string | null, fallback: string = SAFE_PLACEHOLDER): string {
  const s = String(src || '').trim()
  if (!s || isDemoProductImg(s)) return fallback
  if (/\.svg($|\?)/i.test(s)) return fallback
  return s
}

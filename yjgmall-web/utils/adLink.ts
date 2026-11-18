export function normalizeAdLink(link?: string | null) {
  const s = String(link || '').trim()
  if (!s || s === '#' || s === '/' || s === '###' || s.toLowerCase().startsWith('javascript:')) return ''
  return s
}

export function isInternalAdLink(link?: string | null) {
  const s = normalizeAdLink(link)
  return s.startsWith('/') && !s.startsWith('//')
}

export function isExternalAdLink(link?: string | null) {
  const s = normalizeAdLink(link)
  return /^https?:\/\//i.test(s)
}

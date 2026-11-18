/** 行情价格展示：未登录打星（如 29800 → 2****） */

export function maskQuotePrice(n: unknown): string {
  const v = Number(n)
  if (!Number.isFinite(v)) return '****'
  const s = String(Math.round(Math.abs(v)))
  if (!s.length) return '****'
  if (s.length === 1) return '*'
  return s[0] + '*'.repeat(Math.min(4, s.length - 1))
}

export function formatQuotePrice(n: unknown, revealed: boolean): string {
  if (!revealed) return maskQuotePrice(n)
  const v = Number(n)
  if (!Number.isFinite(v)) return '-'
  return Math.round(v).toLocaleString('zh-CN')
}

export function formatQuoteDiff(n: unknown, revealed: boolean): string {
  if (!revealed) return '****'
  const v = Number(n)
  if (!Number.isFinite(v) || v === 0) return '0.00'
  return v > 0 ? `+${v.toFixed(2)}` : v.toFixed(2)
}

export function formatQuotePct(n: unknown, revealed: boolean): string {
  if (!revealed) return '**%'
  const v = Number(n)
  if (!Number.isFinite(v) || v === 0) return '0.00%'
  return `${v > 0 ? '+' : ''}${v.toFixed(2)}%`
}

/** 紧凑页码：1 … 当前邻页 … 末页 */
export function compactPageItems(current: number, last: number): (number | '…')[] {
  const cur = Math.max(1, Math.floor(Number(current) || 1))
  const end = Math.max(1, Math.floor(Number(last) || 1))
  if (end <= 7) return Array.from({ length: end }, (_, i) => i + 1)

  const set = new Set<number>()
  set.add(1)
  set.add(end)
  for (let i = cur - 1; i <= cur + 1; i++) {
    if (i >= 1 && i <= end) set.add(i)
  }
  if (cur <= 3) {
    for (let i = 2; i <= Math.min(4, end - 1); i++) set.add(i)
  }
  if (cur >= end - 2) {
    for (let i = Math.max(2, end - 3); i <= end - 1; i++) set.add(i)
  }

  const sorted = [...set].sort((a, b) => a - b)
  const out: (number | '…')[] = []
  for (let i = 0; i < sorted.length; i++) {
    if (i > 0 && sorted[i] - sorted[i - 1] > 1) out.push('…')
    out.push(sorted[i])
  }
  return out
}

/** Normalize admin form field values so Element Plus / checkbox bindings stay in sync. */

export function normalizeToggle(v: unknown): boolean {
  if (v === true || v === 1 || v === '1') return true
  if (v === false || v === 0 || v === '0' || v === 'false' || v == null || v === '') return false
  return Boolean(v)
}

/** Select option keys must match v-model (always string for el-option :value="String(...)"). */
export function normalizeSelect(v: unknown): string {
  if (v == null || v === '') return ''
  return String(v)
}

/** Multiselect stored as comma/pipe-separated string or string[]. */
export function normalizeMulti(v: unknown): string[] {
  if (Array.isArray(v)) return v.map((x) => String(x).trim()).filter(Boolean)
  if (v == null || v === '') return []
  return String(v)
    .split(/[,|、／/]+/)
    .map((s) => s.trim())
    .filter(Boolean)
}

export function multiToStorage(vals: string[]): string {
  return vals.join(',')
}

/** Turn array-shaped options (from PHP json_encode of 0..n keys) into a string-keyed map. */
export function normalizeOptions(raw: unknown): Record<string, string> {
  if (!raw || typeof raw !== 'object') return {}
  if (Array.isArray(raw)) {
    const out: Record<string, string> = {}
    raw.forEach((lab, i) => {
      if (lab == null) return
      out[String(i)] = String(lab)
    })
    return out
  }
  const out: Record<string, string> = {}
  for (const [k, v] of Object.entries(raw as Record<string, unknown>)) {
    if (v == null) continue
    out[String(k)] = String(v)
  }
  return out
}

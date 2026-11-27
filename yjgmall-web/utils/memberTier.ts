/** 和后台 VipEntitlement::tierCode 同一套：普通 / 白银 / 黄金 / 星钻。 */
export type MemberTier = 'free' | 'silver' | 'gold' | 'diamond'

const TIER_DAYS: Record<MemberTier, number> = {
  free: 7,
  silver: 365,
  gold: 730,
  diamond: 1825,
}

const TIER_LABEL: Record<MemberTier, string> = {
  free: '普通会员',
  silver: '白银会员',
  gold: '黄金会员',
  diamond: '星钻会员',
}

type TierUser = {
  level?: number | null
  vip_grade_code?: string | null
  vip_expire_at?: string | null
  vip_entitlement?: {
    grade_code?: string | null
    is_paid?: boolean
    quote_history_days?: number | null
    vip_expire_at?: string | null
  } | null
} | null | undefined

function parseTime(raw: unknown) {
  if (!raw) return NaN
  return Date.parse(String(raw).replace(' ', 'T'))
}

export function memberTierLabel(tier: MemberTier) {
  return TIER_LABEL[tier]
}

/** 服务器已经算好的档位优先；否则按未过期的档位代码，再按等级。 */
export function memberTier(user: TierUser): MemberTier {
  if (!user) return 'free'
  const ent = user.vip_entitlement
  const entCode = String(ent?.grade_code || '')
  if (ent && (entCode === 'free' || entCode === 'silver' || entCode === 'gold' || entCode === 'diamond')) {
    if (ent.is_paid === false || entCode === 'free') return 'free'
    return entCode
  }
  const exp = parseTime(user.vip_expire_at)
  const opened = Number.isFinite(exp) && exp >= Date.now()
  const code = opened ? String(user.vip_grade_code || '') : ''
  if (code === 'silver' || code === 'gold' || code === 'diamond') return code
  if (opened) {
    const level = Number(user.level || 0)
    if (level >= 3) return 'diamond'
    if (level === 2) return 'gold'
    if (level === 1) return 'silver'
  }
  return 'free'
}

/** 可看行情天数。接口当场返回的上限优先，其次是会员档案里的天数。 */
export function memberHistoryDays(user: TierUser, serverLimit = 0) {
  if (!user) return 0
  const limit = Number(serverLimit || 0)
  if (limit > 0) return limit
  const ent = Number(user.vip_entitlement?.quote_history_days || 0)
  if (ent > 0) return ent
  return TIER_DAYS[memberTier(user)]
}

export function quoteWindowStart(days: number, now = new Date()) {
  const d = new Date(now)
  d.setHours(0, 0, 0, 0)
  d.setDate(d.getDate() - Math.max(0, days - 1))
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${d.getFullYear()}-${m}-${day}`
}

/** 该月只要有一天落在可看窗口内，就允许打开，没有报价时另显示暂无。 */
export function monthInsideHistory(year: number, month: number, days: number) {
  if (days < 1 || month < 1) return false
  const end = `${year}-${String(month).padStart(2, '0')}-31`
  return end >= quoteWindowStart(days)
}

export function yearInsideHistory(year: number, days: number, now = new Date()) {
  if (days < 1) return false
  const endMonth = year < now.getFullYear() ? 12 : now.getMonth() + 1
  return monthInsideHistory(year, endMonth, days)
}

/** 前台走势年份下拉起点（产品：暂时只用 2022 起；后台导入仍可到 2016） */
export const QUOTE_YEAR_MIN = 2022

/** 前台年份下拉：2022～今年；序列里更早的年份不进前台列表 */
export function quoteYearOptions(extraYears: Array<number | string | null | undefined> = [], now = new Date()) {
  const nowY = now.getFullYear()
  const set = new Set<number>()
  for (let y = nowY; y >= QUOTE_YEAR_MIN; y--) set.add(y)
  for (const raw of extraYears) {
    const y = Number(raw)
    // 只并入 ≥2022 的有价年，避免前台被后台历史年拖到 2016
    if (Number.isFinite(y) && y >= QUOTE_YEAR_MIN && y <= nowY + 30) set.add(y)
  }
  return [...set].sort((a, b) => b - a)
}

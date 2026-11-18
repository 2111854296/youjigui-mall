/** 主流有机硅行情品种（固定展示顺序） */
export const QUOTE_CATALOG = [
  '二甲基环硅氧烷混合物（DMC）',
  '八甲基环四硅氧烷（D4）',
  '甲基乙烯基硅橡胶（生胶）',
  '107室温硫化硅橡胶（107胶）',
  '二甲基硅油',
  '外资品牌二甲基硅油',
  '硅橡胶混炼胶',
  '气相法二氧化硅（气相白炭黑）',
  '沉淀二氧化硅（沉淀白炭黑）',
  '421#金属硅',
  '工业硅期货',
  '一氯甲烷',
  '有机硅助剂',
  '裂解料二甲基硅油',
  '废硅胶毛边',
] as const

export const QUOTE_BASE_PRICE: Record<string, number> = {
  '二甲基环硅氧烷混合物（DMC）': 29800,
  '八甲基环四硅氧烷（D4）': 29500,
  '甲基乙烯基硅橡胶（生胶）': 30800,
  '107室温硫化硅橡胶（107胶）': 28200,
  '二甲基硅油': 26800,
  '外资品牌二甲基硅油': 38500,
  '硅橡胶混炼胶': 24500,
  '气相法二氧化硅（气相白炭黑）': 22000,
  '沉淀二氧化硅（沉淀白炭黑）': 6800,
  '421#金属硅': 12800,
  '工业硅期货': 9800,
  '一氯甲烷': 3200,
  '有机硅助剂': 15600,
  '裂解料二甲基硅油': 18500,
  '废硅胶毛边': 4200,
}

/** 短标签（资讯 chips），与 QUOTE_CATALOG 一一对应 */
export const QUOTE_CHIP_LABELS = [
  'DMC',
  'D4',
  '生胶',
  '107胶',
  '二甲基硅油',
  '外资硅油',
  '混炼胶',
  '气相白炭黑',
  '沉淀白炭黑',
  '421#金属硅',
  '工业硅期货',
  '一氯甲烷',
  '有机硅助剂',
  '裂解料硅油',
  '废硅胶毛边',
] as const

export function normQuoteName(raw: string): string {
  const s = String(raw || '').trim()
  const map: Record<string, string> = {
    DMC: '二甲基环硅氧烷混合物（DMC）',
    dmc: '二甲基环硅氧烷混合物（DMC）',
    D4: '八甲基环四硅氧烷（D4）',
    d4: '八甲基环四硅氧烷（D4）',
    生胶: '甲基乙烯基硅橡胶（生胶）',
    '基乙烯基硅橡胶（生胶）': '甲基乙烯基硅橡胶（生胶）',
    基乙烯基硅橡胶: '甲基乙烯基硅橡胶（生胶）',
    '107胶': '107室温硫化硅橡胶（107胶）',
    气相白炭黑: '气相法二氧化硅（气相白炭黑）',
    沉淀白炭黑: '沉淀二氧化硅（沉淀白炭黑）',
  }
  if (map[s]) return map[s]
  return s
    .replace(/^DMC$/i, '二甲基环硅氧烷混合物（DMC）')
    .replace(/^D4$/i, '八甲基环四硅氧烷（D4）')
    .replace(/^生胶$/, '甲基乙烯基硅橡胶（生胶）')
    .replace(/^107胶$/, '107室温硫化硅橡胶（107胶）')
}

export function quoteChipLabel(name: string): string {
  const full = normQuoteName(name)
  const i = (QUOTE_CATALOG as readonly string[]).indexOf(full)
  if (i >= 0) return QUOTE_CHIP_LABELS[i]
  const code = full.match(/[（(]([^）)]{1,10})[）)]/)
  if (code?.[1]) return code[1]
  return full.length > 8 ? full.slice(0, 8) : full
}

/** 价格行情资讯：chip → 标题关键词（绑定品种优先，旧数据按标题兜底） */
export const QUOTE_CHIP_SEARCH_KW: Record<string, string[]> = {
  DMC: ['DMC', '二甲基环硅氧烷'],
  D4: ['D4', '八甲基环四硅氧烷'],
  生胶: ['生胶', '甲基乙烯基硅橡胶'],
  '107胶': ['107胶', '107室温'],
  二甲基硅油: ['二甲基硅油', '甲基硅油'],
  外资硅油: ['外资', '进口硅油'],
  混炼胶: ['混炼胶'],
  气相白炭黑: ['气相', '气相法', '气相白炭黑'],
  沉淀白炭黑: ['沉淀', '沉淀白炭黑', '沉淀二氧化硅'],
  '421#金属硅': ['421', '金属硅'],
  工业硅期货: ['工业硅', '期货'],
  一氯甲烷: ['一氯甲烷', '氯甲烷'],
  有机硅助剂: ['助剂', '有机硅助剂'],
  裂解料硅油: ['裂解料'],
  废硅胶毛边: ['废硅胶', '毛边'],
}

export function quoteChipFullName(chip: string): string {
  const i = (QUOTE_CHIP_LABELS as readonly string[]).indexOf(chip)
  return i >= 0 ? QUOTE_CATALOG[i] : ''
}

/** 从文章池筛出某品种资讯（绑定 quote_product 优先，标题关键词兜底） */
export function filterArticlesByQuoteChip(pool: any[], chip: string): any[] {
  const full = quoteChipFullName(chip)
  const kws = QUOTE_CHIP_SEARCH_KW[chip] || [chip]
  const bound = pool.filter((it) => {
    const pid = Number(it?.quote_product_id || it?.quote_product?.id || 0)
    if (!pid) return false
    const name = String(it?.quote_product?.name || '')
    return name === full || quoteChipLabel(name) === chip
  })
  const boundIds = new Set(bound.map((it) => Number(it.id)))
  const titleHit = pool.filter((it) => {
    if (boundIds.has(Number(it.id))) return false
    if (Number(it?.quote_product_id || it?.quote_product?.id || 0)) return false
    return kws.some((k) => String(it?.title || '').includes(k))
  })
  return [...bound, ...titleHit]
}

/** 只返回接口真实行情；不再用目录价灌假价卡 */
export function buildQuoteProducts(apiRows: any[] = []) {
  const rows = (apiRows || []).map((p: any) => ({
    ...p,
    name: normQuoteName(String(p.name || '')),
  })).filter((p: any) => p.name)
  return sortQuotesByCatalog(rows)
}

export function sortQuotesByCatalog<T extends { name?: string }>(list: T[]): T[] {
  const rank = new Map(QUOTE_CATALOG.map((n, i) => [n, i]))
  return [...list].sort((a, b) => {
    const na = normQuoteName(String(a?.name || ''))
    const nb = normQuoteName(String(b?.name || ''))
    const ra = rank.has(na) ? (rank.get(na) as number) : 999
    const rb = rank.has(nb) ? (rank.get(nb) as number) : 999
    if (ra !== rb) return ra - rb
    return na.localeCompare(nb, 'zh')
  })
}

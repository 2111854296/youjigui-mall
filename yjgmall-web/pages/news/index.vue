<script setup lang="ts">
/**
 * 信息资讯页 · 像素对齐示范 news.html
 */
import { isExternalAdLink, isInternalAdLink, normalizeAdLink } from '~/utils/adLink'
import {
  buildQuoteProducts,
  QUOTE_CHIP_LABELS,
  filterArticlesByQuoteChip,
} from '~/utils/quoteCatalog'

definePageMeta({ layout: 'home' })
useHead({ title: '硅业智讯 · 有机硅商城' })

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const page = computed(() => Number(route.query.page || 1))
const q = computed(() => (route.query.q as string) || '')
const category = computed(() => route.query.category as string | undefined)
const sort = computed(() => (route.query.sort as string) || 'new')
const section = computed(() => String(route.query.section || ''))
/** 行情页「更多」带入：某品种资讯列表（非搜索页） */
const pnQuery = computed(() => {
  const raw = String(route.query.pn || '').trim()
  return (QUOTE_CHIP_LABELS as readonly string[]).includes(raw) ? raw : ''
})

/** 列表态（栏目/品种/搜索/分类）都展示本页关键词框；门户不含 ?pn= */
const showPortal = computed(
  () => !q.value && !category.value && !section.value && !pnQuery.value && page.value === 1,
)

const draftQ = ref(q.value)
watch(q, (v) => { draftQ.value = v })

function submitListSearch() {
  const kw = String(draftQ.value || '').trim()
  router.push({
    path: '/news',
    query: {
      ...route.query,
      q: kw || undefined,
      page: undefined,
    },
  })
}
function clearListSearch() {
  draftQ.value = ''
  if (!q.value) return
  const next = { ...route.query } as Record<string, any>
  delete next.q
  delete next.page
  router.push({ path: '/news', query: next })
}

function uniqArticles(list: any[]) {
  const seen = new Set<number>()
  const out: any[] = []
  for (const it of list || []) {
    const id = Number(it?.id)
    if (!id || seen.has(id)) continue
    seen.add(id)
    out.push(it)
  }
  return out
}

const { data: newsRes } = await useAsyncData(
  () => `news-portal-${page.value}-${q.value}-${category.value}-${sort.value}-${section.value}-${pnQuery.value}`,
  async () => {
    const sec = section.value
    const pn = pnQuery.value
    // 品种「更多」：按行情资讯同一套规则筛出该品种文章，走栏目列表样式（不是关键词搜索）
    if (pn && !sec && !category.value) {
      const res: any = await $fetch(`${config.public.apiBase}/news`, {
        query: {
          page: 1,
          q: q.value || undefined,
          sort: sort.value,
          per_page: 100,
        },
      })
      const pool = uniqArticles([...(res?.headlines || []), ...(res?.data || [])])
      const filtered = filterArticlesByQuoteChip(pool, pn)
      const per = 20
      const total = filtered.length
      const last = Math.max(1, Math.ceil(total / per) || 1)
      const cur = Math.min(Math.max(1, page.value), last)
      const start = (cur - 1) * per
      return {
        ...res,
        data: filtered.slice(start, start + per),
        meta: {
          ...(res?.meta || {}),
          current_page: cur,
          last_page: last,
          per_page: per,
          total,
        },
        __sec: '',
        __pn: pn,
      }
    }
    const res: any = await $fetch(`${config.public.apiBase}/news`, {
      query: {
        page: page.value,
        // 栏目内也可搜关键词（section + q 同时生效）
        q: q.value || undefined,
        section: sec || undefined,
        category: category.value,
        sort: sort.value,
        per_page: showPortal.value ? 60 : 20,
      },
    })
    return { ...res, __sec: sec, __pn: '' }
  },
  { watch: [page, q, category, sort, section, pnQuery] },
)

const PORTAL_SECTION_IDS = [
  'review', 'capacity', 'trade', 'report', 'company', 'talk', 'event', 'product', 'tech', 'expo',
] as const

const { data: portalSecRes } = await useAsyncData(
  'news-portal-sections-v1',
  async () => {
    if (!showPortal.value) return null
    const pairs = await Promise.all(
      PORTAL_SECTION_IDS.map(async (id) => {
        const res: any = await $fetch(`${config.public.apiBase}/news`, {
          query: { section: id, per_page: 10, sort: 'new' },
        })
        return [id, res?.data || []] as const
      }),
    )
    return Object.fromEntries(pairs) as Record<string, any[]>
  },
  { watch: [showPortal] },
)

const { data: homeRes } = await useAsyncData('news-home-quotes', () =>
  $fetch(`${config.public.apiBase}/home`),
)

const newsReady = computed(() => {
  const pack = newsRes.value as any
  if (!pack) return false
  return pack.__sec === section.value && String(pack.__pn || '') === pnQuery.value
})
const items = computed(() => (newsReady.value ? (newsRes.value as any)?.data : []) || [])
const meta = computed(() => (newsReady.value ? (newsRes.value as any)?.meta : null) || null)
const headlines = computed(() => (newsRes.value as any)?.headlines || [])
const home = computed(() => (homeRes.value as any)?.data || {})

/* ---------- 栏目 tabs ---------- */
const CRUMB_TABS = [
  { id: 'price', label: '价格汇总', anchor: 'np-quote' },
  { id: 'review', label: '市场网评', anchor: 'np-review' },
  { id: 'dynamic', label: '价格动态', anchor: 'np-pn' },
  { id: 'chart', label: '图表分析', anchor: 'np-quote' },
  { id: 'capacity', label: '产能产量', anchor: 'np-cap' },
  { id: 'trade', label: '进出口', anchor: 'np-cap' },
  { id: 'report', label: '研究报告', anchor: 'np-cap' },
]
const crumbOn = ref('price')
function goCrumb(tab: (typeof CRUMB_TABS)[0]) {
  crumbOn.value = tab.id
  if (import.meta.client) {
    document.getElementById(tab.anchor)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

/* ---------- 行情品种（对齐首页目录顺序） ---------- */
const quoteProducts = computed(() => buildQuoteProducts(home.value.quote_products || []))

const { user, token, fetchMe } = useAuth()
const { quoteApi } = useQuoteApi()
const priceRevealed = computed(() => Boolean(token.value))
const quoteIdx = ref(0)
const monthTab = ref(0)
const chartMode = ref<'line' | 'candle'>('line')
const chartYear = ref(new Date().getFullYear())
const yearOpen = ref(false)
const yearWrap = ref<HTMLElement | null>(null)
const vipGateOpen = ref(false)
const liveQuote = ref<any>(null)
let quoteReq = 0
const months = computed(() => {
  const now = new Date()
  const last = chartYear.value < now.getFullYear() ? 12 : now.getMonth() + 1
  return Array.from({ length: last }, (_, i) => `${last - i}月`)
})
watch(months, (list) => {
  if (monthTab.value > list.length - 1) monthTab.value = 0
})
const yearOptions = computed(() => {
  const y = new Date().getFullYear()
  return [y, y - 1, y - 2, y - 3]
})
const currentYear = computed(() => new Date().getFullYear())
const current = computed(() => quoteProducts.value[quoteIdx.value] || quoteProducts.value[0])
/** 卡片展示品种均价；忽略库里旧「华东」等字段 */
const quoteScopeLabel = computed(() => {
  const m = String(liveQuote.value?.market || current.value?.market || '').trim()
  if (!m || m === '华东' || m === '市场参考' || m === '市场报价') return '均价'
  return m
})
const quoteName = computed(() => {
  const t = String(current.value?.name || '').trim()
  const m = t.match(/^(.+?)([（(][^）)]+[）)])$/)
  if (!m || t.length < 12) return { main: t || '—', alias: '' }
  return { main: m[1].trim(), alias: m[2] }
})

function num(v: unknown, fallback = 0) {
  const n = Number(v)
  return Number.isFinite(n) ? n : fallback
}
function quoteWindowStart(days: number, end = new Date()) {
  const d = new Date(end)
  d.setHours(0, 0, 0, 0)
  d.setDate(d.getDate() - Math.max(0, days - 1))
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${d.getFullYear()}-${m}-${day}`
}
import { memberHistoryDays, monthInsideHistory, yearInsideHistory } from '~/utils/memberTier'

const serverLimit = ref(0)
const historyDays = computed(() => {
  if (!token.value) return 0
  return memberHistoryDays(user.value, serverLimit.value) || 7
})
function monthInside(index = monthTab.value) {
  if (!token.value || historyDays.value < 1) return false
  return monthInsideHistory(chartYear.value, monthFromTab(index), historyDays.value)
}
const allowedSeries = computed(() => {
  // 登录用详情接口；游客用首页公开 series，避免月轴停在当月空图
  const rows = Array.isArray(liveQuote.value?.series) && liveQuote.value.series.length
    ? liveQuote.value.series
    : (Array.isArray(current.value?.series) ? current.value.series : [])
  const priced = rows.filter((row: any) => num(row?.price_avg, 0) > 0)
  if (!priced.length) return priced
  const last = String(priced[priced.length - 1]?.date || '')
  const end = /^\d{4}-\d{2}-\d{2}$/.test(last) ? new Date(`${last}T12:00:00`) : new Date()
  const windowDays = token.value ? (historyDays.value || 7) : 7
  const from = quoteWindowStart(windowDays, end)
  return priced.filter((row: any) => String(row?.date || '') >= from)
})
async function loadLiveQuote() {
  const id = current.value?.id
  const days = historyDays.value
  if (!import.meta.client || !id || !token.value || days < 1) {
    liveQuote.value = null
    return
  }
  const seq = ++quoteReq
  try {
    const res = await quoteApi<any>(`/quotes/${id}`, { query: { days }, auth: true })
    if (seq !== quoteReq) return
    liveQuote.value = (res as any)?.data || null
    const limit = Number(liveQuote.value?.history_days_limit || 0)
    if (limit > serverLimit.value) serverLimit.value = limit
  } catch {
    if (seq === quoteReq) liveQuote.value = null
  }
}
function monthFromTab(index: number) {
  return parseInt(String(months.value[index] || ''), 10) || 0
}
function pickMonth(index: number) {
  if (!monthInside(index)) {
    vipGateOpen.value = true
    return
  }
  monthTab.value = index
}
function candlesFrom(rows: any[]) {
  return rows.map((row, i, arr) => {
    const close = num(row.price_avg)
    const open = i === 0 ? close : num(arr[i - 1].price_avg, close)
    const highRaw = num(row.price_max, 0)
    const lowRaw = num(row.price_min, 0)
    const high = highRaw > 0 ? Math.max(highRaw, open, close) : Math.max(open, close)
    const low = lowRaw > 0 ? Math.min(lowRaw, open, close) : Math.min(open, close)
    return {
      label: String(row.label || String(row.date).slice(5)),
      open,
      high,
      low,
      close,
    }
  })
}
const series = computed(() => {
  const month = monthFromTab(monthTab.value)
  const rows = allowedSeries.value.filter((row: any) => {
    const date = String(row.date)
    return Number(date.slice(0, 4)) === chartYear.value && Number(date.slice(5, 7)) === month
  })
  return candlesFrom(rows)
})
watch([allowedSeries, chartYear, months, quoteIdx], () => {
  const priced = allowedSeries.value
  if (!priced.length) {
    // 无走势时也尽量落到 quoted_on 所在月，避免默认当月空选中
    const qon = String(current.value?.quoted_on || '').slice(0, 10)
    if (/^\d{4}-\d{2}-\d{2}$/.test(qon)) {
      const y = Number(qon.slice(0, 4))
      const month = Number(qon.slice(5, 7))
      if (y && y !== chartYear.value && y <= currentYear.value) chartYear.value = y
      const idx = months.value.findIndex((label) => parseInt(label, 10) === month)
      if (idx >= 0) monthTab.value = idx
    }
    return
  }
  const last = String(priced[priced.length - 1].date || '')
  const y = Number(last.slice(0, 4))
  if (y && y !== chartYear.value && y <= currentYear.value) chartYear.value = y
  const rows = priced.filter((row: any) => Number(String(row.date).slice(0, 4)) === chartYear.value)
  if (!rows.length) return
  const month = Number(String(rows[rows.length - 1].date).slice(5, 7))
  const idx = months.value.findIndex((label) => parseInt(label, 10) === month)
  if (idx >= 0 && monthFromTab(monthTab.value) !== month) monthTab.value = idx
}, { immediate: true })
const latestRow = computed(() => {
  const rows = allowedSeries.value
  return rows.length ? rows[rows.length - 1] : null
})
const prevRow = computed(() => {
  const rows = allowedSeries.value
  return rows.length >= 2 ? rows[rows.length - 2] : null
})
const latest = computed(() => {
  const raw = liveQuote.value?.price_avg
  if (raw == null || raw === '') return 0
  return num(raw, 0)
})
const prev = computed(() => num(prevRow.value?.price_avg, latest.value))
const dayDiff = computed(() => (latest.value > 0 ? Number((latest.value - prev.value).toFixed(2)) : 0))
const dayPct = computed(() => {
  if (!prev.value || !(latest.value > 0)) return 0
  return Number((((latest.value - prev.value) / prev.value) * 100).toFixed(2))
})
const chgUp = computed(() => dayDiff.value >= 0)
const open = computed(() => (latest.value > 0 ? prev.value : 0))
const high = computed(() => (latest.value > 0 ? num(latestRow.value?.price_max, latest.value) : 0))
const low = computed(() => (latest.value > 0 ? num(latestRow.value?.price_min, latest.value) : 0))
const prevClose = computed(() => (latest.value > 0 ? prev.value : 0))

function fmtPrice(v: number) {
  if (!priceRevealed.value) return '****'
  if (!(v > 0)) return '*****'
  return formatQuotePrice(v, true)
}
function fmtSigned(v: number, digits = 2) {
  if (!priceRevealed.value || !(latest.value > 0)) return '****'
  const s = v.toFixed(digits)
  return v > 0 ? `+${s}` : s
}

const quotePickerOpen = ref(false)
const quotePickerWrap = ref<HTMLElement | null>(null)
const { actionTip, favBusy, shareQuote, favoriteQuote } = useQuoteCardActions()
function toggleQuotePicker() {
  quotePickerOpen.value = !quotePickerOpen.value
}
function closeQuotePicker() {
  quotePickerOpen.value = false
}
function pickQuote(i: number) {
  quoteIdx.value = i
  quotePickerOpen.value = false
}
function onQuotePickerDocClick(e: MouseEvent) {
  if (!quotePickerOpen.value) return
  const el = quotePickerWrap.value
  if (el && !el.contains(e.target as Node)) closeQuotePicker()
}
function onQuotePickerKey(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    closeQuotePicker()
    yearOpen.value = false
  }
}
function toggleYear() {
  yearOpen.value = !yearOpen.value
}
function pickYear(y: number) {
  yearOpen.value = false
  if (y === chartYear.value) return
  if (!token.value) {
    navigateTo({ path: '/login', query: { redirect: '/news' } })
    return
  }
  if (!yearInsideHistory(y, historyDays.value)) {
    vipGateOpen.value = true
    return
  }
  chartYear.value = y
}
function goJoinVip() {
  if (!user.value) {
    navigateTo({ path: '/login', query: { redirect: '/member/grade' } })
    return
  }
  navigateTo('/member/grade')
}
function onYearDocClick(e: MouseEvent) {
  if (!yearOpen.value) return
  const el = yearWrap.value
  if (el && !el.contains(e.target as Node)) yearOpen.value = false
}
onMounted(() => {
  if (!import.meta.client) return
  void fetchMe(true).finally(() => loadLiveQuote())
  document.addEventListener('click', onQuotePickerDocClick, true)
  document.addEventListener('click', onYearDocClick, true)
  document.addEventListener('keydown', onQuotePickerKey)
})
watch([() => current.value?.id, historyDays, token], () => { void loadLiveQuote() })
onBeforeUnmount(() => {
  if (!import.meta.client) return
  document.removeEventListener('click', onQuotePickerDocClick, true)
  document.removeEventListener('click', onYearDocClick, true)
  document.removeEventListener('keydown', onQuotePickerKey)
})

const related = computed(() => {
  const list = quoteProducts.value
  if (!list.length) return []
  const i = quoteIdx.value
  const idxs = [i, (i + 1) % list.length, (i + 2) % list.length]
  return idxs.map((idx) => {
    const p = list[idx]
    const raw = idx === i ? liveQuote.value?.price_avg : p?.price_avg
    const price = raw == null || raw === '' ? 0 : num(raw)
    return { idx, name: p?.name, price, diff: 0, pct: 0, up: true }
  })
})

const regions = computed(() => {
  const list = Array.isArray(liveQuote.value?.regions) ? liveQuote.value.regions : []
  return list.map((r: any) => {
    const price = num(r.price)
    const prev = num(r.prev, price)
    const diff = price - prev
    const pct = prev ? (diff / prev) * 100 : 0
    return { name: String(r.name || ''), price, diff, pct, up: diff >= 0 }
  }).filter((r: { name: string; price: number }) => r.name && r.price > 0).slice(0, 4)
})

function regionDetailTo(name?: string) {
  const pid = current.value?.id
  if (!pid) return '/quotes'
  if (!name) return `/quotes/${pid}`
  return `/quotes/${pid}?region=${encodeURIComponent(name)}`
}
const regionMoreTo = computed(() => {
  const first = regions.value[0]?.name
  return first ? regionDetailTo(first) : regionDetailTo()
})

/* ---------- 广告：只读资讯轮播 news_top，多张在这一格里轮换 ---------- */
const newsAdIndex = ref(0)
let newsAdTimer: ReturnType<typeof setInterval> | null = null
const newsAds = computed(() => {
  const pool = [...(home.value.ads?.news_top || [])].filter((x: any) => {
    const img = String(x?.image || '')
    return img && !/\.svg($|\?)/i.test(img) && !/placehold/i.test(img)
  })
  return pool
})
const adBanner = computed(() => {
  const empty = {
    title: '广告位',
    sub: '企业广告展示',
    link: '/ads?place=news_top',
    image: '',
  }
  const pool = newsAds.value
  const a = pool.length ? pool[newsAdIndex.value % pool.length] : null
  if (!a) return empty
  const image = String(a.image || '').trim()
  const link = normalizeAdLink(a.link)
  const href = (isInternalAdLink(link) || isExternalAdLink(link)) ? link : ''
  if (image) {
    return { title: String(a.title || '').trim(), sub: '', link: href, image }
  }
  return {
    title: String(a.title || empty.title).replace(/\\n/g, '\n'),
    sub: String(a.summary || a.remark || empty.sub),
    link: href || empty.link,
    image: '',
  }
})
onMounted(() => {
  newsAdTimer = setInterval(() => {
    if (newsAds.value.length > 1) newsAdIndex.value++
  }, 5000)
})
onBeforeUnmount(() => {
  if (newsAdTimer) clearInterval(newsAdTimer)
})

/* ---------- 文章块 ---------- */
type Row = { title: string; time: string; to: string }
function fmtTime(raw: string | undefined, withHm = true) {
  if (!raw) return ''
  const d = new Date(raw)
  if (Number.isNaN(d.getTime())) return String(raw).slice(0, 10)
  const md = `${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
  if (!withHm) return md
  return `${md} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}
function articleRows(list: any[], n: number, withHm = true): Row[] {
  return list.slice(0, n).map((it: any) => ({
    title: String(it.title || ''),
    time: fmtTime(it.published_at, withHm),
    to: `/news/${it.id}`,
  }))
}
/** 与接口 section=report、会员报告判断一致，避开「本周报价」误伤。 */
function isReportTitle(title: string) {
  const keys = ['研究报告', '行业报告', '深度报告', '研报', '专刊', '月报', '简析', '展望']
  if (keys.some((k) => title.includes(k))) return true
  return /(?<!本)周报(?!价)/.test(title)
}
const SECTION_DEFS = {
  review: { label: '有机硅资讯网评', keys: ['评述', '日评', '周评', '月评', '盘整', '综述', '网评'] },
  capacity: { label: '产能产量', keys: ['产能', '开工', '装置'] },
  trade: { label: '进出口', keys: ['进出口', '进口', '出口', '聚硅氧烷'] },
  report: { label: '研究报告', keys: [] as string[], report: true },
  company: { label: '企业动态', keys: ['企业', '合盛', '新安'] },
  talk: { label: '对话大咖', keys: ['对话', '访谈', '大咖'] },
  event: { label: '企业活动', keys: ['论坛', '活动', '直播', '对接'] },
  product: { label: '新品推荐', keys: ['新品', '上市', '认证'] },
  tech: { label: '技术分享', keys: ['技术', '加工', '检测', '技巧'] },
  expo: { label: '展会动态', keys: ['展会', '展位', '展览', 'CHINACOAT'] },
} as const
const listTitle = computed(() => {
  const def = SECTION_DEFS[section.value as keyof typeof SECTION_DEFS]
  if (pnQuery.value) {
    const base = `${pnQuery.value} · 价格行情资讯`
    return q.value ? `${base} · 搜索「${q.value}」` : base
  }
  if (def && q.value) return `${def.label} · 搜索「${q.value}」`
  if (def) return def.label
  if (q.value) return `资讯检索 · 「${q.value}」`
  return '资讯列表'
})
const SECTION_SLUG: Record<keyof typeof SECTION_DEFS, string> = {
  review: 'news-review',
  capacity: 'news-capacity',
  trade: 'news-trade',
  report: 'news-report',
  company: 'news-company',
  talk: 'news-talk',
  event: 'news-event',
  product: 'news-product',
  tech: 'news-tech',
  expo: 'news-expo',
}
function matchSection(it: any, id: keyof typeof SECTION_DEFS) {
  const slug = String(it?.category?.slug || '')
  const parent = SECTION_SLUG[id]
  // 认本栏目及子分类（如 news-review-daily）
  return !!(slug && (slug === parent || slug.startsWith(parent + '-')))
}
function uniquePool() {
  const seen = new Set<number>()
  const out: any[] = []
  for (const it of [...headlines.value, ...items.value]) {
    const id = Number(it?.id)
    if (!id || seen.has(id)) continue
    seen.add(id)
    out.push(it)
  }
  return out
}
function sectionRows(id: keyof typeof SECTION_DEFS, n: number, withHm = true): Row[] {
  const fromApi = (portalSecRes.value as Record<string, any[]> | null)?.[id]
  if (fromApi?.length) return articleRows(fromApi, n, withHm)
  const hit = uniquePool().filter((it) => matchSection(it, id))
  return articleRows(hit, n, withHm)
}

/** 栏目 Tab 子集：优先子分类，旧数据按标题关键词兜底 */
function filterBySubTab(
  sectionId: keyof typeof SECTION_DEFS,
  tab: { slug: string; keys: string[] },
  n: number,
  withHm = true,
): Row[] {
  const parent = SECTION_SLUG[sectionId]
  const fromApi = (portalSecRes.value as Record<string, any[]> | null)?.[sectionId]
  const pool = (fromApi?.length ? fromApi : uniquePool().filter((it) => matchSection(it, sectionId))) as any[]
  const bound = pool.filter((it) => String(it?.category?.slug || '') === tab.slug)
  const boundIds = new Set(bound.map((it) => Number(it.id)))
  const titleHit = pool.filter((it) => {
    if (boundIds.has(Number(it.id))) return false
    const slug = String(it?.category?.slug || '')
    // 已挂到其它子类的不进兜底
    if (slug.startsWith(parent + '-') && slug !== tab.slug) return false
    const title = String(it.title || '')
    return tab.keys.some((k) => title.includes(k))
  })
  // 若该 Tab 下完全没有命中，且文章只挂在父级分类，则父级文章各 Tab 都可看到
  if (!bound.length && !titleHit.length) {
    const parentOnly = pool.filter((it) => String(it?.category?.slug || '') === parent)
    return articleRows(parentOnly, n, withHm)
  }
  return articleRows([...bound, ...titleHit], n, withHm)
}

const reviewTab = ref(0)
const reviewTabs = [
  { label: '日评', slug: 'news-review-daily', keys: ['日评'] },
  { label: '周评', slug: 'news-review-weekly', keys: ['周评'] },
  { label: '月评', slug: 'news-review-monthly', keys: ['月评'] },
  { label: '年度盘点', slug: 'news-review-yearly', keys: ['年度盘点', '年评', '年度'] },
] as const
const reviewRows = computed(() => {
  const tab = reviewTabs[reviewTab.value] || reviewTabs[0]
  return filterBySubTab('review', tab, 9)
})

const capTabs = [
  { label: '产能', slug: 'news-capacity-output', keys: ['产能', '产量'] },
  { label: '开工率', slug: 'news-capacity-rate', keys: ['开工率', '开工'] },
] as const

/* 价格行情资讯 chips — 门户内本地切换；行情页「更多」用 ?pn= 进该品种列表 */
const PN_CHIPS = [...QUOTE_CHIP_LABELS]
const pnCur = ref('DMC')
const pnOpen = ref(false)
const PN_VISIBLE = 6
const pnRows = computed(() => articleRows(filterArticlesByQuoteChip(uniquePool(), pnCur.value), 8))
function setPn(chip: string) {
  pnCur.value = chip
  pnOpen.value = false
}

const capTab = ref(0)

const blocks = computed(() => {
  const cap = capTabs[capTab.value] || capTabs[0]
  return {
    capacity: filterBySubTab('capacity', cap, 8),
    trade: sectionRows('trade', 8),
    report: sectionRows('report', 8),
    company: sectionRows('company', 8, false),
    talk: sectionRows('talk', 8, false),
    event: sectionRows('event', 8, false),
    newProduct: sectionRows('product', 8, false),
    tech: sectionRows('tech', 8, false),
    expo: sectionRows('expo', 8, false),
  }
})

function thumbOf(item: any) {
  const t = String(item?.thumb || '')
  if (t && !/\.svg($|\?)/i.test(t) && !/placehold/i.test(t)) return t
  return '/img/logo-yjg.png'
}
</script>

<template>
  <div class="news-portal">
    <!-- 栏目 tabs -->
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <a
            v-for="t in CRUMB_TABS"
            :key="t.id"
            href="javascript:;"
            :class="{ on: crumbOn === t.id }"
            @click.prevent="goCrumb(t)"
          >{{ t.label }}</a>
        </div>
      </div>
    </div>

    <!-- 市场提示条 -->
    <div class="tip-bar">
      <div class="container">
        <b>有机硅市场提示：</b>
        单体厂商挺价意愿明显，下游按需采购，市场处于供应释放与需求博弈期；短期行情以区间震荡为主，建议下游按需采购、贸易商谨慎操作，实际以成交商谈为准。
      </div>
    </div>

    <!-- 检索列表态 -->
    <div v-if="!showPortal" class="section" style="padding-top:14px">
      <div class="container">
        <div class="list-panel">
          <div class="lp-hd">
            <h1>{{ listTitle }}</h1>
            <div class="lp-tools">
              <form class="lp-search" @submit.prevent="submitListSearch">
                <input
                  v-model="draftQ"
                  type="search"
                  enterkeyhint="search"
                  :placeholder="(section || pnQuery) ? `在「${listTitle}」中搜索` : '搜索资讯标题关键词'"
                  aria-label="关键词搜索"
                >
                <button type="submit">搜索</button>
                <button v-if="q" type="button" class="lp-clear" @click="clearListSearch">清除</button>
              </form>
              <NuxtLink class="more-link" to="/news">返回资讯首页 ›</NuxtLink>
            </div>
          </div>
          <NuxtLink v-for="it in items" :key="it.id" class="lp-item" :to="`/news/${it.id}`">
            <img :src="thumbOf(it)" :alt="it.title" loading="lazy">
            <div>
              <div class="tt">{{ it.title }}</div>
              <div class="sum">{{ it.summary || '点击查看详情' }}</div>
              <div class="meta">{{ it.published_at?.slice(0, 10) }} · {{ it.views || 0 }}阅</div>
            </div>
          </NuxtLink>
          <p v-if="!items.length" style="color:var(--ink-3);padding:24px 0;text-align:center">{{ newsReady ? '暂无相关资讯' : '加载中…' }}</p>
          <AppPager :meta="meta" />
        </div>
      </div>
    </div>

    <template v-else>
      <!-- 行1：广告 + 价格行情连体 -->
      <div id="np-quote" class="section" style="padding-top:14px">
        <div class="container">
          <div class="grid-quote">
            <div class="ad-slot">
            <NuxtLink
              v-if="isInternalAdLink(adBanner.link)"
              class="ad-banner"
              :class="{ 'has-img': !!adBanner.image }"
              :to="adBanner.link"
            >
              <span class="ad-tag">广告</span>
              <img v-if="adBanner.image" :src="adBanner.image" :alt="adBanner.title || '广告'" loading="lazy">
              <div v-else>
                <b style="white-space:pre-line">{{ adBanner.title }}</b>
                <p>{{ adBanner.sub }}</p>
                <span class="ad-go">了解详情 →</span>
              </div>
            </NuxtLink>
            <a
              v-else-if="isExternalAdLink(adBanner.link)"
              class="ad-banner"
              :class="{ 'has-img': !!adBanner.image }"
              :href="adBanner.link"
              target="_blank"
              rel="noopener noreferrer"
            >
              <span class="ad-tag">广告</span>
              <img v-if="adBanner.image" :src="adBanner.image" :alt="adBanner.title || '广告'" loading="lazy">
              <div v-else>
                <b style="white-space:pre-line">{{ adBanner.title }}</b>
                <p>{{ adBanner.sub }}</p>
                <span class="ad-go">了解详情 →</span>
              </div>
            </a>
            <div
              v-else
              class="ad-banner"
              :class="{ 'has-img': !!adBanner.image }"
            >
              <span class="ad-tag">广告</span>
              <img v-if="adBanner.image" :src="adBanner.image" :alt="adBanner.title || '广告'" loading="lazy">
              <div v-else>
                <b style="white-space:pre-line">{{ adBanner.title }}</b>
                <p>{{ adBanner.sub }}</p>
                <NuxtLink class="ad-go" to="/ads?place=news_top">了解详情 →</NuxtLink>
              </div>
            </div>
            <div v-if="newsAds.length > 1" class="ad-dots" aria-hidden="true">
              <i v-for="(ad, i) in newsAds" :key="ad.id || i" :class="{ on: i === newsAdIndex % newsAds.length }" />
            </div>
            </div>

            <div class="pz-col">
              <div class="pz-card">
                <div class="pz-body">
                  <div class="pz-info quote-card">
                    <div class="qc-top">
                      <div class="qc-name">{{ quoteName.main }}<template v-if="quoteName.alias"><br>{{ quoteName.alias }}</template></div>
                      <div class="qc-btns">
                        <button type="button" :disabled="favBusy" @click.stop="favoriteQuote(current)">收藏</button>
                        <button type="button" @click.stop="shareQuote(current)">分享</button>
                      </div>
                      <div v-if="actionTip" class="qc-tip">{{ actionTip }}</div>
                    </div>
                    <div class="qc-tags">
                      <span class="tg">市场报价</span>
                      <div ref="quotePickerWrap" class="qp-switch">
                        <button
                          type="button"
                          class="tg qp-switch-btn"
                          :aria-expanded="quotePickerOpen"
                          aria-haspopup="listbox"
                          @click.stop="toggleQuotePicker"
                        >
                          ⇄ 切换品种
                          <span class="chev" :class="{ open: quotePickerOpen }" aria-hidden="true">▾</span>
                        </button>
                        <div
                          v-show="quotePickerOpen"
                          class="qp-drop"
                          role="listbox"
                          aria-label="选择行情品种"
                        >
                          <div class="qp-drop-head">
                            <b>选择品种</b>
                            <span>共 {{ quoteProducts.length }} 个</span>
                          </div>
                          <div class="qp-drop-grid">
                            <button
                              v-for="(p, i) in quoteProducts"
                              :key="p.id || p.name"
                              type="button"
                              class="qp-drop-item"
                              role="option"
                              :aria-selected="i === quoteIdx"
                              :class="{ on: i === quoteIdx }"
                              @click.stop="pickQuote(i)"
                            >
                              <span class="n">{{ p.name }}</span>
                              <span class="p num">{{ fmtPrice(num(p.price_avg, 0)) }}</span>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="qc-price">
                      <b class="num">{{ fmtPrice(latest) }}</b>
                      <small>{{ current?.unit || '元/吨' }}</small>
                      <NuxtLink v-if="!priceRevealed" class="price-login-tip" to="/login?redirect=/news">登录查看</NuxtLink>
                    </div>
                    <div class="qc-chg" :class="{ down: priceRevealed && !chgUp }">
                      <span class="num">{{ fmtSigned(dayDiff) }}</span>
                      <span class="num">{{ priceRevealed ? fmtSigned(dayPct) + '%' : '**%' }}</span>
                    </div>
                    <div class="qc-time">
                      更新于 {{ current?.quoted_on || '—' }} · {{ quoteScopeLabel }}
                    </div>
                    <div class="qc-data">
                      <div class="d"><div class="k">今开</div><div class="v num">{{ fmtPrice(open) }}</div></div>
                      <div class="d"><div class="k">最高</div><div class="v num up">{{ fmtPrice(high) }}</div></div>
                      <div class="d"><div class="k">最低</div><div class="v num down">{{ fmtPrice(low) }}</div></div>
                      <div class="d"><div class="k">昨收</div><div class="v num">{{ fmtPrice(prevClose) }}</div></div>
                      <div class="d"><div class="k">成交量</div><div class="v">--</div></div>
                      <div class="d"><div class="k">成交额</div><div class="v">--</div></div>
                    </div>
                  </div>

                  <div class="pz-chart">
                    <div class="tc-head">
                      <b>价格走势</b>
                      <div class="tc-head-right">
                        <div class="chart-mode" role="tablist" aria-label="图表类型">
                          <button
                            type="button"
                            role="tab"
                            :class="{ on: chartMode === 'line' }"
                            :aria-selected="chartMode === 'line'"
                            @click="chartMode = 'line'"
                          >折线</button>
                          <button
                            type="button"
                            role="tab"
                            :class="{ on: chartMode === 'candle' }"
                            :aria-selected="chartMode === 'candle'"
                            @click="chartMode = 'candle'"
                          >K线</button>
                        </div>
                        <div ref="yearWrap" class="yr-switch">
                          <button
                            type="button"
                            class="yr"
                            :aria-expanded="yearOpen"
                            aria-haspopup="listbox"
                            @click.stop="toggleYear"
                          >{{ chartYear }}年 <span class="chev" :class="{ open: yearOpen }" aria-hidden="true">▾</span></button>
                          <div
                            v-show="yearOpen"
                            class="yr-drop"
                            role="listbox"
                            aria-label="选择年份"
                          >
                            <button
                              v-for="y in yearOptions"
                              :key="y"
                              type="button"
                              role="option"
                              :aria-selected="y === chartYear"
                              :class="{ on: y === chartYear }"
                              @click.stop="pickYear(y)"
                            >{{ y }}年</button>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="month-tabs">
                      <button
                        v-for="(m, i) in months"
                        :key="m"
                        type="button"
                        :class="{ on: i === monthTab }"
                        @click="pickMonth(i)"
                      >{{ m }}</button>
                    </div>
                    <QuoteKlineChart
                      v-if="series.length && latest > 0"
                      :candles="series"
                      :mode="chartMode"
                      :revealed="true"
                      :aria-label="`${current?.name || ''}${chartMode === 'candle' ? 'K线' : '折线'}走势`"
                    />
                    <QuoteKlineChart
                      v-else-if="!monthInside()"
                      :candles="[]"
                      :mode="chartMode"
                      :revealed="false"
                      :gate="priceRevealed ? 'vip' : 'login'"
                      login-redirect="/news"
                      :aria-label="`${current?.name || ''}行情走势`"
                      @upgrade="goJoinVip"
                    />
                    <p v-else class="empty" style="padding:28px 0;text-align:center;color:var(--ink-3)">该月暂无走势数据</p>
                  </div>
                </div>
              </div>

              <div class="pz-bottom">
                <div class="pzb-block">
                  <div class="pzb-head"><b>关联品种</b><NuxtLink to="/quotes">更多 ›</NuxtLink></div>
                  <div class="pzb-cards cols3">
                    <button
                      v-for="r in related"
                      :key="r.idx"
                      type="button"
                      class="pzb-card"
                      :class="{ on: r.idx === quoteIdx }"
                      @click="pickQuote(r.idx)"
                    >
                      <div class="n">{{ r.name }}</div>
                      <div class="p num">{{ fmtPrice(r.price) }}</div>
                      <div class="c num" :class="r.up ? 'up' : 'down'">
                        {{ fmtSigned(r.diff) }} {{ fmtSigned(r.pct) }}%
                      </div>
                    </button>
                  </div>
                </div>
                <div class="pzb-block">
                  <div class="pzb-head">
                    <b>区域价格</b>
                    <NuxtLink :to="regionMoreTo">更多 ›</NuxtLink>
                  </div>
                  <div class="pzb-cards cols4">
                    <NuxtLink
                      v-for="r in regions"
                      :key="r.name"
                      class="pzb-card"
                      :to="regionDetailTo(r.name)"
                    >
                      <div class="n">{{ r.name }}</div>
                      <div class="p num">{{ fmtPrice(r.price) }}<small> 元/吨</small></div>
                      <div class="c num" :class="r.up ? 'up' : 'down'">
                        {{ fmtSigned(r.diff, 0) }} {{ fmtSigned(r.pct) }}%
                      </div>
                    </NuxtLink>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 行2：资讯网评 + 价格行情资讯 -->
      <div id="np-review" class="section" style="padding-top:14px">
        <div class="container">
          <div class="grid-news">
            <div class="blk">
              <div class="b-head">
                <b>有机硅资讯网评</b>
                <div class="sub-tabs">
                  <button
                    v-for="(t, i) in reviewTabs"
                    :key="t.slug"
                    type="button"
                    :class="{ on: i === reviewTab }"
                    @click="reviewTab = i"
                  >{{ t.label }}</button>
                </div>
              </div>
              <NuxtLink
                v-for="(row, i) in reviewRows"
                :key="'rv'+i"
                class="li-row"
                :to="row.to"
              >
                <span class="t">{{ row.title }}</span>
                <time>{{ row.time }}</time>
              </NuxtLink>
              <p v-if="!reviewRows.length" class="li-empty">暂无相关资讯</p>
              <div class="b-foot"><NuxtLink :to="{ path: '/news', query: { section: 'review' } }">更多 ››</NuxtLink></div>
            </div>

            <div id="np-pn" class="blk">
              <div class="b-head">
                <b>价格行情资讯</b>
                <span class="hint">{{ PN_CHIPS.length }} 大品种 · 每日更新 · 点击筛选资讯</span>
              </div>
              <div class="chips">
                <button
                  v-for="c in PN_CHIPS"
                  :key="c"
                  type="button"
                  class="chip"
                  :class="{ active: pnCur === c }"
                  @click="setPn(c)"
                >{{ c }}</button>
              </div>
              <div>
                <NuxtLink
                  v-for="(row, i) in pnRows"
                  :key="'pn'+i"
                  class="li-row"
                  :class="{ 'li-hidden': i >= PN_VISIBLE, open: pnOpen && i >= PN_VISIBLE }"
                  :to="row.to"
                >
                  <span class="t">{{ row.title }}</span>
                  <time>{{ row.time }}</time>
                </NuxtLink>
                <p v-if="!pnRows.length" class="li-empty">暂无相关资讯</p>
              </div>
              <button
                v-if="pnRows.length > PN_VISIBLE"
                type="button"
                class="collapse-btn"
                :class="{ open: pnOpen }"
                @click="pnOpen = !pnOpen"
              >
                {{ pnOpen ? '收起' : '展开更多' }}
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9l6 6 6-6" /></svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- 行3：产能 / 进出口 / 研究报告 -->
      <div id="np-cap" class="section" style="padding-top:0">
        <div class="container">
          <div class="grid3">
            <div class="blk">
              <div class="b-head">
                <b>产能产量</b>
                <div class="sub-tabs">
                  <button
                    v-for="(t, i) in capTabs"
                    :key="t.slug"
                    type="button"
                    :class="{ on: i === capTab }"
                    @click="capTab = i"
                  >{{ t.label }}</button>
                </div>
              </div>
              <NuxtLink v-for="(row, i) in blocks.capacity" :key="'cap'+i" class="li-row" :to="row.to">
                <span class="t">{{ row.title }}</span><time>{{ row.time }}</time>
              </NuxtLink>
              <p v-if="!blocks.capacity.length" class="li-empty">暂无相关资讯</p>
              <div class="b-foot"><NuxtLink :to="{ path: '/news', query: { section: 'capacity' } }">更多 ››</NuxtLink></div>
            </div>
            <div class="blk">
              <div class="b-head"><b>进出口</b></div>
              <NuxtLink v-for="(row, i) in blocks.trade" :key="'tr'+i" class="li-row" :to="row.to">
                <span class="t">{{ row.title }}</span><time>{{ row.time }}</time>
              </NuxtLink>
              <p v-if="!blocks.trade.length" class="li-empty">暂无相关资讯</p>
              <div class="b-foot"><NuxtLink :to="{ path: '/news', query: { section: 'trade' } }">更多 ››</NuxtLink></div>
            </div>
            <div class="blk">
              <div class="b-head">
                <b>研究报告</b>
                <NuxtLink class="custom-report-link" to="/news/custom-report">定制报告</NuxtLink>
              </div>
              <NuxtLink v-for="(row, i) in blocks.report" :key="'rp'+i" class="li-row" :to="row.to">
                <span class="t">{{ row.title }}</span><time>{{ row.time }}</time>
              </NuxtLink>
              <p v-if="!blocks.report.length" class="li-empty">暂无相关资讯</p>
              <div class="b-foot"><NuxtLink :to="{ path: '/news', query: { section: 'report' } }">更多 ››</NuxtLink></div>
            </div>
          </div>
        </div>
      </div>

      <!-- 行4：企业动态 / 对话大咖 / 企业活动 -->
      <div class="section" style="padding-top:0">
        <div class="container">
          <div class="grid3">
            <div class="blk">
              <div class="b-head"><b>企业动态</b></div>
              <NuxtLink v-for="(row, i) in blocks.company" :key="'co'+i" class="li-row" :to="row.to">
                <span class="t">{{ row.title }}</span><time>{{ row.time }}</time>
              </NuxtLink>
              <p v-if="!blocks.company.length" class="li-empty">暂无相关资讯</p>
              <div class="b-foot"><NuxtLink :to="{ path: '/news', query: { section: 'company' } }">更多 ››</NuxtLink></div>
            </div>
            <div class="blk">
              <div class="b-head"><b>对话大咖</b></div>
              <NuxtLink v-for="(row, i) in blocks.talk" :key="'tk'+i" class="li-row" :to="row.to">
                <span class="t">{{ row.title }}</span><time>{{ row.time }}</time>
              </NuxtLink>
              <p v-if="!blocks.talk.length" class="li-empty">暂无相关资讯</p>
              <div class="b-foot"><NuxtLink :to="{ path: '/news', query: { section: 'talk' } }">更多 ››</NuxtLink></div>
            </div>
            <div class="blk">
              <div class="b-head"><b>企业活动</b></div>
              <NuxtLink v-for="(row, i) in blocks.event" :key="'ev'+i" class="li-row" :to="row.to">
                <span class="t">{{ row.title }}</span><time>{{ row.time }}</time>
              </NuxtLink>
              <p v-if="!blocks.event.length" class="li-empty">暂无相关资讯</p>
              <div class="b-foot"><NuxtLink :to="{ path: '/news', query: { section: 'event' } }">更多 ››</NuxtLink></div>
            </div>
          </div>
        </div>
      </div>

      <!-- 行5：新品 / 技术 / 展会 -->
      <div class="section" style="padding-top:0;padding-bottom:24px">
        <div class="container">
          <div class="grid3">
            <div class="blk">
              <div class="b-head"><b>新品推荐</b></div>
              <NuxtLink v-for="(row, i) in blocks.newProduct" :key="'np'+i" class="li-row" :to="row.to">
                <span class="t">{{ row.title }}</span><time>{{ row.time }}</time>
              </NuxtLink>
              <p v-if="!blocks.newProduct.length" class="li-empty">暂无相关资讯</p>
              <div class="b-foot"><NuxtLink :to="{ path: '/news', query: { section: 'product' } }">更多 ››</NuxtLink></div>
            </div>
            <div class="blk">
              <div class="b-head"><b>技术分享</b></div>
              <NuxtLink v-for="(row, i) in blocks.tech" :key="'te'+i" class="li-row" :to="row.to">
                <span class="t">{{ row.title }}</span><time>{{ row.time }}</time>
              </NuxtLink>
              <p v-if="!blocks.tech.length" class="li-empty">暂无相关资讯</p>
              <div class="b-foot"><NuxtLink :to="{ path: '/news', query: { section: 'tech' } }">更多 ››</NuxtLink></div>
            </div>
            <div class="blk">
              <div class="b-head"><b>展会动态</b></div>
              <NuxtLink v-for="(row, i) in blocks.expo" :key="'ex'+i" class="li-row" :to="row.to">
                <span class="t">{{ row.title }}</span><time>{{ row.time }}</time>
              </NuxtLink>
              <p v-if="!blocks.expo.length" class="li-empty">暂无相关资讯</p>
              <div class="b-foot"><NuxtLink :to="{ path: '/news', query: { section: 'expo' } }">更多 ››</NuxtLink></div>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>

  <VipGateModal
    v-model="vipGateOpen"
    title="加入会员后查阅"
    message="更早的行情、K线和报价只对对应会员档开放。开通会员后即可查看更长周期。"
    @join="goJoinVip"
  />
</template>

<style scoped>
.price-login-tip {
  margin-left: 2px;
  font-size: 11px;
  font-weight: 700;
  color: #fff;
  background: rgba(255, 255, 255, 0.22);
  padding: 3px 10px;
  border-radius: 100px;
  text-decoration: none;
  line-height: 1.3;
}
.price-login-tip:hover {
  background: rgba(255, 255, 255, 0.34);
  color: #fff;
}
.li-empty {
  margin: 0;
  padding: 18px 4px;
  color: var(--ink-3, #8a93a3);
  font-size: 13px;
  text-align: center;
}
.tc-head-right {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-left: auto;
}
.yr-switch {
  position: relative;
  display: inline-flex;
  z-index: 6;
}
.yr {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: var(--blue, #2468f2);
  background: #eaf1fe;
  padding: 3px 11px;
  border-radius: 100px;
  font-weight: 600;
  border: 0;
  cursor: pointer;
  font-family: inherit;
  line-height: 1.4;
}
.yr .chev {
  font-size: 10px;
  line-height: 1;
  transition: transform 0.15s;
}
.yr .chev.open {
  transform: rotate(180deg);
}
.yr-drop {
  position: absolute;
  top: calc(100% + 6px);
  right: 0;
  min-width: 96px;
  background: #fff;
  border: 1.5px solid #d7e2f5;
  border-radius: 10px;
  box-shadow: 0 12px 28px rgba(20, 45, 90, 0.16);
  padding: 4px;
  z-index: 20;
}
.yr-drop button {
  display: block;
  width: 100%;
  text-align: left;
  border: 0;
  background: transparent;
  padding: 7px 10px;
  border-radius: 7px;
  font: inherit;
  font-size: 12.5px;
  font-weight: 600;
  color: #1f2a44;
  cursor: pointer;
}
.yr-drop button:hover {
  background: #f7faff;
}
.yr-drop button.on {
  background: #eef4ff;
  color: #2468f2;
}
.chart-mode {
  display: inline-flex;
  align-items: center;
  gap: 0;
  padding: 2px;
  border-radius: 8px;
  background: #eef2f8;
  border: 1px solid #e1e8f4;
}
.chart-mode button {
  height: 26px;
  padding: 0 10px;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: #6b7a94;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  line-height: 1;
}
.chart-mode button.on {
  background: #fff;
  color: #2468f2;
  box-shadow: 0 1px 4px rgba(36, 104, 242, 0.16);
}
.qp-switch {
  position: relative;
  display: inline-flex;
  z-index: 8;
}
.qc-btns button {
  font-size: 11px;
  background: rgba(255, 255, 255, 0.18);
  padding: 3px 11px;
  border-radius: 100px;
  color: #fff;
  border: 0;
  cursor: pointer;
  font-family: inherit;
  line-height: 1.4;
}
.qc-btns button:hover {
  background: rgba(255, 255, 255, 0.28);
}
.qc-btns button:disabled {
  opacity: 0.6;
  cursor: wait;
}
.qc-tip {
  position: absolute;
  top: 40px;
  right: 16px;
  z-index: 12;
  padding: 6px 10px;
  border-radius: 8px;
  background: rgba(15, 28, 52, 0.88);
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  box-shadow: 0 8px 20px rgba(15, 35, 70, 0.25);
  pointer-events: none;
}
.qp-switch-btn {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  font: inherit;
}
.qp-switch-btn .chev {
  font-size: 10px;
  line-height: 1;
  transition: transform 0.15s;
}
.qp-switch-btn .chev.open {
  transform: rotate(180deg);
}
.qp-drop {
  position: absolute;
  top: calc(100% + 8px);
  left: 0;
  width: min(360px, 82vw);
  background: #fff;
  color: var(--ink);
  border-radius: 12px;
  border: 1.5px solid #d7e2f5;
  box-shadow: 0 16px 36px rgba(20, 45, 90, 0.18);
  overflow: hidden;
  z-index: 30;
  animation: qpDropIn 0.14s ease-out;
}
@keyframes qpDropIn {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}
.qp-drop-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 8px;
  padding: 10px 12px 8px;
  border-bottom: 1px solid #eef2f8;
}
.qp-drop-head b {
  font-size: 13px;
  font-weight: 800;
  color: #1f2a44;
}
.qp-drop-head span {
  font-size: 11.5px;
  color: #8a97b0;
}
.qp-drop-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 6px;
  max-height: 280px;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 8px;
  overscroll-behavior: contain;
}
.qp-drop-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
  padding: 8px 10px;
  border: 1px solid #e8eef8;
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
  font: inherit;
  text-align: left;
  transition: border-color 0.15s, background 0.15s;
}
.qp-drop-item:hover {
  border-color: #9bb6eb;
  background: #f7faff;
}
.qp-drop-item.on {
  border-color: var(--blue, #2468f2);
  background: #eef4ff;
  box-shadow: inset 0 0 0 1px rgba(36, 104, 242, 0.12);
}
.qp-drop-item .n {
  font-size: 12px;
  font-weight: 700;
  color: #243356;
  line-height: 1.3;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.qp-drop-item .p {
  font-size: 13px;
  font-weight: 800;
  color: var(--blue, #2468f2);
  font-variant-numeric: tabular-nums;
}
</style>

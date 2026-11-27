<script setup lang="ts">
const props = defineProps<{
  products: Array<{
    id?: number | string
    name: string
    unit?: string
    price_avg?: string | number | null
    price_min?: string | number | null
    price_max?: string | number | null
    market?: string
    quoted_on?: string
    series?: Array<{
      date?: string
      label?: string
      price_avg?: string | number | null
      price_min?: string | number | null
      price_max?: string | number | null
    }>
  }>
}>()

import { memberHistoryDays, monthInsideHistory, quoteYearOptions, yearInsideHistory } from '~/utils/memberTier'

const { actionTip, favBusy, shareQuote, favoriteQuote } = useQuoteCardActions()
const { user, token, fetchMe } = useAuth()
const priceRevealed = computed(() => Boolean(token.value))
const currentYear = computed(() => new Date().getFullYear())
/** 选中的月份超出当前档可看天数时，走势和读数不放开 */
const historyLocked = computed(() => priceRevealed.value && !monthAllowed())
const showPrice = computed(() => priceRevealed.value && !historyLocked.value)

const active = ref(0)
const monthTab = ref(0)
const chartMode = ref<'line' | 'candle'>('line')
const chartYear = ref(new Date().getFullYear())
const yearOpen = ref(false)
const yearWrap = ref<HTMLElement | null>(null)
const vipGateOpen = ref(false)
const months = computed(() => {
  const now = new Date()
  const last = chartYear.value < now.getFullYear() ? 12 : now.getMonth() + 1
  return Array.from({ length: last }, (_, i) => `${last - i}月`)
})
watch(months, (list) => {
  if (monthTab.value > list.length - 1) monthTab.value = 0
})
const list = computed(() => (props.products || []).filter((p) => p?.name).slice(0, 15))
const liveById = ref<Record<string, any>>({})
const { quoteApi } = useQuoteApi()
const historyDays = computed(() => {
  if (!token.value) return 0
  const sample = Object.values(liveById.value)[0] as { history_days_limit?: number } | undefined
  return memberHistoryDays(user.value, Number(sample?.history_days_limit || 0)) || 7
})
/** 前台：2022～今年（不必对齐后台 2016） */
const yearOptions = computed(() => {
  const extra: number[] = []
  const cur = list.value[active.value]
  const src = cur ? (liveById.value[String(cur.id)] || cur) : null
  for (const row of (Array.isArray(src?.series) ? src.series : [])) {
    const y = Number(String(row?.date || '').slice(0, 4))
    if (y > 0) extra.push(y)
  }
  return quoteYearOptions(extra)
})

function sourceOf(p: any) {
  if (!p) return p
  const live = liveById.value[String(p.id)]
  if (!live) return p
  return { ...p, ...live, name: p.name || live.name, unit: p.unit || live.unit }
}

const detailRegions = ref<any[]>([])
let regionReq = 0

async function loadLiveQuotes() {
  if (!token.value) {
    liveById.value = {}
    return
  }
  try {
    const res = await quoteApi<any>('/quotes', { query: { page: 1, per_page: 30 }, auth: true })
    const rows = Array.isArray(res?.data) ? res.data : []
    const map: Record<string, any> = {}
    for (const row of rows) {
      if (row?.id != null) map[String(row.id)] = row
    }
    liveById.value = map
  } catch {
    liveById.value = {}
  }
}

async function loadRegions() {
  const id = current.value?.id
  if (!import.meta.client || !token.value || id == null || String(id).startsWith('q')) {
    detailRegions.value = []
    return
  }
  const seq = ++regionReq
  try {
    const res = await quoteApi<any>(`/quotes/${id}`, { query: { days: historyDays.value }, auth: true })
    if (seq !== regionReq) return
    detailRegions.value = Array.isArray(res?.data?.regions) ? res.data.regions : []
  } catch {
    if (seq === regionReq) detailRegions.value = []
  }
}

watch(list, (v) => {
  if (active.value >= v.length) active.value = 0
}, { immediate: true })

const current = computed(() => list.value[active.value] || null)
/** 概览卡始终是品种均价；库里旧「华东」等市场字段不当作价种标签 */
const priceScopeLabel = computed(() => {
  const m = String(sourceOf(current.value)?.market || current.value?.market || '').trim()
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

/** 未登录用首页公开的 7 天；登录后按会员档的可看天数过滤。以最新有价日为终点。 */
function quoteWindowStart(days: number, end = new Date()) {
  const d = new Date(end)
  d.setHours(0, 0, 0, 0)
  d.setDate(d.getDate() - Math.max(0, days - 1))
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${d.getFullYear()}-${m}-${day}`
}

function storedSeries(p: { id?: string | number; series?: Array<{ date?: string; label?: string; price_avg?: string | number | null; price_min?: string | number | null; price_max?: string | number | null }> } | null | undefined) {
  const src = sourceOf(p)
  const rows = Array.isArray(src?.series) ? src.series : []
  const priced = rows.filter((row) => num(row?.price_avg, 0) > 0)
  if (!priced.length) return priced
  const last = String(priced[priced.length - 1]?.date || '')
  const end = /^\d{4}-\d{2}-\d{2}$/.test(last) ? new Date(`${last}T12:00:00`) : new Date()
  // 游客对齐首页公开 7 天；登录按会员可看天数。都以最新有价日为终点。
  const windowDays = token.value ? Math.max(historyDays.value || 7, 1) : 7
  const from = quoteWindowStart(windowDays, end)
  return priced.filter((row) => String(row?.date || '') >= from)
}

function monthFromTab(index: number) {
  return parseInt(String(months.value[index] || ''), 10) || 0
}

function monthAllowed(index = monthTab.value) {
  if (!token.value || historyDays.value < 1) return false
  return monthInsideHistory(chartYear.value, monthFromTab(index), historyDays.value)
}

function candlesFrom(rows: Array<{ date?: string; label?: string; price_avg?: string | number | null; price_min?: string | number | null; price_max?: string | number | null }>) {
  return rows.map((row, i, arr) => {
    const close = num(row.price_avg)
    const open = i === 0 ? close : num(arr[i - 1].price_avg, close)
    const highRaw = num(row.price_max, 0)
    const lowRaw = num(row.price_min, 0)
    const date = String(row.date || '')
    return {
      label: String(row.label || date.slice(5)),
      open,
      high: highRaw > 0 ? Math.max(highRaw, open, close) : Math.max(open, close),
      low: lowRaw > 0 ? Math.min(lowRaw, open, close) : Math.min(open, close),
      close,
    }
  })
}

const series = computed(() => {
  const month = monthFromTab(monthTab.value)
  const rows = storedSeries(current.value).filter((row) => {
    const date = String(row.date)
    return Number(date.slice(0, 4)) === chartYear.value && Number(date.slice(5, 7)) === month
  })
  return candlesFrom(rows)
})

/** 落到指定年里有数据的最近月份；不改年份（避免和用户点选抢状态） */
function syncMonthForYear(year = chartYear.value) {
  const priced = storedSeries(current.value)
  const rows = priced.filter((row) => Number(String(row.date).slice(0, 4)) === year)
  if (!rows.length) return
  const month = Number(String(rows[rows.length - 1].date).slice(5, 7))
  const idx = months.value.findIndex((label) => parseInt(label, 10) === month)
  if (idx >= 0 && monthFromTab(monthTab.value) !== month) monthTab.value = idx
}

/** 换品种（含首屏等到数据）时跟到最新有价年；之后刷新/用户点选年份不再抢回 */
let syncedProductKey = ''
let pendingYearSnap = true
watch([current, liveById, historyDays], () => {
  const key = String(current.value?.id ?? current.value?.name ?? '')
  if (key !== syncedProductKey) {
    syncedProductKey = key
    pendingYearSnap = true
  }
  const priced = storedSeries(current.value)
  if (!priced.length) {
    if (!pendingYearSnap) return
    const qon = String(sourceOf(current.value)?.quoted_on || '').slice(0, 10)
    if (/^\d{4}-\d{2}-\d{2}$/.test(qon)) {
      const y = Number(qon.slice(0, 4))
      const month = Number(qon.slice(5, 7))
      if (y && y !== chartYear.value && y <= currentYear.value) chartYear.value = y
      const idx = months.value.findIndex((label) => parseInt(label, 10) === month)
      if (idx >= 0) monthTab.value = idx
    }
    return
  }
  if (pendingYearSnap) {
    const last = String(priced[priced.length - 1].date || '')
    const y = Number(last.slice(0, 4))
    if (y && y !== chartYear.value && y <= currentYear.value) chartYear.value = y
    syncMonthForYear(chartYear.value)
    pendingYearSnap = false
  }
}, { immediate: true })

const latestRow = computed(() => {
  const rows = storedSeries(current.value)
  return rows.length ? rows[rows.length - 1] : null
})
const prevRow = computed(() => {
  const rows = storedSeries(current.value)
  return rows.length >= 2 ? rows[rows.length - 2] : null
})
function positive(v: unknown, fallback = 0) {
  const n = Number(v)
  return Number.isFinite(n) && n > 0 ? n : fallback
}
const latest = computed(() => {
  const src = sourceOf(current.value)
  return positive(src?.price_avg, positive(latestRow.value?.price_avg, 0))
})
const prev = computed(() => num(prevRow.value?.price_avg, latest.value))
const dayDiff = computed(() => Number((latest.value - prev.value).toFixed(2)))
const dayPct = computed(() => {
  if (!prev.value) return 0
  return Number((((latest.value - prev.value) / prev.value) * 100).toFixed(2))
})

const open = computed(() => prev.value)
const high = computed(() => positive(latestRow.value?.price_max, positive(sourceOf(current.value)?.price_max, latest.value)))
const low = computed(() => positive(latestRow.value?.price_min, positive(sourceOf(current.value)?.price_min, latest.value)))
const prevClose = computed(() => prev.value)

const related = computed(() => {
  // 含当前品种，第一项高亮，与设计稿一致
  const rest = list.value.filter((_, i) => i !== active.value).slice(0, 2)
  const cur = current.value
  const rows = cur ? [cur, ...rest] : rest
  return rows.slice(0, 3).map((p) => sourceOf(p))
})

watch([() => current.value?.id, token, historyDays], () => {
  void loadRegions()
}, { immediate: true })

const regions = computed(() => {
  const src = sourceOf(current.value)
  const list = detailRegions.value.length
    ? detailRegions.value
    : (Array.isArray(src?.regions) ? src.regions : [])
  return list.map((r) => {
    const price = num(r.price, 0)
    const prev = num(r.prev, price)
    const diff = Number((price - prev).toFixed(2))
    const pct = prev ? Number(((diff / prev) * 100).toFixed(2)) : 0
    return { name: String(r.name || ''), price, diff, pct }
  }).filter((r) => r.name && r.price > 0)
})

const showQuoteTools = false
const tools = [
  { label: '价格提醒', hint: '涨跌推送', to: '/quotes', grad: 'var(--grad-blue)', icon: 'bell' },
  { label: '价格走势', hint: '日内趋势', to: '/quotes', grad: 'linear-gradient(135deg,#F59E0B,#F97316)', icon: 'trend' },
  { label: 'K线图', hint: '技术分析', to: '/quotes', grad: 'linear-gradient(135deg,#06B6D4,#3B82F6)', icon: 'candle' },
  { label: '区域价格', hint: '地区对比', to: '/quotes', grad: 'linear-gradient(135deg,#10B981,#34D399)', icon: 'map' },
  { label: '关联品种', hint: '相关行情', to: '/quotes', grad: 'linear-gradient(135deg,#8B5CF6,#A78BFA)', icon: 'link' },
  { label: '历史价格', hint: '价格查询', to: '/quotes', grad: 'linear-gradient(135deg,#F04B3E,#FB7185)', icon: 'hist' },
]

function regionLink(name: string) {
  const pid = current.value?.id
  if (!pid) return '/quotes'
  return `/quotes/${pid}?region=${encodeURIComponent(name)}`
}
const regionCards = computed(() => regions.value.slice(0, 4))
const regionMore = computed(() => (regions.value[0] ? regionLink(regions.value[0].name) : '/quotes'))

function fmt(n: number) {
  if (historyLocked.value || !(latest.value > 0)) return '*****'
  if (!priceRevealed.value) return '****'
  return n > 0 ? `+${n}` : String(n)
}

function fmtPrice(n: number) {
  if (historyLocked.value || !(n > 0)) return '*****'
  return formatQuotePrice(n, priceRevealed.value)
}

function fmtPct(n: number) {
  if (historyLocked.value || !(latest.value > 0)) return '*****'
  if (!priceRevealed.value) return '**%'
  const t = n > 0 ? `+${n}` : String(n)
  return `${t}%`
}

function displayPrice(p: { id?: string | number; price_avg?: string | number | null } | null | undefined) {
  return positive(sourceOf(p)?.price_avg, 0)
}

function relChange(p: { id?: string | number; series?: Array<{ price_avg?: string | number | null }> }) {
  const rows = storedSeries(p)
  if (rows.length < 2) return { diff: 0, pct: 0 }
  const close = num(rows[rows.length - 1]?.price_avg)
  const open = num(rows[rows.length - 2]?.price_avg, close)
  const diff = Number((close - open).toFixed(2))
  const pct = open ? Number(((diff / open) * 100).toFixed(2)) : 0
  return { diff, pct }
}

const quotePickerOpen = ref(false)
const quotePickerWrap = ref<HTMLElement | null>(null)
const quotePickerBtn = ref<HTMLElement | null>(null)
const dropStyle = ref<Record<string, string>>({})

function placeDrop() {
  if (!import.meta.client || !quotePickerBtn.value) return
  const r = quotePickerBtn.value.getBoundingClientRect()
  const w = Math.min(360, window.innerWidth - 16)
  let left = r.left
  if (left + w > window.innerWidth - 8) left = Math.max(8, window.innerWidth - w - 8)
  dropStyle.value = {
    position: 'fixed',
    top: `${Math.round(r.bottom + 8)}px`,
    left: `${Math.round(left)}px`,
    width: `${w}px`,
    zIndex: '80',
  }
}

function toggleQuotePicker() {
  quotePickerOpen.value = !quotePickerOpen.value
  if (quotePickerOpen.value) nextTick(() => placeDrop())
}
function closeQuotePicker() {
  quotePickerOpen.value = false
}
function pickActive(i: number) {
  if (i < 0 || i >= list.value.length) return
  active.value = i
  quotePickerOpen.value = false
}
function onQuotePickerDocClick(e: MouseEvent) {
  if (!quotePickerOpen.value) return
  const el = quotePickerWrap.value
  const t = e.target as Node
  if (el && el.contains(t)) return
  const drop = document.getElementById('qp-drop-portal')
  if (drop && drop.contains(t)) return
  closeQuotePicker()
}
function onQuotePickerKey(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    closeQuotePicker()
    yearOpen.value = false
  }
}
function onQuotePickerScroll() {
  if (quotePickerOpen.value) placeDrop()
  if (yearOpen.value) placeYearDrop()
}
const yearBtn = ref<HTMLElement | null>(null)
const yearDropStyle = ref<Record<string, string>>({})
function placeYearDrop() {
  if (!import.meta.client || !yearBtn.value) return
  const r = yearBtn.value.getBoundingClientRect()
  const w = 112
  const maxH = 320
  let left = r.right - w
  if (left < 8) left = 8
  if (left + w > window.innerWidth - 8) left = Math.max(8, window.innerWidth - w - 8)
  const below = window.innerHeight - r.bottom - 12
  const above = r.top - 12
  const openUp = below < 200 && above > below
  const h = Math.min(maxH, Math.max(140, openUp ? above : below))
  yearDropStyle.value = {
    position: 'fixed',
    top: openUp ? `${Math.round(r.top - 8 - h)}px` : `${Math.round(r.bottom + 8)}px`,
    left: `${Math.round(left)}px`,
    width: `${w}px`,
    maxHeight: `${h}px`,
    zIndex: '90',
  }
}
function toggleYear() {
  yearOpen.value = !yearOpen.value
  if (yearOpen.value) {
    quotePickerOpen.value = false
    nextTick(() => placeYearDrop())
  }
}
function pickMonth(index: number) {
  monthTab.value = index
}
function pickYear(y: number) {
  yearOpen.value = false
  if (y === chartYear.value) return
  if (!token.value) {
    navigateTo({ path: '/login', query: { redirect: '/' } })
    return
  }
  if (!yearInsideHistory(y, historyDays.value)) {
    vipGateOpen.value = true
    return
  }
  chartYear.value = y
  syncMonthForYear(y)
}
function goRecharge() {
  if (!user.value) {
    navigateTo({ path: '/login', query: { redirect: '/member/grade' } })
    return
  }
  navigateTo('/member/grade')
}
function onYearDocClick(e: MouseEvent) {
  if (!yearOpen.value) return
  const el = yearWrap.value
  const t = e.target as Node
  if (el && el.contains(t)) return
  const drop = document.getElementById('yr-drop-portal-home')
  if (drop && drop.contains(t)) return
  yearOpen.value = false
}

watch(token, () => {
  void loadLiveQuotes()
})

onMounted(() => {
  if (!import.meta.client) return
  void fetchMe().finally(() => {
    void loadLiveQuotes()
    void loadRegions()
  })
  document.addEventListener('click', onQuotePickerDocClick, true)
  document.addEventListener('click', onYearDocClick, true)
  document.addEventListener('keydown', onQuotePickerKey)
  window.addEventListener('scroll', onQuotePickerScroll, true)
  window.addEventListener('resize', onQuotePickerScroll)
})
onBeforeUnmount(() => {
  if (!import.meta.client) return
  document.removeEventListener('click', onQuotePickerDocClick, true)
  document.removeEventListener('click', onYearDocClick, true)
  document.removeEventListener('keydown', onQuotePickerKey)
  window.removeEventListener('scroll', onQuotePickerScroll, true)
  window.removeEventListener('resize', onQuotePickerScroll)
})
</script>

<template>
  <section v-if="list.length && current" id="quote" class="section">
    <div class="sec-head">
      <div>
        <h2>价格行情</h2>
        <p class="sub">每日更新的有机硅主流产品报价与走势</p>
      </div>
      <NuxtLink class="more-link" to="/quotes">查看全部行情 ›</NuxtLink>
    </div>

    <div class="quote-block">
    <div class="quote-top">
      <div class="quote-card">
        <div class="qc-top">
          <div class="qc-name">{{ quoteName.main }}<template v-if="quoteName.alias"><br>{{ quoteName.alias }}</template></div>
          <div class="qc-btns">
            <button type="button" :disabled="favBusy" @click.stop="favoriteQuote(current)">收藏</button>
            <button type="button" @click.stop="shareQuote(current)">分享</button>
          </div>
          <div v-if="actionTip" class="qc-tip">{{ actionTip }}</div>
        </div>
        <div class="qc-tags">
          <span class="tg">均价</span>
          <div ref="quotePickerWrap" class="qp-switch">
            <button
              ref="quotePickerBtn"
              type="button"
              class="tg qp-switch-btn"
              :aria-expanded="quotePickerOpen"
              aria-haspopup="listbox"
              @click.stop="toggleQuotePicker"
            >
              ⇄ 切换品种
              <span class="chev" :class="{ open: quotePickerOpen }" aria-hidden="true">▾</span>
            </button>
            <Teleport to="body">
              <div
                v-show="quotePickerOpen"
                id="qp-drop-portal"
                class="qp-drop"
                role="listbox"
                aria-label="选择行情品种"
                :style="dropStyle"
              >
                <div class="qp-drop-head">
                  <b>选择品种</b>
                  <span>共 {{ list.length }} 个</span>
                </div>
                <div class="qp-drop-grid">
                  <button
                    v-for="(p, i) in list"
                    :key="p.id || p.name"
                    type="button"
                    class="qp-drop-item"
                    role="option"
                    :aria-selected="i === active"
                    :class="{ on: i === active }"
                    @click.stop="pickActive(i)"
                  >
                    <span class="n">{{ p.name }}</span>
                    <span class="p num">{{ fmtPrice(displayPrice(p)) }}</span>
                  </button>
                </div>
              </div>
            </Teleport>
          </div>
        </div>
        <div class="qc-price">
          <b class="num">{{ fmtPrice(latest) }}</b>
          <small v-if="showPrice">{{ current.unit || '元/吨' }}</small>
          <NuxtLink v-if="!priceRevealed" class="price-login-tip" to="/login?redirect=/">登录查看</NuxtLink>
        </div>
        <div class="qc-chg" :class="{ up: showPrice && dayDiff > 0, down: showPrice && dayDiff < 0 }">
          <span class="num">{{ fmt(dayDiff) }}</span>
          <span class="num">{{ fmtPct(dayPct) }}</span>
        </div>
        <div class="qc-time">
          更新于 {{ String(sourceOf(current)?.quoted_on || current.quoted_on || '').slice(0, 10) || '今日' }}
          · {{ priceScopeLabel }}
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

      <div class="trend-card">
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
                ref="yearBtn"
                type="button"
                class="yr"
                :aria-expanded="yearOpen"
                aria-haspopup="listbox"
                @click.stop="toggleYear"
              >{{ chartYear }}年 <span class="chev" :class="{ open: yearOpen }" aria-hidden="true">▾</span></button>
              <Teleport to="body">
                <div
                  v-show="yearOpen"
                  id="yr-drop-portal-home"
                  class="yr-drop"
                  role="listbox"
                  aria-label="选择年份"
                  :style="yearDropStyle"
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
              </Teleport>
            </div>
          </div>
        </div>
        <div class="month-tabs">
          <button
            v-for="(m, i) in months"
            :key="m"
            type="button"
            :class="{ on: monthTab === i }"
            @click="pickMonth(i)"
          >{{ m }}</button>
        </div>
        <QuoteKlineChart
          v-if="series.length && monthAllowed()"
          :candles="series"
          :mode="chartMode"
          :revealed="showPrice"
          :aria-label="`${current.name}${chartMode === 'candle' ? 'K线' : '折线'}走势`"
        />
        <QuoteKlineChart
          v-else-if="!monthAllowed()"
          :candles="[]"
          :mode="chartMode"
          :revealed="false"
          :gate="priceRevealed ? 'vip' : 'login'"
          login-redirect="/"
          :aria-label="`${current?.name || ''}行情走势`"
          @upgrade="goRecharge"
        />
        <p v-else class="empty" style="padding:20px 0;text-align:center;color:var(--ink-3)">该月暂无走势数据</p>
      </div>
    </div>

    <div class="quote-mid">
      <div class="related">
        <div class="r-head">
          <b>关联品种</b>
          <NuxtLink class="more-link" to="/quotes" style="font-size:12.5px">更多 ›</NuxtLink>
        </div>
        <div class="r-grid">
          <a
            v-for="(p, i) in related"
            :key="p.id || p.name"
            class="r-item"
            :class="{ on: p.name === current.name }"
            href="#"
            @click.prevent="pickActive(list.findIndex((x) => x.name === p.name))"
          >
            <div class="n">{{ p.name }}</div>
            <div class="p num">{{ fmtPrice(displayPrice(p)) }}</div>
            <div
              class="c num"
              :class="showPrice && displayPrice(p) > 0 && relChange(p).pct >= 0 ? 'up' : (showPrice && displayPrice(p) > 0 ? 'down' : '')"
            >{{ historyLocked || !(displayPrice(p) > 0) ? '*****' : `${fmt(relChange(p).diff)} ${fmtPct(relChange(p).pct)}` }}</div>
          </a>
        </div>
      </div>
      <div class="region">
        <div class="r-head">
          <b>区域价格</b>
          <NuxtLink class="more-link" :to="regionMore" style="font-size:12.5px">更多 ›</NuxtLink>
        </div>
        <div v-if="regionCards.length" class="rg-grid">
          <NuxtLink v-for="r in regionCards" :key="r.name" class="rg" :to="regionLink(r.name)">
            <div class="n">{{ r.name }}</div>
            <div class="p num">{{ fmtPrice(r.price) }}<small v-if="showPrice">元/吨</small></div>
            <div class="c num" :class="{ up: showPrice && r.diff > 0, down: showPrice && r.diff < 0 }">
              {{ historyLocked ? '*****' : `${fmt(r.diff)} ${fmtPct(r.pct)}` }}
            </div>
          </NuxtLink>
        </div>
        <p v-else class="region-empty">{{ priceRevealed ? '暂无区域价格' : '登录后查看区域价格' }}</p>
      </div>
    </div>

    <div v-if="showQuoteTools" class="tools">
      <NuxtLink v-for="t in tools" :key="t.label" :to="t.to" class="tool">
        <span class="ic" :style="{ background: t.grad }">
          <svg v-if="t.icon === 'bell'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
          <svg v-else-if="t.icon === 'trend'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 17l5-5 4 4 7-7"/><path d="M14 9h5v5"/></svg>
          <svg v-else-if="t.icon === 'candle'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 5v14M16 5v14M8 9h8M8 15h8"/></svg>
          <svg v-else-if="t.icon === 'map'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-5.5 7-11a7 7 0 10-14 0c0 5.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>
          <svg v-else-if="t.icon === 'link'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 007.5.5l3-3a5 5 0 00-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 00-7.5-.5l-3 3a5 5 0 007 7l1.7-1.7"/></svg>
          <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
        </span>
        <span>
          <b>{{ t.label }}</b>
          <span>{{ t.hint }}</span>
        </span>
      </NuxtLink>
    </div>
    </div>
  </section>

  <VipGateModal
    v-model="vipGateOpen"
    title="加入会员后查阅"
    message="这个年份超出当前会员可看的行情天数。普通会员 7 天，白银 1 年，黄金 2 年，星钻 5 年。"
    @join="goRecharge"
  />
</template>

<style scoped>
.region-empty {
  margin: 18px 0 8px;
  color: var(--ink-3);
  font-size: 13px;
  text-align: center;
}
.qc-btns button {
  font-size: 11.5px;
  background: rgba(255, 255, 255, 0.18);
  padding: 3px 12px;
  border-radius: 100px;
  color: #fff;
  border: 0;
  cursor: pointer;
  font-family: inherit;
}
.qc-price {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 6px 8px;
}
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
.qc-btns button:hover {
  background: rgba(255, 255, 255, 0.28);
}
.qc-btns button:disabled {
  opacity: 0.6;
  cursor: wait;
}
.qc-tip {
  position: absolute;
  top: 42px;
  right: 18px;
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
.tool {
  text-decoration: none;
  color: inherit;
}
.r-item {
  text-decoration: none;
  color: inherit;
  cursor: pointer;
  display: block;
  border: 0;
  text-align: left;
}
.month-tabs button {
  cursor: pointer;
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
  min-width: 96px;
  background: #fff;
  border: 1.5px solid #d7e2f5;
  border-radius: 10px;
  box-shadow: 0 12px 28px rgba(20, 45, 90, 0.16);
  padding: 4px;
  overflow-x: hidden;
  overflow-y: auto;
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch;
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
  background: #fff;
  color: var(--ink);
  border-radius: 12px;
  border: 1.5px solid #d7e2f5;
  box-shadow: 0 16px 36px rgba(20, 45, 90, 0.18);
  overflow: hidden;
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

<script setup lang="ts">
definePageMeta({ layout: 'channel' })

import { memberHistoryDays, quoteWindowStart } from '~/utils/memberTier'

const route = useRoute()
const { quoteApi, token } = useQuoteApi()
const { user, fetchMe } = useAuth()

const id = computed(() => String(route.params.id || ''))
const regionName = computed(() => String(route.query.region || ''))

type RangeKey = '7d' | '1m' | '3m' | '6m' | '1y' | '2y' | '5y'
type Candle = { label: string; date?: string; open: number; high: number; low: number; close: number }
const RANGE_OPTS: Array<{ key: RangeKey; label: string; days: number; title: string }> = [
  { key: '7d', label: '7日', days: 7, title: '近 7 日' },
  { key: '1m', label: '1月', days: 30, title: '近 1 月' },
  { key: '3m', label: '3月', days: 90, title: '近 3 月' },
  { key: '6m', label: '半年', days: 180, title: '近半年' },
  { key: '1y', label: '一年', days: 365, title: '近一年' },
  { key: '2y', label: '两年', days: 730, title: '近两年' },
  { key: '5y', label: '五年', days: 1825, title: '近五年' },
]

const rangeKey = ref<RangeKey>('7d')
const rangePicked = ref(false)
/** 走势图 / 历史报价共用的自定义日期区间（与快捷 Tab 互斥） */
const customActive = ref(false)
const dateFrom = ref('')
const dateTo = ref('')
const switchOpen = ref(false)
const switchWrap = ref<HTMLElement | null>(null)
const regionWrap = ref<HTMLElement | null>(null)
const chartMode = ref<'line' | 'candle'>('line')
/** 两年/五年点太密，强制折线、不给切 K 线（圆点在图表组件里也会关掉） */
const forceLineOnly = computed(() => {
  if (customActive.value) return selectedDays.value >= 700
  return rangeKey.value === '2y' || rangeKey.value === '5y'
})
const effectiveChartMode = computed<'line' | 'candle'>(() =>
  forceLineOnly.value || activeRegion.value ? 'line' : chartMode.value,
)
const chartExpandOpen = ref(false)

function daysBetween(from: string, to: string) {
  const a = Date.parse(String(from).replace(/-/g, '/'))
  const b = Date.parse(String(to).replace(/-/g, '/'))
  if (!Number.isFinite(a) || !Number.isFinite(b) || b < a) return 0
  return Math.round((b - a) / 86400000) + 1
}

const customSpanDays = computed(() => {
  if (!dateFrom.value || !dateTo.value) return 0
  return daysBetween(dateFrom.value, dateTo.value)
})

const selectedDays = computed(() => {
  if (customActive.value && customSpanDays.value > 0) return customSpanDays.value
  return RANGE_OPTS.find((r) => r.key === rangeKey.value)?.days || 7
})
const rangeTitle = computed(() => {
  if (customActive.value && dateFrom.value && dateTo.value) {
    return `${dateFrom.value} ~ ${dateTo.value}`
  }
  return RANGE_OPTS.find((r) => r.key === rangeKey.value)?.title || '近 7 日'
})
/** 3 月及以上：总览板式 +「详情展开」横滑细看（与半年/一年一致） */
const canExpandChart = computed(() => selectedDays.value >= 90)

const allowance = computed(() => (token.value ? memberHistoryDays(user.value) : 0))
watch(allowance, (days) => {
  if (rangePicked.value || customActive.value) return
  rangeKey.value = days > 7 ? '1m' : '7d'
}, { immediate: true })

/** 按会员可看上限整段拉取；图上 7日/两年 等只在前端裁切，避免切区间时标题与数据脱节 */
const fetchDays = computed(() => {
  if (!token.value) return 7
  return Math.max(allowance.value || 0, selectedDays.value, 7)
})

/** 历史报价日期排序：由接口 SQL order 返回（desc 新→旧 / asc 旧→新），前端不再自行翻转 */
const histSort = ref<'desc' | 'asc'>('desc')

const { data, error, pending } = await useAsyncData(
  () => `quote-${id.value}-${regionName.value}-${fetchDays.value}-${histSort.value}-${token.value ? 'u' : 'g'}`,
  async () => {
    await fetchMe()
    const days = token.value ? fetchDays.value : 7
    return quoteApi<any>(`/quotes/${id.value}`, {
      query: {
        days,
        region: regionName.value || undefined,
        sort: histSort.value,
      },
      auth: Boolean(token.value),
    })
  },
  { watch: [id, regionName, fetchDays, histSort, token], server: false },
)

const { data: listRes } = await useAsyncData(
  'quote-switch-list',
  () => quoteApi<any>('/quotes', { auth: Boolean(token.value) }),
  { server: false, watch: [token] },
)

const item = computed(() => (data.value as any)?.data)
const regions = computed(() => (item.value?.regions || []) as Array<{ name: string; price?: number }>)
type QuoteAd = {
  title?: string
  image?: string
  link?: string
  content?: string | null
  price?: string | null
  company?: string | null
  vip_grade?: string | null
} | null
type QuoteAdSlot = { slot: number; code: string; label: string; ad?: QuoteAd }

const adSlots = computed(() => {
  const raw = item.value?.ad_slots
  if (Array.isArray(raw) && raw.length) return raw as QuoteAdSlot[]
  const pid = item.value?.id
  if (!pid) return []
  const name = String(item.value?.name || '')
  const alias = name.match(/[（(]([^）)]+)[）)]/)
  const label = alias ? alias[1] : (name || '行情')
  return [1, 2, 3].map((slot) => ({ slot, code: `quote_${pid}_${slot}`, label, ad: null }))
})
const adHref = (slot: QuoteAdSlot) => {
  const link = String(slot?.ad?.link || '').trim()
  return link || `/ads?place=${encodeURIComponent(slot.code || '')}`
}
const adExternal = (slot: QuoteAdSlot) => /^https?:\/\//i.test(adHref(slot))
const adTitle = (slot: QuoteAdSlot) => {
  const t = String(slot.ad?.title || '').trim()
  if (t) return t
  return '广告位 · 虚位以待'
}
const adCompany = (slot: QuoteAdSlot) => {
  const co = String(slot.ad?.company || slot.ad?.content || '').trim()
  if (co && !/^origin:/i.test(co)) return co.length > 14 ? co.slice(0, 14) + '...' : co
  return slot.ad ? '会员商家' : 'VIP 会员优先展示'
}
const adPriceText = (slot: QuoteAdSlot) => {
  const p = slot.ad?.price
  if (p != null && String(p).trim() !== '') {
    const n = Number(p)
    return Number.isFinite(n) ? `¥${money(n)}` : `¥${p}`
  }
  const avg = productAvg.value
  if (avg != null && avg !== '') return `¥${money(avg)}`
  return '面议'
}
const adVipSrc = (slot: QuoteAdSlot) => {
  const code = String(slot.ad?.vip_grade || (slot.ad ? 'gold' : 'normal')).toLowerCase()
  if (code === 'diamond') return '/img/member/diamond.png'
  if (code === 'silver') return '/img/member/silver.png'
  if (code === 'gold') return '/img/member/gold.png'
  return '/img/member/normal.png'
}
const adVipLabel = (slot: QuoteAdSlot) => {
  const code = String(slot.ad?.vip_grade || (slot.ad ? 'gold' : 'normal')).toLowerCase()
  if (code === 'diamond') return '星钻会员'
  if (code === 'silver') return '白银会员'
  if (code === 'gold') return '黄金会员'
  return '普通会员'
}
const productAvg = computed(() => {
  const own = item.value?.product_price_avg
  if (own != null && own !== '') return own
  const fromList = switchList.value.find((p) => String(p.id) === id.value)
  if (fromList?.price_avg != null && fromList.price_avg !== '') return fromList.price_avg
  return activeRegion.value ? null : item.value?.price_avg
})
const activeRegion = computed(() => String(item.value?.active_region || regionName.value || ''))
/** 走势图 / 历史报价标题后的地区提示，如「 (华东地区)」 */
const regionTitleHint = computed(() => {
  const name = activeRegion.value.trim()
  return name ? ` (${name})` : ''
})
const crumbLabel = computed(() => {
  const name = String(item.value?.name || '')
  if (!activeRegion.value) return name || '行情详情'
  const alias = name.match(/[（(]([^）)]+)[）)]/)
  const short = alias ? alias[1] : name
  return `地区报价：${activeRegion.value}（${short}）`
})
useHead(() => ({ title: crumbLabel.value || '行情详情' }))

const switchList = computed(() => {
  const raw = ((listRes.value as any)?.data || []) as Array<{ id: number | string; name: string; price_avg?: string | number; unit?: string }>
  if (raw.length) return raw
  const cur = item.value
  const others = (cur?.others || []) as Array<{ id: number | string; name: string; price_avg?: string | number }>
  return cur ? [{ id: cur.id, name: cur.name, price_avg: cur.price_avg, unit: cur.unit }, ...others] : others
})

const toggleSwitch = () => {
  switchOpen.value = !switchOpen.value
}
const closeSwitch = () => {
  switchOpen.value = false
}
const pickProduct = (pid: number | string) => {
  closeSwitch()
  if (String(pid) === id.value) return
  navigateTo(`/quotes/${pid}`)
}
const regionOpen = ref(false)
const pickRegion = (name: string) => {
  regionOpen.value = false
  navigateTo({ path: `/quotes/${id.value}`, query: name ? { region: name } : {} })
}

const onSwitchDocClick = (e: MouseEvent) => {
  const target = e.target as Node
  if (switchOpen.value) {
    const el = switchWrap.value
    if (el && !el.contains(target)) closeSwitch()
  }
  if (regionOpen.value) {
    const el = regionWrap.value
    if (el && !el.contains(target)) regionOpen.value = false
  }
}
const onSwitchKey = (e: KeyboardEvent) => {
  if (e.key === 'Escape') {
    closeSwitch()
    regionOpen.value = false
    chartExpandOpen.value = false
  }
}

onMounted(() => {
  fetchMe()
  document.addEventListener('click', onSwitchDocClick, true)
  document.addEventListener('keydown', onSwitchKey)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', onSwitchDocClick, true)
  document.removeEventListener('keydown', onSwitchKey)
  document.body.style.overflow = ''
})

watch(id, () => {
  closeSwitch()
  customActive.value = false
})

const needLogin = computed(() => Boolean(item.value?.need_login || (!token.value && !pending.value)))
const historyLimit = computed(() => Number(item.value?.history_days_limit || 0))
const isVip = computed(() => Boolean(item.value?.is_vip))

type SeriesRow = {
  date: string
  label: string
  price_avg: number
  price_min?: number | null
  price_max?: number | null
}

const rawSeries = computed(() => ((item.value?.series || []) as SeriesRow[]))

/** 会员可看的最早日期（相对库内最新报价日） */
const earliestAllowedDate = computed(() => {
  const rows = rawSeries.value
  const limit = Math.max(historyLimit.value || allowance.value || 7, 7)
  if (!rows.length) return quoteWindowStart(limit)
  const last = String(rows[rows.length - 1]?.date || '')
  const end = /^\d{4}-\d{2}-\d{2}$/.test(last) ? new Date(`${last}T12:00:00`) : new Date()
  return quoteWindowStart(limit, end)
})

function seriesDateBounds(rows: Array<{ date?: string }>) {
  let min = ''
  let max = ''
  for (const row of rows) {
    const d = String(row?.date || '').slice(0, 10)
    if (!/^\d{4}-\d{2}-\d{2}$/.test(d)) continue
    if (!min || d < min) min = d
    if (!max || d > max) max = d
  }
  return { min, max }
}

const latestSeriesDate = computed(() => seriesDateBounds(rawSeries.value).max)

const series = computed(() => {
  const rows = rawSeries.value
  if (!rows.length) return rows
  if (customActive.value && dateFrom.value && dateTo.value) {
    const from = dateFrom.value
    const to = dateTo.value
    return rows.filter((row) => {
      const d = String(row.date || '')
      return d >= from && d <= to
    })
  }
  // 以最新有价日为终点回推（不依赖接口 asc/desc 顺序）
  const { max } = seriesDateBounds(rows)
  const end = /^\d{4}-\d{2}-\d{2}$/.test(max) ? new Date(`${max}T12:00:00`) : new Date()
  const from = quoteWindowStart(selectedDays.value, end)
  return rows.filter((row) => String(row.date || '') >= from)
})

/** 库内实际天数远短于所选区间时提示（例如选了两年但只导了 2026） */
const coverageHint = computed(() => {
  if (needLogin.value || series.value.length < 2) return ''
  const { min: from, max: to } = seriesDateBounds(series.value)
  const a = Date.parse(from.replace(/-/g, '/'))
  const b = Date.parse(to.replace(/-/g, '/'))
  if (!Number.isFinite(a) || !Number.isFinite(b) || b < a) return ''
  const spanDays = Math.round((b - a) / 86400000) + 1
  if (!customActive.value && selectedDays.value >= 180 && spanDays < selectedDays.value * 0.55) {
    return `当前库内仅有 ${from} ~ ${to}（约 ${spanDays} 天），不足「${rangeTitle.value}」完整区间。请到后台按年份补导历史报价。`
  }
  return ''
})

const candles = computed<Candle[]>(() => {
  // 走势图始终按日期升序画（左旧右新）；接口可能因历史表 sort=desc 返回倒序
  const rows = [...series.value].sort((a, b) => String(a.date || '').localeCompare(String(b.date || '')))
  return rows.map((p, i, arr) => {
    const close = Number(p.price_avg) || 0
    const open = i === 0 ? close : Number(arr[i - 1].price_avg) || close
    const bodyHi = Math.max(open, close)
    const bodyLo = Math.min(open, close)
    const bodySpan = Math.max(bodyHi - bodyLo, 1)
    const mid = (open + close) / 2 || close
    const rawHigh = Number(p.price_max)
    const rawLow = Number(p.price_min)
    let high = Number.isFinite(rawHigh) && rawHigh > 0 ? rawHigh : bodyHi
    let low = Number.isFinite(rawLow) && rawLow > 0 ? rawLow : bodyLo
    if (high - low > Math.max(bodySpan * 5, mid * 0.018, 60)) {
      const pad = Math.max(bodySpan * 0.45, mid * 0.0028, 18)
      high = bodyHi + pad
      low = Math.max(0, bodyLo - pad)
    } else {
      high = Math.max(high, bodyHi)
      low = Math.min(low, bodyLo)
    }
    return {
      label: p.label || String(p.date || '').slice(5),
      date: String(p.date || ''),
      open,
      high,
      low,
      close,
    }
  })
})

/** 图上切换 7日/1月 等时，历史报价日期与表格跟同一窗口；自定义模式下由用户日期驱动，不再回写覆盖。 */
function syncHistoryDates(list = series.value) {
  if (!list.length) {
    dateFrom.value = ''
    dateTo.value = ''
    return
  }
  const { min, max } = seriesDateBounds(list)
  dateFrom.value = min
  dateTo.value = max
}
watch(series, (list) => {
  if (customActive.value) return
  syncHistoryDates(list)
}, { immediate: true })
watch(rangeKey, () => {
  if (customActive.value) return
  syncHistoryDates()
})

/** 夹紧到会员可看窗口，并开启自定义区间（走势图与表格共用） */
function clampAndActivateCustom(fromIn: string, toIn: string) {
  let from = String(fromIn || '').slice(0, 10)
  let to = String(toIn || '').slice(0, 10)
  const earliest = earliestAllowedDate.value
  const latest = latestSeriesDate.value
  if (earliest && from && from < earliest) from = earliest
  if (latest && to && to > latest) to = latest
  if (from && to && from > to) {
    const t = from
    from = to
    to = t
  }
  if (!from || !to) return false
  dateFrom.value = from
  dateTo.value = to
  customActive.value = true
  rangePicked.value = true
  return true
}

function activateCustomRange() {
  if (needLogin.value) {
    goLogin()
    return
  }
  const bounds = seriesDateBounds(series.value)
  const from = dateFrom.value || bounds.min || earliestAllowedDate.value
  const to = dateTo.value || bounds.max || latestSeriesDate.value
  clampAndActivateCustom(from, to)
}

function onCustomDateInput() {
  if (needLogin.value) return
  if (!dateFrom.value || !dateTo.value) return
  clampAndActivateCustom(dateFrom.value, dateTo.value)
}

const historyDesc = computed(() => {
  // 顺序完全跟接口 series（已按 sort 参数 SQL 排序）；这里只做日期区间过滤，不再 reverse
  let rows = [...series.value]
  if (!customActive.value) {
    const from = dateFrom.value
    const to = dateTo.value
    if (from || to) {
      rows = rows.filter((r) => {
        const d = String(r.date || '')
        if (from && d < from) return false
        if (to && d > to) return false
        return true
      })
    }
  }
  return rows
})

const HIST_PAGE_SIZE = 15
const histPage = ref(1)
const histPageCount = computed(() => Math.max(1, Math.ceil(historyDesc.value.length / HIST_PAGE_SIZE)))
const historyPageRows = computed(() => {
  const start = (histPage.value - 1) * HIST_PAGE_SIZE
  return historyDesc.value.slice(start, start + HIST_PAGE_SIZE)
})
watch(historyDesc, () => { histPage.value = 1 })
watch(histSort, () => { histPage.value = 1 })
watch(histPageCount, (n) => {
  if (histPage.value > n) histPage.value = n
})
function goHistPage(p: number) {
  if (p < 1 || p > histPageCount.value) return
  histPage.value = p
}
function setHistSort(order: 'desc' | 'asc') {
  if (histSort.value === order) return
  histSort.value = order
}

/** 长列表均匀铺多个水印；短列表至少居中 1 个 */
const tableWatermarkTops = computed(() => {
  const n = historyPageRows.value.length
  if (n <= 0) return [] as string[]
  const count = Math.min(3, Math.max(1, Math.ceil(n / 7)))
  return Array.from({ length: count }, (_, i) => `${(((i + 0.5) / count) * 100).toFixed(2)}%`)
})

const rangeAllowed = (opt: (typeof RANGE_OPTS)[0]) => {
  if (needLogin.value) return false
  return opt.days <= Math.max(historyLimit.value, 7)
}

const pickRange = (opt: (typeof RANGE_OPTS)[0]) => {
  if (needLogin.value) {
    goLogin()
    return
  }
  if (!rangeAllowed(opt)) {
    goVip()
    return
  }
  customActive.value = false
  rangePicked.value = true
  rangeKey.value = opt.key
  if (opt.key === '2y' || opt.key === '5y') chartMode.value = 'line'
  nextTick(() => syncHistoryDates())
}

function openChartExpand() {
  if (needLogin.value) {
    goLogin()
    return
  }
  if (!candles.value.length) return
  chartExpandOpen.value = true
}
function closeChartExpand() {
  chartExpandOpen.value = false
}
watch(chartExpandOpen, (open) => {
  if (!import.meta.client) return
  document.body.style.overflow = open ? 'hidden' : ''
})
watch([id, rangeKey, customActive], () => {
  if (chartExpandOpen.value) chartExpandOpen.value = false
})

const goLogin = () => {
  navigateTo({ path: '/login', query: { redirect: route.fullPath } })
}
const goVip = () => {
  navigateTo('/member/grade')
}

const fmtDiff = (n: unknown) => formatQuoteDiff(n, !needLogin.value)
const pct = (n: unknown) => formatQuotePct(n, !needLogin.value)
const diffClass = (n: unknown) => {
  if (needLogin.value) return ''
  const v = Number(n)
  if (v > 0) return 'up'
  if (v < 0) return 'down'
  return 'flat'
}
const money = (n: unknown) => {
  if (n == null || n === '') return needLogin.value ? '****' : '—'
  return formatQuotePrice(n, !needLogin.value)
}
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <NuxtLink to="/quotes">价格行情</NuxtLink>
          <span class="on">{{ crumbLabel }}</span>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>行情提示：</b>
        数据仅供参考，请以企业实际成交为准。
        <template v-if="!needLogin && !isVip">
          普通用户可查看近 {{ historyLimit || 7 }} 天；开通会员可查看更长周期。
        </template>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div v-if="error" class="blk empty">行情不存在或已下架</div>
        <div v-else-if="item" class="dt-layout">
          <div class="dt-main">
            <div class="dt-quote-hero">
              <div class="dt-hero-top">
                <h1>{{ item.name }}</h1>
                <div v-if="regions.length" ref="regionWrap" class="dt-switch">
                  <button type="button" class="dt-switch-btn" @click.stop="regionOpen = !regionOpen">
                    ⇄ {{ activeRegion || '平均价格' }}
                    <span class="chev" :class="{ open: regionOpen }" aria-hidden="true">▾</span>
                  </button>
                  <div v-show="regionOpen" class="dt-switch-panel" role="listbox">
                    <div class="dt-switch-scroll">
                      <button
                        type="button"
                        class="dt-switch-item"
                        :class="{ on: !activeRegion }"
                        @click.stop="pickRegion('')"
                      >
                        <span class="n">平均价格</span>
                        <span class="p num">{{ money(productAvg) }}</span>
                      </button>
                      <button
                        v-for="r in regions"
                        :key="r.name"
                        type="button"
                        class="dt-switch-item"
                        :class="{ on: r.name === activeRegion }"
                        @click.stop="pickRegion(r.name)"
                      >
                        <span class="n">{{ r.name }}</span>
                        <span class="p num">{{ money(r.price) }}</span>
                      </button>
                    </div>
                  </div>
                </div>
                <div ref="switchWrap" class="dt-switch">
                  <button
                    type="button"
                    class="dt-switch-btn"
                    :aria-expanded="switchOpen"
                    aria-haspopup="listbox"
                    @click.stop="toggleSwitch"
                  >
                    ⇄ 切换其他产品
                    <span class="chev" :class="{ open: switchOpen }" aria-hidden="true">▾</span>
                  </button>
                  <div
                    v-show="switchOpen"
                    class="dt-switch-panel"
                    role="listbox"
                    aria-label="选择行情品种"
                  >
                    <div class="dt-switch-head">
                      <b>选择品种</b>
                      <span>共 {{ switchList.length }} 个 · 滑动查看</span>
                    </div>
                    <div class="dt-switch-scroll">
                      <button
                        v-for="p in switchList"
                        :key="p.id"
                        type="button"
                        class="dt-switch-item"
                        role="option"
                        :aria-selected="String(p.id) === id"
                        :class="{ on: String(p.id) === id }"
                        @click.stop="pickProduct(p.id)"
                      >
                        <span class="n">{{ p.name }}</span>
                        <span class="p num">{{ money(p.price_avg) }}</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="qp">
                <b class="num">{{ money(item.price_avg) }}</b>
                <small>{{ item.unit || '元/吨' }}</small>
                <button v-if="needLogin" type="button" class="price-login-tip" @click="goLogin">登录查看</button>
              </div>
              <div class="qd">
                <span :class="diffClass(item.day_diff)">
                  比昨天 {{ fmtDiff(item.day_diff) }} / {{ pct(item.day_pct) }}
                </span>
                <span :class="diffClass(item.month_diff)">
                  比上月 {{ fmtDiff(item.month_diff) }} / {{ pct(item.month_pct) }}
                </span>
              </div>
              <div class="qm">
                <template v-if="!activeRegion">区间 {{ item.price_min ?? '-' }} ~ {{ item.price_max ?? '-' }} · </template>
                {{ activeRegion || '均价' }}
                · 更新 {{ String(item.quoted_on || '').slice(0, 10) || '-' }}
              </div>
            </div>

            <div class="dt-card dt-chart-card">
              <div class="dt-h dt-h-row">
                <span :key="'rt-' + rangeKey">{{ rangeTitle }}价格走势{{ regionTitleHint }}</span>
                <div class="chart-tools">
                  <div v-if="!activeRegion && !forceLineOnly" class="chart-mode" role="tablist" aria-label="图表类型">
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
                  <div v-else class="chart-mode" role="tablist" aria-label="图表类型">
                    <button type="button" role="tab" class="on" aria-selected="true">折线</button>
                  </div>
                  <div class="range-tabs" role="tablist" aria-label="时间范围">
                    <button
                      v-for="opt in RANGE_OPTS"
                      :key="opt.key"
                      type="button"
                      role="tab"
                      :class="{ on: !customActive && rangeKey === opt.key, locked: !rangeAllowed(opt) }"
                      :aria-selected="!customActive && rangeKey === opt.key"
                      @click="pickRange(opt)"
                    >{{ opt.label }}</button>
                    <button
                      type="button"
                      role="tab"
                      class="range-custom"
                      :class="{ on: customActive }"
                      :aria-selected="customActive"
                      :disabled="needLogin"
                      @click="activateCustomRange"
                    >日期选择</button>
                  </div>
                  <div v-if="customActive && !needLogin" class="date-range chart-date-range">
                    <input
                      v-model="dateFrom"
                      type="date"
                      :min="earliestAllowedDate || undefined"
                      :max="dateTo || latestSeriesDate || undefined"
                      aria-label="走势开始日期"
                      @change="onCustomDateInput"
                    >
                    <span class="sep">—</span>
                    <input
                      v-model="dateTo"
                      type="date"
                      :min="dateFrom || earliestAllowedDate || undefined"
                      :max="latestSeriesDate || undefined"
                      aria-label="走势结束日期"
                      @change="onCustomDateInput"
                    >
                  </div>
                  <button
                    v-if="canExpandChart && !needLogin && candles.length"
                    type="button"
                    class="chart-expand-btn"
                    @click="openChartExpand"
                  >详情展开</button>
                </div>
              </div>

              <div class="chart-wrap">
                <div v-if="needLogin" class="gate">
                  <p>登录后可查看价格走势与历史报价</p>
                  <button type="button" class="btn-soft" @click="goLogin">去登录</button>
                </div>
                <QuoteKlineChart
                  v-else-if="candles.length"
                  :candles="candles"
                  :mode="effectiveChartMode"
                  :overview="canExpandChart"
                  :aria-label="`${item.name}${rangeTitle}${regionTitleHint}${effectiveChartMode === 'candle' ? 'K线' : '折线'}走势`"
                />
                <p v-else class="empty" style="padding:20px 0">该周期暂无走势数据</p>
              </div>
              <p v-if="!needLogin" class="vip-hint">
                当前账号可查看近 <b>{{ historyLimit || allowance || 7 }}</b> 天。
                <NuxtLink v-if="(historyLimit || allowance || 7) < 1825" to="/member/grade">升级查看更长周期 ›</NuxtLink>
              </p>
              <p v-if="coverageHint" class="vip-hint" style="color:#b45309">{{ coverageHint }}</p>
            </div>

            <Teleport to="body">
              <div
                v-if="chartExpandOpen"
                class="chart-expand-mask"
                role="dialog"
                aria-modal="true"
                :aria-label="`${item?.name || ''}${rangeTitle}价格走势详情`"
                @click.self="closeChartExpand"
              >
                <div class="chart-expand-panel">
                  <div class="chart-expand-head">
                    <div class="chart-expand-titles">
                      <strong>{{ item?.name || '行情' }} · {{ rangeTitle }}价格走势{{ regionTitleHint }}</strong>
                      <span>左右滑动查看整段；滚轮放大多显示日期，缩小少显示，悬停可读当天价格</span>
                    </div>
                    <button type="button" class="chart-expand-close" aria-label="关闭" @click="closeChartExpand">关闭</button>
                  </div>
                  <QuoteKlineChart
                    :candles="candles"
                    mode="line"
                    expanded
                    :aria-label="`${item?.name || ''}${rangeTitle}${regionTitleHint}放大走势`"
                  />
                </div>
              </div>
            </Teleport>

            <div class="dt-card">
              <div class="dt-h dt-h-row">
                <span>历史报价{{ regionTitleHint }}</span>
                <div v-if="!needLogin" class="hist-tools">
                  <div class="hist-sort" role="group" aria-label="日期排序">
                    <button
                      type="button"
                      :class="{ on: histSort === 'desc' }"
                      @click="setHistSort('desc')"
                    >日期倒序</button>
                    <button
                      type="button"
                      :class="{ on: histSort === 'asc' }"
                      @click="setHistSort('asc')"
                    >日期正序</button>
                  </div>
                  <div class="date-range">
                    <input
                      v-model="dateFrom"
                      type="date"
                      :min="earliestAllowedDate || undefined"
                      :max="dateTo || latestSeriesDate || undefined"
                      aria-label="开始日期"
                      @change="onCustomDateInput"
                    >
                    <span class="sep">—</span>
                    <input
                      v-model="dateTo"
                      type="date"
                      :min="dateFrom || earliestAllowedDate || undefined"
                      :max="latestSeriesDate || undefined"
                      aria-label="结束日期"
                      @change="onCustomDateInput"
                    >
                  </div>
                </div>
              </div>

              <div v-if="needLogin" class="gate">
                <p>登录后可查看历史报价，并可自选日期区间</p>
                <button type="button" class="btn-soft" @click="goLogin">去登录</button>
              </div>
              <template v-else>
                <div class="table-wrap">
                  <img
                    v-for="(top, i) in tableWatermarkTops"
                    :key="'twm' + i"
                    class="table-watermark"
                    :style="{ top }"
                    src="/img/chart-watermark.png?v=2"
                    alt=""
                  >
                  <table class="hist">
                    <thead>
                      <tr>
                        <th>日期</th>
                        <th>{{ activeRegion ? '价格' : '均价' }}</th>
                        <th v-if="!activeRegion">最低</th>
                        <th v-if="!activeRegion">最高</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in historyPageRows" :key="row.date">
                        <td>{{ row.date }}</td>
                        <td class="num price">{{ money(row.price_avg) }}</td>
                        <td v-if="!activeRegion">{{ needLogin ? '****' : (row.price_min ?? '-') }}</td>
                        <td v-if="!activeRegion">{{ needLogin ? '****' : (row.price_max ?? '-') }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div v-if="historyDesc.length > HIST_PAGE_SIZE" class="hist-pager">
                  <button type="button" class="hist-pg" :disabled="histPage <= 1" @click="goHistPage(histPage - 1)">上一页</button>
                  <span class="hist-pg-info">{{ histPage }} / {{ histPageCount }}（共 {{ historyDesc.length }} 条）</span>
                  <button type="button" class="hist-pg" :disabled="histPage >= histPageCount" @click="goHistPage(histPage + 1)">下一页</button>
                </div>
                <p v-if="!historyDesc.length" class="empty" style="padding:16px 0">所选区间暂无数据</p>
                <p class="note">数据仅供参考，请以企业实际交易为准。</p>
              </template>
            </div>
          </div>

          <aside class="dt-side">
            <div class="dt-card">
              <div class="dt-h">其它品种</div>
              <NuxtLink
                v-for="o in (item.others || [])"
                :key="o.id"
                :to="`/quotes/${o.id}`"
                class="other"
              >
                <span>{{ o.name }}</span>
                <b>¥{{ money(o.price_avg) }}</b>
              </NuxtLink>
              <p v-if="!(item.others || []).length" class="empty" style="padding:12px 0">暂无其它品种</p>
              <NuxtLink class="btn-soft-link" to="/quotes" style="margin-top:12px">← 返回价格表</NuxtLink>
            </div>
            <div v-if="adSlots.length" class="dt-ad-stack">
              <NuxtLink
                v-for="slot in adSlots"
                :key="slot.code"
                class="dt-ad"
                :class="{ empty: !slot.ad }"
                :to="adHref(slot)"
                :target="adExternal(slot) ? '_blank' : undefined"
                :external="adExternal(slot) || undefined"
                :title="adTitle(slot)"
              >
                <span class="dt-ad-img">
                  <img v-if="slot.ad?.image" :src="slot.ad.image" :alt="adTitle(slot)" loading="lazy">
                  <span v-else class="dt-ad-ph">{{ slot.label || 'DMC' }}</span>
                  <em class="dt-ad-mark">广告</em>
                </span>
                <span class="dt-ad-body">
                  <b class="dt-ad-title">{{ adTitle(slot) }}</b>
                  <span class="dt-ad-co">
                    <span class="dt-ad-co-t" :title="adCompany(slot)">{{ adCompany(slot) }}</span>
                    <img class="dt-ad-vip" :src="adVipSrc(slot)" :alt="adVipLabel(slot)" :title="adVipLabel(slot)">
                  </span>
                  <strong class="dt-ad-price">{{ adPriceText(slot) }}</strong>
                </span>
              </NuxtLink>
            </div>
          </aside>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dt-quote-hero .up { color: #FF6A5E; }
.dt-quote-hero .down { color: #7CFFB2; }
.dt-quote-hero .flat { color: rgba(255, 255, 255, 0.72); }
.dt-quote-hero .qp {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 6px 8px;
}
.price-login-tip {
  margin-left: 2px;
  border: 0;
  background: rgba(255, 255, 255, 0.22);
  color: #fff;
  font: inherit;
  font-size: 11px;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 100px;
  cursor: pointer;
  line-height: 1.3;
}
.price-login-tip:hover {
  background: rgba(255, 255, 255, 0.34);
}
.dt-hero-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  position: relative;
  z-index: 6;
}
.dt-switch {
  position: relative;
  flex-shrink: 0;
}
.dt-switch-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 34px;
  padding: 0 12px 0 14px;
  border: 0;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.95);
  color: #1e4fc7;
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
  font-family: inherit;
  white-space: nowrap;
  box-shadow: 0 4px 14px rgba(15, 40, 90, 0.18);
}
.dt-switch-btn:hover {
  background: #fff;
}
.dt-switch-btn .chev {
  display: inline-grid;
  place-items: center;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #eaf1ff;
  font-size: 11px;
  transition: transform 0.15s;
}
.dt-switch-btn .chev.open {
  transform: rotate(180deg);
}
.dt-switch-panel {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: min(340px, 78vw);
  background: #fff;
  color: var(--ink);
  border-radius: 14px;
  border: 1.5px solid #d7e2f5;
  box-shadow: 0 18px 40px rgba(20, 45, 90, 0.2);
  overflow: hidden;
  z-index: 20;
  animation: dtSwitchIn 0.15s ease-out;
}
@keyframes dtSwitchIn {
  from { opacity: 0; transform: translateY(-6px); }
  to { opacity: 1; transform: translateY(0); }
}
.dt-switch-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 8px;
  padding: 12px 14px 10px;
  border-bottom: 1px solid #eef2f8;
}
.dt-switch-head b {
  font-size: 14px;
  font-weight: 800;
  color: #1f2a44;
}
.dt-switch-head span {
  font-size: 12px;
  color: #8a97b0;
}
.dt-switch-scroll {
  max-height: 320px;
  overflow-y: auto;
  overflow-x: hidden;
  overscroll-behavior: contain;
  padding: 6px;
  scrollbar-gutter: stable;
}
.dt-switch-scroll::-webkit-scrollbar {
  width: 8px;
}
.dt-switch-scroll::-webkit-scrollbar-track {
  background: #eef2f8;
  border-radius: 8px;
}
.dt-switch-scroll::-webkit-scrollbar-thumb {
  background: #9eb2d4;
  border-radius: 8px;
}
.dt-switch-item {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 12px;
  border: 0;
  border-radius: 10px;
  background: transparent;
  cursor: pointer;
  font-family: inherit;
  text-align: left;
}
.dt-switch-item:hover {
  background: #f3f7ff;
}
.dt-switch-item.on {
  background: #e8f0ff;
  box-shadow: inset 0 0 0 1.5px #2769dc;
}
.dt-switch-item .n {
  flex: 1;
  min-width: 0;
  font-size: 13px;
  font-weight: 700;
  color: #243356;
  line-height: 1.35;
}
.dt-switch-item .p {
  flex-shrink: 0;
  font-size: 13px;
  font-weight: 800;
  color: #e04b3f;
}
.dt-h-row {
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 10px 14px !important;
}
.dt-h-row::before {
  align-self: center;
}
.hist-tools {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px 12px;
  margin-left: auto;
}
.hist-sort {
  display: inline-flex;
  align-items: center;
  gap: 0;
  padding: 2px;
  border-radius: 8px;
  background: #eef2f8;
  border: 1px solid #e1e8f4;
}
.hist-sort button {
  height: 28px;
  padding: 0 10px;
  border: 0;
  border-radius: 6px;
  background: transparent;
  color: #6b7a94;
  font-size: 12.5px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  line-height: 1;
}
.hist-sort button.on {
  background: #fff;
  color: #2468f2;
  box-shadow: 0 1px 4px rgba(36, 104, 242, 0.16);
}
.chart-tools {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  margin-left: auto;
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
.range-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}
.range-tabs button {
  height: 28px;
  padding: 0 10px;
  border-radius: 8px;
  border: 1px solid var(--line);
  background: #fff;
  color: var(--ink-2);
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
}
.range-tabs button.on {
  background: var(--blue);
  border-color: var(--blue);
  color: #fff;
}
.range-tabs button.locked {
  opacity: 0.45;
}
.range-tabs button.range-custom:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}
.chart-date-range {
  margin-left: 0;
}
.chart-expand-btn {
  height: 28px;
  padding: 0 12px;
  border-radius: 8px;
  border: 1px solid #2468f2;
  background: #eef4ff;
  color: #2468f2;
  font-size: 12.5px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  white-space: nowrap;
}
.chart-expand-btn:hover {
  background: #2468f2;
  color: #fff;
}
.chart-expand-mask {
  position: fixed;
  inset: 0;
  z-index: 80;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  background: rgba(15, 23, 42, 0.48);
  backdrop-filter: blur(2px);
}
.chart-expand-panel {
  width: min(1120px, 100%);
  max-height: min(92vh, 760px);
  overflow: auto;
  padding: 18px 18px 12px;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 24px 64px rgba(15, 23, 42, 0.28);
}
.chart-expand-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 12px;
}
.chart-expand-titles {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 0;
}
.chart-expand-titles strong {
  font-size: 16px;
  color: #13294b;
  font-weight: 800;
  line-height: 1.35;
}
.chart-expand-titles span {
  font-size: 12.5px;
  color: #7a889f;
  font-weight: 600;
}
.chart-expand-close {
  flex-shrink: 0;
  height: 32px;
  padding: 0 14px;
  border-radius: 8px;
  border: 1px solid var(--line);
  background: #fff;
  color: var(--ink-2);
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
}
.chart-expand-close:hover {
  border-color: #2468f2;
  color: #2468f2;
}
.date-range {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-left: auto;
}
.date-range input[type='date'] {
  height: 30px;
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 0 8px;
  font-size: 12.5px;
  color: var(--ink);
  background: #fff;
  font-family: inherit;
}
.date-range .sep {
  color: var(--ink-3);
  font-size: 12px;
}
.gate {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  min-height: 160px;
  padding: 24px 12px;
  background: linear-gradient(180deg, #f7f9fd 0%, #eef3fb 100%);
  border-radius: 12px;
  text-align: center;
}
.gate p {
  margin: 0;
  font-size: 14px;
  color: var(--ink-2);
}
.vip-hint {
  margin: 10px 0 0;
  font-size: 12.5px;
  color: var(--ink-3);
}
.vip-hint a {
  color: var(--blue);
  text-decoration: none;
  margin-left: 4px;
}
.vip-hint a:hover { text-decoration: underline; }
.table-wrap { position: relative; overflow-x: auto; }
.table-watermark {
  position: absolute;
  left: 50%;
  z-index: 0;
  width: min(180px, 42%);
  height: auto;
  transform: translate(-50%, -50%);
  opacity: 0.42;
  pointer-events: none;
  user-select: none;
}
.hist { position: relative; z-index: 1; width: 100%; border-collapse: collapse; font-size: 13.5px; background: transparent; }
.hist td { background: transparent; }
.hist th, .hist td {
  border-bottom: 1px solid var(--line);
  padding: 10px 8px;
  text-align: left;
}
.hist th { color: var(--ink-3); font-weight: 600; background: #F8FAFD; }
.hist .price { color: var(--up); font-weight: 700; }
.hist-pager {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-top: 12px;
  flex-wrap: wrap;
}
.hist-pg {
  border: 1px solid #d7e2f5;
  background: #fff;
  color: var(--ink-2);
  border-radius: 8px;
  padding: 6px 12px;
  font: inherit;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
}
.hist-pg:hover:not(:disabled) { border-color: #8eb0ef; color: var(--blue); }
.hist-pg:disabled { opacity: .45; cursor: not-allowed; }
.hist-pg-info { font-size: 12.5px; color: var(--ink-3); font-weight: 600; }
.note { margin: 12px 0 0; font-size: 12.5px; color: var(--ink-3); }
.dt-ad-stack {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.dt-ad {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 9px;
  background: #f8fafd;
  border: 1px solid #e3eaf5;
  border-radius: 12px;
  color: inherit;
  text-decoration: none;
  overflow: hidden;
  transition: transform .18s ease, box-shadow .18s ease;
}
.dt-ad:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(30, 79, 199, 0.12);
}
.dt-ad-img {
  position: relative;
  display: block;
  width: 100%;
  aspect-ratio: 16 / 9;
  border-radius: 8px;
  overflow: hidden;
  background: #eef2f8;
}
.dt-ad-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.dt-ad-ph {
  display: grid;
  place-items: center;
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #1e4fc7 0%, #3b82f6 55%, #60a5fa 100%);
  color: #fff;
  font-size: 18px;
  font-weight: 800;
  letter-spacing: 0.04em;
}
.dt-ad-mark {
  position: absolute;
  top: 6px;
  left: 6px;
  z-index: 2;
  margin: 0;
  background: #e10600;
  color: #fff;
  font-style: normal;
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
  padding: 3px 6px;
  border-radius: 4px;
}
.dt-ad-body {
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}
.dt-ad-title {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: var(--ink, #1a2333);
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.dt-ad-co {
  display: flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
}
.dt-ad-co-t {
  flex: 1;
  min-width: 0;
  font-size: 12px;
  font-weight: 600;
  color: var(--ink-2, #6b7285);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.dt-ad-vip {
  flex-shrink: 0;
  height: 15px;
  width: auto;
  display: block;
}
.dt-ad-price {
  margin-top: 2px;
  font-size: 15px;
  font-weight: 800;
  color: #e10600;
  line-height: 1.3;
}
.dt-ad.empty {
  border-style: dashed;
  border-color: #b9cbee;
  background: #f7faff;
}
.dt-ad.empty .dt-ad-title {
  color: #1d4fd7;
}
.other {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  padding: 10px 0;
  border-bottom: 1px dashed var(--line);
  text-decoration: none;
  color: inherit;
  font-size: 13.5px;
  min-width: 0;
}
.other > span {
  flex: 1;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.other:hover { color: var(--blue); }
.other b { color: var(--up); font-weight: 700; white-space: nowrap; flex-shrink: 0; }
@media (max-width: 700px) {
  .hist-tools {
    width: 100%;
    margin-left: 0;
  }
  .date-range {
    width: 100%;
    margin-left: 0;
  }
  .date-range input[type='date'] {
    flex: 1;
    min-width: 0;
  }
}
</style>

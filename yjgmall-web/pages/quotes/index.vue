<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '价格行情' })

import {
  QUOTE_CHIP_LABELS,
  filterArticlesByQuoteChip,
  normQuoteName,
  sortQuotesByCatalog,
} from '~/utils/quoteCatalog'
import { formatQuoteDiff, formatQuotePct, formatQuotePrice } from '~/utils/quotePrice'

const config = useRuntimeConfig()
const { quoteApi } = useQuoteApi()
const { token, fetchMe } = useAuth()
const priceRevealed = computed(() => Boolean(token.value))

const { data } = await useAsyncData(
  'quotes-channel',
  () => quoteApi<any>('/quotes', { auth: Boolean(token.value) }),
  { server: false, watch: [token] },
)

/** 价格行情资讯：复制资讯页同套品种逻辑，打散为 15 个独立板块 */
const { data: newsPack } = await useAsyncData('quotes-pn-news', async () => {
  const res: any = await $fetch(`${config.public.apiBase}/news`, {
    query: { per_page: 100, sort: 'new' },
  })
  return {
    items: res?.data || [],
    headlines: res?.headlines || [],
  }
})

function fmtNewsTime(raw: string | undefined) {
  if (!raw) return ''
  const d = new Date(raw)
  if (Number.isNaN(d.getTime())) return String(raw).slice(0, 10)
  const md = `${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
  return `${md} ${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`
}

const newsPool = computed(() => {
  const seen = new Set<number>()
  const out: any[] = []
  const pack = newsPack.value || { items: [], headlines: [] }
  for (const it of [...(pack.headlines || []), ...(pack.items || [])]) {
    const id = Number(it?.id)
    if (!id || seen.has(id)) continue
    seen.add(id)
    out.push(it)
  }
  return out
})

const pnBoards = computed(() => {
  const pool = newsPool.value
  return QUOTE_CHIP_LABELS.map((chip) => {
    const rows = filterArticlesByQuoteChip(pool, chip).slice(0, 6).map((it: any) => ({
      title: String(it.title || ''),
      time: fmtNewsTime(it.published_at),
      to: `/news/${it.id}`,
    }))
    return {
      chip,
      rows,
      moreTo: { path: '/news', query: { pn: chip } },
    }
  })
})
const rawItems = computed(() => ((data.value as any)?.data || []).map((p: any) => ({
  ...p,
  name: normQuoteName(String(p.name || '')),
})))
const items = computed(() => sortQuotesByCatalog(rawItems.value))
const activeIdx = ref(0)
const current = computed(() => items.value[activeIdx.value] || items.value[0] || null)
const quoteScopeLabel = computed(() => {
  const m = String(current.value?.market || '').trim()
  if (!m || m === '华东' || m === '市场参考' || m === '市场报价') return '均价'
  return m
})

watch(items, (list) => {
  if (activeIdx.value >= list.length) activeIdx.value = 0
})

onMounted(() => {
  void fetchMe()
})

function num(v: unknown, fallback = 0) {
  const n = Number(v)
  return Number.isFinite(n) ? n : fallback
}

/** 只画接口返回的已保存报价。未登录和普通会员看 7 天，白银及以上看一个月。 */
function seriesFor(p: any) {
  const rows = Array.isArray(p?.series) ? p.series : []
  const pts = rows
    .map((row: any) => ({
      date: String(row?.date || '').slice(0, 10),
      label: String(row?.label || String(row?.date || '').slice(5)),
      value: num(row?.price_avg, 0),
    }))
    .filter((pt: { label: string; value: number }) => pt.label && pt.value > 0)
  if (pts.length < 2) return pts
  const limit = Number(p?.history_days_limit || 0)
  const days = !priceRevealed.value || limit <= 7 ? 7 : 30
  const last = pts[pts.length - 1].date
  if (!/^\d{4}-\d{2}-\d{2}$/.test(last)) return pts.slice(-days)
  const end = new Date(`${last}T00:00:00`)
  const start = new Date(end)
  start.setDate(start.getDate() - (days - 1))
  const key = `${start.getFullYear()}-${String(start.getMonth() + 1).padStart(2, '0')}-${String(start.getDate()).padStart(2, '0')}`
  const out = pts.filter((pt) => pt.date >= key)
  return out.length >= 2 ? out : pts.slice(-days)
}

const chart = computed(() => {
  const pts = current.value ? seriesFor(current.value) : []
  const w = 520
  const h = 158
  const empty = {
    line: '',
    area: '',
    end: null as null | { x: number; y: number },
    w,
    h,
    yLeft: [] as string[],
    yRight: [] as string[],
    xLabels: [] as { x: number; t: string }[],
    gridY: [] as number[],
    plotBottom: h - 22,
  }
  if (pts.length < 2) return empty

  const padL = 42
  const padR = 40
  const padT = 14
  const padB = 24
  const vals = pts.map((p) => p.value)
  let vmin = Math.min(...vals)
  let vmax = Math.max(...vals)
  const mid0 = (vmin + vmax) / 2
  const rawSpan = Math.max(vmax - vmin, 0)
  // 几乎无起伏：对称放大量程，折线落在图中
  const flatThresh = Math.max(80, Math.abs(mid0) * 0.006)
  if (rawSpan < flatThresh) {
    const half = Math.max(100, Math.abs(mid0) * 0.008)
    vmin = mid0 - half
    vmax = mid0 + half
  } else {
    const pad = Math.max(rawSpan * 0.12, Math.abs(mid0) * 0.004, 1)
    vmin -= pad
    vmax += pad
  }
  const span = Math.max(vmax - vmin, 1)
  const mid = (vmin + vmax) / 2
  const xStep = (w - padL - padR) / (pts.length - 1)
  const yOf = (v: number) => padT + (1 - (v - vmin) / span) * (h - padT - padB)
  const dots = pts.map((p, i) => ({
    x: padL + i * xStep,
    y: yOf(p.value),
    label: p.label,
    value: p.value,
  }))
  const plotBottom = h - padB
  const line = dots.map((d, i) => `${i ? 'L' : 'M'}${d.x.toFixed(1)},${d.y.toFixed(1)}`).join(' ')
  const area = `${line} L${dots[dots.length - 1].x.toFixed(1)},${plotBottom.toFixed(1)} L${dots[0].x.toFixed(1)},${plotBottom.toFixed(1)} Z`
  const yLevels = [vmax, mid + span * 0.25, mid, vmin]
  const revealed = priceRevealed.value
  const yLeft = yLevels.map((v) => formatQuotePrice(v, revealed))
  const yRight = yLevels.map((v) => {
    if (!revealed) return '**%'
    const p = ((v - mid) / mid) * 100
    return `${p >= 0 ? '+' : ''}${p.toFixed(2)}%`
  })
  const gridY = yLevels.map((v) => yOf(v))
  const xLabels = dots.filter((_, i) => (dots.length - 1 - i) % 3 === 0).map((d) => ({ x: d.x, t: d.label }))
  return {
    line,
    area,
    end: dots[dots.length - 1],
    w,
    h,
    yLeft,
    yRight,
    xLabels,
    gridY,
    plotBottom,
    padL,
    padR,
  }
})

function fmtDiff(n: unknown) {
  return formatQuoteDiff(n, priceRevealed.value)
}
function pct(n: unknown) {
  return formatQuotePct(n, priceRevealed.value)
}
function diffClass(n: unknown) {
  if (!priceRevealed.value) return ''
  const v = Number(n)
  if (v > 0) return 'up'
  if (v < 0) return 'down'
  return 'flat'
}
function money(n: unknown) {
  if (n == null || n === '') return priceRevealed.value ? '—' : '****'
  return formatQuotePrice(n, priceRevealed.value)
}

function goLogin() {
  navigateTo({ path: '/login', query: { redirect: '/quotes' } })
}

const sparkUid = `qh${Math.random().toString(36).slice(2, 9)}`
const sparkFillId = `qhSparkFill-${sparkUid}`
const sparkClipId = `qhSparkClip-${sparkUid}`
const sparkClipW = ref(0)
let sparkRaf = 0
const SPARK_DRAW_MS = 1200

function stopSparkDraw() {
  if (sparkRaf) {
    cancelAnimationFrame(sparkRaf)
    sparkRaf = 0
  }
}

function playSparkDraw() {
  stopSparkDraw()
  const target = Math.max(chart.value.w || 0, 1)
  if (!chart.value.line) {
    sparkClipW.value = target
    return
  }
  sparkClipW.value = 0
  const start = performance.now()
  const tick = (now: number) => {
    const t = Math.min(1, (now - start) / SPARK_DRAW_MS)
    const eased = 1 - (1 - t) ** 3
    sparkClipW.value = target * eased
    if (t < 1) sparkRaf = requestAnimationFrame(tick)
    else sparkRaf = 0
  }
  sparkRaf = requestAnimationFrame(tick)
}

watch(
  () => [activeIdx.value, chart.value.line, priceRevealed.value] as const,
  () => nextTick(playSparkDraw),
)
onMounted(() => nextTick(playSparkDraw))
onBeforeUnmount(stopSparkDraw)

const sparkDrawDone = computed(() => sparkClipW.value >= (chart.value.w || 0) - 1)
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <a class="on" href="#ch-quote-main">全部品种</a>
          <a href="#ch-quote-list">行情资讯</a>
          <NuxtLink to="/news">硅业智讯</NuxtLink>
          <NuxtLink to="/member/quotes">我的报价</NuxtLink>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>有机硅市场提示：</b>
        短期行情以区间震荡为主，建议按需采购；数据仅供参考，请以企业实际成交为准。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="sec-head">
          <div>
            <h2>价格行情</h2>
            <p class="sub">主流有机硅品种实时参考价与涨跌，覆盖中间体、硅油、混炼胶等核心品类</p>
          </div>
          <NuxtLink class="more-link" to="/news">查看行情资讯 ›</NuxtLink>
        </div>

        <div v-if="current" id="ch-quote-main" class="quote-hero">
          <NuxtLink class="qh-card" :to="`/quotes/${current.id}`">
            <div class="qh-card-top">
              <div class="qc-name">{{ current.name }}</div>
              <span class="qh-detail-tip">
                查看价格详情
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M9 18l6-6-6-6" /></svg>
              </span>
            </div>
            <div class="qc-tags">
              <span>均价</span>
              <span>点击卡片进入详情走势</span>
            </div>
            <div class="qc-price" :title="priceRevealed ? undefined : '登录后查看完整价格'" @click.prevent="!priceRevealed && goLogin()">
              <b class="num">{{ money(current.price_avg) }}</b>
              <small>{{ current.unit || '元/吨' }}</small>
              <button v-if="!priceRevealed" type="button" class="price-login-tip" @click.stop="goLogin">登录查看</button>
            </div>
            <div class="qc-chg" :class="diffClass(current.day_diff)">
              <span class="num">{{ fmtDiff(current.day_diff) }}</span>
              <span class="num">{{ pct(current.day_pct) }}</span>
            </div>
            <div class="qc-time">
              更新于 {{ String(current.quoted_on || '').slice(0, 10) || '-' }} · {{ quoteScopeLabel }}
            </div>

            <div class="qc-chart" :class="{ locked: !priceRevealed }" aria-hidden="true">
              <svg
                :viewBox="`0 0 ${chart.w} ${chart.h}`"
                preserveAspectRatio="none"
                class="spark"
              >
                <defs>
                  <linearGradient :id="sparkFillId" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="rgba(255,255,255,.32)" />
                    <stop offset="100%" stop-color="rgba(255,255,255,0)" />
                  </linearGradient>
                  <clipPath :id="sparkClipId">
                    <rect x="0" y="0" :width="sparkClipW" :height="chart.h" />
                  </clipPath>
                </defs>
                <!-- 网格 -->
                <g stroke="rgba(255,255,255,.18)" stroke-width="1">
                  <line
                    v-for="(y, i) in chart.gridY"
                    :key="'g' + i"
                    :x1="chart.padL || 42"
                    :y1="y"
                    :x2="chart.w - (chart.padR || 40)"
                    :y2="y"
                  />
                </g>
                <!-- 左价格轴 -->
                <g font-size="10" fill="rgba(255,255,255,.78)" text-anchor="start">
                  <text
                    v-for="(t, i) in chart.yLeft"
                    :key="'yl' + i"
                    x="4"
                    :y="(chart.gridY[i] || 0) + 3.5"
                  >{{ t }}</text>
                </g>
                <!-- 右涨跌%轴 -->
                <g font-size="10" fill="rgba(255,255,255,.65)" text-anchor="end">
                  <text
                    v-for="(t, i) in chart.yRight"
                    :key="'yr' + i"
                    :x="chart.w - 4"
                    :y="(chart.gridY[i] || 0) + 3.5"
                    :fill="i < 2 ? '#FFD0CC' : i === 2 ? 'rgba(255,255,255,.7)' : '#B8F5D4'"
                  >{{ t }}</text>
                </g>
                <g :clip-path="`url(#${sparkClipId})`">
                  <path v-if="chart.area" :d="chart.area" :fill="`url(#${sparkFillId})`" />
                  <path
                    v-if="chart.line"
                    :d="chart.line"
                    fill="none"
                    stroke="rgba(255,255,255,.95)"
                    stroke-width="2.4"
                    stroke-linejoin="round"
                    stroke-linecap="round"
                  />
                </g>
                <circle
                  v-if="chart.end && sparkDrawDone"
                  :cx="chart.end.x"
                  :cy="chart.end.y"
                  r="4"
                  fill="#fff"
                  stroke="rgba(39,105,220,.95)"
                  stroke-width="2"
                />
                <!-- 底日期轴 -->
                <g font-size="9.5" fill="rgba(255,255,255,.72)" text-anchor="middle">
                  <text
                    v-for="(x, i) in chart.xLabels"
                    :key="'xl' + i"
                    :x="x.x"
                    :y="chart.h - 6"
                  >{{ x.t }}</text>
                </g>
              </svg>
              <img class="chart-watermark" src="/img/chart-watermark.png?v=2" alt="">
              <div v-if="!priceRevealed" class="qc-chart-gate" @click.prevent.stop="goLogin">
                <span>登录后可查看完整价格</span>
                <em>立即登录</em>
              </div>
            </div>

            <div class="qc-data">
              <div class="d"><div class="k">最低</div><div class="v num">{{ money(current.price_min) }}</div></div>
              <div class="d"><div class="k">最高</div><div class="v num">{{ money(current.price_max) }}</div></div>
              <div class="d"><div class="k">均价</div><div class="v num">{{ money(current.price_avg) }}</div></div>
              <div class="d"><div class="k">涨跌</div><div class="v num" :class="diffClass(current.day_diff)">{{ fmtDiff(current.day_diff) }}</div></div>
            </div>
          </NuxtLink>

          <div class="qh-side">
            <div class="blk qh-switch">
              <div class="b-head">
                <b>切换品种</b>
                <span class="hint">共 {{ items.length }} 个 · 滚动查看全部</span>
              </div>
              <div class="qh-scroll">
                <div class="quote-grid qh-switch-grid">
                  <button
                    v-for="(item, i) in items"
                    :key="'sw' + (item.id || item.name) + '-' + i"
                    type="button"
                    class="q-card"
                    :class="{ on: i === activeIdx }"
                    @click="activeIdx = i"
                  >
                    <div class="n">{{ item.name }}</div>
                    <div class="p num">{{ money(item.price_avg) }}<small>{{ item.unit || '元/吨' }}</small></div>
                    <div class="c num" :class="diffClass(item.day_diff)">{{ pct(item.day_pct) }}</div>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div id="ch-quote-list" class="sec-head" style="margin-top:28px">
          <div>
            <h2>价格行情资讯</h2>
            <p class="sub">{{ pnBoards.length }} 大品种 · 每日更新 · 点击更多进入硅业智讯对应品种</p>
          </div>
          <NuxtLink class="more-link" to="/news">进入硅业智讯 ›</NuxtLink>
        </div>

        <div class="pn-grid">
          <div v-for="board in pnBoards" :key="board.chip" class="blk">
            <div class="b-head">
              <b>{{ board.chip }}</b>
              <span class="hint">价格动态</span>
            </div>
            <NuxtLink
              v-for="(row, i) in board.rows"
              :key="board.chip + '-' + i"
              class="li-row"
              :to="row.to"
            >
              <span class="t">{{ row.title }}</span>
              <time>{{ row.time }}</time>
            </NuxtLink>
            <p v-if="!board.rows.length" class="li-empty">暂无相关资讯</p>
            <div class="b-foot">
              <NuxtLink :to="board.moreTo">更多 ››</NuxtLink>
            </div>
          </div>
        </div>
        <p class="empty" style="padding-top:12px;font-size:12px">数据仅供参考，请以企业实际交易为准。</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.qc-chart {
  position: relative;
  z-index: 1;
  margin: 14px 0 12px;
  height: 150px;
  flex: none;
}
.qc-chart .spark {
  width: 100%;
  height: 150px;
  display: block;
}
.qc-chart .chart-watermark {
  position: absolute;
  left: 50%;
  top: 46%;
  z-index: 2;
  width: min(150px, 48%);
  height: auto;
  transform: translate(-50%, -50%);
  opacity: 0.5;
  filter: brightness(0) invert(1);
  pointer-events: none;
  user-select: none;
}
.qc-chart.locked .spark {
  filter: blur(1.4px);
  opacity: 0.78;
}
.qc-chart-gate {
  position: absolute;
  inset: 0;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: rgba(15, 40, 90, 0.28);
  border-radius: 10px;
  cursor: pointer;
}
.qc-chart-gate span {
  font-size: 13px;
  font-weight: 700;
  color: #fff;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
}
.qc-chart-gate em {
  font-style: normal;
  font-size: 12px;
  font-weight: 700;
  color: #2468f2;
  background: #fff;
  padding: 5px 14px;
  border-radius: 999px;
}
.qc-price {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 6px 8px;
  position: relative;
  z-index: 1;
}
.price-login-tip {
  margin-left: 4px;
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
.qh-side {
  /* height:0 + min-height:100%：行高由左侧蓝卡决定，右侧跟齐并内部滚动 */
  height: 0;
  min-height: 100%;
  max-height: none;
  display: flex;
  flex-direction: column;
  min-width: 0;
  overflow: hidden;
}
.qh-switch {
  flex: 1;
  min-height: 0;
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.qh-scroll {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
  padding-right: 4px;
  scrollbar-gutter: stable;
}
.qh-scroll::-webkit-scrollbar {
  width: 8px;
}
.qh-scroll::-webkit-scrollbar-track {
  background: #eef2f8;
  border-radius: 8px;
}
.qh-scroll::-webkit-scrollbar-thumb {
  background: #9eb2d4;
  border-radius: 8px;
}
.qh-scroll::-webkit-scrollbar-thumb:hover {
  background: #7a94bf;
}
.qh-switch-grid {
  grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
}
.qh-switch-grid .q-card {
  text-align: left;
  width: 100%;
  cursor: pointer;
  font-family: inherit;
  border: 1.5px solid transparent;
  background: #fff;
}
.qh-switch-grid .q-card.on {
  border-color: var(--blue);
  background: #eef4ff;
}
.qh-switch-grid .q-card:hover {
  transform: none;
}
.qh-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  position: relative;
  z-index: 1;
}
.qh-card-top .qc-name {
  flex: 1;
  min-width: 0;
  font-size: 22px;
  font-weight: 800;
  line-height: 1.35;
  letter-spacing: 0.01em;
}
.qh-detail-tip {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  gap: 3px;
  margin-top: 0;
  padding: 8px 14px 8px 16px;
  border-radius: 999px;
  font-size: 13px;
  font-weight: 800;
  letter-spacing: 0.03em;
  color: #fff;
  background: linear-gradient(135deg, #ff9a2e 0%, #ff6b1a 100%);
  box-shadow: 0 4px 16px rgba(255, 107, 26, 0.45), 0 0 0 2px rgba(255, 255, 255, 0.35);
  white-space: nowrap;
  transition: transform 0.15s, box-shadow 0.15s, filter 0.15s;
}
.qh-card:hover .qh-detail-tip {
  transform: translateX(3px) scale(1.03);
  box-shadow: 0 6px 20px rgba(255, 107, 26, 0.55), 0 0 0 2px rgba(255, 255, 255, 0.5);
  filter: brightness(1.06);
}
</style>

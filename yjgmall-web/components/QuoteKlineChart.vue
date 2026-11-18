<script setup lang="ts">
/** 折线 / K线 双模式走势图；未登录价格打星并提示登录 */
export type KlinePoint = {
  label: string
  open?: number
  high?: number
  low?: number
  close: number
}

export type ChartMode = 'line' | 'candle'

const props = withDefaults(defineProps<{
  candles: KlinePoint[]
  mode?: ChartMode
  ariaLabel?: string
  /** 强制覆盖是否揭示价格；默认跟登录态 */
  revealed?: boolean | null
  loginRedirect?: string
  /** 未揭示价格时的遮罩：login 去登录，vip 引导升级会员 */
  gate?: 'login' | 'vip'
  /** 详情展开：更高、点距更宽，便于长周期左右滑动看起伏 */
  expanded?: boolean
  /**
   * 总览（后台板式）：整段区间压进当前宽度，一眼看清起伏。
   * 用于 3 月/半年/一年/两年/五年主图；与 expanded 横滑细看互补。
   */
  overview?: boolean
}>(), {
  mode: 'line',
  ariaLabel: '价格走势',
  revealed: null,
  loginRedirect: '',
  gate: 'login',
  expanded: false,
  overview: false,
})

const emit = defineEmits<{ upgrade: [] }>()

const { token } = useAuth()
const route = useRoute()
const priceRevealed = computed(() =>
  props.revealed == null ? Boolean(token.value) : Boolean(props.revealed),
)

const svgRef = ref<SVGSVGElement | null>(null)
const scrollRef = ref<HTMLElement | null>(null)
const hoverIdx = ref<number | null>(null)
const viewW = ref(0)

/** 多实例并存时避免渐变 / clipPath id 冲突 */
const chartUid = `qk${Math.random().toString(36).slice(2, 9)}`
const fillGradId = `quoteLineFill-${chartUid}`
const drawClipId = `quoteDrawClip-${chartUid}`
const clipW = ref(0)
let drawRaf = 0
const DRAW_MS = 1200

// 展开详情：点距收紧，一年图左右滑时不至于一天隔很远
const SLOT = computed(() => (props.expanded ? 22 : 48))
const h = computed(() => (props.expanded ? 360 : (props.overview ? 280 : 200)))
const padT = computed(() => (props.overview ? 18 : 16))
const padB = computed(() => (props.overview ? 36 : 28))
const innerPad = 10

type PlotPt = {
  label: string
  open: number
  high: number
  low: number
  close: number
  value: number
  x: number
  y: number
  bodyTop: number
  bodyH: number
  wickTop: number
  wickBot: number
  up: boolean
  bw: number
}

const isCandle = computed(() => props.mode === 'candle')

const chart = computed(() => {
  const raw = props.candles || []
  const revealed = priceRevealed.value
  const boxW = Math.max(viewW.value || 0, 240)
  if (raw.length < 1) {
    return {
      points: [] as PlotPt[],
      line: '',
      area: '',
      yLeft: [] as string[],
      yRight: [] as string[],
      xLabels: [] as { x: number; t: string }[],
      gridY: [] as number[],
      yOf: (_v: number) => 0,
      mid: 0,
      w: boxW,
      h: h.value,
      scrollable: false,
      showDots: false,
      dotR: 2.2,
    }
  }

  const closes = raw.map((p) => Number(p.close) || 0)
  const highs = raw.map((p, i) => {
    const c = closes[i]
    const h0 = Number(p.high)
    return Number.isFinite(h0) && h0 > 0 ? h0 : c
  })
  const lows = raw.map((p, i) => {
    const c = closes[i]
    const l0 = Number(p.low)
    return Number.isFinite(l0) && l0 > 0 ? l0 : c
  })

  let vmin: number
  let vmax: number
  if (isCandle.value) {
    vmin = Math.min(...lows)
    vmax = Math.max(...highs)
  } else {
    vmin = Math.min(...closes)
    vmax = Math.max(...closes)
  }
  const mid0 = (vmin + vmax) / 2
  const rawSpan = Math.max(vmax - vmin, 0)
  // 几乎无起伏：对称放大量程；总览对齐后台板 0.18 留白
  const flatThresh = Math.max(80, Math.abs(mid0) * 0.006)
  if (rawSpan < flatThresh) {
    const half = Math.max(100, Math.abs(mid0) * 0.008)
    vmin = mid0 - half
    vmax = mid0 + half
  } else {
    const padRatio = props.overview ? 0.18 : 0.12
    const pad = Math.max(rawSpan * padRatio, Math.abs(mid0) * 0.004, 1)
    vmin -= pad
    vmax += pad
  }
  const span = Math.max(vmax - vmin, 1)
  const mid = (vmin + vmax) / 2
  const n = raw.length
  const slot = SLOT.value
  const chartH = h.value
  const pt = padT.value
  const pb = padB.value
  // 总览 / 单月：整段铺满当前宽度（后台板式）
  // 展开：按点距拉宽横滑；其它多月点距滚动
  const fitWidth = props.overview || (!props.expanded && n <= 31)
  const plotW = fitWidth
    ? Math.max(boxW - innerPad * 2, 200)
    : Math.max(isCandle.value ? n * slot : Math.max(n - 1, 1) * slot, boxW - innerPad * 2)
  const chartW = fitWidth ? boxW : plotW + innerPad * 2
  const scrollable = !fitWidth && chartW > boxW + 1
  const gap = isCandle.value ? plotW / Math.max(n, 1) : (n <= 1 ? plotW : plotW / (n - 1))
  const bw = Math.max(4, Math.min(14, gap * 0.62))
  const yOf = (v: number) => pt + (1 - (v - vmin) / span) * (chartH - pt - pb)

  const points: PlotPt[] = raw.map((p, i) => {
    const close = closes[i]
    const open = Number.isFinite(Number(p.open)) ? Number(p.open) : (i === 0 ? close : closes[i - 1])
    let high = highs[i]
    let low = lows[i]
    const bodyHi = Math.max(open, close)
    const bodyLo = Math.min(open, close)
    const bodySpan = Math.max(bodyHi - bodyLo, 1)
    if (high - low > Math.max(bodySpan * 5, mid * 0.018, 60)) {
      const wickPad = Math.max(bodySpan * 0.45, mid * 0.0028, 18)
      high = bodyHi + wickPad
      low = Math.max(0, bodyLo - wickPad)
    } else {
      high = Math.max(high, bodyHi)
      low = Math.min(low, bodyLo)
    }
    const x = isCandle.value
      ? innerPad + gap * (i + 0.5)
      : (n === 1 ? innerPad + plotW / 2 : innerPad + gap * i)
    const yO = yOf(open)
    const yC = yOf(close)
    return {
      label: p.label,
      open,
      high,
      low,
      close,
      value: close,
      x,
      y: yC,
      bodyTop: Math.min(yO, yC),
      bodyH: Math.max(2.5, Math.abs(yC - yO)),
      wickTop: yOf(high),
      wickBot: yOf(low),
      up: close >= open,
      bw,
    }
  })

  const line = points.map((p, i) => `${i ? 'L' : 'M'}${p.x.toFixed(2)},${p.y.toFixed(2)}`).join(' ')
  const baseY = chartH - pb
  const area = points.length
    ? `${line} L${points[points.length - 1].x.toFixed(2)},${baseY} L${points[0].x.toFixed(2)},${baseY} Z`
    : ''

  const yLevels = [vmax, mid + span * 0.25, mid, mid - span * 0.25, vmin]
  const hidden = !revealed
  const yLeft = yLevels.map((v) => (hidden && props.gate === 'vip' ? '*****' : formatQuotePrice(v, revealed)))
  const yRight = yLevels.map((v) => {
    if (hidden && props.gate === 'vip') return '*****'
    if (!revealed) return '**%'
    const pct = mid ? ((v - mid) / mid) * 100 : 0
    return `${pct >= 0 ? '+' : ''}${pct.toFixed(2)}%`
  })
  const gridY = yLevels.map((v) => yOf(v))
  // 横轴刻度：按图宽定密度 + 最小间距，避免「月初贴首日叠字」或「整段只剩 3 个日期太空」
  let xLabels: { x: number; t: string }[] = []
  // 展开长图刻度更密；总览仍控制不叠字
  const MIN_GAP = props.expanded ? 48 : (props.overview ? 54 : 62)
  const dayOf = (i: number) => {
    const m = String(points[i]?.label || '').match(/-(\d{1,2})$/)
    return m ? Number(m[1]) : 99
  }
  const prefer = (i: number) => {
    let s = 0
    if (i === 0 || i === n - 1) s += 80
    const d = dayOf(i)
    if (d === 1) s += 60
    else if (d === 15) s += 25
    return s
  }
  const mergeSpaced = (idxs: number[]) => {
    const sorted = [...new Set(idxs)].filter((i) => i >= 0 && i < n).sort((a, b) => a - b)
    const kept: number[] = []
    for (const i of sorted) {
      if (!kept.length) {
        kept.push(i)
        continue
      }
      const prev = kept[kept.length - 1]
      if (points[i].x - points[prev].x >= MIN_GAP) {
        kept.push(i)
        continue
      }
      // 过近：优先月初 / 首尾；末点尽量保住
      if (i === n - 1 || prefer(i) > prefer(prev)) kept[kept.length - 1] = i
    }
    if (kept.length && kept[kept.length - 1] !== n - 1) {
      const lastKept = kept[kept.length - 1]
      if (points[n - 1].x - points[lastKept].x >= MIN_GAP) kept.push(n - 1)
      else kept[kept.length - 1] = n - 1
    }
    xLabels = kept.map((i) => ({ x: points[i].x, t: points[i].label }))
  }
  // 总览：约每 68px 一个；展开长图按像素多标，滑到哪一段底下都有日期
  const labelEveryPx = props.expanded ? 56 : 68
  const maxTicks = props.expanded ? 80 : 14
  const targetCount = Math.min(
    maxTicks,
    Math.max(n <= 12 ? n : 5, Math.round(plotW / labelEveryPx)),
  )
  if (props.overview && n >= 3) {
    // 跨年时同月（如 2025-03 与 2026-03）不能共用一个 key
    const monthFirst = new Map<string, number>()
    let yearCursor = 0
    let lastMonth = 0
    for (let i = 0; i < n; i++) {
      const lb = String(points[i].label || '')
      const m = lb.match(/^(\d{1,2})-(\d{1,2})$/)
      if (!m) continue
      const month = Number(m[1])
      const day = Number(m[2])
      if (lastMonth && month < lastMonth) yearCursor += 1
      lastMonth = month
      const monthKey = `${yearCursor}-${String(month).padStart(2, '0')}`
      const prev = monthFirst.get(monthKey)
      if (prev == null) {
        monthFirst.set(monthKey, i)
        continue
      }
      const prevDay = Number(String(points[prev].label).split('-')[1] || 99)
      // 优先真正的 1 号；否则保留更靠前的日期
      if (day === 1 && prevDay !== 1) monthFirst.set(monthKey, i)
      else if (day !== 1 && prevDay !== 1 && day < prevDay) monthFirst.set(monthKey, i)
    }
    const idxSet = new Set<number>([0, n - 1])
    for (const idx of monthFirst.values()) idxSet.add(idx)
    // 月数过多（两年/五年）先按月抽稀，再按图宽补点
    let monthIdxs = [...monthFirst.values()].sort((a, b) => a - b)
    if (monthIdxs.length > 16) {
      const stepM = Math.max(1, Math.ceil((monthIdxs.length - 1) / 15))
      monthIdxs = monthIdxs.filter((i, k) => k === 0 || k === monthIdxs.length - 1 || k % stepM === 0)
      idxSet.clear()
      idxSet.add(0)
      idxSet.add(n - 1)
      for (const i of monthIdxs) idxSet.add(i)
    }
    if (idxSet.size < targetCount) {
      const step = Math.max(1, Math.round((n - 1) / Math.max(targetCount - 1, 1)))
      for (let i = 0; i < n; i += step) idxSet.add(i)
      idxSet.add(n - 1)
    }
    mergeSpaced([...idxSet])
  } else {
    const step = Math.max(1, Math.round((n - 1) / Math.max(targetCount - 1, 1)))
    const idxSet = new Set<number>([0, n - 1])
    for (let i = step; i < n - 1; i += step) idxSet.add(i)
    mergeSpaced([...idxSet])
  }
  // 总览圆点：点数太多（两年/五年）会糊成「打码」，直接关掉只留折线
  const showDots = props.overview && !isCandle.value && n < 280 && gap >= 3.2
  let dotR = 2.2
  if (showDots) {
    if (n >= 220) dotR = 1.4
    else if (n >= 140) dotR = 1.7
    else if (n >= 80) dotR = 2
    else if (gap < 6) dotR = 1.6
  }
  return {
    points,
    line,
    area,
    yLeft,
    yRight,
    xLabels,
    gridY,
    yOf,
    mid,
    w: chartW,
    h: chartH,
    scrollable,
    showDots,
    dotR,
  }
})

const active = computed(() => {
  if (!priceRevealed.value) return null
  const i = hoverIdx.value
  if (i == null || !chart.value.points[i]) return null
  return chart.value.points[i]
})

/** 悬停时隐藏过近的轴刻度，避免蓝底压住轴日期 */
const visibleXLabels = computed(() => {
  const labels = chart.value.xLabels
  const a = active.value
  if (!a) return labels
  // 悬停蓝底约 60px 宽，过近轴刻度先藏；别留太大空隙显得空
  return labels.filter((lb) => Math.abs(lb.x - a.x) >= 58)
})

function fmtPrice(n: number) {
  return formatQuotePrice(n, priceRevealed.value)
}

function clientToSvgX(clientX: number) {
  const svg = svgRef.value
  if (!svg) return 0
  const pt = svg.createSVGPoint()
  pt.x = clientX
  pt.y = 0
  const ctm = svg.getScreenCTM()
  if (!ctm) return 0
  return pt.matrixTransform(ctm.inverse()).x
}

function pickIndex(svgX: number) {
  const pts = chart.value.points
  if (!pts.length) return null
  let best = 0
  let bestD = Infinity
  for (let i = 0; i < pts.length; i++) {
    const d = Math.abs(pts[i].x - svgX)
    if (d < bestD) {
      bestD = d
      best = i
    }
  }
  return best
}

function onMove(e: MouseEvent | TouchEvent) {
  if (!priceRevealed.value) return
  const clientX = 'touches' in e ? e.touches[0]?.clientX : e.clientX
  if (clientX == null) return
  hoverIdx.value = pickIndex(clientToSvgX(clientX))
}

function onLeave() {
  hoverIdx.value = null
}

function pinLatest() {
  const el = scrollRef.value
  if (!el) return
  el.scrollLeft = el.scrollWidth
}

function stopDrawAnim() {
  if (drawRaf) {
    cancelAnimationFrame(drawRaf)
    drawRaf = 0
  }
}

/** 折线从左到右画出；切换品种 / 月份 / 模式时重放 */
function playDrawAnim() {
  stopDrawAnim()
  const target = Math.max(chart.value.w || 0, 1)
  if (isCandle.value || !chart.value.line) {
    clipW.value = target
    return
  }
  clipW.value = 0
  const start = performance.now()
  const tick = (now: number) => {
    const t = Math.min(1, (now - start) / DRAW_MS)
    const eased = 1 - (1 - t) ** 3
    clipW.value = target * eased
    if (t < 1) drawRaf = requestAnimationFrame(tick)
    else drawRaf = 0
  }
  drawRaf = requestAnimationFrame(tick)
}

let resizeObserver: ResizeObserver | null = null
onMounted(() => {
  const el = scrollRef.value
  if (el) {
    const measure = () => {
      const next = el.clientWidth
      if (next > 0) viewW.value = next
    }
    measure()
    if (typeof ResizeObserver !== 'undefined') {
      resizeObserver = new ResizeObserver(measure)
      resizeObserver.observe(el)
    }
    pinLatest()
  }
  nextTick(playDrawAnim)
})
onBeforeUnmount(() => {
  resizeObserver?.disconnect()
  stopDrawAnim()
})

function goLogin() {
  const redirect = props.loginRedirect || route.fullPath || '/'
  navigateTo({ path: '/login', query: { redirect } })
}

watch(() => [props.candles, props.mode, priceRevealed.value], () => {
  hoverIdx.value = null
  nextTick(() => {
    pinLatest()
    playDrawAnim()
  })
})
watch(viewW, (w, prev) => {
  if (!prev || prev === 640) {
    nextTick(() => {
      pinLatest()
      playDrawAnim()
    })
  }
})

const yBadgeW = computed(() => {
  if (!active.value) return 58
  const t = fmtPrice(active.value.value)
  return Math.max(58, 10 + t.length * 7.2)
})

function hoverDate(label: string) {
  const m = String(label || '').match(/^(\d{2})-(\d{2})$/)
  if (!m) return label
  return `${Number(m[1])}月${Number(m[2])}日`
}

/** 轴刻度：仅首尾贴边对齐；悬停蓝底按位置左/中/右对齐，避免裁切与叠字 */
function tickStyle(x: number, text: string, kind: 'axis' | 'hover' = 'axis') {
  const t = String(text || '')
  const width = chart.value.w || 1
  const px = Math.min(Math.max(Number(x) || 0, 0), width)
  if (kind === 'hover') {
    const est = Math.max(52, 12 + [...t].length * 12)
    if (px <= est * 0.55) return { left: `${px}px`, transform: 'translateX(0)' }
    if (px >= width - est * 0.55) return { left: `${px}px`, transform: 'translateX(-100%)' }
    return { left: `${px}px`, transform: 'translateX(-50%)' }
  }
  const labels = chart.value.xLabels
  const first = labels[0]
  const last = labels[labels.length - 1]
  if (first && Math.abs(first.x - px) < 0.5) return { left: `${px}px`, transform: 'translateX(0)' }
  if (last && Math.abs(last.x - px) < 0.5) return { left: `${px}px`, transform: 'translateX(-100%)' }
  return { left: `${px}px`, transform: 'translateX(-50%)' }
}
</script>

<template>
  <div class="line-wrap" :class="{ locked: !priceRevealed, expanded, overview }">
    <div class="line-stage">
      <div class="chart-viewport">
        <div class="y-col left" aria-hidden="true" :style="{ height: `${chart.h}px` }">
          <span
            v-for="(t, i) in chart.yLeft"
            :key="'yl' + i"
            :style="{ top: `${chart.gridY[i] || 0}px`, color: i < 2 ? '#F04B3E' : i === 2 ? '#13294B' : '#14B968' }"
          >{{ t }}</span>
          <span v-if="active" class="y-badge" :style="{ top: `${active.y}px`, width: `${yBadgeW}px` }">{{ fmtPrice(active.value) }}</span>
        </div>
        <div ref="scrollRef" class="chart-scroll" :class="{ fit: !chart.scrollable }">
      <svg
        ref="svgRef"
        :viewBox="`0 0 ${chart.w} ${chart.h}`"
        :width="chart.scrollable ? chart.w : '100%'"
        :height="chart.h"
        class="line-chart"
        :style="{ height: `${chart.h}px` }"
        preserveAspectRatio="none"
        role="img"
        :aria-label="ariaLabel"
        @mousemove="onMove"
        @mouseleave="onLeave"
        @touchstart.passive="onMove"
        @touchmove.passive="onMove"
        @touchend="onLeave"
      >
        <defs>
          <linearGradient :id="fillGradId" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="rgba(36,104,242,.28)" />
            <stop offset="100%" stop-color="rgba(36,104,242,0)" />
          </linearGradient>
          <clipPath :id="drawClipId">
            <rect x="0" y="0" :width="clipW" :height="chart.h" />
          </clipPath>
        </defs>

        <g stroke="#EDF1F8">
          <line
            v-for="(y, i) in chart.gridY"
            :key="'g' + i"
            x1="0"
            :y1="y"
            :x2="chart.w"
            :y2="y"
          />
        </g>

        <template v-if="!isCandle">
          <g :clip-path="`url(#${drawClipId})`">
            <path v-if="chart.area" :d="chart.area" :fill="`url(#${fillGradId})`" />
            <path
              v-if="chart.line"
              :d="chart.line"
              fill="none"
              stroke="#2468F2"
              stroke-width="2.4"
              :stroke-linejoin="overview ? 'miter' : 'round'"
              :stroke-linecap="overview ? 'butt' : 'round'"
            />
            <!-- 总览对齐后台：每个报价日落蓝点 -->
            <g v-if="chart.showDots">
              <circle
                v-for="(p, i) in chart.points"
                :key="'dot' + i"
                :cx="p.x"
                :cy="p.y"
                :r="chart.dotR"
                fill="#2468F2"
                stroke="#fff"
                :stroke-width="chart.dotR <= 1.7 ? 1 : 1.4"
              >
                <title>{{ p.label }} {{ fmtPrice(p.value) }}</title>
              </circle>
            </g>
          </g>
        </template>

        <g v-else>
          <g v-for="(b, i) in chart.points" :key="'c' + i">
            <line
              :x1="b.x"
              :y1="b.wickTop"
              :x2="b.x"
              :y2="b.wickBot"
              :stroke="b.up ? '#F04B3E' : '#14B968'"
              stroke-width="1.4"
            />
            <rect
              :x="b.x - b.bw / 2"
              :y="b.bodyTop"
              :width="b.bw"
              :height="b.bodyH"
              :fill="b.up ? '#F04B3E' : '#14B968'"
              rx="0.5"
            />
          </g>
        </g>

        <g v-if="active" pointer-events="none">
          <line
            :x1="active.x"
            :y1="padT"
            :x2="active.x"
            :y2="chart.h - padB"
            stroke="#2468F2"
            stroke-width="1.2"
            stroke-dasharray="4 3"
            opacity="0.9"
          />
          <line
            x1="0"
            :y1="active.y"
            :x2="chart.w"
            :y2="active.y"
            stroke="#2468F2"
            stroke-width="1.2"
            stroke-dasharray="4 3"
            opacity="0.9"
          />
          <circle
            :cx="active.x"
            :cy="active.y"
            r="5"
            fill="#2468F2"
            stroke="#fff"
            stroke-width="2.2"
          />
        </g>
      </svg>
      <div
        v-if="chart.xLabels.length"
        class="x-axis"
        :style="chart.scrollable ? { width: `${chart.w}px` } : { width: '100%' }"
        aria-hidden="true"
      >
        <span
          v-for="(x, i) in visibleXLabels"
          :key="'xl' + i"
          class="x-tick"
          :style="tickStyle(x.x, x.t, 'axis')"
        >{{ x.t }}</span>
        <span
          v-if="active"
          class="x-tick hover-date"
          :style="tickStyle(active.x, hoverDate(active.label), 'hover')"
        >{{ hoverDate(active.label) }}</span>
      </div>
        </div>
        <div class="y-col right" aria-hidden="true" :style="{ height: `${chart.h}px` }">
          <span
            v-for="(t, i) in chart.yRight"
            :key="'yr' + i"
            :style="{ top: `${chart.gridY[i] || 0}px`, color: i < 2 ? '#F04B3E' : i === 2 ? '#98A5BC' : '#14B968' }"
          >{{ t }}</span>
        </div>
      </div>
      <img
        class="chart-watermark"
        src="/img/chart-watermark.png?v=2"
        alt=""
      >

      <div v-if="!priceRevealed" class="chart-gate">
        <div v-if="gate === 'vip'" class="chart-gate-card">
          <p class="chart-gate-title">升级会员查看历史行情</p>
          <p class="chart-gate-desc">开通会员后可回看历史月份，最高可查看近 5 年价格行情</p>
          <button type="button" class="chart-gate-btn" @click="emit('upgrade')">去升级</button>
        </div>
        <div v-else class="chart-gate-card">
          <p class="chart-gate-title">登录后可查看完整价格</p>
          <p class="chart-gate-desc">走势坐标与读数已隐藏，登录即可查看详情</p>
          <button type="button" class="chart-gate-btn" @click="goLogin">立即登录</button>
        </div>
      </div>
    </div>
    <p class="line-hint">
      <template v-if="!priceRevealed && gate === 'vip'">升级会员后可查看该月价格与涨跌幅</template>
      <template v-else-if="!priceRevealed">登录后可滑动查看价格读数</template>
      <template v-else-if="chart.scrollable">{{ expanded ? '左右滑动查看整段起伏，悬停可读当天价格' : '左右滑动查看更早的日期，悬停可看当天价格' }}</template>
      <template v-else-if="overview">整段区间一屏展示；点「详情展开」可放大横滑细看</template>
      <template v-else>{{ isCandle ? '悬停查看开高低收与坐标读数' : '悬停查看坐标读数' }}</template>
    </p>
  </div>
</template>

<style scoped>
.line-wrap {
  width: 100%;
  min-height: 252px;
}
.line-wrap.expanded {
  min-height: 420px;
}
.line-wrap.overview {
  min-height: 332px;
}
.line-stage {
  position: relative;
  width: 100%;
}
.chart-viewport {
  display: grid;
  grid-template-columns: 52px minmax(0, 1fr) 48px;
  align-items: start;
}
.chart-scroll {
  min-width: 0;
  overflow-x: auto;
  overflow-y: hidden;
  scrollbar-width: thin;
}
.chart-scroll.fit {
  overflow-x: hidden;
  overflow-y: visible;
}
.chart-scroll.fit .line-chart {
  width: 100% !important;
  max-width: 100% !important;
}
.chart-scroll.fit .x-axis {
  width: 100% !important;
}
.y-col {
  position: relative;
  height: 200px;
  pointer-events: none;
}
.line-wrap.overview .y-col {
  height: 280px;
}
.line-wrap.expanded .y-col {
  height: 360px;
}
.y-col span {
  position: absolute;
  left: 0;
  right: 0;
  font-size: 10.5px;
  line-height: 1;
  transform: translateY(-50%);
  white-space: nowrap;
}
.y-col.right span {
  text-align: right;
}
.y-badge {
  z-index: 2;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 22px;
  border-radius: 4px;
  background: #2468f2;
  color: #fff !important;
  font-size: 11px;
  font-weight: 700;
}
.chart-watermark {
  position: absolute;
  left: 50%;
  top: 42%;
  z-index: 0;
  width: min(168px, 42%);
  height: auto;
  transform: translate(-50%, -50%);
  opacity: 0.55;
  pointer-events: none;
  user-select: none;
}
.line-chart {
  position: relative;
  z-index: 1;
  display: block;
  /* 高度由 :style / :height 绑定 chart.h，避免与 Y 轴错位 */
  max-width: none;
  cursor: crosshair;
  touch-action: pan-x pan-y;
  overflow: visible;
}
.x-axis {
  position: relative;
  height: 26px;
  margin-top: 2px;
  pointer-events: none;
  z-index: 2;
  overflow: visible;
}
.x-tick {
  position: absolute;
  top: 2px;
  transform: translateX(-50%);
  font-size: 13px;
  line-height: 20px;
  color: #5a6b85;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}
.x-tick.hover-date {
  z-index: 3;
  color: #fff;
  background: #2468f2;
  border-radius: 4px;
  padding: 0 7px;
  font-weight: 700;
}
.line-wrap.locked .line-chart {
  cursor: default;
  filter: blur(1.2px);
  opacity: 0.72;
}
.line-hint {
  margin: 4px 0 0;
  height: 18px;
  font-size: 11.5px;
  color: #98a5bc;
  text-align: right;
  line-height: 18px;
}
.chart-gate {
  position: absolute;
  inset: 0;
  z-index: 3;
  display: grid;
  place-items: center;
  background: linear-gradient(180deg, rgba(246, 249, 254, 0.18), rgba(246, 249, 254, 0.55));
  backdrop-filter: blur(0.5px);
}
.chart-gate-card {
  text-align: center;
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid #e9eef6;
  border-radius: 14px;
  padding: 14px 18px 12px;
  box-shadow: 0 10px 28px rgba(19, 41, 75, 0.12);
  max-width: min(280px, 92%);
}
.chart-gate-title {
  margin: 0;
  font-size: 14px;
  font-weight: 800;
  color: #13294b;
}
.chart-gate-desc {
  margin: 6px 0 0;
  font-size: 12px;
  color: #5a6b85;
  line-height: 1.45;
}
.chart-gate-btn {
  margin-top: 10px;
  height: 34px;
  padding: 0 16px;
  border: 0;
  border-radius: 999px;
  background: linear-gradient(135deg, #1d4fd7, #3f86f9);
  color: #fff;
  font: inherit;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 6px 14px rgba(36, 104, 242, 0.28);
}
.chart-gate-btn:hover {
  filter: brightness(1.04);
}
</style>

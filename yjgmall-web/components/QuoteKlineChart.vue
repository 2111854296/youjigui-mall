<script setup lang="ts">
/** 折线 / K线 双模式走势图；未登录价格打星并提示登录 */
export type KlinePoint = {
  label: string
  /** YYYY-MM-DD，多年图用来画年份分界；总览 01-01/06-01，展开约每 10 日 */
  date?: string
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

/** 展开图滚轮缩放：越大日期间距越宽（点距越大） */
const slotZoom = ref(1)
const SLOT_BASE = 22
const SLOT = computed(() => {
  if (!props.expanded) return 48
  return Math.round(SLOT_BASE * slotZoom.value * 10) / 10
})
const h = computed(() => (props.expanded ? 360 : (props.overview ? 280 : 200)))
const padT = computed(() => (props.overview ? 18 : 16))
const padB = computed(() => (props.overview ? 36 : 28))
/** 展开图左右多留空，避免首尾点/描边被裁成「显示不全」 */
const innerPad = computed(() => (props.expanded ? 20 : 10))

watch(() => props.expanded, (on) => {
  if (on) slotZoom.value = 1
})

/** 滚轮缩放后图宽会变；裁剪宽度必须跟上，否则折线被 clip 裁没 */
function syncClipToWidth() {
  stopDrawAnim()
  clipW.value = Math.max(chart.value.w || 0, 1)
}

type PlotPt = {
  label: string
  date: string
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

type YearMark = { x: number; year: string; midX?: number }

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
      yearLines: [] as YearMark[],
      yearLabels: [] as YearMark[],
      gridY: [] as number[],
      yOf: (_v: number) => 0,
      mid: 0,
      w: boxW,
      h: h.value,
      scrollable: false,
      showDots: false,
      dotR: 2.2,
      multiYear: false,
      padTop: padT.value,
    }
  }

  // 去掉无报价点；若混入异常低价（如误导 107），按中位数剔除，避免量程被拉垮、折线坠底
  const positiveIdx: number[] = []
  for (let i = 0; i < raw.length; i++) {
    if (Number(raw[i].close) > 0) positiveIdx.push(i)
  }
  let useIdx = positiveIdx
  if (positiveIdx.length >= 5) {
    const sorted = positiveIdx.map((i) => Number(raw[i].close)).sort((a, b) => a - b)
    const med = sorted[Math.floor(sorted.length / 2)] || 0
    if (med >= 500) {
      const floor = med * 0.25
      const kept = positiveIdx.filter((i) => Number(raw[i].close) >= floor)
      if (kept.length >= 3) useIdx = kept
    }
  }
  const plotRaw = useIdx.map((i) => raw[i])
  if (plotRaw.length < 1) {
    return {
      points: [] as PlotPt[],
      line: '',
      area: '',
      yLeft: [] as string[],
      yRight: [] as string[],
      xLabels: [] as { x: number; t: string }[],
      yearLines: [] as YearMark[],
      yearLabels: [] as YearMark[],
      gridY: [] as number[],
      yOf: (_v: number) => 0,
      mid: 0,
      w: boxW,
      h: h.value,
      scrollable: false,
      showDots: false,
      dotR: 2.2,
      multiYear: false,
      padTop: padT.value,
    }
  }

  const closes = plotRaw.map((p) => Number(p.close) || 0)
  const highs = plotRaw.map((p, i) => {
    const c = closes[i]
    const h0 = Number(p.high)
    return Number.isFinite(h0) && h0 > 0 ? h0 : c
  })
  const lows = plotRaw.map((p, i) => {
    const c = closes[i]
    const l0 = Number(p.low)
    return Number.isFinite(l0) && l0 > 0 ? l0 : c
  })

  let dataMin: number
  let dataMax: number
  if (isCandle.value) {
    dataMin = Math.min(...lows)
    dataMax = Math.max(...highs)
  } else {
    dataMin = Math.min(...closes)
    dataMax = Math.max(...closes)
  }
  const mid0 = (dataMin + dataMax) / 2
  const dataSpan = Math.max(dataMax - dataMin, 0)
  // 留白收紧：展开图尤其贴数据，避免「往上抬一大截」
  const flatThresh = Math.max(80, Math.abs(mid0) * 0.006)
  let lo = dataMin
  let hi = dataMax
  if (dataSpan < flatThresh) {
    const half = Math.max(80, Math.abs(mid0) * 0.006)
    lo = mid0 - half
    hi = mid0 + half
  } else {
    const padRatio = props.expanded ? 0.04 : (props.overview ? 0.1 : 0.06)
    const pad = Math.max(dataSpan * padRatio, Math.abs(mid0) * 0.002, 1)
    lo = dataMin - pad
    hi = dataMax + pad
  }
  /** 等间距 5 档，优先小步长、贴数据，避免量程抬得过高 */
  const niceNum = (rough: number) => {
    const r = Math.max(Math.abs(rough), 1e-6)
    const exp = Math.floor(Math.log10(r))
    const f = r / 10 ** exp
    const nf = f <= 1 ? 1 : f <= 2 ? 2 : f <= 5 ? 5 : 10
    return nf * 10 ** exp
  }
  const niceScale = (() => {
    const tickCount = 5
    const gaps = tickCount - 1
    const candidates = [50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000]
    const want = Math.max((hi - lo) / gaps, 1)
    const pack = (s: number) => {
      const maxA = Math.ceil(hi / s) * s
      const minA = maxA - gaps * s
      const okA = minA <= lo + s * 1e-9
      const minB = Math.floor(lo / s) * s
      const maxB = minB + gaps * s
      const okB = maxB >= hi - s * 1e-9
      if (!okA && !okB) return null
      if (okA && !okB) return { min: minA, max: maxA, waste: maxA - minA - (hi - lo) }
      if (!okA && okB) return { min: minB, max: maxB, waste: maxB - minB - (hi - lo) }
      const wasteA = maxA - minA - (hi - lo)
      const wasteB = maxB - minB - (hi - lo)
      return wasteA <= wasteB
        ? { min: minA, max: maxA, waste: wasteA }
        : { min: minB, max: maxB, waste: wasteB }
    }
    let best: { min: number; max: number; waste: number; step: number } | null = null
    for (const s of candidates) {
      if (s < want * 0.5) continue
      const w = pack(s)
      if (!w) continue
      // 拒绝明显空荡的大步长
      if (w.max - w.min > Math.max(hi - lo, 1) * 1.65 && s > want * 1.05) continue
      if (!best || w.waste < best.waste - 1e-6 || (Math.abs(w.waste - best.waste) < 1e-6 && s < best.step)) {
        best = { ...w, step: s }
      }
      if (best && best.waste <= Math.max(hi - lo, 1) * 0.25 && best.step <= want * 1.2) break
    }
    if (!best) {
      for (const s of candidates) {
        const w = pack(s)
        if (w) {
          best = { ...w, step: s }
          break
        }
      }
    }
    if (!best) {
      const s = Math.max(niceNum(want), 1)
      const max = Math.ceil(hi / s) * s
      best = { min: max - gaps * s, max, waste: 0, step: s }
    }
    const ticks: number[] = []
    for (let i = 0; i < tickCount; i++) {
      ticks.push(Math.round(best.max - i * best.step))
    }
    return {
      vmin: ticks[ticks.length - 1],
      vmax: ticks[0],
      ticks,
      step: best.step,
    }
  })()
  let vmin = niceScale.vmin
  let vmax = niceScale.vmax
  const span = Math.max(vmax - vmin, 1)
  const mid = (vmin + vmax) / 2
  const n = plotRaw.length
  const slot = SLOT.value
  const chartH = h.value
  const padX = innerPad.value
  /** 补齐 YYYY-MM-DD：优先用 date；否则用 MM-DD 标签按月份回绕推断年份 */
  const normDates: string[] = (() => {
    const out: string[] = new Array(n).fill('')
    let seedYear = 0
    for (let i = 0; i < n; i++) {
      const full = String(plotRaw[i]?.date || '').slice(0, 10)
      if (/^\d{4}-\d{2}-\d{2}$/.test(full)) {
        out[i] = full
        seedYear = Number(full.slice(0, 4))
      }
    }
    if (!seedYear) {
      // 无任何完整日期时，用当前年往回推（仅作兜底，多年分界仍尽量可用）
      seedYear = new Date().getFullYear()
    }
    // 从第一个已知完整日期向两侧填；若全无则从 seedYear 起按月回绕
    let firstKnown = out.findIndex((d) => d)
    if (firstKnown < 0) {
      let y = seedYear
      let lastM = 0
      for (let i = 0; i < n; i++) {
        const m = String(plotRaw[i]?.label || '').match(/^(\d{1,2})-(\d{1,2})$/)
        if (!m) continue
        const month = Number(m[1])
        const day = Number(m[2])
        if (lastM && month < lastM) y += 1
        lastM = month
        out[i] = `${y}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`
      }
    } else {
      // 向前填
      let y = Number(out[firstKnown].slice(0, 4))
      let nextM = Number(out[firstKnown].slice(5, 7))
      for (let i = firstKnown - 1; i >= 0; i--) {
        if (out[i]) {
          y = Number(out[i].slice(0, 4))
          nextM = Number(out[i].slice(5, 7))
          continue
        }
        const m = String(plotRaw[i]?.label || '').match(/^(\d{1,2})-(\d{1,2})$/)
        if (!m) continue
        const month = Number(m[1])
        const day = Number(m[2])
        if (month > nextM) y -= 1
        nextM = month
        out[i] = `${y}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`
      }
      // 向后填
      y = Number(out[firstKnown].slice(0, 4))
      let lastM = Number(out[firstKnown].slice(5, 7))
      for (let i = firstKnown + 1; i < n; i++) {
        if (out[i]) {
          y = Number(out[i].slice(0, 4))
          lastM = Number(out[i].slice(5, 7))
          continue
        }
        const m = String(plotRaw[i]?.label || '').match(/^(\d{1,2})-(\d{1,2})$/)
        if (!m) continue
        const month = Number(m[1])
        const day = Number(m[2])
        if (lastM && month < lastM) y += 1
        lastM = month
        out[i] = `${y}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`
      }
    }
    return out
  })()
  const datedList = normDates.filter((d) => /^\d{4}-\d{2}-\d{2}$/.test(d))
  const firstYearRaw = datedList.length ? Number(datedList[0].slice(0, 4)) : 0
  const lastYearRaw = datedList.length ? Number(datedList[datedList.length - 1].slice(0, 4)) : 0
  const multiYearEarly = firstYearRaw > 0 && lastYearRaw > firstYearRaw
  // 多年图顶部留给年份标注（总览 + 展开详情同理）
  const pt = padT.value + (multiYearEarly ? 16 : 0)
  const pb = padB.value
  const pointTimes = normDates.map((d) =>
    (/^\d{4}-\d{2}-\d{2}$/.test(d) ? Date.parse(d.replace(/-/g, '/')) : NaN),
  )
  const finiteTimes = pointTimes.filter((t) => Number.isFinite(t))
  const tMin = finiteTimes.length ? Math.min(...finiteTimes) : 0
  const tMax = finiteTimes.length ? Math.max(...finiteTimes) : 0
  const daySpan = tMax > tMin ? Math.max(1, Math.round((tMax - tMin) / 86400000)) : Math.max(n - 1, 1)
  // 总览 / 单月：整段铺满；展开：按实际日历跨度拉宽（缺口日也占位，避免挤在一起像断线）
  const fitWidth = props.overview || (!props.expanded && n <= 31)
  const plotW = fitWidth
    ? Math.max(boxW - padX * 2, 200)
    : Math.max(
      isCandle.value ? n * slot : (props.expanded ? daySpan * slot : Math.max(n - 1, 1) * slot),
      boxW - padX * 2,
    )
  const chartW = fitWidth ? boxW : plotW + padX * 2
  const scrollable = !fitWidth && chartW > boxW + 1
  const gap = isCandle.value ? plotW / Math.max(n, 1) : (n <= 1 ? plotW : plotW / (n - 1))
  const bw = Math.max(4, Math.min(14, gap * 0.62))
  // 与侧轴同一套线性映射，不再把低点钳到「假的轴底」（否则 13600 会看起来和 14000 一样高）
  const yOf = (v: number) => pt + (1 - (v - vmin) / span) * (chartH - pt - pb)

  const points: PlotPt[] = plotRaw.map((p, i) => {
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
    const fullDate = normDates[i] || String(p.date || '').slice(0, 10)
    const t = pointTimes[i]
    let x: number
    if (isCandle.value) {
      x = padX + gap * (i + 0.5)
    } else if (n === 1) {
      x = padX + plotW / 2
    } else if (props.expanded && Number.isFinite(t) && tMax > tMin) {
      x = padX + ((t - tMin) / (tMax - tMin)) * plotW
    } else {
      x = padX + gap * i
    }
    const yO = yOf(open)
    const yC = yOf(close)
    return {
      label: p.label || (fullDate ? fullDate.slice(5) : ''),
      date: /^\d{4}-\d{2}-\d{2}$/.test(fullDate) ? fullDate : '',
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

  // 日期间隔过大则断开，不硬连成「坠底再消失」的斜线；展开图不做面积封口
  const GAP_BREAK_DAYS = 5
  const line = (() => {
    if (!points.length) return ''
    let d = ''
    for (let i = 0; i < points.length; i++) {
      let brk = i === 0
      if (!brk && props.expanded) {
        const t0 = pointTimes[i - 1]
        const t1 = pointTimes[i]
        if (Number.isFinite(t0) && Number.isFinite(t1) && (t1 - t0) / 86400000 > GAP_BREAK_DAYS) brk = true
      }
      d += `${brk ? 'M' : 'L'}${points[i].x.toFixed(2)},${points[i].y.toFixed(2)} `
    }
    return d.trim()
  })()
  const baseY = chartH - pb
  const area = (!points.length || props.expanded)
    ? ''
    : `${line} L${points[points.length - 1].x.toFixed(2)},${baseY} L${points[0].x.toFixed(2)},${baseY} Z`

  const yLevels = niceScale.ticks
  const hidden = !revealed
  const yLeft = yLevels.map((v) => (hidden && props.gate === 'vip' ? '*****' : formatQuotePrice(v, revealed)))
  const yRight = yLevels.map((v) => {
    if (hidden && props.gate === 'vip') return '*****'
    if (!revealed) return '**%'
    const pct = mid ? ((v - mid) / mid) * 100 : 0
    return `${pct >= 0 ? '+' : ''}${pct.toFixed(2)}%`
  })
  const gridY = yLevels.map((v) => yOf(v))

  /** 按完整日期在点列上取 x：精确命中，否则插值到相邻有日期点之间 */
  const xAtDate = (iso: string): number | null => {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(iso) || !points.length) return null
    const datedIdx: number[] = []
    for (let i = 0; i < n; i++) {
      if (/^\d{4}-\d{2}-\d{2}$/.test(points[i].date)) datedIdx.push(i)
    }
    if (!datedIdx.length) return null
    const first = datedIdx[0]
    const last = datedIdx[datedIdx.length - 1]
    if (iso <= points[first].date) return points[first].x
    if (iso >= points[last].date) return points[last].x
    let lo = 0
    let hi = datedIdx.length - 1
    while (lo <= hi) {
      const midK = (lo + hi) >> 1
      const midI = datedIdx[midK]
      const d = points[midI].date
      if (d === iso) return points[midI].x
      if (d < iso) lo = midK + 1
      else hi = midK - 1
    }
    const rightK = Math.min(datedIdx.length - 1, Math.max(1, lo))
    const leftI = datedIdx[rightK - 1]
    const rightI = datedIdx[rightK]
    const d0 = points[leftI].date
    const d1 = points[rightI].date
    const t0 = Date.parse(d0.replace(/-/g, '/'))
    const t1 = Date.parse(d1.replace(/-/g, '/'))
    const t = Date.parse(iso.replace(/-/g, '/'))
    if (!Number.isFinite(t0) || !Number.isFinite(t1) || t1 <= t0) return points[rightI].x
    const r = Math.min(1, Math.max(0, (t - t0) / (t1 - t0)))
    return points[leftI].x + (points[rightI].x - points[leftI].x) * r
  }

  const firstDated = points.find((p) => /^\d{4}-\d{2}-\d{2}$/.test(p.date))
  const lastDated = [...points].reverse().find((p) => /^\d{4}-\d{2}-\d{2}$/.test(p.date))
  const firstY = firstDated ? Number(firstDated.date.slice(0, 4)) : 0
  const lastY = lastDated ? Number(lastDated.date.slice(0, 4)) : 0
  const multiYear = firstY > 0 && lastY > firstY

  const yearLines: YearMark[] = []
  const yearLabels: YearMark[] = []
  if (multiYear) {
    for (let y = firstY + 1; y <= lastY; y++) {
      const x = xAtDate(`${y}-01-01`)
      if (x == null) continue
      // 分界线落在首尾点上就没辨识度，略过
      if (x <= points[0].x + 1 || x >= points[n - 1].x - 1) continue
      yearLines.push({ x, year: String(y) })
    }
    for (let y = firstY; y <= lastY; y++) {
      const startIso = y === firstY ? (firstDated?.date || `${y}-01-01`) : `${y}-01-01`
      const endIso = y === lastY ? (lastDated?.date || `${y}-12-31`) : `${y}-12-31`
      const x0 = xAtDate(startIso)
      const x1 = xAtDate(endIso)
      if (x0 == null || x1 == null) continue
      yearLabels.push({ x: x0, year: String(y), midX: (x0 + x1) / 2 })
    }
  }

  // 横轴刻度：按图宽定密度 + 最小间距，避免「月初贴首日叠字」或「整段只剩 3 个日期太空」
  let xLabels: { x: number; t: string }[] = []
  // 展开：轴标最小间距固定，放大后点距变大 → 自然多露出日期；总览仍控制不叠字
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
    else if (d === 10 || d === 20) s += 40
    else if (d === 15) s += 25
    return s
  }
  const mergeSpaced = (idxs: number[], labelOf?: (i: number) => string) => {
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
    xLabels = kept.map((i) => ({
      x: points[i].x,
      t: labelOf ? labelOf(i) : points[i].label,
    }))
  }
  // 总览：约每 68px 一个；展开长图按像素多标，滑到哪一段底下都有日期
  const labelEveryPx = props.expanded ? 56 : 68
  const maxTicks = props.expanded ? 80 : 14
  const targetCount = Math.min(
    maxTicks,
    Math.max(n <= 12 ? n : 5, Math.round(plotW / labelEveryPx)),
  )

  // 详情展开：滚轮放大 → 底下日期变多；缩小 → 变少（与点距联动）
  // 多年总览：一屏太密，只标 01-01 / 06-01
  if (props.expanded) {
    const ticks: { x: number; t: string }[] = []
    const pushTick = (iso: string, text: string) => {
      const x = xAtDate(iso)
      if (x == null) return
      if (ticks.length && Math.abs(x - ticks[ticks.length - 1].x) < MIN_GAP * 0.85) return
      ticks.push({ x, t: text })
    }
    const startIso = firstDated?.date || ''
    const endIso = lastDated?.date || ''
    const z = slotZoom.value
    // 缩：每月 1 号；默认：1/10/20；放大：更密；再放大：有报价日尽量都标
    let markDays: number[] | 'dense' | 'daily'
    if (z < 0.62) markDays = [1]
    else if (z < 1.12) markDays = [1, 10, 20]
    else if (z < 1.75) markDays = [1, 5, 10, 15, 20, 25]
    else if (z < 2.2) markDays = 'dense'
    else markDays = 'daily'

    if (/^\d{4}-\d{2}-\d{2}$/.test(startIso) && /^\d{4}-\d{2}-\d{2}$/.test(endIso)) {
      ticks.push({ x: points[0].x, t: startIso.slice(5) })

      if (markDays === 'daily' || markDays === 'dense') {
        const stepDay = markDays === 'daily' ? 1 : 2
        const t0 = Date.parse(startIso.replace(/-/g, '/'))
        const t1 = Date.parse(endIso.replace(/-/g, '/'))
        if (Number.isFinite(t0) && Number.isFinite(t1) && t1 > t0) {
          for (let t = t0 + stepDay * 86400000; t < t1; t += stepDay * 86400000) {
            const d = new Date(t)
            const iso = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
            pushTick(iso, iso.slice(5))
          }
        }
      } else {
        let y = Number(startIso.slice(0, 4))
        let m = Number(startIso.slice(5, 7))
        const endY = Number(endIso.slice(0, 4))
        const endM = Number(endIso.slice(5, 7))
        while (y < endY || (y === endY && m <= endM)) {
          const mm = String(m).padStart(2, '0')
          const dim = new Date(y, m, 0).getDate()
          for (const d of markDays) {
            if (d > dim) continue
            const dd = String(d).padStart(2, '0')
            const iso = `${y}-${mm}-${dd}`
            if (iso <= startIso || iso >= endIso) continue
            pushTick(iso, `${mm}-${dd}`)
          }
          m += 1
          if (m > 12) {
            m = 1
            y += 1
          }
        }
      }

      const endMd = endIso.slice(5)
      const lastX = points[n - 1].x
      if (endMd && (!ticks.length || lastX - ticks[ticks.length - 1].x >= MIN_GAP * 0.5)) {
        if (!ticks.length || ticks[ticks.length - 1].t !== endMd) {
          ticks.push({ x: lastX, t: endMd })
        }
      }
    }
    xLabels = ticks
  } else if ((props.overview || multiYear) && n >= 3) {
    // 总览/多年：两端钉死真实起止日，中间按像素等分取文案（视觉间隔均匀，且不叠字）
    const x0 = points[0].x
    const x1 = points[n - 1].x
    const spanX = Math.max(x1 - x0, 1)
    const mdOf = (i: number) => {
      const p = points[i]
      const raw = (p?.date || p?.label || '').slice(-5)
      return /^\d{2}-\d{2}$/.test(raw) ? raw : String(p?.label || '')
    }
    const startT = mdOf(0)
    const endT = mdOf(n - 1)
    const tickPx = Math.max(MIN_GAP, multiYear ? 92 : 80)
    let count = Math.max(3, Math.min(multiYear ? 8 : 7, Math.round(spanX / tickPx) + 1))
    while (count > 3 && spanX / (count - 1) < MIN_GAP) count -= 1
    const slot = spanX / Math.max(count - 1, 1)
    const ticks: { x: number; t: string }[] = [{ x: x0, t: startT || '—' }]

    for (let i = 1; i < count - 1; i++) {
      const targetX = x0 + slot * i
      if (targetX - x0 < MIN_GAP * 0.95 || x1 - targetX < MIN_GAP * 0.95) continue

      let best = 0
      let bestDist = Infinity
      for (let j = 0; j < n; j++) {
        const d = Math.abs(points[j].x - targetX)
        if (d < bestDist) {
          bestDist = d
          best = j
        }
      }
      // 半槽内优先月初/月中，文案更整齐
      let pick = best
      let pickScore = -bestDist
      for (let j = 0; j < n; j++) {
        const d = Math.abs(points[j].x - targetX)
        if (d > slot * 0.45) continue
        const day = dayOf(j)
        const bonus = day === 1 ? 40 : day === 15 ? 25 : (day === 10 || day === 20) ? 12 : 0
        const score = bonus - d
        if (score > pickScore) {
          pickScore = score
          pick = j
        }
      }
      const t = mdOf(pick)
      if (!t) continue
      const prev = ticks[ticks.length - 1]
      if (targetX - prev.x < MIN_GAP * 0.92) continue
      if (prev.t === t) continue
      ticks.push({ x: targetX, t })
    }

    // 末端必留：若末个中间标过近则挤掉它
    while (ticks.length > 1 && x1 - ticks[ticks.length - 1].x < MIN_GAP * 0.92) ticks.pop()
    const lastT = endT || ticks[ticks.length - 1]?.t || ''
    if (ticks.length && ticks[ticks.length - 1].t === lastT && Math.abs(ticks[ticks.length - 1].x - x1) < 2) {
      ticks[ticks.length - 1] = { x: x1, t: lastT }
    } else {
      ticks.push({ x: x1, t: lastT })
    }
    xLabels = ticks.filter((tk) => tk.t)
  } else {
    // 短周期：按点位等分，日期取对应点，间隔视觉均匀
    const count = Math.min(Math.max(targetCount, 4), n)
    const ticks: { x: number; t: string }[] = []
    for (let i = 0; i < count; i++) {
      const idx = Math.round((i * (n - 1)) / Math.max(count - 1, 1))
      const p = points[idx]
      const t = (p.date ? p.date.slice(5) : p.label) || ''
      if (ticks.length && Math.abs(ticks[ticks.length - 1].x - p.x) < 4) continue
      ticks.push({ x: p.x, t })
    }
    xLabels = ticks
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
    yearLines,
    yearLabels,
    gridY,
    yOf,
    mid,
    w: chartW,
    h: chartH,
    scrollable,
    showDots,
    dotR,
    multiYear,
    padTop: pt,
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
  // 悬停带年份后蓝底更宽，轴刻度再让开一点
  const gap = chart.value.multiYear ? 96 : 58
  return labels.filter((lb) => Math.abs(lb.x - a.x) >= gap)
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

/** 展开图：滚轮放大/缩小日期间距（点距），锚在鼠标下以免跳位 */
function onWheelZoom(e: WheelEvent) {
  if (!props.expanded || !priceRevealed.value) return
  const el = scrollRef.value
  if (!el || !chart.value.scrollable) return
  e.preventDefault()
  const beforeW = Math.max(chart.value.w, 1)
  const rect = el.getBoundingClientRect()
  const localX = e.clientX - rect.left
  const anchor = el.scrollLeft + localX
  const ratio = anchor / beforeW
  const step = e.deltaY > 0 ? 0.88 : 1.14
  const next = Math.min(2.6, Math.max(0.38, slotZoom.value * step))
  if (Math.abs(next - slotZoom.value) < 0.001) return
  slotZoom.value = Math.round(next * 100) / 100
  nextTick(() => {
    // 图宽已变：立刻铺满裁剪，避免「日期还在、折线没了」
    syncClipToWidth()
    const afterW = Math.max(chart.value.w, 1)
    el.scrollLeft = Math.max(0, ratio * afterW - localX)
  })
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
  // 展开长图（尤其滚轮缩放后）不做擦除动画，避免 clip 宽度跟不上图宽导致线消失
  if (props.expanded || isCandle.value || !chart.value.line) {
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
watch(() => chart.value.w, (w) => {
  if (!props.expanded) return
  const width = Math.max(Number(w) || 0, 1)
  if (clipW.value < width - 0.5) clipW.value = width
})

const yBadgeW = computed(() => {
  if (!active.value) return 58
  const t = fmtPrice(active.value.value)
  return Math.max(58, 10 + t.length * 7.2)
})

function hoverDate(label: string, date = '') {
  const full = String(date || '').slice(0, 10)
  if (/^\d{4}-\d{2}-\d{2}$/.test(full)) {
    const y = Number(full.slice(0, 4))
    const m = Number(full.slice(5, 7))
    const d = Number(full.slice(8, 10))
    // 悬停始终带年份，避免两年/五年图只看月日分不清哪一年
    return `${y}年${m}月${d}日`
  }
  const m = String(label || '').match(/^(\d{1,2})-(\d{1,2})$/)
  if (!m) return label
  return `${Number(m[1])}月${Number(m[2])}日`
}

/** 轴刻度：仅首尾贴边对齐；悬停蓝底按位置左/中/右对齐，避免裁切与叠字 */
function tickStyle(x: number, text: string, kind: 'axis' | 'hover' = 'axis') {
  const t = String(text || '')
  const width = chart.value.w || 1
  const px = Math.min(Math.max(Number(x) || 0, 0), width)
  if (kind === 'hover') {
    const est = Math.max(72, 14 + [...t].length * 11)
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
        <div
          ref="scrollRef"
          class="chart-scroll"
          :class="{ fit: !chart.scrollable }"
          @wheel="onWheelZoom"
        >
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

        <!-- 多年（总览/展开详情）：年份分界竖线 + 年标 -->
        <g v-if="chart.yearLines.length" pointer-events="none">
          <line
            v-for="(yl, i) in chart.yearLines"
            :key="'yln' + i"
            :x1="yl.x"
            :y1="8"
            :x2="yl.x"
            :y2="chart.h - padB"
            stroke="#8FA0BC"
            stroke-width="1.6"
            stroke-dasharray="6 4"
            opacity="1"
          />
        </g>
        <g v-if="chart.yearLabels.length" pointer-events="none">
          <text
            v-for="(yl, i) in chart.yearLabels"
            :key="'ylb' + i"
            :x="yl.midX ?? yl.x"
            y="14"
            text-anchor="middle"
            fill="#3D4F6F"
            font-size="12"
            font-weight="800"
            font-family="inherit"
          >{{ yl.year }}</text>
        </g>

        <template v-if="!isCandle">
          <g :clip-path="`url(#${drawClipId})`">
            <path
              v-if="chart.area"
              :d="chart.area"
              :fill="`url(#${fillGradId})`"
              stroke="none"
              :opacity="expanded ? 0.72 : 1"
            />
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
          :style="tickStyle(active.x, hoverDate(active.label, active.date), 'hover')"
        >{{ hoverDate(active.label, active.date) }}</span>
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
      <template v-else-if="chart.scrollable">{{ expanded ? '左右滑动查看整段；滚轮放大会多显示日期，缩小则少显示，悬停可读当天价格' : '左右滑动查看更早的日期，悬停可看当天价格' }}</template>
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

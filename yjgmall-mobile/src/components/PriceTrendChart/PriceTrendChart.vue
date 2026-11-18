<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { getToken } from "@/utils/request";

const props = defineProps<{
  products: Array<{
    id?: number | string;
    name: string;
    unit?: string;
    price_avg?: string | number | null;
    price_min?: string | number | null;
    price_max?: string | number | null;
    series?: Array<{
      date?: string;
      label?: string;
      price_avg?: string | number | null;
    }>;
  }>;
}>();

const active = ref(0);
const list = computed(() => (props.products || []).filter((p) => p?.name).slice(0, 15));
watch(
  list,
  (v) => {
    if (active.value >= v.length) active.value = 0;
  },
  { immediate: true },
);

const current = computed(() => list.value[active.value] || null);
const loggedIn = computed(() => Boolean(getToken()));

function num(v: unknown, fallback = NaN) {
  const n = Number(v);
  return Number.isFinite(n) ? n : fallback;
}

function masked(n: unknown) {
  return n === null || n === undefined || n === "";
}

/** 只用接口真实 series，禁止假走势 */
function realSeries(p: NonNullable<typeof current.value>) {
  const rows = Array.isArray(p.series) ? p.series : [];
  return rows
    .map((r) => ({
      label: String(r.label || r.date || "").slice(-5),
      value: num(r.price_avg),
    }))
    .filter((r) => Number.isFinite(r.value));
}

const series = computed(() => (current.value ? realSeries(current.value) : []));
const priceRevealed = computed(() => loggedIn.value && !masked(current.value?.price_avg));

const latest = computed(() => {
  if (!priceRevealed.value) return null;
  const fromSeries = series.value[series.value.length - 1]?.value;
  if (Number.isFinite(fromSeries as number)) return fromSeries as number;
  const avg = num(current.value?.price_avg);
  return Number.isFinite(avg) ? avg : null;
});

const dayDiff = computed(() => {
  if (!priceRevealed.value || series.value.length < 2) return null;
  const a = series.value[series.value.length - 1].value;
  const b = series.value[series.value.length - 2].value;
  return Number((a - b).toFixed(2));
});

const monthDiff = computed(() => {
  if (!priceRevealed.value || series.value.length < 2) return null;
  const a = series.value[series.value.length - 1].value;
  const b = series.value[0].value;
  return Number((a - b).toFixed(2));
});

const chart = computed(() => {
  const pts = series.value;
  const w = 320;
  const h = 160;
  if (!priceRevealed.value || pts.length < 2) {
    return { line: "", area: "", dots: [] as any[], yLabels: [] as string[], xLabels: [] as string[], w, h };
  }
  const padL = 36;
  const padR = 8;
  const padT = 12;
  const padB = 22;
  const vals = pts.map((p) => p.value);
  let vmin = Math.min(...vals);
  let vmax = Math.max(...vals);
  const mid0 = (vmin + vmax) / 2;
  const rawSpan = Math.max(vmax - vmin, 0);
  const flatThresh = Math.max(80, Math.abs(mid0) * 0.006);
  if (rawSpan < flatThresh) {
    const half = Math.max(100, Math.abs(mid0) * 0.008);
    vmin = mid0 - half;
    vmax = mid0 + half;
  } else {
    const pad = Math.max(rawSpan * 0.12, Math.abs(mid0) * 0.004, 1);
    vmin -= pad;
    vmax += pad;
  }
  const span = Math.max(vmax - vmin, 1);
  const xStep = (w - padL - padR) / (pts.length - 1);
  const yOf = (v: number) => padT + (1 - (v - vmin) / span) * (h - padT - padB);
  const dots = pts.map((p, i) => ({
    x: padL + i * xStep,
    y: yOf(p.value),
    label: p.label,
    value: p.value,
  }));
  const line = dots.map((d, i) => `${i ? "L" : "M"}${d.x.toFixed(1)},${d.y.toFixed(1)}`).join(" ");
  const area = `${line} L${dots[dots.length - 1].x.toFixed(1)},${(h - padB).toFixed(1)} L${dots[0].x.toFixed(1)},${(h - padB).toFixed(1)} Z`;
  const yLabels = [vmax, (vmax + vmin) / 2, vmin].map((v) => v.toFixed(0));
  const xLabels = [pts[0].label, pts[Math.floor(pts.length / 2)].label, pts[pts.length - 1].label];
  return { line, area, dots, yLabels, xLabels, w, h };
});

function money(n: number | null) {
  if (n == null || !Number.isFinite(n)) return loggedIn.value ? "-" : "****";
  return n.toLocaleString("zh-CN");
}
function fmt(n: number | null) {
  if (n == null || !Number.isFinite(n)) return loggedIn.value ? "-" : "**";
  return n > 0 ? `+${n}` : String(n);
}
function diffClass(n: number | null) {
  if (n == null || !Number.isFinite(n) || n === 0) return "";
  return n > 0 ? "up" : "down";
}
function goMore() {
  uni.navigateTo({ url: "/pages/quotes/index" });
}
function goDetail() {
  const id = current.value?.id;
  if (id == null) return;
  uni.navigateTo({ url: `/pages/quotes/detail?id=${id}` });
}
function goLogin() {
  uni.navigateTo({ url: "/pages/login/index" });
}
</script>

<template>
  <view v-if="list.length" class="trend card">
    <view class="hd">
      <text class="title">价格行情</text>
      <text class="more" @click="goMore">更多 ›</text>
    </view>
    <scroll-view scroll-x class="tabs" :show-scrollbar="false">
      <text
        v-for="(p, i) in list"
        :key="p.id || p.name"
        class="tab"
        :class="{ on: active === i }"
        @click="active = i"
      >{{ p.name }}</text>
    </scroll-view>
    <view v-if="current" class="bd" @click="goDetail">
      <view class="meta">
        <text class="name">{{ current.name }}</text>
        <view class="price-row">
          <text class="price">¥{{ money(latest) }}</text>
          <text class="unit muted">{{ current.unit || "元/吨" }}</text>
        </view>
        <view class="diffs">
          <text :class="diffClass(dayDiff)">日 {{ fmt(dayDiff) }}</text>
          <text :class="diffClass(monthDiff)">月 {{ fmt(monthDiff) }}</text>
        </view>
      </view>
      <view class="chart-wrap">
        <view v-if="!priceRevealed" class="gate" @click.stop="goLogin">
          <text class="gate-t">登录后查看价格与走势</text>
          <text class="gate-a">去登录</text>
        </view>
        <view v-else-if="series.length < 2" class="gate muted-only">
          <text class="gate-t">暂无足够走势数据</text>
        </view>
        <svg v-else :viewBox="`0 0 ${chart.w} ${chart.h}`" class="chart">
          <path :d="chart.area" fill="rgba(39,105,220,.12)" />
          <path :d="chart.line" fill="none" stroke="#2769dc" stroke-width="2" />
          <circle
            v-for="(d, i) in chart.dots"
            :key="i"
            :cx="d.x"
            :cy="d.y"
            r="2.5"
            fill="#2769dc"
          />
          <text
            v-for="(yl, i) in chart.yLabels"
            :key="'y' + i"
            x="4"
            :y="16 + i * ((chart.h - 34) / 2)"
            font-size="10"
            fill="#99a"
          >{{ yl }}</text>
          <text
            v-for="(xl, i) in chart.xLabels"
            :key="'x' + i"
            :x="36 + i * ((chart.w - 44) / 2)"
            :y="chart.h - 4"
            font-size="10"
            fill="#99a"
          >{{ xl }}</text>
        </svg>
      </view>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.trend { margin: 16rpx 20rpx; padding: 20rpx 0 12rpx; }
.hd {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0 24rpx 12rpx;
}
.title { font-size: 30rpx; font-weight: 700; }
.more { font-size: 24rpx; color: #2769dc; }
.tabs { white-space: nowrap; padding: 0 16rpx 8rpx; }
.tab {
  display: inline-block; padding: 10rpx 18rpx; margin-right: 8rpx;
  font-size: 24rpx; color: #666; background: #f3f5f9; border-radius: 8rpx;
}
.tab.on { background: #e8f0ff; color: #2769dc; font-weight: 700; }
.bd { padding: 8rpx 24rpx 12rpx; }
.meta { margin-bottom: 8rpx; }
.name { font-size: 28rpx; font-weight: 600; display: block; }
.price-row { display: flex; align-items: baseline; gap: 10rpx; margin-top: 6rpx; }
.price { font-size: 40rpx; font-weight: 800; color: #c2410c; }
.unit { font-size: 22rpx; }
.diffs { display: flex; gap: 20rpx; margin-top: 8rpx; font-size: 22rpx; color: #666; }
.up { color: #dc2626; }
.down { color: #16a34a; }
.chart-wrap { width: 100%; overflow: hidden; position: relative; min-height: 280rpx; }
.chart { width: 100%; height: 280rpx; display: block; }
.gate {
  min-height: 280rpx; display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 12rpx; background: linear-gradient(180deg, #f7f9fd, #eef3fb);
  border-radius: 12rpx;
}
.gate-t { font-size: 24rpx; color: #667; }
.gate-a { font-size: 24rpx; color: #2769dc; font-weight: 700; }
.muted-only .gate-t { color: #99a; }
</style>

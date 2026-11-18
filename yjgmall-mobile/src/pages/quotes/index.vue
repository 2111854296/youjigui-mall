<script setup lang="ts">
import { computed, ref, onMounted } from "vue";
import { onPullDownRefresh } from "@dcloudio/uni-app";
import { getToken } from "@/utils/request";
import { quoteApi } from "@/utils/quoteApi";
import { errMsg } from "@/utils/auth";
import { normQuoteName, sortQuotesByCatalog } from "@/utils/quoteCatalog";

const loading = ref(true);
const items = ref<any[]>([]);
const active = ref(0);

const current = computed(() => items.value[active.value] || items.value[0] || null);
const loggedIn = computed(() => Boolean(getToken()));
/** 未登录接口会抹价，勿把 null 渲染成 0 */
function masked(n: unknown) {
  return n === null || n === undefined || n === "";
}

function fmtDiff(n: unknown) {
  if (masked(n)) return loggedIn.value ? "-" : "**";
  const v = Number(n);
  if (!Number.isFinite(v) || v === 0) return "0.00";
  return v > 0 ? `+${v.toFixed(2)}` : v.toFixed(2);
}
function pct(n: unknown) {
  if (masked(n)) return loggedIn.value ? "-" : "**%";
  const v = Number(n);
  if (!Number.isFinite(v) || v === 0) return "0.00%";
  return `${v > 0 ? "+" : ""}${v.toFixed(2)}%`;
}
function diffClass(n: unknown) {
  if (masked(n)) return "";
  const v = Number(n);
  if (v > 0) return "up";
  if (v < 0) return "down";
  return "";
}
function money(n: unknown) {
  if (masked(n)) return loggedIn.value ? "-" : "****";
  const v = Number(n);
  if (!Number.isFinite(v)) return "-";
  return v.toLocaleString("zh-CN");
}
function realSeries(p: any): { label: string; value: number }[] {
  const rows = Array.isArray(p?.series) ? p.series : [];
  return rows
    .map((r: any) => ({
      label: String(r.label || r.date || "").slice(-5),
      value: Number(r.price_avg),
    }))
    .filter((r: { value: number }) => Number.isFinite(r.value));
}

const chart = computed(() => {
  const pts = current.value ? realSeries(current.value) : [];
  const w = 320;
  const h = 110;
  if (pts.length < 2) {
    return { line: "", area: "", end: null as null | { x: number; y: number }, w, h };
  }
  const padL = 6;
  const padR = 6;
  const padT = 8;
  const padB = 8;
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
  const dots = pts.map((p, i) => ({ x: padL + i * xStep, y: yOf(p.value) }));
  const line = dots.map((d, i) => `${i ? "L" : "M"}${d.x.toFixed(1)},${d.y.toFixed(1)}`).join(" ");
  const area = `${line} L${dots[dots.length - 1].x.toFixed(1)},${(h - 4).toFixed(1)} L${dots[0].x.toFixed(1)},${(h - 4).toFixed(1)} Z`;
  return { line, area, end: dots[dots.length - 1], w, h };
});

async function load() {
  loading.value = true;
  try {
    const res = await quoteApi<any>("/quotes");
    const rows = (res?.data || []).map((p: any) => ({
      ...p,
      name: normQuoteName(String(p.name || "")),
    }));
    items.value = sortQuotesByCatalog(rows);
    if (active.value >= items.value.length) active.value = 0;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
}

function goDetail(id?: string | number) {
  const sid = id ?? current.value?.id;
  if (sid == null) return;
  uni.navigateTo({ url: `/pages/quotes/detail?id=${sid}` });
}

onMounted(load);
onPullDownRefresh(async () => {
  await load();
  uni.stopPullDownRefresh();
});
</script>

<template>
  <view class="page">
    <view class="tip muted">数据仅供参考，请以企业实际交易为准</view>
    <view v-if="loading" class="center"><u-loading-icon color="#2769dc" /></view>
    <template v-else>
      <view v-if="current" class="hero" @click="goDetail()">
        <view class="hero-top">
          <text class="hero-name">{{ current.name }}</text>
          <text class="hero-tag">{{ current.market || "均价" }}</text>
        </view>
        <view class="hero-price">
          <text class="num">{{ money(current.price_avg) }}</text>
          <text class="unit">{{ current.unit || "元/吨" }}</text>
        </view>
        <text class="hero-chg" :class="diffClass(current.day_diff)">
          {{ fmtDiff(current.day_diff) }} · {{ pct(current.day_pct) }}
        </text>
        <view class="hero-chart">
          <view v-if="masked(current.price_avg)" class="hero-mask">登录后查看走势</view>
          <svg v-else :viewBox="`0 0 ${chart.w} ${chart.h}`" class="spark">
            <path v-if="chart.area" :d="chart.area" fill="rgba(255,255,255,.22)" />
            <path
              v-if="chart.line"
              :d="chart.line"
              fill="none"
              stroke="#fff"
              stroke-width="2.2"
              stroke-linejoin="round"
              stroke-linecap="round"
            />
            <circle
              v-if="chart.end"
              :cx="chart.end.x"
              :cy="chart.end.y"
              r="3.5"
              fill="#fff"
            />
          </svg>
        </view>
        <view class="hero-data">
          <view class="hd"><text class="k">最低</text><text class="v">{{ money(current.price_min) }}</text></view>
          <view class="hd"><text class="k">最高</text><text class="v">{{ money(current.price_max) }}</text></view>
          <view class="hd"><text class="k">均价</text><text class="v">{{ money(current.price_avg) }}</text></view>
          <view class="hd"><text class="k">涨跌</text><text class="v" :class="diffClass(current.day_diff)">{{ fmtDiff(current.day_diff) }}</text></view>
        </view>
      </view>

      <scroll-view scroll-x class="tabs" :show-scrollbar="false">
        <text
          v-for="(item, i) in items"
          :key="item.id"
          class="tab"
          :class="{ on: active === i }"
          @click="active = i"
        >{{ item.name }}</text>
      </scroll-view>

      <view class="grid">
        <view
          v-for="item in items"
          :key="item.id"
          class="quote-card"
          @click="goDetail(item.id)"
        >
          <view class="row-top">
            <text class="name">{{ item.name }}</text>
            <u-icon name="arrow-right" color="#94a3b8" size="14" />
          </view>
          <text class="avg">¥{{ money(item.price_avg) }}</text>
          <text class="unit muted">{{ item.unit || "元/吨" }}</text>
          <text class="chg" :class="diffClass(item.day_diff)">
            {{ fmtDiff(item.day_diff) }} / {{ pct(item.day_pct) }}
          </text>
        </view>
        <view v-if="!items.length" class="empty muted">暂无行情报价</view>
      </view>
    </template>
  </view>
</template>

<style lang="scss" scoped>
.tip { padding: 16rpx 28rpx 0; font-size: 22rpx; }
.center { padding: 80rpx; text-align: center; }
.hero {
  margin: 16rpx 20rpx;
  padding: 28rpx 24rpx 20rpx;
  border-radius: 20rpx;
  background: linear-gradient(155deg, #4b86e8 0%, #2769dc 55%, #1e5fc4 100%);
  color: #fff;
  box-shadow: 0 12rpx 32rpx rgba(39, 105, 220, 0.28);
}
.hero-top { display: flex; align-items: center; gap: 12rpx; margin-bottom: 12rpx; }
.hero-name { font-size: 32rpx; font-weight: 800; }
.hero-tag {
  font-size: 20rpx; padding: 4rpx 14rpx; border-radius: 999rpx;
  background: rgba(255, 255, 255, 0.18);
}
.hero-price { display: flex; align-items: baseline; gap: 12rpx; }
.hero-price .num { font-size: 52rpx; font-weight: 800; letter-spacing: -1rpx; }
.hero-price .unit { font-size: 24rpx; opacity: 0.85; }
.hero-chg { display: block; margin-top: 8rpx; font-size: 26rpx; font-weight: 700; color: #ffb4ae; }
.hero-chg.up { color: #ffb4ae; }
.hero-chg.down { color: #7cffb2; }
.hero-chart { margin: 16rpx 0 12rpx; height: 180rpx; }
.hero-mask {
  height: 180rpx; display: flex; align-items: center; justify-content: center;
  font-size: 26rpx; color: rgba(255,255,255,.88); letter-spacing: 1rpx;
}
.spark { width: 100%; height: 180rpx; display: block; }
.hero-data {
  display: grid; grid-template-columns: repeat(4, 1fr);
  background: #fff; border-radius: 14rpx; padding: 14rpx 4rpx;
}
.hd { text-align: center; }
.hd .k { display: block; font-size: 20rpx; color: #64748b; }
.hd .v { display: block; margin-top: 4rpx; font-size: 24rpx; font-weight: 800; color: #1e5fc4; }
.hd .v.up { color: #dc2626; }
.hd .v.down { color: #16a34a; }
.tabs { white-space: nowrap; padding: 8rpx 20rpx 4rpx; }
.tab {
  display: inline-block; padding: 12rpx 22rpx; margin-right: 12rpx;
  font-size: 24rpx; color: #64748b; background: #fff; border-radius: 999rpx;
  border: 1rpx solid #e8edf3;
}
.tab.on {
  color: #fff; background: #2769dc; border-color: #2769dc; font-weight: 700;
}
.grid {
  display: flex; flex-wrap: wrap; padding: 8rpx 12rpx 40rpx;
}
.quote-card {
  width: calc(50% - 16rpx); margin: 8rpx; background: #fff; border-radius: 16rpx;
  padding: 24rpx 20rpx; box-sizing: border-box;
  box-shadow: 0 6rpx 20rpx rgba(31, 56, 100, 0.06);
}
.row-top {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 12rpx;
}
.name { font-size: 28rpx; font-weight: 700; color: #1f2937; }
.avg { display: block; font-size: 34rpx; font-weight: 800; color: #c2410c; line-height: 1.2; }
.unit { font-size: 22rpx; display: block; margin-top: 4rpx; }
.chg { display: block; margin-top: 12rpx; font-size: 24rpx; color: #64748b; }
.chg.up { color: #dc2626; }
.chg.down { color: #16a34a; }
.empty { width: 100%; padding: 60rpx; text-align: center; }
.muted { color: #64748b; }
</style>

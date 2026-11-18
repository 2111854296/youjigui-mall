<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { onLoad, onShow } from "@dcloudio/uni-app";
import { getToken } from "@/utils/request";
import { quoteApi } from "@/utils/quoteApi";
import { errMsg } from "@/utils/auth";

type RangeKey = "7d" | "1m" | "3m" | "6m" | "1y" | "2y" | "5y";
const RANGE_OPTS: Array<{ key: RangeKey; label: string; days: number; title: string }> = [
  { key: "7d", label: "7日", days: 7, title: "近 7 日" },
  { key: "1m", label: "1月", days: 30, title: "近 1 月" },
  { key: "3m", label: "3月", days: 90, title: "近 3 月" },
  { key: "6m", label: "半年", days: 180, title: "近半年" },
  { key: "1y", label: "一年", days: 365, title: "近一年" },
  { key: "2y", label: "两年", days: 730, title: "近两年" },
  { key: "5y", label: "五年", days: 1825, title: "近五年" },
];

const loading = ref(true);
const item = ref<any>(null);
const id = ref("");
const rangeKey = ref<RangeKey>("7d");
const customActive = ref(false);
const dateFrom = ref("");
const dateTo = ref("");
const expandOpen = ref(false);

function daysBetween(from: string, to: string) {
  const a = Date.parse(String(from).replace(/-/g, "/"));
  const b = Date.parse(String(to).replace(/-/g, "/"));
  if (!Number.isFinite(a) || !Number.isFinite(b) || b < a) return 0;
  return Math.round((b - a) / 86400000) + 1;
}

const loggedIn = computed(() => Boolean(getToken()));
const historyLimit = computed(() => Number(item.value?.history_days_limit || 0));
const needLogin = computed(() => Boolean(item.value?.need_login) || !loggedIn.value);

const customSpanDays = computed(() => {
  if (!dateFrom.value || !dateTo.value) return 0;
  return daysBetween(dateFrom.value, dateTo.value);
});

const selectedDays = computed(() => {
  if (customActive.value && customSpanDays.value > 0) return customSpanDays.value;
  return RANGE_OPTS.find((r) => r.key === rangeKey.value)?.days || 7;
});

const rangeTitle = computed(() => {
  if (customActive.value && dateFrom.value && dateTo.value) {
    return `${dateFrom.value} ~ ${dateTo.value}`;
  }
  return RANGE_OPTS.find((r) => r.key === rangeKey.value)?.title || "近 7 日";
});

const canExpandChart = computed(() => selectedDays.value >= 90);

const priceMetaLine = computed(() => {
  const it = item.value;
  if (!it) return "";
  const scope = String(it.active_region || it.market || "均价");
  const date = String(it.quoted_on || "").slice(0, 10) || "-";
  if (it.active_region) return `${scope} · ${date}`;
  return `区间 ${money(it.price_min)} ~ ${money(it.price_max)} · ${scope} · ${date}`;
});

function rangeAllowed(opt: { days: number }) {
  if (!loggedIn.value) return opt.days <= 7;
  const limit = historyLimit.value || 7;
  return opt.days <= limit;
}

function pickRange(opt: { key: RangeKey; days: number }) {
  if (!rangeAllowed(opt)) {
    uni.showToast({ title: loggedIn.value ? "当前会员档位不可查看该周期" : "登录后可查看更长周期", icon: "none" });
    if (!loggedIn.value) goLogin();
    return;
  }
  customActive.value = false;
  rangeKey.value = opt.key;
  expandOpen.value = false;
  load(id.value);
}

function openCustom() {
  if (!loggedIn.value) {
    uni.showToast({ title: "登录后可自选日期", icon: "none" });
    goLogin();
    return;
  }
  customActive.value = true;
  if (!dateTo.value) dateTo.value = todayStr();
  if (!dateFrom.value) dateFrom.value = shiftDate(dateTo.value, -29);
  expandOpen.value = false;
  // 必须按自定义跨度重新拉数；否则沿用上一周期（如仅 7 日）前端裁切会缺点
  load(id.value);
}

function applyCustom() {
  if (!dateFrom.value || !dateTo.value) {
    uni.showToast({ title: "请选择起止日期", icon: "none" });
    return;
  }
  if (customSpanDays.value < 1) {
    uni.showToast({ title: "结束日期不能早于开始日期", icon: "none" });
    return;
  }
  const limit = historyLimit.value || 7;
  if (customSpanDays.value > limit) {
    uni.showToast({ title: `当前最多可看 ${limit} 天`, icon: "none" });
    return;
  }
  customActive.value = true;
  expandOpen.value = false;
  load(id.value);
}

function todayStr() {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
}

function shiftDate(base: string, delta: number) {
  const t = Date.parse(String(base).replace(/-/g, "/"));
  const d = new Date(Number.isFinite(t) ? t : Date.now());
  d.setDate(d.getDate() + delta);
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
}

function onFromChange(e: any) {
  dateFrom.value = String(e?.detail?.value || "");
}
function onToChange(e: any) {
  dateTo.value = String(e?.detail?.value || "");
}

function goLogin() {
  uni.navigateTo({ url: "/pages/login/index" });
}

function money(n: unknown) {
  if (needLogin.value) return "****";
  const v = Number(n);
  if (!Number.isFinite(v)) return "-";
  return v.toLocaleString("zh-CN");
}

const rawSeries = computed(() => (item.value?.series || []) as Array<{
  date: string;
  label: string;
  price_avg: number;
  price_min?: number;
  price_max?: number;
}>);

/** 自定义区间时在前端再裁切；快捷 Tab 用接口 days */
const series = computed(() => {
  const rows = rawSeries.value;
  if (!customActive.value || !dateFrom.value || !dateTo.value) return rows;
  return rows.filter((r) => r.date >= dateFrom.value && r.date <= dateTo.value);
});

const historyDesc = computed(() => [...series.value].reverse().slice(0, 20));

const chartW = computed(() => {
  if (expandOpen.value && series.value.length > 20) {
    return Math.max(320, series.value.length * 14);
  }
  return 320;
});

const chart = computed(() => {
  const pts = series.value;
  if (pts.length < 2 || needLogin.value) {
    return { line: "", area: "", dots: [] as any[], yLabels: [] as string[], xLabels: [] as string[], w: chartW.value, h: 160 };
  }
  const w = chartW.value;
  const h = 160;
  const padL = 36;
  const padR = 8;
  const padT = 12;
  const padB = 22;
  const vals = pts.map((p) => p.price_avg);
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
    y: yOf(p.price_avg),
    label: p.label,
    value: p.price_avg,
  }));
  const line = dots.map((d, i) => `${i ? "L" : "M"}${d.x.toFixed(1)},${d.y.toFixed(1)}`).join(" ");
  const area = `${line} L${dots[dots.length - 1].x.toFixed(1)},${(h - padB).toFixed(1)} L${dots[0].x.toFixed(1)},${(h - padB).toFixed(1)} Z`;
  const yLabels = [vmax, (vmax + vmin) / 2, vmin].map((v) => v.toFixed(0));
  const xLabels = [pts[0].label, pts[Math.floor(pts.length / 2)].label, pts[pts.length - 1].label];
  return { line, area, dots, yLabels, xLabels, w, h };
});

function fmtDiff(n: unknown) {
  if (needLogin.value) return "**";
  const v = Number(n);
  if (!Number.isFinite(v) || v === 0) return "0.00";
  return v > 0 ? `+${v.toFixed(2)}` : v.toFixed(2);
}
function pct(n: unknown) {
  if (needLogin.value) return "**%";
  const v = Number(n);
  if (!Number.isFinite(v) || v === 0) return "0.00%";
  return `${v > 0 ? "+" : ""}${v.toFixed(2)}%`;
}
function diffClass(n: unknown) {
  if (needLogin.value) return "";
  const v = Number(n);
  if (v > 0) return "up";
  if (v < 0) return "down";
  return "";
}

async function load(qid: string) {
  if (!qid) return;
  loading.value = true;
  try {
    let days = selectedDays.value;
    if (!loggedIn.value) days = Math.min(days, 7);
    else if (historyLimit.value > 0) days = Math.min(days, Math.max(historyLimit.value, 7));
    // 自定义区间：按会员上限拉全量再前端裁切
    if (customActive.value && loggedIn.value) {
      days = Math.max(historyLimit.value || 7, customSpanDays.value || 7);
    }
    const res = await quoteApi<any>(`/quotes/${qid}`, {
      query: { days },
      auth: Boolean(getToken()),
    });
    item.value = res?.data || res;
    uni.setNavigationBarTitle({ title: item.value?.name ? `${item.value.name}行情` : "行情详情" });
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
}

function goOther(oid: string | number) {
  uni.redirectTo({ url: `/pages/quotes/detail?id=${oid}` });
}

watch(loggedIn, () => {
  if (id.value) {
    if (!loggedIn.value) {
      rangeKey.value = "7d";
      customActive.value = false;
    } else if (historyLimit.value > 7) {
      rangeKey.value = "1m";
    }
    load(id.value);
  }
});

onLoad((q) => {
  if (q?.id) {
    id.value = String(q.id);
    if (loggedIn.value) rangeKey.value = "1m";
    load(id.value);
  }
});

/** getToken 非响应式；登录页返回时靠 onShow 感知态变化并重拉 */
let lastAuth = Boolean(getToken());
onShow(() => {
  if (!id.value) return;
  const authed = Boolean(getToken());
  if (authed === lastAuth) return;
  lastAuth = authed;
  if (!authed) {
    rangeKey.value = "7d";
    customActive.value = false;
  } else if (historyLimit.value > 7) {
    rangeKey.value = "1m";
  }
  load(id.value);
});
</script>

<template>
  <view class="page">
    <view v-if="loading" class="center"><u-loading-icon color="#2769dc" /></view>
    <template v-else-if="item">
      <view class="card head">
        <view class="name-row">
          <text class="name">{{ item.name }}</text>
          <text class="unit muted">{{ item.unit || "元/吨" }}</text>
        </view>
        <text class="price">¥{{ money(item.price_avg) }}</text>
        <view class="diffs">
          <text :class="diffClass(item.day_diff)">
            比昨天 {{ fmtDiff(item.day_diff) }} / {{ pct(item.day_pct) }}
          </text>
          <text :class="diffClass(item.month_diff)">
            比上月 {{ fmtDiff(item.month_diff) }} / {{ pct(item.month_pct) }}
          </text>
        </view>
        <text class="meta muted">{{ priceMetaLine }}</text>
        <view v-if="needLogin" class="login-tip" @click="goLogin">
          <text>登录后可查看完整价格与更长历史</text>
          <text class="login-btn">去登录</text>
        </view>
      </view>

      <view class="card">
        <view class="section-hd">
          <text class="title">{{ rangeTitle }}走势</text>
          <text
            v-if="canExpandChart && !needLogin"
            class="expand"
            @click="expandOpen = !expandOpen"
          >{{ expandOpen ? "收起" : "详情展开" }}</text>
        </view>

        <scroll-view scroll-x class="range-scroll" :show-scrollbar="false">
          <view class="range-row">
            <view
              v-for="opt in RANGE_OPTS"
              :key="opt.key"
              class="range-chip"
              :class="{ on: !customActive && rangeKey === opt.key, locked: !rangeAllowed(opt) }"
              @click="pickRange(opt)"
            >{{ opt.label }}</view>
            <view
              class="range-chip"
              :class="{ on: customActive, locked: !loggedIn }"
              @click="openCustom"
            >日期选择</view>
          </view>
        </scroll-view>

        <view v-if="customActive && loggedIn" class="date-row">
          <picker mode="date" :value="dateFrom" @change="onFromChange">
            <view class="date-box">{{ dateFrom || "开始日期" }}</view>
          </picker>
          <text class="tilde">至</text>
          <picker mode="date" :value="dateTo" @change="onToChange">
            <view class="date-box">{{ dateTo || "结束日期" }}</view>
          </picker>
          <view class="date-apply" @click="applyCustom">查看</view>
        </view>

        <scroll-view
          :scroll-x="expandOpen"
          class="chart-scroll"
          :class="{ expand: expandOpen }"
        >
          <view class="chart-wrap" :style="expandOpen ? { width: chart.w + 'px' } : {}">
            <svg
              v-if="chart.dots.length"
              :viewBox="`0 0 ${chart.w} ${chart.h}`"
              class="chart"
              :style="expandOpen ? { width: chart.w + 'px' } : {}"
            >
              <defs>
                <linearGradient id="h5QuoteFill" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#2769dc" stop-opacity="0.28" />
                  <stop offset="100%" stop-color="#2769dc" stop-opacity="0.02" />
                </linearGradient>
              </defs>
              <line
                v-for="(yl, i) in chart.yLabels"
                :key="'yg'+i"
                :x1="36"
                :x2="chart.w - 8"
                :y1="12 + i * ((chart.h - 34) / 2)"
                :y2="12 + i * ((chart.h - 34) / 2)"
                stroke="#f0f0f0"
                stroke-width="1"
              />
              <text
                v-for="(yl, i) in chart.yLabels"
                :key="'yt'+i"
                x="2"
                :y="16 + i * ((chart.h - 34) / 2)"
                fill="#999"
                font-size="8"
              >{{ yl }}</text>
              <path :d="chart.area" fill="url(#h5QuoteFill)" />
              <path :d="chart.line" stroke="#2769dc" stroke-width="2" fill="none" />
              <circle
                v-for="(d, i) in chart.dots"
                :key="'d'+i"
                :cx="d.x"
                :cy="d.y"
                r="2"
                fill="#2769dc"
              />
              <text
                v-for="(xl, i) in chart.xLabels"
                :key="'xt'+i"
                :x="36 + i * ((chart.w - 44) / 2)"
                :y="chart.h - 4"
                fill="#999"
                font-size="8"
              >{{ xl }}</text>
            </svg>
            <text v-else-if="needLogin" class="muted chart-mask">登录后可查看走势坐标与读数</text>
            <text v-else class="muted">暂无走势数据</text>
          </view>
        </scroll-view>
      </view>

      <view class="card">
        <view class="section-hd">
          <text class="title">历史报价</text>
        </view>
        <view class="hist-row head-row">
          <text>日期</text>
          <text>均价</text>
          <text>区间</text>
        </view>
        <view v-if="needLogin" class="login-tip hist-login" @click="goLogin">
          <text>登录后可查看历史报价</text>
          <text class="login-btn">去登录</text>
        </view>
        <template v-else>
          <view v-for="row in historyDesc" :key="row.date" class="hist-row">
            <text>{{ row.date }}</text>
            <text class="num">{{ row.price_avg }}</text>
            <text class="muted">{{ row.price_min ?? "-" }}~{{ row.price_max ?? "-" }}</text>
          </view>
          <view v-if="!historyDesc.length" class="muted empty">暂无历史</view>
        </template>
      </view>

      <view v-if="(item.others || []).length" class="card">
        <view class="section-hd">
          <text class="title">其它品种</text>
        </view>
        <view
          v-for="o in item.others"
          :key="o.id"
          class="other"
          @click="goOther(o.id)"
        >
          <text>{{ o.name }}</text>
          <text class="num">¥{{ needLogin ? "****" : (o.price_avg ?? "-") }}</text>
        </view>
      </view>

      <view class="foot muted">数据仅供参考，请以企业实际交易为准</view>
    </template>
  </view>
</template>

<style lang="scss" scoped>
.center { padding: 80rpx; text-align: center; }
.head { padding: 28rpx 24rpx; }
.name-row { display: flex; align-items: baseline; gap: 12rpx; }
.name { font-size: 36rpx; font-weight: 700; }
.unit { font-size: 24rpx; }
.price {
  display: block;
  margin-top: 16rpx;
  font-size: 48rpx;
  font-weight: 800;
  color: #c2410c;
  line-height: 1.1;
}
.diffs {
  margin-top: 16rpx;
  display: flex;
  flex-direction: column;
  gap: 8rpx;
  font-size: 24rpx;
  color: #666;
}
.diffs .up { color: #dc2626; }
.diffs .down { color: #16a34a; }
.meta { display: block; margin-top: 14rpx; font-size: 22rpx; }
.login-tip {
  margin-top: 18rpx;
  padding: 16rpx 18rpx;
  border-radius: 12rpx;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 24rpx;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12rpx;
}
.login-btn {
  flex-shrink: 0;
  padding: 8rpx 18rpx;
  border-radius: 999rpx;
  background: #2769dc;
  color: #fff;
  font-size: 22rpx;
  font-weight: 600;
}
.section-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0;
  margin-bottom: 12rpx;
}
.title { font-size: 30rpx; font-weight: 700; }
.expand { font-size: 24rpx; color: #2769dc; }
.range-scroll { width: 100%; margin-bottom: 12rpx; }
.range-row { display: inline-flex; gap: 12rpx; padding: 4rpx 0 8rpx; }
.range-chip {
  flex-shrink: 0;
  padding: 10rpx 22rpx;
  border-radius: 999rpx;
  background: #f3f5f9;
  color: #444;
  font-size: 24rpx;
}
.range-chip.on { background: #2769dc; color: #fff; font-weight: 600; }
.range-chip.locked { opacity: 0.45; }
.date-row {
  display: flex;
  align-items: center;
  gap: 10rpx;
  margin-bottom: 14rpx;
  flex-wrap: wrap;
}
.date-box {
  min-width: 180rpx;
  padding: 12rpx 16rpx;
  border-radius: 10rpx;
  background: #f3f5f9;
  font-size: 24rpx;
  color: #333;
}
.tilde { font-size: 22rpx; color: #888; }
.date-apply {
  padding: 12rpx 22rpx;
  border-radius: 10rpx;
  background: #2769dc;
  color: #fff;
  font-size: 24rpx;
  font-weight: 600;
}
.chart-scroll { width: 100%; }
.chart-scroll.expand { white-space: nowrap; }
.chart-wrap { padding: 8rpx 0 16rpx; }
.chart { width: 100%; height: 320rpx; }
.chart-mask { display: block; padding: 48rpx 0; text-align: center; }
.hist-row {
  display: grid;
  grid-template-columns: 1.2fr 1fr 1.4fr;
  gap: 8rpx;
  padding: 16rpx 0;
  border-bottom: 1rpx solid #f3f3f3;
  font-size: 24rpx;
}
.head-row { color: #888; font-weight: 600; border-bottom-color: #e8e8e8; }
.hist-login { margin: 12rpx 0 4rpx; }
.num { color: #c2410c; font-weight: 700; }
.other {
  display: flex;
  justify-content: space-between;
  padding: 20rpx 0;
  border-bottom: 1rpx solid #f3f3f3;
  font-size: 28rpx;
}
.other:last-child { border-bottom: none; }
.empty { padding: 24rpx 0; text-align: center; }
.foot { text-align: center; padding: 12rpx 24rpx 40rpx; font-size: 22rpx; }
.card { padding: 20rpx 24rpx; }
</style>

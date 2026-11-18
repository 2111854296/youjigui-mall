<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { api } from "@/utils/request";

const props = withDefaults(defineProps<{ autoLoad?: boolean }>(), { autoLoad: true });

const emit = defineEmits<{
  (e: "verified", token: string): void;
  (e: "reset"): void;
}>();

const loading = ref(true);
const checking = ref(false);
const ok = ref(false);
const err = ref("");
const captchaKey = ref("");
const bg = ref("");
const slice = ref("");
const pieceY = ref(0);
const bgW = ref(310);
const bgH = ref(155);
const piece = ref(47);

const dragging = ref(false);
const startClientX = ref(0);
const startOffset = ref(0);
const offsetX = ref(0);

const displayW = ref(310);
const scale = computed(() => (bgW.value > 0 ? displayW.value / bgW.value : 1));
const maxX = computed(() => Math.max(0, bgW.value - piece.value));
const tip = computed(() => {
  if (ok.value) return "验证通过";
  if (checking.value) return "校验中…";
  if (dragging.value) return "松开完成验证";
  return "向右拖动滑块完成拼图";
});

function measureWidth() {
  try {
    const w = Number(uni.getSystemInfoSync()?.windowWidth || 360);
    displayW.value = Math.max(240, Math.min(bgW.value, w - 64));
  } catch {
    displayW.value = Math.min(310, bgW.value);
  }
}

async function load() {
  loading.value = true;
  err.value = "";
  ok.value = false;
  offsetX.value = 0;
  captchaKey.value = "";
  emit("reset");
  try {
    const res = await api<any>("/captcha/slide", { auth: false });
    const d = res?.data || res || {};
    captchaKey.value = d.captcha_key || "";
    bg.value = d.bg || "";
    slice.value = d.slice || "";
    pieceY.value = Number(d.y || 0);
    bgW.value = Number(d.bg_w || 310);
    bgH.value = Number(d.bg_h || 155);
    piece.value = Number(d.piece || 47);
    measureWidth();
  } catch (e: any) {
    err.value = e?.data?.message || e?.message || "验证码加载失败";
  } finally {
    loading.value = false;
  }
}

function clamp(n: number) {
  return Math.min(maxX.value, Math.max(0, n));
}

function touchX(e: any) {
  const t = e?.touches?.[0] || e?.changedTouches?.[0] || e;
  return Number(t?.clientX ?? t?.pageX ?? 0);
}

function onTouchStart(e: any) {
  if (ok.value || loading.value || checking.value || !captchaKey.value) return;
  dragging.value = true;
  startClientX.value = touchX(e);
  startOffset.value = offsetX.value;
}

function onTouchMove(e: any) {
  if (!dragging.value) return;
  const dx = (touchX(e) - startClientX.value) / Math.max(scale.value, 0.01);
  offsetX.value = clamp(startOffset.value + dx);
}

async function onTouchEnd() {
  if (!dragging.value) return;
  dragging.value = false;
  await submit();
}

async function submit() {
  if (ok.value || checking.value) return;
  checking.value = true;
  err.value = "";
  try {
    const res = await api<any>("/captcha/slide/check", {
      method: "POST",
      auth: false,
      body: { captcha_key: captchaKey.value, x: Math.round(offsetX.value) },
    });
    const token = res?.data?.captcha_token || res?.captcha_token || "";
    if (!token) throw new Error("no token");
    ok.value = true;
    emit("verified", token);
  } catch (e: any) {
    err.value = e?.data?.message || e?.message || "验证失败，请重试";
    offsetX.value = 0;
    emit("reset");
    await load();
  } finally {
    checking.value = false;
  }
}

onMounted(() => {
  measureWidth();
  if (props.autoLoad) load();
});

defineExpose({ reload: load, reset: load });
</script>

<template>
  <view class="slide-cap">
    <view class="sc-hd">
      <text>安全验证</text>
      <text class="sc-refresh" :class="{ off: loading || checking }" @click="load">刷新</text>
    </view>

    <view v-if="loading" class="sc-loading">验证码加载中…</view>
    <view v-else class="sc-panel" :style="{ width: displayW + 'px' }">
      <view class="sc-bg" :style="{ height: bgH * scale + 'px' }">
        <image class="sc-bg-img" :src="bg" mode="scaleToFill" />
        <image
          class="sc-slice"
          :src="slice"
          mode="scaleToFill"
          :style="{
            left: offsetX * scale + 'px',
            top: pieceY * scale + 'px',
            width: piece * scale + 'px',
            height: piece * scale + 'px',
          }"
        />
      </view>

      <view class="sc-track" :class="{ on: dragging, ok, bad: !!err && !ok }">
        <view class="sc-fill" :style="{ width: offsetX * scale + (piece * scale) / 2 + 'px' }" />
        <view
          class="sc-btn"
          :style="{ transform: `translateX(${offsetX * scale}px)` }"
          @touchstart.prevent="onTouchStart"
          @touchmove.prevent="onTouchMove"
          @touchend.prevent="onTouchEnd"
          @touchcancel.prevent="onTouchEnd"
        >
          <text>{{ ok ? "✓" : "››" }}</text>
        </view>
        <text class="sc-tip">{{ tip }}</text>
      </view>
    </view>
    <text v-if="err" class="sc-err">{{ err }}</text>
  </view>
</template>

<style lang="scss" scoped>
.slide-cap { margin-top: 8rpx; }
.sc-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16rpx;
  font-size: 26rpx;
  font-weight: 600;
  color: #334155;
}
.sc-refresh { color: #2468f2; font-size: 24rpx; font-weight: 600; }
.sc-refresh.off { opacity: 0.5; }
.sc-loading {
  height: 220rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f7fb;
  border-radius: 16rpx;
  color: #94a3b8;
  font-size: 26rpx;
}
.sc-panel { max-width: 100%; }
.sc-bg {
  position: relative;
  overflow: hidden;
  border-radius: 16rpx;
  background: #e8eef6;
}
.sc-bg-img {
  display: block;
  width: 100%;
  height: 100%;
}
.sc-slice {
  position: absolute;
  left: 0;
  top: 0;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.35));
}
.sc-track {
  position: relative;
  margin-top: 20rpx;
  height: 84rpx;
  border-radius: 999rpx;
  background: #f1f5f9;
  border: 1rpx solid #e2e8f0;
  overflow: hidden;
}
.sc-track.on { border-color: #93c5fd; }
.sc-track.ok { border-color: #22c55e; background: #ecfdf5; }
.sc-track.bad { border-color: #fca5a5; }
.sc-fill {
  position: absolute;
  left: 0; top: 0; bottom: 0;
  background: rgba(36, 104, 242, 0.15);
  pointer-events: none;
}
.sc-track.ok .sc-fill { background: rgba(34, 197, 94, 0.18); }
.sc-btn {
  position: absolute;
  left: 0; top: 0;
  width: 84rpx; height: 84rpx;
  border-radius: 50%;
  background: #fff;
  border: 1rpx solid #cbd5e1;
  box-shadow: 0 4rpx 16rpx rgba(15, 23, 42, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #2468f2;
  font-weight: 800;
  font-size: 32rpx;
  z-index: 2;
}
.sc-track.ok .sc-btn { color: #16a34a; border-color: #22c55e; }
.sc-tip {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 26rpx;
  color: #94a3b8;
  pointer-events: none;
  padding-left: 72rpx;
}
.sc-track.ok .sc-tip { color: #16a34a; font-weight: 600; }
.sc-err {
  display: block;
  margin-top: 12rpx;
  font-size: 24rpx;
  color: #dc2626;
}
</style>

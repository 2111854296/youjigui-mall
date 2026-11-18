<script setup lang="ts">
import { onLaunch } from "@dcloudio/uni-app";
import { useAuth } from "@/composables/useAuth";
import SlideCaptchaModal from "@/components/SlideCaptchaModal/SlideCaptchaModal.vue";
import {
  quoteCaptchaOpen,
  onQuoteCaptchaVerified,
  onQuoteCaptchaCancel,
} from "@/utils/quoteApi";

onLaunch(() => {
  const { token, fetchMe } = useAuth();
  if (token()) fetchMe().catch(() => null);
});
</script>

<template>
  <!-- 行情频控滑块：任意页 quoteApi 触发时弹出 -->
  <SlideCaptchaModal
    v-model="quoteCaptchaOpen"
    @verified="onQuoteCaptchaVerified"
    @cancel="onQuoteCaptchaCancel"
  />
</template>

<style lang="scss">
@import "uview-plus/index.scss";

:root,
page {
  --brand: #2769dc;
  --brand-dark: #1e5fc4;
  --brand-soft: #eef4ff;
  --bg: #f2f5fa;
  --surface: #ffffff;
  --line: #e8edf3;
  --text: #1f2937;
  --muted: #64748b;
  --price: #c2410c;
  --radius: 16rpx;
  --shadow: 0 6rpx 24rpx rgba(31, 56, 100, 0.06);
}

page {
  background: var(--bg);
  color: var(--text);
  font-family: "PingFang SC", "Microsoft YaHei", "Noto Sans SC", sans-serif;
  font-size: 28rpx;
  line-height: 1.5;
  -webkit-font-smoothing: antialiased;
}

.page {
  min-height: 100vh;
  background: var(--bg);
  padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
}

.card {
  background: var(--surface);
  margin: 16rpx 20rpx;
  border-radius: var(--radius);
  overflow: hidden;
  box-shadow: var(--shadow);
}

.section-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 28rpx 24rpx 12rpx;
}
.section-hd .title {
  font-size: 32rpx;
  font-weight: 700;
  color: var(--text);
  position: relative;
  padding-left: 18rpx;
}
.section-hd .title::before {
  content: "";
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 6rpx;
  height: 28rpx;
  background: linear-gradient(180deg, #4b86e8, var(--brand));
  border-radius: 4rpx;
}
.section-hd .more {
  font-size: 24rpx;
  color: var(--brand);
  font-weight: 500;
}

.muted {
  color: var(--muted);
}
.price {
  color: var(--price);
  font-weight: 700;
}

.thumb-contain {
  width: 100%;
  height: 100%;
  object-fit: contain;
  background: #f7f9fc;
}

/* 列表页蓝顶搜索 */
.filter {
  padding: 20rpx 24rpx 24rpx;
  background: linear-gradient(180deg, #3d7ae8 0%, var(--brand) 100%);
}
.filter .u-search__action,
.filter :deep(.u-search__action) {
  color: #fff !important;
  font-weight: 600;
  font-size: 28rpx;
  padding-left: 20rpx;
}
.filter .u-search__content,
.filter :deep(.u-search__content) {
  box-shadow: 0 4rpx 16rpx rgba(15, 40, 90, 0.12);
}

/* 分类 chips */
.chips {
  background: var(--surface);
  border-bottom: 1rpx solid var(--line);
  margin-bottom: 8rpx;
  box-shadow: 0 2rpx 8rpx rgba(31, 56, 100, 0.03);
}
.chips-x {
  white-space: nowrap;
  padding: 18rpx 16rpx;
}
.chip {
  display: inline-block;
  padding: 12rpx 24rpx;
  margin-right: 12rpx;
  font-size: 24rpx;
  color: var(--muted);
  background: #f4f7fb;
  border-radius: 999rpx;
  border: 1rpx solid transparent;
}
.chip.on {
  background: var(--brand-soft);
  color: var(--brand);
  font-weight: 600;
  border-color: rgba(39, 105, 220, 0.18);
}
.chip.more {
  background: transparent;
  color: var(--brand);
  border: none;
}

/* 详情底栏 */
.bottom-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  gap: 8rpx;
  padding: 12rpx 20rpx calc(12rpx + env(safe-area-inset-bottom));
  background: rgba(255, 255, 255, 0.96);
  backdrop-filter: blur(12px);
  box-shadow: 0 -8rpx 28rpx rgba(31, 56, 100, 0.08);
  border-top: 1rpx solid rgba(232, 237, 243, 0.8);
}
.bar-icon {
  width: 96rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4rpx;
  font-size: 20rpx;
  color: #64748b;
}

.footer-tip {
  text-align: center;
  padding: 32rpx;
  font-size: 24rpx;
  color: var(--muted);
}

.kv {
  display: flex;
  flex-direction: column;
  gap: 14rpx;
}
.kv-row {
  display: flex;
  align-items: flex-start;
  gap: 16rpx;
}
.kv-row .k {
  width: 100rpx;
  flex-shrink: 0;
  font-size: 26rpx;
  color: var(--muted);
}
.kv-row .v {
  flex: 1;
  font-size: 26rpx;
  color: var(--text);
  line-height: 1.4;
  word-break: break-all;
}
</style>

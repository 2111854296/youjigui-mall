<script setup lang="ts">
const title = "图库";
function goHome() {
  uni.switchTab({ url: "/pages/index/index" });
}
</script>

<template>
  <view class="page">
    <view class="card empty">
      <text class="title">{{ title }}暂未开放</text>
      <text class="tip muted">功能已保留，后续按需开启。</text>
      <u-button type="primary" color="#2769dc" text="返回首页" @click="goHome" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.empty {
  margin: 40rpx 24rpx;
  padding: 48rpx 32rpx;
  display: flex;
  flex-direction: column;
  gap: 20rpx;
  align-items: stretch;
}
.title { font-size: 32rpx; font-weight: 700; text-align: center; }
.tip { font-size: 26rpx; text-align: center; line-height: 1.5; }
</style>

<script setup lang="ts">
function go(url: string) {
  uni.navigateTo({ url });
}
</script>

<template>
  <view class="page">
    <view class="card info">
      <text class="sec-title">客服中心</text>
      <text class="line">工作时间：周一至周五 9:00–18:00</text>
      <text class="line muted">财富热线 / 客服电话：请见联系我们页面</text>
      <view class="btns">
        <u-button type="primary" color="#2769dc" text="在线留言" size="small" @click="go('/pages/guestbook/index')" />
        <u-button type="primary" color="#2769dc" plain text="帮助中心" size="small" @click="go('/pages/cms/page?slug=help')" />
        <u-button type="primary" color="#2769dc" plain text="联系我们" size="small" @click="go('/pages/cms/page?slug=contact')" />
      </view>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.info { margin: 20rpx; padding: 28rpx 24rpx; }
.sec-title { font-size: 32rpx; font-weight: 700; display: block; margin-bottom: 16rpx; }
.line { display: block; font-size: 26rpx; margin-bottom: 8rpx; line-height: 1.5; }
.btns { display: flex; flex-wrap: wrap; gap: 12rpx; margin-top: 20rpx; }
</style>

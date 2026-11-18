<script setup lang="ts">
import { onLoad } from "@dcloudio/uni-app";

onLoad(() => {
  uni.showToast({ title: "第三方登录暂未开放", icon: "none" });
  setTimeout(() => {
    uni.redirectTo({ url: "/pages/member/index" });
  }, 400);
});
</script>

<template>
  <view class="page center">
    <text class="muted">第三方登录暂未开放</text>
  </view>
</template>

<style lang="scss" scoped>
.center { padding: 80rpx; text-align: center; }
</style>

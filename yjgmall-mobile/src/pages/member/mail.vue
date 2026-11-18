<script setup lang="ts">
import { onShow } from "@dcloudio/uni-app";
import { ensureLogin } from "@/utils/auth";

onShow(() => {
  if (!ensureLogin()) return;
  uni.showToast({ title: "邮件订阅暂未开放", icon: "none" });
  setTimeout(() => {
    uni.redirectTo({ url: "/pages/member/index" });
  }, 500);
});
</script>

<template>
  <view class="page center">
    <text class="muted">邮件订阅暂未开放，发信邮箱配置完成后再开通。</text>
  </view>
</template>

<style lang="scss" scoped>
.center { padding: 80rpx 40rpx; text-align: center; line-height: 1.6; }
</style>

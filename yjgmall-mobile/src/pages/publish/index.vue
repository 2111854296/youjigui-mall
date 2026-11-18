<script setup lang="ts">
import { ensureLogin, ensureEnterprise } from "@/utils/auth";

async function go(path: string, need: "login" | "enterprise" = "login") {
  if (need === "enterprise") {
    if (!(await ensureEnterprise({ tip: "发布供应仅对企业会员开放，请先升级为企业会员" }))) {
      return;
    }
  } else if (!ensureLogin()) {
    return;
  }
  uni.navigateTo({ url: path });
}
</script>

<template>
  <view class="page">
    <view class="card">
      <view class="item" @click="go('/pages/publish/supply', 'enterprise')">
        <view class="icon supply">
          <u-icon name="home" color="#fff" size="22" />
        </view>
        <view class="body">
          <text class="name">发布供应</text>
          <text class="tip muted">发布产品供应信息（企业会员）</text>
        </view>
        <u-icon name="arrow-right" color="#ccc" size="14" />
      </view>
      <view class="item" @click="go('/pages/publish/purchase')">
        <view class="icon buy">
          <u-icon name="order" color="#fff" size="22" />
        </view>
        <view class="body">
          <text class="name">发布采购</text>
          <text class="tip muted">发布采购需求，等待报价</text>
        </view>
        <u-icon name="arrow-right" color="#ccc" size="14" />
      </view>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.card { margin-top: 24rpx; padding: 8rpx 0; }
.item {
  display: flex; align-items: center; gap: 20rpx;
  padding: 28rpx 24rpx; border-bottom: 1rpx solid #f1f4f7;
}
.item:last-child { border-bottom: none; }
.icon {
  width: 80rpx; height: 80rpx; border-radius: 16rpx;
  display: flex; align-items: center; justify-content: center;
}
.icon.supply { background: #2769dc; }
.icon.buy { background: #ea580c; }
.body { flex: 1; }
.name { font-size: 30rpx; font-weight: 600; display: block; }
.tip { font-size: 22rpx; margin-top: 6rpx; display: block; }
</style>

<script setup lang="ts">
import { onShow } from "@dcloudio/uni-app";
import { ensureLogin } from "@/utils/auth";

onShow(() => {
  ensureLogin();
});

function goInquiries() {
  uni.navigateTo({ url: "/pages/member/inquiries" });
}
function goMember() {
  uni.reLaunch({ url: "/pages/member/index" });
}
</script>

<template>
  <view class="page">
    <view class="card">
      <text class="title">站内交谈已关闭</text>
      <text class="desc">如需联系企业，请通过供应/商品/旺铺页提交询盘留言，由官方后台审核后再决定是否转达企业。</text>
      <u-button type="primary" color="#2769dc" text="我的询盘" @click="goInquiries" />
      <view class="gap" />
      <u-button plain text="返回会员中心" @click="goMember" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.page { padding: 24rpx; }
.card {
  background: #fff;
  border-radius: 16rpx;
  padding: 32rpx 28rpx;
}
.title { display: block; font-size: 32rpx; font-weight: 700; margin-bottom: 16rpx; }
.desc { display: block; font-size: 26rpx; color: #64748b; line-height: 1.6; margin-bottom: 28rpx; }
.gap { height: 16rpx; }
</style>

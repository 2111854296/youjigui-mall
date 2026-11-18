<script setup lang="ts">
import { computed, ref } from "vue";
import { onShow } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const data = ref<any>(null);
const loading = ref(false);

const fullLink = computed(() => {
  if (!data.value?.invite_code) return "";
  // #ifdef H5
  if (typeof location !== "undefined") {
    const base = location.href.split("#")[0].replace(/\/?$/, "/");
    return `${base}#/pages/register/index?invite=${data.value.invite_code}`;
  }
  // #endif
  return `/pages/register/index?invite=${data.value.invite_code}`;
});

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/vip/invite");
    data.value = res?.data || res;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
}

function copy() {
  if (!fullLink.value) return;
  // #ifdef H5
  if (typeof navigator !== "undefined" && navigator.clipboard?.writeText) {
    navigator.clipboard.writeText(fullLink.value).then(
      () => uni.showToast({ title: "已复制邀请链接", icon: "success" }),
      () => uni.setClipboardData({ data: fullLink.value }),
    );
    return;
  }
  // #endif
  uni.setClipboardData({ data: fullLink.value });
}

onShow(load);
</script>

<template>
  <view class="page">
    <view class="card">
      <view class="section">拉新激励机制</view>
      <text class="lead muted">通过邀请码拉新：注册奖励 7 天同档（免费企业奖励 3 天白银）；对方升级会员再奖 1 个月（免费企业奖 15 天白银）。</text>
      <view v-if="data" class="box">
        <text class="code-line">我的邀请码：<text class="code">{{ data.invite_code }}</text></text>
        <text class="link">{{ fullLink }}</text>
        <view class="gap" />
        <u-button type="primary" color="#2769dc" text="复制链接" @click="copy" />
      </view>
      <view v-if="loading" class="muted tip">加载中...</view>
    </view>

    <view class="card">
      <view class="section">已邀请</view>
      <view v-for="u in (data?.invitees || [])" :key="u.id" class="li">
        <text>{{ u.company_name || u.name || u.username }}</text>
        <text class="muted"> · {{ String(u.created_at || "").slice(0, 10) }}</text>
        <text v-if="u.vip_grade_code" class="muted"> · {{ u.vip_grade_code }}</text>
      </view>
      <text v-if="!(data?.invitees || []).length" class="muted tip">暂无</text>
    </view>

    <view class="card">
      <view class="section">奖励记录</view>
      <view v-for="r in (data?.rewards || [])" :key="r.id" class="li">
        <text>{{ r.reward_type }} · +{{ r.days_granted }}天</text>
        <text v-if="r.note" class="muted"> · {{ r.note }}</text>
      </view>
      <text v-if="!(data?.rewards || []).length" class="muted tip">暂无</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.card { margin: 20rpx; padding: 28rpx; background: #fff; border-radius: 16rpx; }
.section {
  font-size: 28rpx; font-weight: 700; padding-left: 14rpx;
  border-left: 6rpx solid #2769dc; margin-bottom: 12rpx;
}
.lead { display: block; font-size: 24rpx; line-height: 1.45; margin-bottom: 16rpx; }
.box { background: #f8fafd; padding: 20rpx; border-radius: 12rpx; }
.code-line { display: block; font-size: 26rpx; margin-bottom: 8rpx; }
.code { font-weight: 800; color: #2769dc; }
.link { display: block; word-break: break-all; font-size: 22rpx; color: #2769dc; }
.gap { height: 16rpx; }
.li { font-size: 26rpx; padding: 10rpx 0; border-bottom: 1rpx solid #f1f4f7; }
.tip { display: block; padding: 8rpx 0; font-size: 24rpx; }
.muted { color: #6b7280; }
</style>

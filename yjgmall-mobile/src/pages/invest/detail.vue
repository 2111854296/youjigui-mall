<script setup lang="ts">
import { ref } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { errMsg } from "@/utils/auth";
import { contentNodes } from "@/utils/content";

const id = ref("");
const item = ref<any>(null);
const loading = ref(true);

onLoad(async (q) => {
  id.value = String(q?.id || "");
  if (!id.value) return;
  try {
    const res = await api<any>(`/invest/${id.value}`);
    item.value = res?.data || res;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
});

function goApply() {
  uni.navigateTo({ url: `/pages/invest/index?id=${id.value}` });
}
</script>

<template>
  <view class="page">
    <view v-if="loading" class="center"><u-loading-icon color="#2769dc" /></view>
    <view v-else-if="item" class="card article">
      <text class="title">{{ item.title }}</text>
      <text class="meta muted">
        {{ item.area || "" }} · {{ item.amount || "金额面议" }} · 浏览 {{ item.views || 0 }}
      </text>
      <text v-if="item.summary" class="summary">{{ item.summary }}</text>
      <rich-text class="body" :nodes="contentNodes(item.content)" />
      <text v-if="item.contact_name" class="contact">
        联系：{{ item.contact_name }} {{ item.contact_phone || "" }}
      </text>
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="提交合作意向" @click="goApply" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.center { padding: 80rpx; text-align: center; }
.article { padding: 28rpx 24rpx; }
.title { font-size: 36rpx; font-weight: 700; display: block; margin-bottom: 12rpx; }
.meta { font-size: 22rpx; display: block; margin-bottom: 12rpx; }
.summary { display: block; font-size: 26rpx; color: #555; margin-bottom: 12rpx; line-height: 1.5; }
.body { font-size: 28rpx; line-height: 1.7; }
.contact { display: block; margin-top: 20rpx; font-size: 26rpx; color: #2769dc; }
.gap { height: 24rpx; }
</style>

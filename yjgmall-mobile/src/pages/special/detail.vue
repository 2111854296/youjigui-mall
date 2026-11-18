<script setup lang="ts">
import { ref } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { errMsg } from "@/utils/auth";
import { contentNodes } from "@/utils/content";

const item = ref<any>(null);
const loading = ref(true);

onLoad(async (q) => {
  const key = q?.id || q?.slug;
  if (!key) return;
  try {
    const res = await api<any>(`/special/${key}`);
    item.value = res?.data || res;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <view class="page">
    <view v-if="loading" class="center"><u-loading-icon color="#2769dc" /></view>
    <view v-else-if="item" class="card article">
      <text class="title">{{ item.name || item.title }}</text>
      <text v-if="item.summary" class="summary">{{ item.summary }}</text>
      <rich-text class="body" :nodes="contentNodes(item.content)" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.center { padding: 80rpx; text-align: center; }
.article { padding: 28rpx 24rpx; }
.title { font-size: 36rpx; font-weight: 700; display: block; margin-bottom: 12rpx; }
.summary { display: block; font-size: 26rpx; color: #555; margin-bottom: 16rpx; line-height: 1.5; }
.body { font-size: 28rpx; line-height: 1.7; }
</style>

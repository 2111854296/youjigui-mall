<script setup lang="ts">
import { ref } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { errMsg } from "@/utils/auth";
import { contentNodes } from "@/utils/content";

const pageData = ref<any>(null);
const loading = ref(true);
const slug = ref("");

onLoad(async (q) => {
  slug.value = String(q?.slug || "about");
  try {
    const res = await api<any>(`/pages/${slug.value}`);
    pageData.value = res?.data || res;
    if (pageData.value?.title) {
      uni.setNavigationBarTitle({ title: pageData.value.title });
    }
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "页面不存在"), icon: "none" });
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <view class="page">
    <view v-if="loading" class="center"><u-loading-icon color="#2769dc" /></view>
    <view v-else-if="pageData" class="card article">
      <text class="title">{{ pageData.title }}</text>
      <rich-text class="body" :nodes="contentNodes(pageData.content)" />
    </view>
    <view v-else class="center muted">页面不存在</view>
  </view>
</template>

<style lang="scss" scoped>
.center { padding: 80rpx; text-align: center; }
.article { padding: 28rpx 24rpx; }
.title { font-size: 36rpx; font-weight: 700; display: block; margin-bottom: 20rpx; }
.body { font-size: 28rpx; line-height: 1.7; }
</style>

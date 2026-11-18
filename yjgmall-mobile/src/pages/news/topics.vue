<script setup lang="ts">
import { ref } from "vue";
import { onLoad, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const loading = ref(false);

async function load() {
  loading.value = true;
  try {
    const res = await api<any>("/news/topics");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function goTopic(item: any) {
  const slug = item.slug || item.id;
  uni.navigateTo({ url: `/pages/news/topic?slug=${encodeURIComponent(slug)}` });
}

onLoad(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view
      v-for="(item, i) in items"
      :key="item.id || i"
      class="row card"
      @click="goTopic(item)"
    >
      <text class="title">{{ item.name }}</text>
      <text v-if="item.summary" class="summary muted">{{ item.summary }}</text>
      <text class="meta muted">文章 {{ item.articles_count || 0 }}</text>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无专题</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.row { padding: 24rpx 20rpx; }
.title { font-size: 30rpx; font-weight: 600; display: block; }
.summary { display: block; margin-top: 8rpx; font-size: 24rpx; }
.meta { display: block; margin-top: 8rpx; font-size: 22rpx; }
.footer-tip { text-align: center; padding: 24rpx; font-size: 24rpx; }
</style>

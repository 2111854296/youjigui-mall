<script setup lang="ts">
import { ref } from "vue";
import { onLoad, onPullDownRefresh } from "@dcloudio/uni-app";
import { api, mediaUrl } from "@/utils/request";
import { errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const loading = ref(false);

async function load() {
  loading.value = true;
  try {
    const res = await api<any>("/news/rank");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function goDetail(id: number | string) {
  uni.navigateTo({ url: `/pages/news/detail?id=${id}` });
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
      @click="goDetail(item.id)"
    >
      <view class="no" :class="{ top: i < 3 }">{{ i + 1 }}</view>
      <image class="thumb" :src="mediaUrl(item.thumb)" mode="aspectFill" />
      <view class="info">
        <text class="title">{{ item.title }}</text>
        <text class="meta muted">{{ item.views || 0 }} 阅</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无数据</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.row { display: flex; gap: 16rpx; align-items: center; padding: 16rpx 20rpx; }
.no {
  width: 44rpx; height: 44rpx; border-radius: 8rpx; background: #94a3b8;
  color: #fff; font-size: 22rpx; font-weight: 800;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.no.top { background: #2769dc; }
.thumb { width: 140rpx; height: 100rpx; border-radius: 8rpx; background: #f7f8fa; flex-shrink: 0; }
.info { flex: 1; min-width: 0; }
.title {
  font-size: 28rpx; font-weight: 600; display: -webkit-box;
  -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.meta { display: block; margin-top: 8rpx; font-size: 22rpx; }
.footer-tip { text-align: center; padding: 24rpx; font-size: 24rpx; }
</style>

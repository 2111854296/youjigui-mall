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
    const res = await api<any>("/links");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function open(url: string) {
  if (!url) return;
  // #ifdef H5
  window.open(url, "_blank");
  // #endif
  // #ifndef H5
  uni.setClipboardData({
    data: url,
    success: () => uni.showToast({ title: "链接已复制", icon: "none" }),
  });
  // #endif
}

onLoad(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view class="card wrap">
      <text
        v-for="(item, i) in items"
        :key="item.id || i"
        class="link"
        @click="open(item.url)"
      >{{ item.name }}</text>
      <view class="footer-tip muted">
        <text v-if="loading">加载中...</text>
        <text v-else-if="!items.length">暂无友链</text>
      </view>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.wrap {
  margin: 20rpx; padding: 24rpx; display: flex; flex-wrap: wrap; gap: 20rpx 28rpx;
}
.link { color: #2769dc; font-size: 28rpx; }
.footer-tip { width: 100%; text-align: center; padding: 16rpx 0; font-size: 24rpx; }
</style>

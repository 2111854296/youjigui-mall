<script setup lang="ts">
import { ref, computed } from "vue";
import { onLoad, onReachBottom, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const page = ref(1);
const totalPages = ref(1);
const loading = ref(false);
const finished = computed(() => page.value >= totalPages.value);

async function fetchList(reset = false) {
  if (loading.value) return;
  if (reset) {
    page.value = 1;
    items.value = [];
  }
  loading.value = true;
  try {
    const res = await api<any>("/special", { query: { page: page.value } });
    const list = res?.data || [];
    items.value = reset ? list : items.value.concat(list);
    totalPages.value = Number(res?.meta?.last_page || res?.meta?.pages || 1);
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function goDetail(item: any) {
  const key = item.slug || item.id;
  uni.navigateTo({ url: `/pages/special/detail?id=${encodeURIComponent(key)}` });
}

onLoad(() => fetchList(true));
onReachBottom(() => {
  if (finished.value || loading.value) return;
  page.value += 1;
  fetchList();
});
onPullDownRefresh(() => fetchList(true));
</script>

<template>
  <view class="page">
    <view
      v-for="(item, i) in items"
      :key="item.id || i"
      class="row card"
      @click="goDetail(item)"
    >
      <text class="title">{{ item.name || item.title }}</text>
      <text v-if="item.summary" class="summary muted">{{ item.summary }}</text>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无专题</text>
      <text v-else-if="finished">没有更多了</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.row { padding: 24rpx 20rpx; }
.title { font-size: 30rpx; font-weight: 600; display: block; }
.summary { display: block; margin-top: 8rpx; font-size: 24rpx; }
.footer-tip { text-align: center; padding: 24rpx; font-size: 24rpx; }
</style>

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
    const res = await api<any>("/jobs", { query: { page: page.value } });
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

function goDetail(id: number | string) {
  uni.navigateTo({ url: `/pages/job/detail?id=${id}` });
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
      @click="goDetail(item.id)"
    >
      <view class="top">
        <text class="title">{{ item.title }}</text>
        <text class="salary">{{ item.salary || "薪资面议" }}</text>
      </view>
      <text class="sub muted">{{ item.company_name || "-" }} · {{ item.area || "地区不限" }}</text>
      <text class="meta muted">浏览 {{ item.views || 0 }}</text>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无招聘</text>
      <text v-else-if="finished">没有更多了</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.row { padding: 24rpx 20rpx; }
.top { display: flex; justify-content: space-between; gap: 16rpx; align-items: flex-start; }
.title { flex: 1; font-size: 30rpx; font-weight: 600; line-height: 1.35; }
.salary { color: #c2410c; font-weight: 700; font-size: 26rpx; white-space: nowrap; }
.sub { display: block; margin-top: 10rpx; font-size: 24rpx; }
.meta { display: block; margin-top: 6rpx; font-size: 22rpx; }
.footer-tip { text-align: center; padding: 24rpx; font-size: 24rpx; }
</style>

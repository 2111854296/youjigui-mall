<script setup lang="ts">
import { ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const loading = ref(false);

function linkOf(item: any) {
  if (item.type === "purchase") return `/pages/buy/detail?id=${item.target_id}`;
  if (item.type === "product") return `/pages/mall/detail?id=${item.target_id}`;
  return `/pages/sell/detail?id=${item.target_id}`;
}

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/favorites");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function remove(id: number) {
  try {
    await api(`/member/favorites/${id}`, { method: "DELETE" });
    uni.showToast({ title: "已取消", icon: "none" });
    load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  }
}

function open(item: any) {
  uni.navigateTo({ url: linkOf(item) });
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view v-for="item in items" :key="item.id" class="row card">
      <view class="body" @click="open(item)">
        <text class="type muted">{{ item.type }}</text>
        <text class="title">{{ item.title }}</text>
        <text class="meta muted">{{ item.created_at?.slice?.(0, 16) || "" }}</text>
      </view>
      <text class="danger" @click="remove(item.id)">取消</text>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无收藏</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.row { display: flex; gap: 12rpx; padding: 22rpx 20rpx; align-items: center; }
.body { flex: 1; min-width: 0; }
.type { font-size: 22rpx; display: block; margin-bottom: 4rpx; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 8rpx; }
.meta { font-size: 22rpx; }
.danger { color: #e11d48; font-size: 24rpx; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>

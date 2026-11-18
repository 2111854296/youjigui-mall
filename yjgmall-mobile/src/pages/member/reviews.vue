<script setup lang="ts">
import { ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const loading = ref(false);

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/reviews");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function openProduct(item: any) {
  const id = item.product?.id || item.product_id;
  if (!id) return;
  uni.navigateTo({ url: `/pages/mall/detail?id=${id}` });
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view v-for="item in items" :key="item.id" class="row card" @click="openProduct(item)">
      <view class="body">
        <text class="title">{{ item.product?.title || "商品评价" }}</text>
        <text class="rating">{{ item.rating }}星</text>
        <text class="content">{{ item.content }}</text>
        <text class="meta muted">{{ item.created_at?.slice?.(0, 16) || "" }}</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无评价</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.row { padding: 22rpx 20rpx; }
.body { flex: 1; min-width: 0; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 8rpx; }
.rating { font-size: 24rpx; color: #ca8a04; display: block; margin-bottom: 8rpx; }
.content { font-size: 26rpx; color: #333; display: block; margin-bottom: 8rpx; }
.meta { font-size: 22rpx; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>

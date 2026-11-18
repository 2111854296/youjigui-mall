<script setup lang="ts">
import { ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import { statusLabel } from "@/utils/status";

const items = ref<any[]>([]);
const loading = ref(false);

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/purchases");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function remove(id: number) {
  uni.showModal({
    title: "确认删除？",
    success: async (r) => {
      if (!r.confirm) return;
      try {
        await api(`/member/purchases/${id}`, { method: "DELETE" });
        uni.showToast({ title: "已删除", icon: "success" });
        load();
      } catch (e: any) {
        uni.showToast({ title: errMsg(e), icon: "none" });
      }
    },
  });
}

function create() {
  uni.navigateTo({ url: "/pages/publish/purchase" });
}

function edit(id: number) {
  uni.navigateTo({ url: `/pages/publish/purchase?id=${id}` });
}

function goDetail(id: number) {
  uni.navigateTo({ url: `/pages/buy/detail?id=${id}` });
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view class="top">
      <u-button type="primary" color="#2769dc" size="small" text="发布采购" @click="create" />
    </view>
    <view v-for="item in items" :key="item.id" class="row card">
      <view class="body" @click="goDetail(item.id)">
        <text class="title">{{ item.title }}</text>
        <text class="meta muted">{{ statusLabel(item.status) }} · 预算 {{ item.budget || "面议" }}</text>
      </view>
      <view class="acts">
        <text @click="edit(item.id)">编辑</text>
        <text class="danger" @click="remove(item.id)">删除</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无采购</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.top { padding: 16rpx 20rpx; display: flex; justify-content: flex-end; }
.row { display: flex; gap: 12rpx; padding: 22rpx 20rpx; align-items: center; }
.body { flex: 1; min-width: 0; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 8rpx; }
.meta { font-size: 22rpx; }
.acts { display: flex; flex-direction: column; gap: 12rpx; font-size: 24rpx; color: #2769dc; }
.danger { color: #e11d48; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>

<script setup lang="ts">
import { ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import { statusLabel } from "@/utils/status";

const items = ref<any[]>([]);
const loading = ref(false);
const showcase = ref({ grade_name: "普通会员", limit: 0, used: 0 });

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/products");
    items.value = res?.data || [];
    showcase.value = res?.showcase || showcase.value;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function create() {
  uni.navigateTo({ url: "/pages/member/product-edit" });
}

function edit(id: number) {
  uni.navigateTo({ url: `/pages/member/product-edit?id=${id}` });
}

async function toggleSample(item: any) {
  const on = item.is_sample ? 0 : 1;
  try {
    const res = await api<any>("/member/products/sample", { method: "POST", body: { id: item.id, on } });
    uni.showToast({ title: res?.message || (on ? "已放入样品专区" : "已移出"), icon: "none" });
    load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  }
}

async function toggleShowcase(item: any) {
  const on = item.is_showcase ? 0 : 1;
  try {
    const res = await api<any>("/member/products/showcase", { method: "POST", body: { id: item.id, on } });
    uni.showToast({ title: res?.message || (on ? "已设为展示" : "已取消"), icon: "none" });
    load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  }
}

async function remove(id: number) {
  uni.showModal({
    title: "确认删除？",
    success: async (r) => {
      if (!r.confirm) return;
      try {
        await api(`/member/products/${id}`, { method: "DELETE" });
        uni.showToast({ title: "已删除", icon: "success" });
        load();
      } catch (e: any) {
        uni.showToast({ title: errMsg(e), icon: "none" });
      }
    },
  });
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view class="tip muted">
      {{ showcase.limit ? `${showcase.grade_name}可在商品页展示 ${showcase.limit} 款，已选 ${showcase.used} 款` : "普通会员不能展示本店商品。白银2款、黄金3款、星钻5款" }}
    </view>
    <view class="top">
      <u-button type="primary" color="#2769dc" size="small" text="发布商品" @click="create" />
    </view>
    <view v-for="item in items" :key="item.id" class="row card">
      <view class="body">
        <text class="title">{{ item.title }}</text>
        <text class="meta muted">¥{{ item.price }} · 库存 {{ item.stock }} · {{ statusLabel(item.status) }}<text v-if="item.showcase_on"> · 展示中</text><text v-if="item.is_sample"> · 样品中</text></text>
      </view>
      <view class="acts">
        <text @click="toggleShowcase(item)">{{ item.is_showcase ? "取消展示" : "展示" }}</text>
        <text @click="toggleSample(item)">{{ item.is_sample ? "移出样品" : "放入样品" }}</text>
        <text @click="edit(item.id)">编辑</text>
        <text class="danger" @click="remove(item.id)">删除</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无商品</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.tip { padding: 16rpx 20rpx 0; font-size: 22rpx; line-height: 1.5; }
.top { padding: 16rpx 20rpx; display: flex; justify-content: flex-end; }
.row { display: flex; gap: 12rpx; padding: 22rpx 20rpx; align-items: center; }
.body { flex: 1; min-width: 0; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 8rpx; }
.meta { font-size: 22rpx; }
.acts { display: flex; flex-direction: column; gap: 12rpx; font-size: 24rpx; color: #2769dc; }
.danger { color: #e11d48; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>

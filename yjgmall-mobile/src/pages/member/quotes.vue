<script setup lang="ts">
import { ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import { statusLabel } from "@/utils/status";
import { openChat } from "@/utils/chat";

const items = ref<any[]>([]);
const loading = ref(false);

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/quotes");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function setStatus(id: number, status: string) {
  try {
    await api(`/member/quotes/${id}/status`, { method: "POST", body: { status } });
    load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  }
}

function quoteTitle(item: any) {
  return item.purchase?.title || item.title || item.purchase_title || `报价 #${item.id}`;
}

function quoteUnit(item: any) {
  const u = String(item.unit || "").trim();
  if (!u || u === "?" || u === "？" || u === "-") return "";
  return "/" + u;
}

function goPurchase(item: any) {
  const pid = item.purchase?.id || item.purchase_id;
  if (!pid) {
    uni.showToast({ title: "采购单不可用", icon: "none" });
    return;
  }
  uni.navigateTo({ url: `/pages/buy/detail?id=${pid}` });
}

function chatPeer(item: any) {
  openChat(item.user?.id);
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view v-for="item in items" :key="item.id" class="row card">
      <text class="title" @click="goPurchase(item)">{{ quoteTitle(item) }}</text>
      <text class="meta">
        报价方：{{ item.contact_name || item.user?.company_name || item.user?.name || "—" }}
        <template v-if="item.contact_phone"> · {{ item.contact_phone }}</template>
      </text>
      <text class="meta">
        报价：{{ item.price != null ? `¥${item.price}${quoteUnit(item)}` : "面议" }}
        <template v-if="item.quantity"> · 数量 {{ item.quantity }}</template>
        · {{ statusLabel(item.status) }}
      </text>
      <text class="content">{{ item.content }}</text>
      <view class="acts">
        <template v-if="item.status === 'new'">
          <text @click="setStatus(item.id, 'accepted')">采纳</text>
          <text class="danger" @click="setStatus(item.id, 'rejected')">婉拒</text>
        </template>
        <text v-else-if="item.status === 'accepted'" @click="setStatus(item.id, 'done')">完成</text>
        <text v-if="item.purchase?.id || item.purchase_id" @click="goPurchase(item)">查看采购</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无报价（他人对您的采购报价会出现在这里）</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.row { padding: 22rpx 20rpx; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 8rpx; color: #2769dc; }
.meta { font-size: 24rpx; display: block; margin-bottom: 4rpx; }
.content { font-size: 26rpx; margin-top: 8rpx; display: block; line-height: 1.5; }
.acts { display: flex; flex-wrap: wrap; gap: 28rpx; margin-top: 16rpx; color: #2769dc; font-size: 26rpx; }
.danger { color: #e11d48; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>

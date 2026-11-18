<script setup lang="ts">
import { ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

type PayOrder = {
  id: number;
  order_no: string;
  title?: string;
  amount_yuan: string;
  status: string;
  status_label: string;
  created_at?: string;
  expire_at?: string;
  admin_remark?: string | null;
};

const items = ref<PayOrder[]>([]);
const loading = ref(true);
const err = ref("");

function openStatus(s: string) {
  return s === "pending_pay" || s === "pending_review";
}

function whenOf(item: PayOrder) {
  return String(item.created_at || item.expire_at || "").replace("T", " ").slice(0, 16);
}

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  err.value = "";
  try {
    const res = await api<{ data: PayOrder[] }>("/member/fund/orders");
    items.value = res?.data || [];
  } catch (e: any) {
    err.value = errMsg(e, "加载失败");
    items.value = [];
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function open(id: number) {
  uni.navigateTo({ url: `/pages/member/fund-pay-detail?id=${id}` });
}

function goFinance() {
  uni.navigateTo({ url: "/pages/member/finance" });
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view class="card lead-card">
      <text class="lead">充值、报告、次数包、商城、团购、积分的对公转账都在这里。上传凭证后由财务审核，通过后再生效。</text>
      <text class="link" @click="goFinance">去充值</text>
    </view>

    <view v-if="loading" class="footer-tip muted">加载中...</view>
    <view v-else-if="err" class="card err">{{ err }}</view>
    <view v-else-if="!items.length" class="footer-tip muted">暂无付款订单。请先到资金流水发起对公充值。</view>

    <view
      v-for="item in items"
      :key="item.id"
      class="card row"
      :class="{ open: openStatus(item.status) }"
      @click="open(item.id)"
    >
      <view class="body">
        <text class="title">{{ item.title || "账户充值" }}</text>
        <text class="meta muted">{{ item.order_no }}</text>
        <text class="meta muted">{{ whenOf(item) }}</text>
        <text v-if="item.status === 'rejected' && item.admin_remark" class="rej">{{ item.admin_remark }}</text>
      </view>
      <view class="right">
        <text class="price">¥{{ item.amount_yuan }}</text>
        <text class="st" :data-s="item.status">{{ item.status_label }}</text>
        <text class="go">{{ openStatus(item.status) ? "继续" : "详情" }}</text>
      </view>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.lead-card { padding: 24rpx 28rpx; }
.lead { display: block; font-size: 24rpx; color: #64748b; line-height: 1.5; }
.link { display: inline-block; margin-top: 12rpx; color: #2769dc; font-size: 26rpx; font-weight: 700; }
.err { padding: 24rpx; color: #b91c1c; font-size: 26rpx; }
.row { display: flex; padding: 22rpx 24rpx; gap: 16rpx; align-items: flex-start; }
.row.open { box-shadow: inset 6rpx 0 0 #0f766e; }
.body { flex: 1; min-width: 0; }
.title { display: block; font-size: 28rpx; font-weight: 700; margin-bottom: 6rpx; }
.meta { display: block; font-size: 22rpx; word-break: break-all; }
.rej { display: block; margin-top: 8rpx; font-size: 22rpx; color: #b91c1c; }
.right { display: flex; flex-direction: column; align-items: flex-end; gap: 8rpx; }
.price { font-size: 30rpx; font-weight: 800; color: #c2410c; }
.st {
  font-size: 22rpx; font-weight: 700; padding: 4rpx 14rpx; border-radius: 999rpx;
  background: #f1f5f9; color: #475569;
}
.st[data-s="pending_pay"] { background: #fff7ed; color: #c2410c; }
.st[data-s="pending_review"] { background: #eff6ff; color: #1d4fd7; }
.st[data-s="approved"] { background: #ecfdf5; color: #047857; }
.st[data-s="rejected"] { background: #fef2f2; color: #b91c1c; }
.go { font-size: 22rpx; color: #0f766e; font-weight: 700; }
</style>

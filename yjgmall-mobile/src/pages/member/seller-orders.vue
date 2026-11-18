<script setup lang="ts">
import { ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const loading = ref(false);
const openId = ref<number | null>(null);
const detail = ref<any>(null);
const acting = ref(false);

const orderStatus: Record<string, string> = {
  pending: "待支付",
  paid: "已支付",
  shipped: "已发货",
  completed: "已完成",
  cancelled: "已取消",
};

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/seller-orders");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function toggle(item: any) {
  if (openId.value === item.id) {
    openId.value = null;
    detail.value = null;
    return;
  }
  openId.value = item.id;
  try {
    const res = await api<any>(`/member/orders/${item.id}`);
    detail.value = res?.data || res;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
    openId.value = null;
  }
}

async function act(action: string) {
  if (!detail.value) return;
  acting.value = true;
  try {
    const res = await api<any>(`/member/orders/${detail.value.id}/status`, {
      method: "POST",
      body: { action },
    });
    detail.value = res?.data || detail.value;
    uni.showToast({ title: "操作成功", icon: "success" });
    await load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "操作失败"), icon: "none" });
  } finally {
    acting.value = false;
  }
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view v-for="item in items" :key="item.id" class="card block">
      <view class="row" @click="toggle(item)">
        <view class="body">
          <text class="title">{{ item.order_no || "订单 #" + item.id }}</text>
          <text class="meta muted">买家 {{ item.receiver_name || item.user?.name || "-" }} · {{ orderStatus[item.status] || item.status }}</text>
          <text class="meta muted">¥{{ item.amount }} · {{ item.created_at?.slice?.(0, 16) || "" }}</text>
        </view>
        <u-icon :name="openId === item.id ? 'arrow-up' : 'arrow-down'" color="#999" size="14" />
      </view>
      <view v-if="openId === item.id && detail" class="detail">
        <text class="line">买家：{{ detail.user?.name || detail.receiver_name }} · {{ orderStatus[detail.status] || detail.status }}</text>
        <text class="line">收货：{{ detail.receiver_name }} / {{ detail.receiver_phone }} / {{ detail.receiver_address }}</text>
        <view v-for="it in detail.items || []" :key="it.id" class="item-line">
          <text>{{ it.title }}</text>
          <text class="muted">¥{{ it.price }} × {{ it.quantity }}</text>
        </view>
        <view class="acts">
          <u-button
            v-if="['paid', 'pending'].includes(detail.status)"
            type="primary"
            color="#2769dc"
            size="small"
            text="发货"
            :loading="acting"
            @click="act('ship')"
          />
          <u-button
            v-if="['pending', 'paid'].includes(detail.status)"
            plain
            color="#e11d48"
            size="small"
            text="取消"
            :loading="acting"
            @click="act('cancel')"
          />
        </view>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无卖出订单</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.block { padding: 0; }
.row { display: flex; align-items: center; gap: 12rpx; padding: 22rpx 20rpx; }
.body { flex: 1; min-width: 0; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 8rpx; }
.meta { font-size: 22rpx; display: block; }
.detail {
  padding: 0 20rpx 22rpx;
  border-top: 1rpx solid #f1f4f7;
  font-size: 26rpx;
  line-height: 1.6;
}
.line { display: block; margin-top: 8rpx; color: #333; }
.item-line {
  display: flex; justify-content: space-between; gap: 12rpx;
  margin-top: 10rpx; padding: 10rpx 0; border-bottom: 1rpx solid #f5f5f5;
}
.acts { display: flex; gap: 16rpx; margin-top: 16rpx; flex-wrap: wrap; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>

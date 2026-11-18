<script setup lang="ts">
import { computed, ref } from "vue";
import { onShow } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const wallet = ref<any>({});
const points = ref(100);
const loading = ref(false);
const saving = ref(false);
const cost = computed(() => (Number(points.value || 0) * 0.1).toFixed(2));

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/finance");
    wallet.value = res?.wallet || {};
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
  }
}

async function buy() {
  const ok = await new Promise<boolean>((resolve) => {
    uni.showModal({
      title: "确认购买",
      content: `将创建对公付款订单 ¥${cost.value}，购买 ${points.value} 积分。审核通过后到账。`,
      success: (r) => resolve(!!r.confirm),
      fail: () => resolve(false),
    });
  });
  if (!ok) return;
  saving.value = true;
  try {
    const res: any = await api("/member/points/buy", { method: "POST", body: { points: Number(points.value) } });
    const oid = res?.data?.id;
    if (oid) {
      uni.navigateTo({ url: `/pages/member/fund-pay-detail?id=${oid}` });
      return;
    }
    uni.showToast({ title: res?.message || "请上传付款凭证", icon: "none" });
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "购买失败"), icon: "none" });
  } finally {
    saving.value = false;
  }
}

onShow(load);
</script>

<template>
  <view class="page">
    <view class="card stats">
      <view class="stat"><text class="b">{{ wallet.balance ?? "0.00" }}</text><text class="muted">余额</text></view>
      <view class="stat"><text class="b">{{ wallet.points ?? 0 }}</text><text class="muted">积分</text></view>
    </view>
    <view class="card form">
      <view class="section">购买积分</view>
      <text class="hint muted">兑换比例：1 积分 = 0.1 元</text>
      <view class="gap" />
      <u-input v-model="points" placeholder="积分数量（最少 10）" border="surround" type="number" />
      <view class="gap" />
      <text class="cost">需支付 {{ cost }} 元</text>
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="购买" :loading="saving" @click="buy" />
      <view v-if="loading" class="footer-tip muted">加载中...</view>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.stats { display: flex; padding: 20rpx 8rpx; }
.stat {
  width: 50%; box-sizing: border-box; padding: 16rpx;
  display: flex; flex-direction: column; align-items: center;
}
.b { font-size: 34rpx; font-weight: 700; color: #c2410c; }
.form { padding: 28rpx; }
.section {
  font-size: 28rpx; font-weight: 700; padding-left: 14rpx;
  border-left: 6rpx solid #2769dc; margin-bottom: 12rpx;
}
.hint { display: block; font-size: 22rpx; }
.gap { height: 18rpx; }
.cost { font-size: 28rpx; color: #c2410c; font-weight: 600; }
.footer-tip { text-align: center; padding: 24rpx; }
</style>

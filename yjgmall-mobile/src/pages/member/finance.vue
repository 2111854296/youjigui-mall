<script setup lang="ts">
import { ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const records = ref<any[]>([]);
const wallet = ref<any>({});
const amount = ref("100");
const loading = ref(false);
const charging = ref(false);

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/finance");
    records.value = res?.data || [];
    wallet.value = res?.wallet || {};
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function charge() {
  const n = Number(amount.value);
  if (!n || n <= 0) {
    uni.showToast({ title: "请输入有效金额", icon: "none" });
    return;
  }
  const ok = await new Promise<boolean>((resolve) => {
    uni.showModal({
      title: "确认充值",
      content: `将创建对公充值订单 ¥${n}。请转账并上传凭证，审核通过后余额入账。`,
      success: (r) => resolve(!!r.confirm),
      fail: () => resolve(false),
    });
  });
  if (!ok) return;
  charging.value = true;
  try {
    const res: any = await api("/member/finance/charge", { method: "POST", body: { amount: n } });
    const oid = res?.data?.id;
    if (oid) {
      uni.navigateTo({ url: `/pages/member/fund-pay-detail?id=${oid}` });
      return;
    }
    uni.showToast({ title: res?.message || "请上传付款凭证", icon: "none" });
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    charging.value = false;
  }
}

function go(path: string) {
  uni.navigateTo({ url: path });
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view class="card stats">
      <view class="stat"><text class="b">{{ wallet.balance ?? "0.00" }}</text><text class="muted">余额</text></view>
      <view class="stat"><text class="b">{{ wallet.deposit ?? "0.00" }}</text><text class="muted">保证金</text></view>
      <view class="stat"><text class="b">{{ wallet.points ?? 0 }}</text><text class="muted">积分</text></view>
      <view class="stat"><text class="b">{{ wallet.credit ?? 0 }}</text><text class="muted">信用</text></view>
    </view>

    <view class="card form">
      <view class="section">余额充值</view>
      <u-input v-model="amount" placeholder="金额" border="surround" type="digit" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="确认充值" :loading="charging" @click="charge" />
      <view class="links">
        <text @click="go('/pages/member/fund-pay')">付款订单</text>
        <text @click="go('/pages/member/deposit')">保证金</text>
        <text @click="go('/pages/member/cash')">提现</text>
        <text @click="go('/pages/member/credit')">购积分</text>
      </view>
    </view>

    <view class="section-hd"><text class="title">资金流水</text></view>
    <view v-for="r in records" :key="r.id" class="row card">
      <view class="body">
        <text class="title">{{ r.title || r.type }}</text>
        <text class="meta muted">{{ r.created_at?.slice?.(0, 16) }} · {{ r.type }}</text>
      </view>
      <view class="right">
        <text class="amt">{{ r.amount }}</text>
        <text class="meta muted">余 {{ r.balance_after }}</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!records.length">暂无流水</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.stats {
  display: flex; flex-wrap: wrap; padding: 20rpx 8rpx;
}
.stat {
  width: 50%; box-sizing: border-box; padding: 16rpx;
  display: flex; flex-direction: column; align-items: center;
}
.b { font-size: 34rpx; font-weight: 700; color: #c2410c; }
.form { padding: 28rpx; }
.section {
  font-size: 28rpx; font-weight: 700; padding-left: 14rpx;
  border-left: 6rpx solid #2769dc; margin-bottom: 16rpx;
}
.gap { height: 18rpx; }
.links {
  display: flex; gap: 28rpx; margin-top: 20rpx; color: #2769dc; font-size: 26rpx;
}
.row { display: flex; padding: 22rpx 20rpx; gap: 12rpx; align-items: center; }
.body { flex: 1; min-width: 0; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 6rpx; }
.meta { font-size: 22rpx; display: block; }
.right { text-align: right; }
.amt { font-size: 28rpx; color: #c2410c; font-weight: 600; display: block; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>

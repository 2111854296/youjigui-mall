<script setup lang="ts">
import { ref } from "vue";
import { onShow } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const wallet = ref<any>({});
const amount = ref("100");
const loading = ref(false);
const saving = ref(false);

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

async function submit() {
  saving.value = true;
  try {
    const res: any = await api("/member/deposit", { method: "POST", body: { amount: Number(amount.value) } });
    uni.showToast({ title: res?.message || "成功", icon: "success" });
    await load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "操作失败"), icon: "none" });
  } finally {
    saving.value = false;
  }
}

onShow(load);
</script>

<template>
  <view class="page">
    <view class="card stats">
      <view class="stat"><text class="b">{{ wallet.balance ?? "0.00" }}</text><text class="muted">可用余额</text></view>
      <view class="stat"><text class="b">{{ wallet.deposit ?? "0.00" }}</text><text class="muted">保证金</text></view>
    </view>
    <view class="card form">
      <view class="section">从余额转入保证金</view>
      <u-input v-model="amount" placeholder="金额" border="surround" type="digit" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="转入保证金" :loading="saving" @click="submit" />
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
  border-left: 6rpx solid #2769dc; margin-bottom: 16rpx;
}
.gap { height: 18rpx; }
.footer-tip { text-align: center; padding: 24rpx; }
</style>

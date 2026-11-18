<script setup lang="ts">
import { reactive, ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import { statusLabel } from "@/utils/status";

const list = ref<any[]>([]);
const wallet = ref<any>({});
const loading = ref(false);
const saving = ref(false);
const form = reactive({
  amount: "100",
  bank_name: "",
  bank_account: "",
  account_name: "",
  pay_password: "",
});

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/withdraws");
    list.value = res?.data || [];
    wallet.value = res?.wallet || {};
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function submit() {
  if (!form.amount || !form.bank_name || !form.bank_account || !form.account_name) {
    uni.showToast({ title: "请填写完整提现信息", icon: "none" });
    return;
  }
  const ok = await new Promise<boolean>((resolve) => {
    uni.showModal({
      title: "确认提现",
      content: `确认申请提现 ¥${form.amount} 到 ${form.bank_name}？`,
      success: (r) => resolve(!!r.confirm),
      fail: () => resolve(false),
    });
  });
  if (!ok) return;
  saving.value = true;
  try {
    await api("/member/withdraws", {
      method: "POST",
      body: {
        amount: Number(form.amount),
        bank_name: form.bank_name,
        bank_account: form.bank_account,
        account_name: form.account_name,
        pay_password: form.pay_password || null,
      },
    });
    uni.showToast({ title: "提现申请已提交", icon: "success" });
    await load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "提交失败"), icon: "none" });
  } finally {
    saving.value = false;
  }
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view class="card form">
      <text class="bal muted">可用余额：{{ wallet.balance ?? "0.00" }}</text>
      <view class="section">申请提现</view>
      <u-input v-model="form.amount" placeholder="金额 *" border="surround" type="digit" />
      <view class="gap" />
      <u-input v-model="form.bank_name" placeholder="开户行 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.bank_account" placeholder="账号 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.account_name" placeholder="户名 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.pay_password" placeholder="支付密码（已设置则必填）" border="surround" password />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="提交申请" :loading="saving" @click="submit" />
    </view>

    <view v-for="r in list" :key="r.id" class="row card">
      <view class="body">
        <text class="title">¥{{ r.amount }}</text>
        <text class="meta muted">{{ r.bank_name }} / {{ r.bank_account }}</text>
        <text class="meta muted">{{ r.created_at?.slice?.(0, 16) }}</text>
      </view>
      <text class="st">{{ statusLabel(r.status) }}</text>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!list.length">暂无记录</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.form { margin: 20rpx; padding: 28rpx; }
.bal { display: block; margin-bottom: 12rpx; font-size: 24rpx; }
.section {
  font-size: 28rpx; font-weight: 700; padding-left: 14rpx;
  border-left: 6rpx solid #2769dc; margin-bottom: 16rpx;
}
.gap { height: 18rpx; }
.row { display: flex; padding: 22rpx 20rpx; align-items: center; gap: 12rpx; }
.body { flex: 1; min-width: 0; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 6rpx; color: #c2410c; }
.meta { font-size: 22rpx; display: block; }
.st { font-size: 24rpx; color: #2769dc; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>

<script setup lang="ts">
import { reactive, ref } from "vue";
import { onShow } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const loading = ref(false);
const form = reactive({
  current_password: "",
  password: "",
  password_confirmation: "",
  current_pay_password: "",
  pay_password: "",
  pay_password_confirmation: "",
});

onShow(() => {
  ensureLogin();
});

async function submit() {
  if (!form.password && !form.pay_password) {
    uni.showToast({ title: "请填写要修改的密码", icon: "none" });
    return;
  }
  loading.value = true;
  try {
    const body: Record<string, string> = {};
    if (form.password) {
      body.current_password = form.current_password;
      body.password = form.password;
      body.password_confirmation = form.password_confirmation;
    }
    if (form.pay_password) {
      body.current_pay_password = form.current_pay_password;
      body.pay_password = form.pay_password;
      body.pay_password_confirmation = form.pay_password_confirmation;
    }
    await api("/member/passwords", { method: "PUT", body });
    uni.showToast({ title: "密码已更新", icon: "success" });
    Object.keys(form).forEach((k) => ((form as any)[k] = ""));
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "保存失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <view class="page">
    <view class="card form">
      <view class="section">登录密码</view>
      <u-input v-model="form.current_password" placeholder="现有密码" border="surround" password />
      <view class="gap" />
      <u-input v-model="form.password" placeholder="新密码" border="surround" password />
      <view class="gap" />
      <u-input v-model="form.password_confirmation" placeholder="确认新密码" border="surround" password />

      <view class="section">支付密码</view>
      <text class="hint muted">提现等资金操作使用；首次设置可不填现有支付密码</text>
      <view class="gap" />
      <u-input v-model="form.current_pay_password" placeholder="现有支付密码" border="surround" password />
      <view class="gap" />
      <u-input v-model="form.pay_password" placeholder="新支付密码" border="surround" password />
      <view class="gap" />
      <u-input v-model="form.pay_password_confirmation" placeholder="确认支付密码" border="surround" password />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="保存密码" :loading="loading" @click="submit" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.form { margin: 20rpx; padding: 28rpx; }
.gap { height: 18rpx; }
.section {
  margin: 28rpx 0 16rpx; font-size: 28rpx; font-weight: 700;
  padding-left: 14rpx; border-left: 6rpx solid #2769dc;
}
.section:first-child { margin-top: 0; }
.hint { display: block; font-size: 22rpx; margin-bottom: 8rpx; }
</style>

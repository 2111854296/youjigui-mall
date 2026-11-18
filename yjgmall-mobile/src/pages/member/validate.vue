<script setup lang="ts">
import { reactive, ref } from "vue";
import { onShow } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import { useAuth } from "@/composables/useAuth";

const { fetchMe } = useAuth();
const info = ref<any>({});
const submitting = ref("");
const mobile = ref("");
const smsCode = ref("");
const sending = ref(false);
const truename = ref("");
const bank = reactive({ bank_name: "", bank_account: "", truename: "" });

function stateText(verified: boolean, status?: string) {
  if (verified) return "已认证";
  if (status === "pending") return "审核中";
  if (status === "rejected") return "未通过";
  return "未认证";
}

async function sendCode() {
  sending.value = true;
  try {
    const res = await api<any>("/member/validations/sms", { method: "POST", body: { mobile: mobile.value } });
    uni.showToast({ title: res?.message || "请输入短信验证码", icon: "none" });
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "获取失败"), icon: "none" });
  } finally {
    sending.value = false;
  }
}

async function load() {
  if (!ensureLogin()) return;
  try {
    const res = await api<any>("/member/validations");
    const v = res?.data || {};
    info.value = v;
    mobile.value = v.mobile || "";
    truename.value = v.truename || "";
    bank.bank_name = v.bank_name || "";
    bank.bank_account = v.bank_account || "";
    bank.truename = v.truename || "";
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  }
}

async function submit(type: string, body: Record<string, string>) {
  submitting.value = type;
  try {
    await api("/member/validations", { method: "POST", body: { type, ...body } });
    await load();
    await fetchMe(true);
    uni.showToast({ title: type === "mobile" ? "短信验证成功，已提交审核" : "已提交，等待审核", icon: "none" });
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "提交失败"), icon: "none" });
  } finally {
    submitting.value = "";
  }
}

onShow(load);
</script>

<template>
  <view class="page">
    <view class="stats card">
      <view class="stat"><text class="b">{{ stateText(!!info.mobile_verified, info.mobile_status) }}</text><text class="muted">手机</text></view>
      <view class="stat"><text class="b">{{ stateText(!!info.truename_verified, info.truename_status) }}</text><text class="muted">实名</text></view>
      <view class="stat"><text class="b">{{ stateText(!!info.bank_verified, info.bank_status) }}</text><text class="muted">银行</text></view>
    </view>

    <view class="card form">
      <view class="section">手机认证</view>
      <text v-if="info.mobile_status === 'rejected' && info.mobile_remark" class="rej">{{ info.mobile_remark }}</text>
      <u-input v-model="mobile" placeholder="手机号 *" border="surround" />
      <view class="gap" />
      <u-input v-model="smsCode" placeholder="短信验证码 *" border="surround" />
      <view class="gap" />
      <u-button type="primary" plain color="#2769dc" :text="sending ? '发送中…' : '获取验证码'" :loading="sending" @click="sendCode" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="提交手机认证" :loading="submitting === 'mobile'" @click="submit('mobile', { mobile, sms_code: smsCode })" />
    </view>

    <view class="card form">
      <view class="section">实名认证</view>
      <text v-if="info.truename_status === 'rejected' && info.truename_remark" class="rej">{{ info.truename_remark }}</text>
      <u-input v-model="truename" placeholder="真实姓名 *" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="提交实名认证" :loading="submitting === 'truename'" @click="submit('truename', { truename })" />
    </view>

    <view class="card form">
      <view class="section">银行认证</view>
      <text v-if="info.bank_status === 'rejected' && info.bank_remark" class="rej">{{ info.bank_remark }}</text>
      <u-input v-model="bank.bank_name" placeholder="开户行 *" border="surround" />
      <view class="gap" />
      <u-input v-model="bank.bank_account" placeholder="账号 *" border="surround" />
      <view class="gap" />
      <u-input v-model="bank.truename" placeholder="户名" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="提交银行认证" :loading="submitting === 'bank'" @click="submit('bank', { ...bank })" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.stats {
  margin: 20rpx; padding: 24rpx;
  display: flex; justify-content: space-around; text-align: center;
}
.stat { display: flex; flex-direction: column; gap: 8rpx; }
.b { font-size: 28rpx; font-weight: 700; color: #2769dc; }
.form { margin: 20rpx; padding: 24rpx; }
.gap { height: 18rpx; }
.section {
  margin: 0 0 16rpx; font-size: 28rpx; font-weight: 700;
  padding-left: 14rpx; border-left: 6rpx solid #2769dc;
}
.rej { display: block; margin-bottom: 12rpx; color: #b91c1c; font-size: 24rpx; }
</style>

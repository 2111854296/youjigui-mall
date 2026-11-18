<script setup lang="ts">
import { ref } from "vue";
import { useAuth } from "@/composables/useAuth";
import { errMsg, consumeLoginRedirect } from "@/utils/auth";
import SlideCaptchaModal from "@/components/SlideCaptchaModal/SlideCaptchaModal.vue";

const login = ref("");
const password = ref("");
const loading = ref(false);
const showCaptcha = ref(false);
const { login: doLogin } = useAuth();

function submit() {
  if (!login.value.trim() || !password.value) {
    uni.showToast({ title: "请输入账号和密码", icon: "none" });
    return;
  }
  showCaptcha.value = true;
}

async function onCaptchaVerified(captchaToken: string) {
  loading.value = true;
  try {
    await doLogin({
      login: login.value.trim(),
      password: password.value,
      captcha_token: captchaToken,
    });
    uni.showToast({ title: "登录成功", icon: "success" });
    setTimeout(() => consumeLoginRedirect("/pages/member/index"), 400);
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "登录失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
}

function goRegister() {
  uni.navigateTo({ url: "/pages/register/index" });
}
function goForgot() {
  uni.navigateTo({ url: "/pages/forgot-password/index" });
}
</script>

<template>
  <view class="page login">
    <view class="brand">有机硅商城</view>
    <view class="card form">
      <u-input v-model="login" placeholder="用户名 / 手机 / 邮箱" border="surround" clearable />
      <view class="gap" />
      <u-input
        v-model="password"
        placeholder="密码"
        border="surround"
        password
        clearable
      />
      <view class="gap" />
      <u-button
        type="primary"
        color="#2769dc"
        text="登录"
        :loading="loading"
        @click="submit"
      />
      <view class="links">
        <text @click="goRegister">注册账号</text>
        <text class="sep">|</text>
        <text @click="goForgot">忘记密码</text>
      </view>
    </view>

    <SlideCaptchaModal v-model="showCaptcha" @verified="onCaptchaVerified" />
  </view>
</template>

<style lang="scss" scoped>
.login { padding: 60rpx 32rpx; }
.brand {
  text-align: center;
  font-size: 44rpx;
  font-weight: 800;
  color: #2769dc;
  margin-bottom: 48rpx;
}
.form { padding: 36rpx 28rpx; }
.gap { height: 24rpx; }
.links {
  margin-top: 28rpx;
  text-align: center;
  color: #2769dc;
  font-size: 26rpx;
  display: flex;
  justify-content: center;
  gap: 20rpx;
  align-items: center;
}
.sep { color: #ccc; }
</style>

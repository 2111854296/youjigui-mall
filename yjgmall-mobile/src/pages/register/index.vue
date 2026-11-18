<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api, setToken } from "@/utils/request";
import { errMsg } from "@/utils/auth";

const form = reactive({
  member_type: "company",
  username: "",
  name: "",
  gender: "先生",
  email: "",
  mobile: "",
  qq: "",
  area: "",
  company_name: "",
  company_type: "企业单位",
  password: "",
  password_confirmation: "",
  invite_code: "",
});
const loading = ref(false);
const genderOpts = ["先生", "女士"];
const companyTypeOpts = ["企业单位", "事业社团", "个体经营", "其他组织"];
const memberTypeOpts = [
  { value: "company", label: "企业" },
  { value: "personal", label: "个人" },
];

onLoad((q) => {
  const code = q?.invite || q?.invite_code;
  if (code) form.invite_code = String(code);
});

watch(
  () => form.member_type,
  (t) => {
    if (t === "personal") {
      form.company_name = "";
      form.company_type = "企业单位";
    }
  },
);

function memberTypeLabel() {
  return memberTypeOpts.find((o) => o.value === form.member_type)?.label || "企业";
}

async function submit() {
  if (!form.username.trim() || form.username.trim().length < 4) {
    uni.showToast({ title: "会员名至少 4 位", icon: "none" });
    return;
  }
  if (!form.password) {
    uni.showToast({ title: "请填写密码", icon: "none" });
    return;
  }
  if (form.password !== form.password_confirmation) {
    uni.showToast({ title: "两次密码不一致", icon: "none" });
    return;
  }
  if (!form.name.trim()) {
    uni.showToast({ title: "请填写真实姓名", icon: "none" });
    return;
  }
  if (!form.email.trim()) {
    uni.showToast({ title: "请填写邮箱", icon: "none" });
    return;
  }
  if (form.member_type === "company" && !form.company_name.trim()) {
    uni.showToast({ title: "请填写公司名称", icon: "none" });
    return;
  }

  loading.value = true;
  try {
    const body: Record<string, any> = { ...form };
    if (form.member_type !== "company") {
      body.company_name = null;
      body.company_type = null;
    }
    const res = await api<{ data: any; token: string }>("/auth/register", {
      method: "POST",
      body,
      auth: false,
    });
    if (res.token) setToken(res.token);
    uni.showToast({ title: "注册成功", icon: "success" });
    setTimeout(() => uni.switchTab({ url: "/pages/member/index" }), 400);
  } catch (e: any) {
    const errors = e?.data?.errors;
    const msg = errors
      ? Object.values(errors).flat().join("；")
      : errMsg(e, "注册失败");
    uni.showToast({ title: String(msg), icon: "none", duration: 2800 });
  } finally {
    loading.value = false;
  }
}

function goLogin() {
  uni.navigateTo({ url: "/pages/login/index" });
}
</script>

<template>
  <view class="page">
    <view class="card form">
      <text class="sec">帐户信息</text>
      <picker
        :range="memberTypeOpts"
        range-key="label"
        @change="(e: any) => { form.member_type = memberTypeOpts[Number(e.detail.value)]?.value || 'company' }"
      >
        <view class="row">
          <text class="label">会员类型</text>
          <text class="val">{{ memberTypeLabel() }}</text>
          <u-icon name="arrow-right" color="#ccc" size="14" />
        </view>
      </picker>
      <view class="gap" />
      <u-input v-model="form.username" placeholder="会员名（至少4位）" border="surround" />
      <view class="gap" />
      <u-input v-model="form.password" placeholder="密码" border="surround" password />
      <view class="gap" />
      <u-input v-model="form.password_confirmation" placeholder="重复密码" border="surround" password />

      <text class="sec">联系方式</text>
      <u-input v-model="form.name" placeholder="真实姓名（必填）" border="surround" />
      <view class="gap" />
      <picker :range="genderOpts" @change="(e: any) => { form.gender = genderOpts[Number(e.detail.value)] || '先生' }">
        <view class="row">
          <text class="label">称谓</text>
          <text class="val">{{ form.gender }}</text>
          <u-icon name="arrow-right" color="#ccc" size="14" />
        </view>
      </picker>
      <view class="gap" />
      <u-input v-model="form.area" placeholder="所在地区，如：广东东莞" border="surround" />
      <view class="gap" />
      <u-input v-model="form.email" placeholder="邮箱（必填）" border="surround" />
      <view class="gap" />
      <u-input v-model="form.mobile" placeholder="手机" border="surround" />
      <view class="gap" />
      <u-input v-model="form.qq" placeholder="QQ" border="surround" />

      <template v-if="form.member_type === 'company'">
        <text class="sec">公司信息</text>
        <u-input v-model="form.company_name" placeholder="公司名称（必填）" border="surround" />
        <view class="gap" />
        <picker
          :range="companyTypeOpts"
          @change="(e: any) => { form.company_type = companyTypeOpts[Number(e.detail.value)] || '企业单位' }"
        >
          <view class="row">
            <text class="label">公司类型</text>
            <text class="val">{{ form.company_type }}</text>
            <u-icon name="arrow-right" color="#ccc" size="14" />
          </view>
        </picker>
      </template>

      <text class="sec">邀请码</text>
      <u-input v-model="form.invite_code" placeholder="好友邀请码（选填）" border="surround" />

      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="立即注册" :loading="loading" @click="submit" />
      <view class="gap" />
      <view class="login-link" @click="goLogin">
        <text class="muted">已有账号？</text>
        <text class="link">去登录</text>
      </view>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.form { margin: 20rpx; padding: 28rpx; }
.gap { height: 18rpx; }
.sec {
  display: block;
  margin: 28rpx 0 16rpx;
  font-size: 28rpx;
  font-weight: 700;
  color: #1a1a1a;
  padding-left: 14rpx;
  border-left: 6rpx solid #2769dc;
}
.sec:first-child { margin-top: 0; }
.row {
  display: flex; align-items: center; gap: 12rpx;
  padding: 20rpx 16rpx; border: 1rpx solid #dadbde; border-radius: 8rpx;
}
.label { color: #666; width: 140rpx; }
.val { flex: 1; color: #333; }
.login-link { text-align: center; font-size: 26rpx; padding: 8rpx 0; }
.link { color: #2769dc; margin-left: 8rpx; }
</style>

<script setup lang="ts">
import { reactive, ref } from "vue";
import { api, getToken } from "@/utils/request";
import { errMsg } from "@/utils/auth";

const loading = ref(false);
const form = reactive({
  title: "",
  content: "",
  contact_name: "",
  contact_phone: "",
});

async function submit() {
  if (!form.content.trim() || !form.contact_name.trim() || !form.contact_phone.trim()) {
    uni.showToast({ title: "请填写内容、联系人与电话", icon: "none" });
    return;
  }
  loading.value = true;
  try {
    await api("/inquiries", {
      method: "POST",
      body: {
        type: "guestbook",
        title: form.title || "网站留言",
        content: form.content,
        contact_name: form.contact_name,
        contact_phone: form.contact_phone,
      },
      auth: Boolean(getToken()),
    });
    uni.showToast({ title: "留言已提交", icon: "success" });
    form.title = "";
    form.content = "";
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "提交失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <view class="page">
    <view class="card form">
      <text class="sec-title">留言反馈</text>
      <u-input v-model="form.title" placeholder="主题（可选）" border="surround" />
      <view class="gap" />
      <u-textarea v-model="form.content" placeholder="内容 *" height="160" border="surround" />
      <view class="gap" />
      <u-input v-model="form.contact_name" placeholder="联系人 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.contact_phone" placeholder="电话 *" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="提交留言" :loading="loading" @click="submit" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.form { margin: 20rpx; padding: 28rpx 24rpx; }
.sec-title { font-size: 32rpx; font-weight: 700; display: block; margin-bottom: 24rpx; }
.gap { height: 20rpx; }
</style>

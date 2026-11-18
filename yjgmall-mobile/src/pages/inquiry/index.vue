<script setup lang="ts">
import { reactive, ref } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { useAuth } from "@/composables/useAuth";
import { errMsg } from "@/utils/auth";

const { fetchMe, user, token } = useAuth();
const loading = ref(false);
const type = ref<"supply" | "purchase" | "product" | "company">("supply");
const id = ref("");
const form = reactive({
  contact_name: "",
  contact_phone: "",
  contact_email: "",
  title: "",
  content: "",
});

onLoad(async (q) => {
  type.value = (q?.type as any) || "supply";
  id.value = String(q?.id || "");
  const title = q?.title ? decodeURIComponent(String(q.title)) : "";
  form.title = title ? `询价：${title}` : "产品询价";
  if (token()) {
    const u = await fetchMe();
    if (u) {
      form.contact_name = u.name || u.username || "";
      form.contact_phone = u.mobile || "";
      form.contact_email = u.email || "";
    }
  }
});

async function submit() {
  if (!form.contact_name || !form.contact_phone || !form.content) {
    uni.showToast({ title: "请填写联系人、手机和内容", icon: "none" });
    return;
  }
  if (!id.value) {
    uni.showToast({ title: "缺少目标信息", icon: "none" });
    return;
  }
  loading.value = true;
  try {
    await api("/inquiries", {
      method: "POST",
      body: {
        type: type.value,
        id: Number(id.value),
        ...form,
      },
      auth: !!token(),
    });
    uni.showToast({ title: "已提交，平台审核后转达", icon: "success" });
    setTimeout(() => uni.navigateBack(), 500);
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "发送失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <view class="page">
    <view class="card form">
      <u-input v-model="form.contact_name" placeholder="联系人" border="surround" />
      <view class="gap" />
      <u-input v-model="form.contact_phone" placeholder="手机" border="surround" />
      <view class="gap" />
      <u-input v-model="form.contact_email" placeholder="邮箱（选填）" border="surround" />
      <view class="gap" />
      <u-input v-model="form.title" placeholder="标题" border="surround" />
      <view class="gap" />
      <u-textarea v-model="form.content" placeholder="询盘内容" height="160" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="提交询盘" :loading="loading" @click="submit" />
      <view v-if="!user()" class="tip muted">未登录也可提交；登录后可在「询盘管理」查看往来。</view>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.form { margin: 20rpx; padding: 28rpx; }
.gap { height: 20rpx; }
.tip { margin-top: 20rpx; font-size: 22rpx; line-height: 1.5; }
</style>

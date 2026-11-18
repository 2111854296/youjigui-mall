<script setup lang="ts">
import { reactive, ref } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import { contentNodes } from "@/utils/content";

const id = ref("");
const item = ref<any>(null);
const loading = ref(true);
const submitting = ref(false);
const form = reactive({
  receiver_name: "",
  receiver_phone: "",
  receiver_address: "",
});

async function load() {
  if (!id.value) return;
  loading.value = true;
  try {
    const res = await api<any>(`/gift/${id.value}`);
    item.value = res?.data || res;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
  }
}

async function redeem() {
  if (!ensureLogin()) return;
  if (!form.receiver_name.trim() || !form.receiver_phone.trim() || !form.receiver_address.trim()) {
    uni.showToast({ title: "请填写完整收货信息", icon: "none" });
    return;
  }
  submitting.value = true;
  try {
    await api(`/gift/${id.value}/redeem`, { method: "POST", body: { ...form } });
    uni.showToast({ title: "兑换成功", icon: "success" });
    await load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "兑换失败"), icon: "none" });
  } finally {
    submitting.value = false;
  }
}

onLoad((q) => {
  id.value = String(q?.id || "");
  load();
});
</script>

<template>
  <view class="page">
    <view v-if="loading" class="center"><u-loading-icon color="#2769dc" /></view>
    <template v-else-if="item">
      <view class="card article">
        <text class="title">{{ item.title }}</text>
        <text class="points">{{ item.points }} 积分</text>
        <text class="meta muted">库存 {{ item.stock ?? "-" }}</text>
        <text v-if="item.summary" class="summary">{{ item.summary }}</text>
        <rich-text class="body" :nodes="contentNodes(item.content)" />
      </view>
      <view class="card form">
        <text class="sec-title">兑换收货信息</text>
        <u-input v-model="form.receiver_name" placeholder="收货人 *" border="surround" />
        <view class="gap" />
        <u-input v-model="form.receiver_phone" placeholder="电话 *" border="surround" />
        <view class="gap" />
        <u-input v-model="form.receiver_address" placeholder="地址 *" border="surround" />
        <view class="gap" />
        <u-button type="primary" color="#2769dc" text="立即兑换" :loading="submitting" @click="redeem" />
      </view>
    </template>
  </view>
</template>

<style lang="scss" scoped>
.center { padding: 80rpx; text-align: center; }
.article, .form { padding: 28rpx 24rpx; margin-bottom: 16rpx; }
.title { font-size: 36rpx; font-weight: 700; display: block; margin-bottom: 12rpx; }
.points { display: block; font-size: 34rpx; font-weight: 800; color: #c2410c; margin-bottom: 8rpx; }
.meta { font-size: 22rpx; display: block; margin-bottom: 12rpx; }
.summary { display: block; font-size: 26rpx; color: #555; margin-bottom: 12rpx; line-height: 1.5; }
.body { font-size: 28rpx; line-height: 1.7; }
.sec-title { font-size: 30rpx; font-weight: 700; display: block; margin-bottom: 20rpx; }
.gap { height: 20rpx; }
</style>

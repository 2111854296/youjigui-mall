<script setup lang="ts">
import { reactive, ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import { statusLabel } from "@/utils/status";

const items = ref<any[]>([]);
const loading = ref(false);
const saving = ref(false);
const form = reactive({ title: "", content: "", contact_name: "", contact_phone: "" });

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/asks");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function submit() {
  if (!form.title.trim() || !form.content.trim()) {
    uni.showToast({ title: "请填写标题和内容", icon: "none" });
    return;
  }
  saving.value = true;
  try {
    await api("/member/asks", { method: "POST", body: { ...form } });
    uni.showToast({ title: "已提交", icon: "success" });
    form.title = "";
    form.content = "";
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
      <view class="section">提问客服</view>
      <u-input v-model="form.title" placeholder="标题 *" border="surround" />
      <view class="gap" />
      <u-textarea v-model="form.content" placeholder="内容 *" height="140" border="surround" />
      <view class="gap" />
      <u-input v-model="form.contact_name" placeholder="联系人" border="surround" />
      <view class="gap" />
      <u-input v-model="form.contact_phone" placeholder="电话" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="提交" :loading="saving" @click="submit" />
    </view>

    <view v-for="a in items" :key="a.id" class="row card">
      <view class="body">
        <text class="title">{{ a.title }}</text>
        <text class="meta muted">{{ statusLabel(a.status) }}</text>
        <text v-if="a.reply" class="reply">回复：{{ a.reply }}</text>
        <text v-else class="meta muted">暂无回复</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无工单</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.form { margin: 20rpx; padding: 24rpx; }
.gap { height: 18rpx; }
.section {
  margin: 0 0 16rpx; font-size: 28rpx; font-weight: 700;
  padding-left: 14rpx; border-left: 6rpx solid #2769dc;
}
.row { padding: 22rpx 20rpx; }
.body { flex: 1; min-width: 0; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 8rpx; }
.meta { font-size: 22rpx; display: block; }
.reply { font-size: 24rpx; color: #2769dc; display: block; margin-top: 8rpx; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>

<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const shop = ref<any>({});
const loading = ref(false);
const saving = ref(false);
const form = reactive({ title: "", slug: "", content: "" });
const pages = computed(() => shop.value.pages || []);

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/shop");
    shop.value = res?.data || {};
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function add() {
  if (!form.title.trim()) {
    uni.showToast({ title: "请填写标题", icon: "none" });
    return;
  }
  saving.value = true;
  try {
    await api("/member/shop/pages", { method: "POST", body: { ...form } });
    Object.assign(form, { title: "", slug: "", content: "" });
    uni.showToast({ title: "已添加", icon: "success" });
    await load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    saving.value = false;
  }
}

async function remove(id: number) {
  try {
    await api(`/member/shop/pages/${id}`, { method: "DELETE" });
    load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  }
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view v-for="p in pages" :key="p.id" class="row card">
      <view class="body">
        <text class="title">{{ p.title }}</text>
        <text class="meta muted">{{ p.slug || "无标识" }}</text>
      </view>
      <text class="danger" @click="remove(p.id)">删除</text>
    </view>
    <view class="footer-tip muted" v-if="loading || !pages.length">
      <text v-if="loading">加载中...</text>
      <text v-else>暂无单页</text>
    </view>
    <view class="card form">
      <view class="section">添加单页</view>
      <u-input v-model="form.title" placeholder="标题 *" border="surround" />
      <view class="gap" />
      <u-input v-model="form.slug" placeholder="标识（可选）" border="surround" />
      <view class="gap" />
      <u-textarea v-model="form.content" placeholder="内容" height="140" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="添加单页" :loading="saving" @click="add" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.row { display: flex; padding: 22rpx 20rpx; align-items: center; gap: 12rpx; }
.body { flex: 1; min-width: 0; }
.title { font-size: 28rpx; font-weight: 600; display: block; }
.meta { font-size: 22rpx; }
.danger { color: #e11d48; font-size: 24rpx; }
.footer-tip { text-align: center; padding: 24rpx; }
.form { margin: 16rpx 20rpx 32rpx; padding: 28rpx; }
.section {
  font-size: 28rpx; font-weight: 700; padding-left: 14rpx;
  border-left: 6rpx solid #2769dc; margin-bottom: 16rpx;
}
.gap { height: 18rpx; }
</style>

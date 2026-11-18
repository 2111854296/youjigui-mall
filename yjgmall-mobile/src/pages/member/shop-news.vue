<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const shop = ref<any>({});
const loading = ref(false);
const saving = ref(false);
const form = reactive({ title: "", content: "" });
const news = computed(() => shop.value.news || []);

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
    await api("/member/shop/news", { method: "POST", body: { ...form } });
    form.title = "";
    form.content = "";
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
    await api(`/member/shop/news/${id}`, { method: "DELETE" });
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
    <view v-for="n in news" :key="n.id" class="row card">
      <text class="title">{{ n.title }}</text>
      <text class="danger" @click="remove(n.id)">删除</text>
    </view>
    <view class="footer-tip muted" v-if="loading || !news.length">
      <text v-if="loading">加载中...</text>
      <text v-else>暂无新闻</text>
    </view>
    <view class="card form">
      <view class="section">添加新闻</view>
      <u-input v-model="form.title" placeholder="标题 *" border="surround" />
      <view class="gap" />
      <u-textarea v-model="form.content" placeholder="内容" height="140" border="surround" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="添加新闻" :loading="saving" @click="add" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.row { display: flex; padding: 22rpx 20rpx; align-items: center; gap: 12rpx; }
.title { flex: 1; font-size: 28rpx; font-weight: 600; }
.danger { color: #e11d48; font-size: 24rpx; }
.footer-tip { text-align: center; padding: 24rpx; }
.form { margin: 16rpx 20rpx 32rpx; padding: 28rpx; }
.section {
  font-size: 28rpx; font-weight: 700; padding-left: 14rpx;
  border-left: 6rpx solid #2769dc; margin-bottom: 16rpx;
}
.gap { height: 18rpx; }
</style>

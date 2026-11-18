<script setup lang="ts">
import { ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import { useAuth } from "@/composables/useAuth";

const { fetchMe } = useAuth();
const myId = ref<number | string>("");
const items = ref<any[]>([]);
const loading = ref(false);
const adding = ref(false);
const friendId = ref("");

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const me = await fetchMe();
    myId.value = me?.id || "";
  } catch { /* ignore */ }
  try {
    const res = await api<any>("/member/friends");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function add() {
  if (!friendId.value.trim()) {
    uni.showToast({ title: "请填写对方用户ID", icon: "none" });
    return;
  }
  adding.value = true;
  try {
    await api("/member/friends", { method: "POST", body: { friend_id: Number(friendId.value) } });
    uni.showToast({ title: "已添加", icon: "success" });
    friendId.value = "";
    await load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "添加失败"), icon: "none" });
  } finally {
    adding.value = false;
  }
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view class="card form">
      <view class="section">添加商友</view>
      <text v-if="myId" class="mine muted">我的用户ID：{{ myId }}</text>
      <u-input v-model="friendId" placeholder="对方用户ID *" border="surround" type="number" />
      <view class="gap" />
      <u-button type="primary" color="#2769dc" text="添加好友" :loading="adding" @click="add" />
    </view>

    <view v-for="item in items" :key="item.id" class="row card">
      <view class="body">
        <text class="title">{{ item.name || item.username || `会员${item.id}` }}</text>
        <text class="meta muted">ID {{ item.id }} · {{ item.username || "—" }}</text>
        <text class="meta muted">{{ item.company_name || "暂无公司" }}</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无好友</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.form { margin: 20rpx; padding: 24rpx; }
.mine { display: block; margin: -6rpx 0 16rpx; font-size: 24rpx; }
.gap { height: 18rpx; }
.section {
  margin: 0 0 16rpx; font-size: 28rpx; font-weight: 700;
  padding-left: 14rpx; border-left: 6rpx solid #2769dc;
}
.row { display: flex; gap: 12rpx; padding: 22rpx 20rpx; align-items: center; }
.body { flex: 1; min-width: 0; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 8rpx; }
.meta { font-size: 22rpx; display: block; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>

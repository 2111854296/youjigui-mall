<script setup lang="ts">
import { ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import { statusLabel } from "@/utils/status";

const items = ref<any[]>([]);
const loading = ref(false);
const busyId = ref(0);

function openLabel(open: any) {
  const v = String(open ?? "public").toLowerCase();
  return v === "private" || v === "0" ? "未公开" : "已公开";
}

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/resumes");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function create() {
  uni.navigateTo({ url: "/pages/member/resume-edit" });
}

function edit(id: number) {
  uni.navigateTo({ url: `/pages/member/resume-edit?id=${id}` });
}

function goApps() {
  uni.navigateTo({ url: "/pages/member/job-applications" });
}

function goReceived() {
  uni.navigateTo({ url: "/pages/member/job-received" });
}

async function setDefault(id: number) {
  busyId.value = id;
  try {
    await api(`/member/resumes/${id}/default`, { method: "POST" });
    uni.showToast({ title: "已设为默认", icon: "success" });
    load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "设置失败"), icon: "none" });
  } finally {
    busyId.value = 0;
  }
}

async function remove(id: number) {
  uni.showModal({
    title: "确认删除该简历？",
    success: async (r) => {
      if (!r.confirm) return;
      try {
        await api(`/member/resumes/${id}`, { method: "DELETE" });
        uni.showToast({ title: "已删除", icon: "success" });
        load();
      } catch (e: any) {
        uni.showToast({ title: errMsg(e), icon: "none" });
      }
    },
  });
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view class="top">
      <view class="links">
        <text @click="goApps">我投递的</text>
        <text @click="goReceived">收到的投递</text>
      </view>
      <u-button type="primary" color="#2769dc" size="small" text="新建简历" @click="create" />
    </view>
    <view class="tip muted">简历需平台审核通过后才能投递；公开简历审核通过后进入人才库。</view>
    <view v-for="item in items" :key="item.id" class="row card">
      <view class="body">
        <text class="title">
          {{ item.title }}
          <text v-if="Number(item.is_default)" class="def">默认</text>
        </text>
        <text class="meta muted">
          {{ item.truename || "—" }} · {{ item.area || "—" }} · {{ openLabel(item.open) }}
        </text>
        <text class="meta muted">审核：{{ statusLabel(item.status) }}</text>
      </view>
      <view class="acts">
        <text @click="edit(item.id)">编辑</text>
        <text v-if="!Number(item.is_default)" @click="busyId !== item.id && setDefault(item.id)">设默认</text>
        <text class="danger" @click="remove(item.id)">删除</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无简历，请新建并提交审核</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.top {
  padding: 16rpx 20rpx; display: flex; align-items: center; justify-content: space-between; gap: 12rpx;
}
.links { display: flex; gap: 20rpx; font-size: 24rpx; color: #2769dc; }
.tip { padding: 0 20rpx 12rpx; font-size: 22rpx; line-height: 1.5; }
.row { display: flex; gap: 12rpx; padding: 22rpx 20rpx; align-items: center; }
.body { flex: 1; min-width: 0; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 8rpx; }
.def {
  display: inline-block; margin-left: 8rpx; padding: 2rpx 10rpx; border-radius: 6rpx;
  background: #eef4ff; color: #2769dc; font-size: 20rpx; font-weight: 600;
}
.meta { font-size: 22rpx; display: block; }
.acts { display: flex; flex-direction: column; gap: 12rpx; font-size: 24rpx; color: #2769dc; }
.danger { color: #e11d48; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>

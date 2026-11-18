<script setup lang="ts">
import { ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureCertifiedEnterprise, ensureLogin, errMsg } from "@/utils/auth";
import { statusLabel } from "@/utils/status";

const items = ref<any[]>([]);
const loading = ref(false);

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/jobs");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function create() {
  if (!(await ensureCertifiedEnterprise())) return;
  uni.navigateTo({ url: "/pages/member/job-edit" });
}

function goReceived() {
  uni.navigateTo({ url: "/pages/member/job-received" });
}

function edit(id: number) {
  uni.navigateTo({ url: `/pages/member/job-edit?id=${id}` });
}

async function remove(id: number) {
  uni.showModal({
    title: "确认删除该招聘？",
    success: async (r) => {
      if (!r.confirm) return;
      try {
        await api(`/member/jobs/${id}`, { method: "DELETE" });
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
      <text class="link" @click="goReceived">收到的投递</text>
      <u-button type="primary" color="#2769dc" size="small" text="发布招聘" @click="create" />
    </view>
    <view v-for="item in items" :key="item.id" class="row card">
      <view class="body">
        <text class="title">{{ item.title }}</text>
        <text class="meta muted">
          {{ item.area || "—" }} · {{ item.salary || "面议" }} · {{ statusLabel(item.status) }}
        </text>
        <text class="meta muted">浏览 {{ item.views || 0 }}</text>
      </view>
      <view class="acts">
        <text @click="edit(item.id)">编辑</text>
        <text class="danger" @click="remove(item.id)">删除</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无招聘，点击右上角发布</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.top { padding: 16rpx 20rpx; display: flex; justify-content: flex-end; align-items: center; gap: 20rpx; }
.link { font-size: 24rpx; color: #2769dc; }
.row { display: flex; gap: 12rpx; padding: 22rpx 20rpx; align-items: center; }
.body { flex: 1; min-width: 0; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 8rpx; }
.meta { font-size: 22rpx; display: block; }
.acts { display: flex; flex-direction: column; gap: 12rpx; font-size: 24rpx; color: #2769dc; }
.danger { color: #e11d48; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>

<script setup lang="ts">
import { ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const loading = ref(false);
const statusMap: Record<string, string> = {
  pending: "待处理",
  viewed: "已查看",
  accepted: "已通过",
  rejected: "未通过",
};

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/job-applications");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function goResumes() {
  uni.navigateTo({ url: "/pages/member/resumes" });
}

function goJob(id: number) {
  if (!id) return;
  uni.navigateTo({ url: `/pages/job/detail?id=${id}` });
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view class="top">
      <text class="link" @click="goResumes">简历管理</text>
    </view>
    <view v-for="item in items" :key="item.id" class="row card" @click="goJob(item.job_post_id)">
      <view class="body">
        <text class="title">{{ item.job?.title || ("职位 #" + item.job_post_id) }}</text>
        <text class="meta muted">{{ item.company_name || "—" }} · 简历 {{ item.resume?.title || item.resume_id }}</text>
        <text class="meta muted">
          {{ statusMap[item.status] || item.status }} · {{ String(item.created_at || "").slice(0, 16) }}
        </text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无投递记录</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.top { padding: 16rpx 20rpx; display: flex; justify-content: flex-end; }
.link { font-size: 24rpx; color: #2769dc; }
.row { padding: 22rpx 20rpx; }
.body { min-width: 0; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 8rpx; }
.meta { font-size: 22rpx; display: block; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>

<script setup lang="ts">
import { ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const loading = ref(false);
const busyId = ref(0);
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
    const res = await api<any>("/member/job-received-applications");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function markSeen(item: any) {
  if (Number(item.is_read) || item.status !== "pending") return;
  try {
    await api(`/member/job-applications/${item.id}/read`, { method: "POST" });
    item.is_read = 1;
  } catch {
    /* ignore */
  }
}

async function setStatus(id: number, status: string) {
  busyId.value = id;
  try {
    await api(`/member/job-applications/${id}/status`, { method: "POST", body: { status } });
    uni.showToast({ title: "已更新", icon: "success" });
    load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "操作失败"), icon: "none" });
  } finally {
    busyId.value = 0;
  }
}

async function unlock(item: any) {
  const resumeId = Number(item.resume?.id || item.resume_id || 0);
  if (!resumeId) {
    uni.showToast({ title: "无简历信息", icon: "none" });
    return;
  }
  busyId.value = item.id;
  try {
    await markSeen(item);
    const res = await api<any>("/vip/unlock-contact", {
      method: "POST",
      body: { target_type: "resume", target_id: resumeId },
    });
    const contact = res?.data?.contact || {};
    if (item.resume) {
      item.resume.mobile = contact.mobile || contact.phone || item.resume.mobile;
      item.resume.email = contact.email || item.resume.email;
      item.resume.contact_unlocked = true;
    }
    uni.showToast({
      title: contact.mobile ? `电话 ${contact.mobile}` : "已解锁",
      icon: "none",
    });
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "解锁失败"), icon: "none" });
  } finally {
    busyId.value = 0;
  }
}

function goJobs() {
  uni.navigateTo({ url: "/pages/member/jobs" });
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view class="top">
      <text class="link" @click="goJobs">招聘管理</text>
    </view>
    <view v-for="item in items" :key="item.id" class="card block">
      <text class="title">{{ item.job?.title || ("职位 #" + item.job_post_id) }}</text>
      <text class="meta muted">
        求职者：{{ item.resume?.truename || item.applicant?.name || item.applicant?.username || item.applicant_user_id }}
      </text>
      <text class="meta muted">简历：{{ item.resume?.title || item.resume_id }} · {{ statusMap[item.status] || item.status }}</text>
      <text class="meta muted">
        联系：
        <template v-if="item.resume?.contact_unlocked">
          {{ item.resume?.mobile || "—" }} {{ item.resume?.email || "" }}
        </template>
        <template v-else>{{ item.resume?.mobile || "已隐藏" }}</template>
      </text>
      <view class="acts">
        <text v-if="!item.resume?.contact_unlocked" @click="unlock(item)">解锁联系</text>
        <text :class="{ disabled: busyId === item.id }" @click="setStatus(item.id, 'accepted')">通过</text>
        <text :class="{ disabled: busyId === item.id }" @click="setStatus(item.id, 'rejected')">拒绝</text>
        <text :class="{ disabled: busyId === item.id }" @click="setStatus(item.id, 'pending')">待处理</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无收到的投递</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.top { padding: 16rpx 20rpx; display: flex; justify-content: flex-end; }
.link { font-size: 24rpx; color: #2769dc; }
.block { margin: 0 20rpx 16rpx; padding: 22rpx 20rpx; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 8rpx; }
.meta { font-size: 22rpx; display: block; margin-bottom: 6rpx; }
.acts {
  display: flex; flex-wrap: wrap; gap: 20rpx; margin-top: 14rpx;
  font-size: 24rpx; color: #2769dc;
}
.disabled { opacity: 0.5; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>

<script setup lang="ts">
import { ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const loading = ref(false);
const active = ref<number | null>(null);
const replyText = ref("");
const replyLoading = ref(false);

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/messages", { query: { box: "inbox" } });
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function canReplyInquiry(item: any) {
  const title = String(item?.title || "");
  return title.includes("收到询盘") || (String(item?.action_to || "").includes("inquiries") && title.includes("询盘"));
}

async function open(item: any) {
  active.value = active.value === item.id ? null : item.id;
  replyText.value = "";
  if (!item.is_read) {
    try {
      await api(`/member/messages/${item.id}/read`, { method: "POST" });
      item.is_read = true;
    } catch { /* ignore */ }
  }
}

async function submitReply(item: any) {
  const content = replyText.value.trim();
  if (!content) {
    uni.showToast({ title: "请填写回复", icon: "none" });
    return;
  }
  replyLoading.value = true;
  try {
    const title = String(item.title || "收到询盘");
    const to = item.from?.username || item.from?.id || "";
    await api("/member/messages", {
      method: "POST",
      body: {
        to: String(to),
        title: title.startsWith("回复：") ? title : `回复：${title}`,
        content,
      },
    });
    uni.showToast({ title: "已提交审核", icon: "success" });
    replyText.value = "";
    active.value = null;
    load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "提交失败"), icon: "none" });
  } finally {
    replyLoading.value = false;
  }
}

function goInquiries() {
  uni.navigateTo({ url: "/pages/member/inquiries" });
}

onShow(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view class="card tip">
      <text>会员自由互发已关闭。询盘通知可以回复，提交后需平台审核才会转达对方。</text>
    </view>

    <view v-for="item in items" :key="item.id" class="card block">
      <view class="row" @click="open(item)">
        <view class="dot" :class="{ on: !item.is_read }" />
        <view class="body">
          <text class="title">{{ item.title || "系统通知" }}</text>
          <text class="meta muted">{{ item.from?.name || item.from?.username || "平台通知" }} · {{ item.created_at?.slice?.(0, 16) || "" }}</text>
        </view>
      </view>
      <view v-if="active === item.id" class="detail">
        <text>{{ item.content || item.body || item.message || "无正文" }}</text>
        <view class="acts">
          <u-button size="small" plain text="询盘管理" @click="goInquiries" />
        </view>
        <view v-if="canReplyInquiry(item)" class="reply">
          <u-textarea v-model="replyText" placeholder="回复此询盘（提交后需审核）" height="100" border="surround" />
          <view class="acts">
            <u-button size="small" type="primary" color="#2769dc" :loading="replyLoading" text="提交回复" @click="submitReply(item)" />
          </view>
        </view>
      </view>
      <view v-else class="preview muted">{{ (item.content || item.body || "").slice(0, 80) }}</view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无通知</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.tip {
  margin: 20rpx;
  padding: 24rpx;
  font-size: 24rpx;
  line-height: 1.6;
  color: #52667d;
  background: #f5f8ff;
}
.block { padding: 0; margin: 0 20rpx 16rpx; }
.row { display: flex; align-items: center; gap: 16rpx; padding: 22rpx 20rpx; }
.dot {
  width: 14rpx; height: 14rpx; border-radius: 50%; background: transparent; flex-shrink: 0;
}
.dot.on { background: #2769dc; }
.body { flex: 1; min-width: 0; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 6rpx; }
.meta { font-size: 22rpx; display: block; }
.detail {
  padding: 0 20rpx 22rpx 50rpx; font-size: 26rpx; line-height: 1.6; color: #333;
  border-top: 1rpx solid #f1f4f7; white-space: pre-wrap;
}
.reply { margin-top: 16rpx; }
.acts { margin-top: 16rpx; display: flex; gap: 12rpx; }
.preview {
  padding: 0 20rpx 18rpx 50rpx; font-size: 22rpx;
}
.footer-tip { text-align: center; padding: 32rpx; }
</style>

<script setup lang="ts">
import { ref } from "vue";
import { onShow, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { ensureLogin, errMsg } from "@/utils/auth";
import { statusLabel } from "@/utils/status";
const items = ref<any[]>([]);
const loading = ref(false);
const active = ref<number | null>(null);
const replyText = ref("");

async function load() {
  if (!ensureLogin()) return;
  loading.value = true;
  try {
    const res = await api<any>("/member/inquiries");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function open(item: any) {
  active.value = active.value === item.id ? null : item.id;
  replyText.value = "";
}

async function reply(item: any) {
  if (!replyText.value.trim()) return;
  try {
    await api(`/member/inquiries/${item.id}/reply`, {
      method: "POST",
      body: { reply: replyText.value },
    });
    uni.showToast({ title: "已提交审核", icon: "success" });
    replyText.value = "";
    load();
  } catch (e: any) {
    uni.showToast({ title: errMsg(e), icon: "none" });
  }
}

async function closeIt(item: any) {
  try {
    await api(`/member/inquiries/${item.id}/status`, {
      method: "POST",
      body: { status: "closed" },
    });
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
    <view v-for="item in items" :key="item.id" class="card block">
      <view class="row" @click="open(item)">
        <view class="body">
          <text class="dir">{{ item.direction === "inbox" ? "收到" : "发出" }} · {{ statusLabel(item.status) }}</text>
          <text class="title">{{ item.title }}</text>
          <text class="meta muted">{{ item.contact_name }} {{ item.contact_phone }} · {{ item.created_at?.slice?.(0, 16) }}</text>
        </view>
        <u-icon :name="active === item.id ? 'arrow-up' : 'arrow-down'" color="#ccc" size="14" />
      </view>
      <view v-if="active === item.id" class="detail">
        <text class="content">{{ item.content }}</text>
        <text v-if="item.reply" class="reply muted">回复：{{ item.reply }}{{ item.status === 'reply_pending' ? '（审核中）' : '' }}</text>
        <template v-if="item.direction === 'inbox' && item.status !== 'closed' && item.status !== 'done'">
          <u-textarea v-model="replyText" placeholder="输入回复（提交后需平台审核）" height="100" border="surround" />
          <view class="acts">
            <u-button size="small" type="primary" color="#2769dc" :text="item.status === 'reply_pending' ? '重新提交' : '提交回复'" @click="reply(item)" />
            <u-button size="small" plain text="关闭" @click="closeIt(item)" />
          </view>
        </template>
        <view v-else class="acts">
          <u-button v-if="item.status !== 'closed'" size="small" plain text="关闭询盘" @click="closeIt(item)" />
        </view>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无询盘</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.block { padding: 0; overflow: hidden; }
.row { display: flex; align-items: center; padding: 22rpx 20rpx; gap: 12rpx; }
.body { flex: 1; min-width: 0; }
.dir { font-size: 22rpx; color: #2769dc; display: block; margin-bottom: 6rpx; }
.title { font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 6rpx; }
.meta { font-size: 22rpx; }
.detail { padding: 0 20rpx 20rpx; border-top: 1rpx solid #f1f4f7; }
.content { display: block; padding: 16rpx 0; font-size: 26rpx; line-height: 1.5; }
.reply { display: block; margin-bottom: 12rpx; font-size: 24rpx; }
.acts { display: flex; gap: 16rpx; margin-top: 12rpx; }
.footer-tip { text-align: center; padding: 32rpx; }
</style>

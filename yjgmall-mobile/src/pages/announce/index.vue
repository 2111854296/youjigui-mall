<script setup lang="ts">
import { ref } from "vue";
import { onLoad, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { errMsg } from "@/utils/auth";
import { contentNodes } from "@/utils/content";

const items = ref<any[]>([]);
const active = ref<any>(null);
const loading = ref(false);

async function load() {
  loading.value = true;
  try {
    const res = await api<any>("/announcements");
    items.value = res?.data || [];
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function openLink() {
  if (!active.value?.link) return;
  // #ifdef H5
  window.open(active.value.link, "_blank");
  // #endif
  // #ifndef H5
  uni.setClipboardData({ data: active.value.link });
  // #endif
}

function goHelp() {
  uni.navigateTo({ url: "/pages/cms/page?slug=help" });
}
function goGuestbook() {
  uni.navigateTo({ url: "/pages/guestbook/index" });
}

onLoad(load);
onPullDownRefresh(load);
</script>

<template>
  <view class="page">
    <view v-if="active" class="card article">
      <text class="back" @click="active = null">« 返回列表</text>
      <text class="title">{{ active.title }}</text>
      <text class="meta muted">{{ String(active.created_at || "").slice(0, 16) }}</text>
      <rich-text class="body" :nodes="contentNodes(active.content)" />
      <text v-if="active.link" class="link" @click="openLink">相关链接 ›</text>
    </view>
    <template v-else>
      <view
        v-for="(a, i) in items"
        :key="a.id || i"
        class="row card"
        @click="active = a"
      >
        <text class="title">{{ a.title }}</text>
        <text class="meta muted">{{ String(a.created_at || "").slice(0, 10) }}</text>
      </view>
      <view class="footer-tip muted">
        <text v-if="loading">加载中...</text>
        <text v-else-if="!items.length">暂无公告</text>
      </view>
      <view class="help card">
        <text class="muted" @click="goHelp">帮助中心</text>
        <text class="sep">·</text>
        <text class="muted" @click="goGuestbook">网站留言</text>
      </view>
    </template>
  </view>
</template>

<style lang="scss" scoped>
.row { padding: 24rpx 20rpx; display: flex; justify-content: space-between; gap: 16rpx; }
.article { padding: 28rpx 24rpx; }
.back { display: block; color: #2769dc; font-size: 26rpx; margin-bottom: 16rpx; }
.title { font-size: 30rpx; font-weight: 600; flex: 1; }
.article .title { font-size: 36rpx; font-weight: 700; display: block; margin-bottom: 12rpx; }
.meta { font-size: 22rpx; flex-shrink: 0; }
.body { font-size: 28rpx; line-height: 1.7; margin-top: 16rpx; }
.link { display: block; margin-top: 20rpx; color: #2769dc; font-size: 26rpx; }
.footer-tip { text-align: center; padding: 24rpx; font-size: 24rpx; }
.help {
  margin: 16rpx 20rpx; padding: 20rpx; text-align: center; font-size: 24rpx;
  display: flex; justify-content: center; gap: 12rpx;
}
.sep { color: #ccc; }
</style>

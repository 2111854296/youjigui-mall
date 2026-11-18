<script setup lang="ts">
import { computed, ref } from "vue";
import { onLoad, onReachBottom, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { errMsg } from "@/utils/auth";

const BOARDS = [
  { id: "review", label: "有机硅资讯网评" },
  { id: "capacity", label: "产能产量" },
  { id: "trade", label: "进出口" },
  { id: "report", label: "研究报告" },
  { id: "company", label: "企业动态" },
  { id: "talk", label: "对话大咖" },
  { id: "event", label: "企业活动" },
  { id: "product", label: "新品推荐" },
  { id: "tech", label: "技术分享" },
  { id: "expo", label: "展会动态" },
];

const section = ref("");
const boards = ref<{ id: string; label: string; items: any[] }[]>([]);
const items = ref<any[]>([]);
const page = ref(1);
const totalPages = ref(1);
const loading = ref(false);
const finished = computed(() => page.value >= totalPages.value);
const sectionLabel = computed(() => BOARDS.find((b) => b.id === section.value)?.label || "资讯列表");

function rowTime(item: any) {
  return String(item.published_at || item.addtime || item.created_at || "").slice(0, 16);
}

async function loadBoards() {
  loading.value = true;
  try {
    const rows = await Promise.all(
      BOARDS.map(async (b) => {
        const res = await api<any>("/news", { query: { section: b.id, per_page: 4, page: 1 } });
        return { ...b, items: res?.data || [] };
      }),
    );
    boards.value = rows;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

async function fetchList(reset = false) {
  if (loading.value) return;
  if (reset) {
    page.value = 1;
    items.value = [];
  }
  loading.value = true;
  try {
    const res = await api<any>("/news", {
      query: { page: page.value, per_page: 20, section: section.value },
    });
    const list = res?.data || [];
    items.value = reset ? list : items.value.concat(list);
    totalPages.value = Number(res?.meta?.last_page || 1);
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function goDetail(id: number | string) {
  uni.navigateTo({ url: `/pages/news/detail?id=${id}` });
}
function goSection(id: string) {
  uni.navigateTo({ url: `/pages/news/index?section=${id}` });
}
function goPortal() {
  if (!section.value) return;
  uni.redirectTo({ url: "/pages/news/index" });
}
function goRank() {
  uni.navigateTo({ url: "/pages/news/rank" });
}
function goTopics() {
  uni.navigateTo({ url: "/pages/news/topics" });
}

onLoad((q) => {
  section.value = String(q?.section || "");
  uni.setNavigationBarTitle({ title: section.value ? sectionLabel.value : "信息资讯" });
  if (section.value) fetchList(true);
  else loadBoards();
});
onReachBottom(() => {
  if (!section.value || finished.value || loading.value) return;
  page.value += 1;
  fetchList();
});
onPullDownRefresh(() => {
  if (section.value) fetchList(true);
  else loadBoards();
});
</script>

<template>
  <view class="page">
    <view class="tabs card">
      <text class="tab" :class="{ on: !section }" @click="goPortal">资讯板块</text>
      <text class="tab" @click="goRank">排行</text>
      <text class="tab" @click="goTopics">专题</text>
    </view>

    <template v-if="!section">
      <view v-for="b in boards" :key="b.id" class="board card">
        <view class="board-hd">
          <text class="board-title">{{ b.label }}</text>
          <text class="more" @click="goSection(b.id)">更多</text>
        </view>
        <view
          v-for="item in b.items"
          :key="item.id"
          class="row"
          @click="goDetail(item.id)"
        >
          <text class="title">{{ item.title }}</text>
          <text class="meta muted">{{ rowTime(item) }}</text>
        </view>
        <text v-if="!b.items.length" class="empty muted">暂无相关资讯</text>
      </view>
      <view v-if="loading" class="footer-tip muted">加载中...</view>
    </template>

    <template v-else>
      <view class="board card">
        <view class="board-hd">
          <text class="board-title">{{ sectionLabel }}</text>
        </view>
        <view
          v-for="item in items"
          :key="item.id"
          class="row"
          @click="goDetail(item.id)"
        >
          <text class="title">{{ item.title }}</text>
          <text class="meta muted">{{ rowTime(item) }}</text>
        </view>
        <text v-if="!loading && !items.length" class="empty muted">暂无相关资讯</text>
      </view>
      <view class="footer-tip muted">
        <text v-if="loading">加载中...</text>
        <text v-else-if="finished && items.length">没有更多了</text>
      </view>
    </template>
  </view>
</template>

<style lang="scss" scoped>
.tabs {
  display: flex; margin: 16rpx 20rpx 8rpx; padding: 8rpx;
}
.tab {
  flex: 1; text-align: center; font-size: 26rpx; color: #666;
  padding: 14rpx 0; border-radius: 8rpx;
}
.tab.on { background: #eef4ff; color: #2769dc; font-weight: 700; }
.board { margin: 16rpx 20rpx; padding: 8rpx 20rpx 12rpx; }
.board-hd {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16rpx 0 8rpx;
}
.board-title { font-size: 30rpx; font-weight: 700; color: #1f2937; }
.more { font-size: 24rpx; color: #2769dc; }
.row { padding: 18rpx 0; border-top: 1rpx solid #f1f5f9; }
.title { font-size: 28rpx; font-weight: 600; display: block; line-height: 1.45; color: #1f2937; }
.meta { font-size: 22rpx; display: block; margin-top: 6rpx; }
.empty { display: block; padding: 12rpx 0 16rpx; font-size: 24rpx; }
.footer-tip { text-align: center; padding: 24rpx; }
</style>

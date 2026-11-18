<script setup lang="ts">
import { ref } from "vue";
import { onLoad, onReachBottom, onPullDownRefresh } from "@dcloudio/uni-app";
import { api, mediaUrl } from "@/utils/request";
import { errMsg } from "@/utils/auth";

const keyword = ref("");
const type = ref("sell");
const items = ref<any[]>([]);
const page = ref(1);
const hasMore = ref(true);
const loading = ref(false);

const tabs = [
  { key: "sell", label: "供应" },
  { key: "buy", label: "采购" },
  { key: "mall", label: "商城" },
  { key: "company", label: "企业" },
  { key: "news", label: "资讯" },
];

async function load(reset = false) {
  if (loading.value) return;
  if (reset) {
    page.value = 1;
    hasMore.value = true;
    items.value = [];
  }
  if (!hasMore.value && !reset) return;
  loading.value = true;
  try {
    const res = await api<any>("/search", {
      query: { type: type.value, q: keyword.value.trim() || undefined, page: page.value },
      auth: false,
    });
    const list = res?.data || [];
    items.value = reset ? list : items.value.concat(list);
    const meta = res?.meta;
    if (meta?.last_page != null) hasMore.value = page.value < Number(meta.last_page);
    else hasMore.value = list.length >= 10;
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "搜索失败"), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function setType(key: string) {
  if (type.value === key) return;
  type.value = key;
  load(true);
}

function doSearch() {
  load(true);
}

function titleOf(item: any) {
  return item.title || item.name || "—";
}

function subOf(item: any) {
  if (type.value === "buy") {
    return `${item.quantity || "-"} ${item.unit || ""} · ${item.budget ? `预算¥${item.budget}` : "预算面议"} · ${item.area || ""}`;
  }
  if (type.value === "mall") return item.price ? `¥${item.price}` : "面议";
  if (type.value === "company") {
    return [item.area, item.business].filter(Boolean).join(" · ") || "企业资料";
  }
  if (type.value === "news") {
    return `${String(item.published_at || "").slice(0, 10)} · ${item.views || 0}阅`;
  }
  return `${item.price ? `¥${item.price}/${item.unit || ""}` : "面议"} · ${item.area || "-"} · ${item.user?.company_name || item.user?.name || ""}`;
}

function open(item: any) {
  if (type.value === "buy") uni.navigateTo({ url: `/pages/buy/detail?id=${item.id}` });
  else if (type.value === "mall") uni.navigateTo({ url: `/pages/mall/detail?id=${item.id}` });
  else if (type.value === "company") {
    uni.navigateTo({ url: `/pages/companies/shop?slug=${encodeURIComponent(item.slug || item.id)}` });
  } else if (type.value === "news") uni.navigateTo({ url: `/pages/news/detail?id=${item.id}` });
  else uni.navigateTo({ url: `/pages/sell/detail?id=${item.id}` });
}

onLoad((q: any) => {
  if (q?.q) keyword.value = String(q.q);
  if (q?.type) type.value = String(q.type);
  load(true);
});
onPullDownRefresh(() => load(true));
onReachBottom(() => {
  if (!hasMore.value || loading.value) return;
  page.value += 1;
  load(false);
});
</script>

<template>
  <view class="page">
    <view class="filter">
      <u-search
        v-model="keyword"
        placeholder="输入关键词搜索"
        action-text="搜索"
        bg-color="#fff"
        @search="doSearch"
        @custom="doSearch"
      />
    </view>
    <view class="tabs">
      <text
        v-for="t in tabs"
        :key="t.key"
        class="tab"
        :class="{ on: type === t.key }"
        @click="setType(t.key)"
      >{{ t.label }}</text>
    </view>
    <view
      v-for="(item, i) in items"
      :key="item.id || i"
      class="row card"
      @click="open(item)"
    >
      <image class="thumb" :src="mediaUrl(item.thumb || item.logo)" mode="aspectFill" />
      <view class="body">
        <text class="title">{{ titleOf(item) }}</text>
        <text class="sub muted">{{ subOf(item) }}</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">未找到相关结果，试试换个关键词或频道</text>
      <text v-else-if="!hasMore">没有更多了</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.tabs {
  display: flex; background: #fff; border-bottom: 1rpx solid #e8edf3;
  overflow-x: auto; box-shadow: 0 2rpx 8rpx rgba(31, 56, 100, 0.03);
}
.tab {
  flex: 1; text-align: center; padding: 24rpx 8rpx; font-size: 26rpx; color: #64748b;
  border-bottom: 4rpx solid transparent; white-space: nowrap;
}
.tab.on { color: #2769dc; font-weight: 700; border-bottom-color: #2769dc; }
.row { display: flex; gap: 16rpx; padding: 22rpx; align-items: center; }
.thumb {
  width: 140rpx; height: 140rpx; border-radius: 14rpx; background: #f7f9fc;
  flex-shrink: 0; border: 1rpx solid #eef2f7;
}
.body { flex: 1; min-width: 0; }
.title {
  font-size: 28rpx; font-weight: 600; display: block; color: #1f2937;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.sub { display: block; font-size: 22rpx; margin-top: 10rpx; line-height: 1.4; }
</style>

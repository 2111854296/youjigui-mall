<script setup lang="ts">
import { ref, computed } from "vue";
import { onLoad, onShow, onReachBottom, onPullDownRefresh } from "@dcloudio/uni-app";
import { api, mediaUrl } from "@/utils/request";
import { errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const categories = ref<any[]>([]);
const page = ref(1);
const totalPages = ref(1);
const loading = ref(false);
const q = ref("");
const category = ref("");
const finished = computed(() => page.value >= totalPages.value);
const floors = ref<any[]>([]);
const showHub = computed(() => !q.value && !category.value);
const searchActionStyle = {
  color: "#ffffff",
  fontWeight: "600",
  fontSize: "28rpx",
  paddingLeft: "20rpx",
  paddingRight: "4rpx",
};

async function fetchList(reset = false) {
  if (loading.value) return;
  if (reset) {
    page.value = 1;
    items.value = [];
  }
  loading.value = true;
  try {
    const res = await api<any>("/companies", {
      query: {
        page: page.value,
        q: q.value || undefined,
        category: category.value || undefined,
      },
    });
    const list = res?.data || [];
    items.value = reset ? list : items.value.concat(list);
    totalPages.value = Number(res?.meta?.last_page || 1);
    if (res?.categories?.length) categories.value = res.categories;
    if (reset && !q.value && !category.value) {
      floors.value = res?.floors || [];
    } else if (reset) {
      floors.value = [];
    }
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function setCat(id: string | number | "") {
  category.value = id === "" ? "" : String(id);
  fetchList(true);
}

function goShop(c: any) {
  uni.navigateTo({
    url: `/pages/companies/shop?slug=${encodeURIComponent(c.slug || c.id)}`,
  });
}

function onSearch() {
  fetchList(true);
}

function companyRows(list: any[]) {
  return (list || []).slice(0, 4).map((c) => ({
    id: c.slug || c.id,
    title: c.name || c.company || "",
    meta: c.area || "",
  }));
}

function openCompany(id: string | number) {
  uni.navigateTo({ url: `/pages/companies/shop?slug=${encodeURIComponent(String(id))}` });
}

function openCats() {
  uni.navigateTo({ url: "/pages/category/index?module=company&target=companies" });
}

onLoad((query) => {
  if (query?.q) q.value = String(query.q);
  if (query?.category) category.value = String(query.category);
});

onShow(() => {
  const sq = uni.getStorageSync("search_q");
  if (sq) {
    q.value = String(sq);
    uni.removeStorageSync("search_q");
  }
  const sc = uni.getStorageSync("pick_category");
  if (sc) {
    try {
      const o = typeof sc === "string" ? JSON.parse(sc) : sc;
      if (o?.target === "companies" && o?.id != null) category.value = String(o.id);
    } catch { /* ignore */ }
    uni.removeStorageSync("pick_category");
  }
  fetchList(true);
});

onReachBottom(() => {
  if (finished.value || loading.value) return;
  page.value += 1;
  fetchList();
});
onPullDownRefresh(() => fetchList(true));
</script>

<template>
  <view class="page">
    <view class="filter">
      <u-search
        v-model="q"
        placeholder="搜索企业"
        :show-action="true"
        action-text="搜索"
        shape="round"
        height="68rpx"
        bg-color="#ffffff"
        search-icon-color="#9ca3af"
        placeholder-color="#9ca3af"
        :action-style="searchActionStyle"
        @search="onSearch"
        @custom="onSearch"
      />
    </view>
    <view class="chips">
      <scroll-view scroll-x class="chips-x">
        <text class="chip" :class="{ on: !category }" @click="setCat('')">全部</text>
        <text
          v-for="c in categories.slice(0, 16)"
          :key="c.id"
          class="chip"
          :class="{ on: category === String(c.id) }"
          @click="setCat(c.id)"
        >{{ c.name }}</text>
        <text class="chip more" @click="openCats">全部分类 ›</text>
      </scroll-view>
    </view>
    <template v-if="showHub">
      <ChannelBoard
        v-for="f in floors"
        :key="f.category?.id || f.floor_no"
        :title="f.category?.name || '分类'"
        :rows="companyRows(f.companies)"
        @more="setCat(f.category?.id)"
        @open="openCompany"
      />
    </template>
    <view
      v-for="(c, i) in items"
      :key="c.id || i"
      class="row card"
      @click="goShop(c)"
    >
      <image class="logo" :src="mediaUrl(c.logo || c.thumb)" mode="aspectFit" />
      <view class="body">
        <text class="title">{{ c.company || c.name || c.title }}</text>
        <text class="meta muted">{{ c.area || c.address || "" }}</text>
        <text class="meta muted">{{ c.mode || c.business || "" }}</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无企业</text>
      <text v-else-if="finished">没有更多了</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.row { display: flex; gap: 20rpx; padding: 22rpx; }
.logo {
  width: 120rpx; height: 120rpx; flex-shrink: 0;
  background: #f7f9fc; border-radius: 14rpx; border: 1rpx solid #eef2f7;
}
.body { flex: 1; min-width: 0; }
.title {
  font-size: 30rpx; font-weight: 700; display: block; margin-bottom: 10rpx; color: #1f2937;
  overflow: hidden; white-space: nowrap; text-overflow: ellipsis;
}
.meta {
  font-size: 22rpx; display: block; line-height: 1.5;
  overflow: hidden; white-space: nowrap; text-overflow: ellipsis;
}
</style>

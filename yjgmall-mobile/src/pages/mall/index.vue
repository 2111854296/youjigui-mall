<script setup lang="ts">
import { ref, computed } from "vue";
import { onLoad, onReachBottom } from "@dcloudio/uni-app";
import { api, mediaUrl } from "@/utils/request";

const items = ref<any[]>([]);
const page = ref(1);
const totalPages = ref(1);
const loading = ref(false);
const q = ref("");
const category = ref("");
const floors = ref<any[]>([]);
const hot = ref<any[]>([]);
const brands = ref<any[]>([]);
const showHub = computed(() => !q.value && !category.value);
const finished = computed(() => page.value >= totalPages.value);

async function fetchList(reset = false) {
  if (loading.value) return;
  if (reset) {
    page.value = 1;
    items.value = [];
  }
  loading.value = true;
  try {
    const res = await api<any>("/mall", {
      query: { page: page.value, q: q.value || undefined, category: category.value || undefined },
    });
    const list = res?.data || [];
    items.value = reset ? list : items.value.concat(list);
    totalPages.value = Number(res?.meta?.last_page || 1);
    if (reset && !q.value && !category.value) {
      floors.value = res?.floors || [];
      hot.value = res?.hot || [];
      brands.value = res?.brands || [];
    } else if (reset) {
      floors.value = [];
      hot.value = [];
      brands.value = [];
    }
  } finally {
    loading.value = false;
  }
}

onLoad((query) => {
  if (query?.q) q.value = String(query.q);
  fetchList(true);
});
onReachBottom(() => {
  if (finished.value || loading.value) return;
  page.value += 1;
  fetchList();
});

function onSearch() {
  category.value = "";
  fetchList(true);
}
function setCat(id: string | number) {
  category.value = String(id || "");
  q.value = "";
  fetchList(true);
}
function goDetail(id: number | string) {
  uni.navigateTo({ url: `/pages/mall/detail?id=${id}` });
}
function searchBrand(name: string) {
  q.value = name;
  category.value = "";
  fetchList(true);
}
function rowsOf(list: any[], price = true) {
  return (list || []).slice(0, 4).map((it) => ({
    id: it.id,
    title: it.title || it.name || "",
    meta: price && it.price ? `¥${it.price}` : "",
  }));
}
</script>

<template>
  <view class="page">
    <view class="filter">
      <u-search
        v-model="q"
        placeholder="搜索商品"
        :show-action="true"
        action-text="搜索"
        bg-color="#fff"
        @search="onSearch"
        @custom="onSearch"
      />
    </view>
    <template v-if="showHub">
      <ChannelBoard
        v-if="hot.length"
        title="热销推荐"
        hide-more
        :rows="rowsOf(hot)"
        @open="goDetail"
      />
      <ChannelBoard
        v-if="brands.length"
        title="知名品牌"
        hide-more
        :rows="brands.slice(0, 8).map((b) => ({ id: b.id || b.name, title: b.name }))"
        @open="(id) => searchBrand(String(brands.find((b) => (b.id || b.name) === id)?.name || id))"
      />
      <ChannelBoard
        v-for="f in floors"
        :key="f.category?.id || f.floor_no"
        :title="f.category?.name || '分类'"
        :rows="rowsOf(f.products)"
        @more="setCat(f.category?.id)"
        @open="goDetail"
      />
    </template>
    <view class="grid">
      <view
        v-for="(item, i) in items"
        :key="item.id || i"
        class="item"
        @click="goDetail(item.id)"
      >
        <image class="pic" :src="mediaUrl(item.thumb)" mode="aspectFit" />
        <text class="title">{{ item.title }}</text>
        <text class="price">{{ item.price ? `¥${item.price}` : "面议" }}</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无商品</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.grid { display: flex; flex-wrap: wrap; padding: 12rpx; }
.item {
  width: 50%; padding: 12rpx; box-sizing: border-box;
}
.pic {
  width: 100%; height: 240rpx; background: #fff; border-radius: 16rpx; margin-bottom: 12rpx;
  box-shadow: 0 4rpx 16rpx rgba(31, 56, 100, 0.06); overflow: hidden;
  border: 1rpx solid #eef2f7;
}
.title {
  font-size: 26rpx; height: 72rpx; overflow: hidden; color: #1f2937; font-weight: 500;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
}
</style>

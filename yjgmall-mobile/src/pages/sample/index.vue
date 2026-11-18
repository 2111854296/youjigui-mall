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
    const res = await api<any>("/samples", {
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
function floorRows(floor: any) {
  const ads = Array.isArray(floor?.ads) ? floor.ads : [];
  const slots: Array<any | null> = [null, null, null, null];
  for (const ad of ads) {
    const slot = Number(ad?.slot);
    if (slot < 1 || slot > 4 || slots[slot - 1] || !ad?.image) continue;
    slots[slot - 1] = {
      id: `ad-${ad.id}`,
      title: ad.title || "推广",
      meta: "推广",
    };
  }
  let i = 0;
  const products = floor?.products || [];
  const rows: any[] = [];
  for (let n = 0; n < 4; n++) {
    if (slots[n]) {
      rows.push(slots[n]);
      continue;
    }
    const p = products[i++];
    if (!p) continue;
    rows.push({
      id: p.id,
      title: p.title || p.name || "",
      meta: p.price ? `¥${p.price}` : "",
    });
  }
  return rows;
}
function openFloor(floor: any, id: string | number) {
  const ad = (floor?.ads || []).find((a: any) => `ad-${a.id}` === String(id));
  if (!ad) {
    goDetail(id);
    return;
  }
  const link = String(ad.link || "");
  const mall = link.match(/\/mall\/(\d+)/);
  if (mall) {
    goDetail(mall[1]);
    return;
  }
  if (link.startsWith("http") && typeof window !== "undefined") {
    window.location.href = link;
  }
}
</script>

<template>
  <view class="page">
    <view class="filter">
      <u-search
        v-model="q"
        placeholder="搜索样品"
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
        title="样品推荐"
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
        :title="`${f.category?.name || '分类'}（样品）`"
        :rows="floorRows(f)"
        @more="setCat(f.category?.id)"
        @open="(id) => openFloor(f, id)"
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
      <text v-else-if="!items.length">暂无样品</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.grid { display: flex; flex-wrap: wrap; padding: 12rpx; }
.item { width: 50%; padding: 12rpx; box-sizing: border-box; }
.pic {
  width: 100%; height: 240rpx; background: #fff; border-radius: 16rpx; margin-bottom: 12rpx;
  box-shadow: 0 4rpx 16rpx rgba(31, 56, 100, 0.06); overflow: hidden;
  border: 1rpx solid #eef2f7;
}
.title {
  font-size: 26rpx; height: 72rpx; overflow: hidden; color: #1f2937; font-weight: 500;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
}
.price { display: block; margin-top: 6rpx; color: #e11d48; font-weight: 700; }
</style>

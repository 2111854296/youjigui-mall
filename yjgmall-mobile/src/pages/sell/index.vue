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
const sort = ref("refresh");
const selected = ref<(number | string)[]>([]);
const eliteOnly = ref(false);
const floors = ref<any[]>([]);
const elite = ref<any[]>([]);
const showHub = computed(() => !q.value && !category.value && !eliteOnly.value);

const sortOpts = [
  { v: "refresh", label: "默认" },
  { v: "hits", label: "人气" },
  { v: "price_asc", label: "低价" },
  { v: "price_desc", label: "高价" },
];

async function fetchList(reset = false) {
  if (loading.value) return;
  if (reset) {
    page.value = 1;
    items.value = [];
  }
  loading.value = true;
  try {
    const res = await api<any>("/sell", {
      query: {
        page: page.value,
        q: q.value || undefined,
        category: category.value || undefined,
        sort: sort.value,
        elite: eliteOnly.value ? 1 : undefined,
      },
    });
    const list = res?.data || [];
    items.value = reset ? list : items.value.concat(list);
    totalPages.value = Number(res?.meta?.last_page || res?.meta?.pages || 1);
    if (res?.categories?.length) categories.value = res.categories;
    if (reset && !q.value && !category.value && !eliteOnly.value) {
      floors.value = res?.floors || [];
      elite.value = res?.elite || [];
    } else if (reset) {
      floors.value = [];
      elite.value = [];
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
  eliteOnly.value = false;
  fetchList(true);
}

function showElite() {
  eliteOnly.value = true;
  category.value = "";
  q.value = "";
  fetchList(true);
}

function setSort(v: string) {
  sort.value = v;
  fetchList(true);
}

function goDetail(id: number | string) {
  uni.navigateTo({ url: `/pages/sell/detail?id=${id}` });
}

function toggleSelect(id: number | string) {
  const i = selected.value.indexOf(id);
  if (i >= 0) selected.value.splice(i, 1);
  else if (selected.value.length < 5) selected.value.push(id);
  else uni.showToast({ title: "最多对比5条", icon: "none" });
}

function goCompare() {
  if (selected.value.length < 2) {
    uni.showToast({ title: "请至少选择2条供应", icon: "none" });
    return;
  }
  uni.navigateTo({
    url: `/pages/sell/compare?ids=${selected.value.join(",")}`,
  });
}

function onSearch() {
  eliteOnly.value = false;
  fetchList(true);
}

function floorRows(list: any[]) {
  return (list || []).slice(0, 4).map((it) => ({
    id: it.id,
    title: it.title || it.name || "",
    meta: it.price ? `¥${it.price}${it.unit ? "/" + it.unit : ""}` : (it.area || ""),
  }));
}

function openCats() {
  uni.navigateTo({ url: "/pages/category/index?module=sell&target=sell" });
}

onLoad((query) => {
  if (query?.q) q.value = String(query.q);
  if (query?.category) category.value = String(query.category);
  if (query?.ids) {
    selected.value = String(query.ids).split(",").filter(Boolean);
  }
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
      if (o?.target === "sell" && o?.id != null) category.value = String(o.id);
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
        placeholder="搜索供应信息"
        :show-action="true"
        action-text="搜索"
        bg-color="#fff"
        @search="onSearch"
        @custom="onSearch"
      />
    </view>

    <view class="chips">
      <scroll-view scroll-x class="chips-x">
        <text class="chip" :class="{ on: !category }" @click="setCat('')">全部</text>
        <text
          v-for="c in categories.slice(0, 20)"
          :key="c.id"
          class="chip"
          :class="{ on: category === String(c.id) }"
          @click="setCat(c.id)"
        >{{ c.name }}</text>
        <text class="chip more" @click="openCats">全部分类 ›</text>
      </scroll-view>
    </view>

    <view class="sort-bar">
      <text
        v-for="s in sortOpts"
        :key="s.v"
        class="sort"
        :class="{ on: sort === s.v }"
        @click="setSort(s.v)"
      >{{ s.label }}</text>
      <text class="sort compare" :class="{ on: selected.length >= 2 }" @click="goCompare">
        对比{{ selected.length ? `(${selected.length})` : "" }}
      </text>
    </view>

    <template v-if="showHub">
      <ChannelBoard
        v-if="elite.length"
        title="推荐供应"
        :rows="floorRows(elite)"
        @more="showElite"
        @open="goDetail"
      />
      <ChannelBoard
        v-for="f in floors"
        :key="f.category?.id || f.floor_no"
        :title="f.category?.name || '分类'"
        :rows="floorRows(f.items)"
        @more="setCat(f.category?.id)"
        @open="goDetail"
      />
    </template>

    <view
      v-for="(item, i) in items"
      :key="item.id || i"
      class="row card"
      @click="goDetail(item.id)"
    >
      <view
        class="check"
        :class="{ on: selected.includes(item.id) }"
        @click.stop="toggleSelect(item.id)"
      >{{ selected.includes(item.id) ? "✓" : "" }}</view>
      <view class="pic">
        <image class="thumb-contain" :src="mediaUrl(item.thumb)" mode="aspectFit" />
      </view>
      <view class="body">
        <text class="title">{{ item.title }}</text>
        <text class="price">{{ item.price ? `¥${item.price}` : "面议" }}</text>
        <text class="meta muted">{{ item.company || item.username || "" }} · {{ item.area || "" }}</text>
      </view>
    </view>

    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无数据</text>
      <text v-else-if="finished">没有更多了</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.sort-bar {
  display: flex; gap: 8rpx; padding: 14rpx 20rpx; background: #fff;
  margin-bottom: 8rpx; box-shadow: 0 2rpx 8rpx rgba(31, 56, 100, 0.03);
}
.sort { flex: 1; text-align: center; font-size: 24rpx; color: #64748b; padding: 12rpx 0; border-radius: 8rpx; }
.sort.on { color: #2769dc; font-weight: 700; background: #eef4ff; }
.sort.compare { flex: 1.2; }
.row { display: flex; gap: 16rpx; padding: 22rpx; align-items: flex-start; }
.check {
  width: 40rpx; height: 40rpx; border: 2rpx solid #cbd5e1; border-radius: 10rpx;
  flex-shrink: 0; margin-top: 70rpx; display: flex; align-items: center; justify-content: center;
  font-size: 22rpx; color: #fff;
}
.check.on { background: #2769dc; border-color: #2769dc; }
.pic {
  width: 180rpx; height: 180rpx; flex-shrink: 0;
  background: #f7f9fc; border-radius: 14rpx; overflow: hidden; border: 1rpx solid #eef2f7;
}
.body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 10rpx; }
.title {
  font-size: 28rpx; font-weight: 600; line-height: 1.4; color: #1f2937;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.meta { font-size: 22rpx; }
</style>

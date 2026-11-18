<script setup lang="ts">
import { ref, computed } from "vue";
import { onLoad, onShow, onReachBottom, onPullDownRefresh } from "@dcloudio/uni-app";
import { api } from "@/utils/request";
import { errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const categories = ref<any[]>([]);
const page = ref(1);
const totalPages = ref(1);
const loading = ref(false);
const q = ref("");
const category = ref("");
const urgent = ref(false);
const finished = computed(() => page.value >= totalPages.value);
const floors = ref<any[]>([]);
const showHub = computed(() => !q.value && !category.value && !urgent.value);

async function fetchList(reset = false) {
  if (loading.value) return;
  if (reset) {
    page.value = 1;
    items.value = [];
  }
  loading.value = true;
  try {
    const res = await api<any>("/buy", {
      query: {
        page: page.value,
        q: q.value || undefined,
        category: category.value || undefined,
        urgent: urgent.value ? 1 : undefined,
      },
    });
    const list = res?.data || [];
    items.value = reset ? list : items.value.concat(list);
    totalPages.value = Number(res?.meta?.last_page || 1);
    if (res?.categories?.length) categories.value = res.categories;
    if (reset && !q.value && !category.value && !urgent.value) {
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

function goDetail(id: number | string) {
  uni.navigateTo({ url: `/pages/buy/detail?id=${id}` });
}

function onSearch() {
  fetchList(true);
}

function floorRows(list: any[]) {
  return (list || []).slice(0, 4).map((it) => ({
    id: it.id,
    title: it.title || "",
    meta: it.budget ? `预算 ¥${it.budget}` : (it.area || ""),
  }));
}

function toggleUrgent() {
  urgent.value = !urgent.value;
  fetchList(true);
}

function openCats() {
  uni.navigateTo({ url: "/pages/category/index?module=buy&target=buy" });
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
      if (o?.target === "buy" && o?.id != null) category.value = String(o.id);
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
        placeholder="搜索采购信息"
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
        <text class="chip" :class="{ on: urgent }" @click="toggleUrgent">紧急</text>
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
      <view class="tag" v-if="item.urgent || item.is_urgent">急</view>
      <view class="body">
        <text class="title">{{ item.title }}</text>
        <text class="meta muted">
          {{ item.area || "地区不限" }}
          <text v-if="item.budget"> · 预算¥{{ item.budget }}</text>
          <text v-else> · 预算面议</text>
        </text>
      </view>
      <u-icon name="arrow-right" color="#ccc" size="14" />
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无数据</text>
      <text v-else-if="finished">没有更多了</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.row {
  display: flex; align-items: center; gap: 16rpx; padding: 26rpx 22rpx;
}
.tag {
  width: 40rpx; height: 40rpx; border-radius: 10rpx;
  background: linear-gradient(135deg, #f87171, #e11d48); color: #fff; font-size: 22rpx;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  font-weight: 700;
}
.body { flex: 1; min-width: 0; }
.title {
  font-size: 28rpx; font-weight: 600; display: block; margin-bottom: 8rpx; color: #1f2937;
  overflow: hidden; white-space: nowrap; text-overflow: ellipsis;
}
.meta { font-size: 22rpx; }
</style>

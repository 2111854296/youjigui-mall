<script setup lang="ts">
import { ref, computed } from "vue";
import { onLoad, onReachBottom, onPullDownRefresh } from "@dcloudio/uni-app";
import { api, mediaUrl } from "@/utils/request";
import { errMsg } from "@/utils/auth";

const slug = ref("");
const topic = ref<any>(null);
const items = ref<any[]>([]);
const page = ref(1);
const totalPages = ref(1);
const loading = ref(false);
const finished = computed(() => page.value >= totalPages.value);

async function fetchList(reset = false) {
  if (!slug.value || loading.value) return;
  if (reset) {
    page.value = 1;
    items.value = [];
  }
  loading.value = true;
  try {
    const res = await api<any>(`/news/topics/${slug.value}`, { query: { page: page.value } });
    topic.value = res?.topic || res?.data?.topic || topic.value;
    const list = res?.data || [];
    // if data is topic object with nested articles
    const arts = Array.isArray(list) ? list : res?.articles || [];
    items.value = reset ? arts : items.value.concat(arts);
    if (!Array.isArray(list) && res?.data && !arts.length) {
      topic.value = res.data;
    }
    totalPages.value = Number(res?.meta?.last_page || res?.meta?.pages || 1);
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

onLoad((q) => {
  slug.value = String(q?.slug || q?.id || "");
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
    <view v-if="topic" class="card head">
      <text class="title">{{ topic.name }}</text>
      <text v-if="topic.summary" class="summary muted">{{ topic.summary }}</text>
    </view>
    <view
      v-for="(item, i) in items"
      :key="item.id || i"
      class="row card"
      @click="goDetail(item.id)"
    >
      <image class="thumb" :src="mediaUrl(item.thumb)" mode="aspectFill" />
      <view class="info">
        <text class="ttl">{{ item.title }}</text>
        <text class="meta muted">{{ String(item.published_at || item.created_at || "").slice(0, 10) }}</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无文章</text>
      <text v-else-if="finished">没有更多了</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.head { padding: 24rpx 20rpx; margin-bottom: 8rpx; }
.title { font-size: 34rpx; font-weight: 700; display: block; }
.summary { display: block; margin-top: 10rpx; font-size: 24rpx; }
.row { display: flex; gap: 16rpx; padding: 16rpx 20rpx; }
.thumb { width: 160rpx; height: 120rpx; border-radius: 8rpx; background: #f7f8fa; flex-shrink: 0; }
.info { flex: 1; min-width: 0; }
.ttl {
  font-size: 28rpx; font-weight: 600; display: -webkit-box;
  -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
.meta { display: block; margin-top: 8rpx; font-size: 22rpx; }
.footer-tip { text-align: center; padding: 24rpx; font-size: 24rpx; }
</style>

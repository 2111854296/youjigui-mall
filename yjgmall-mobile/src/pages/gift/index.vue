<script setup lang="ts">
import { ref, computed } from "vue";
import { onLoad, onReachBottom, onPullDownRefresh } from "@dcloudio/uni-app";
import { api, mediaUrl } from "@/utils/request";
import { errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const page = ref(1);
const totalPages = ref(1);
const loading = ref(false);
const finished = computed(() => page.value >= totalPages.value);

async function fetchList(reset = false) {
  if (loading.value) return;
  if (reset) {
    page.value = 1;
    items.value = [];
  }
  loading.value = true;
  try {
    const res = await api<any>("/gift", { query: { page: page.value } });
    const list = res?.data || [];
    items.value = reset ? list : items.value.concat(list);
    totalPages.value = Number(res?.meta?.last_page || res?.meta?.pages || 1);
  } catch (e: any) {
    uni.showToast({ title: errMsg(e, "加载失败"), icon: "none" });
  } finally {
    loading.value = false;
    uni.stopPullDownRefresh();
  }
}

function goDetail(id: number | string) {
  uni.navigateTo({ url: `/pages/gift/detail?id=${id}` });
}

onLoad(() => fetchList(true));
onReachBottom(() => {
  if (finished.value || loading.value) return;
  page.value += 1;
  fetchList();
});
onPullDownRefresh(() => fetchList(true));
</script>

<template>
  <view class="page">
    <view class="grid">
      <view
        v-for="(item, i) in items"
        :key="item.id || i"
        class="cell card"
        @click="goDetail(item.id)"
      >
        <image
          v-if="item.thumb || item.image"
          class="thumb"
          :src="mediaUrl(item.thumb || item.image)"
          mode="aspectFill"
        />
        <text class="title">{{ item.title }}</text>
        <text class="points">{{ item.points }} 积分</text>
        <text class="meta muted">库存 {{ item.stock ?? "-" }}</text>
      </view>
    </view>
    <view class="footer-tip muted">
      <text v-if="loading">加载中...</text>
      <text v-else-if="!items.length">暂无礼品</text>
      <text v-else-if="finished">没有更多了</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.grid { display: flex; flex-wrap: wrap; padding: 8rpx 12rpx; }
.cell { width: calc(50% - 16rpx); margin: 8rpx; padding: 16rpx; box-sizing: border-box; }
.thumb { width: 100%; height: 180rpx; border-radius: 8rpx; background: #f7f8fa; margin-bottom: 10rpx; }
.title {
  font-size: 26rpx; font-weight: 600; display: -webkit-box;
  -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; height: 72rpx;
}
.points { display: block; margin-top: 8rpx; color: #c2410c; font-weight: 700; font-size: 26rpx; }
.meta { display: block; margin-top: 4rpx; font-size: 22rpx; }
.footer-tip { text-align: center; padding: 24rpx; font-size: 24rpx; width: 100%; }
</style>

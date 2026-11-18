<script setup lang="ts">
import { ref } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api } from "@/utils/request";

type CatNode = { id: number | string; name: string; children?: CatNode[] };

const target = ref("sell");
const cats = ref<CatNode[]>([]);
const activeRoot = ref<CatNode | null>(null);

onLoad(async (q) => {
  const moduleName = String(q?.module || "sell");
  target.value = String(q?.target || moduleName);
  try {
    const res = await api<any>("/categories", { query: { module: moduleName } });
    cats.value = res?.data || [];
    activeRoot.value = cats.value[0] || null;
  } catch {
    cats.value = [];
  }
});

function pick(id: number | string | undefined, name?: string) {
  if (id == null) return;
  uni.setStorageSync(
    "pick_category",
    JSON.stringify({ target: target.value, id, name }),
  );
  if (target.value === "sell") uni.switchTab({ url: "/pages/sell/index" });
  else if (target.value === "buy") uni.switchTab({ url: "/pages/buy/index" });
  else if (target.value === "companies") uni.switchTab({ url: "/pages/companies/index" });
  else uni.navigateBack();
}
</script>

<template>
  <view class="page cat">
    <view class="left">
      <text
        v-for="c in cats"
        :key="c.id"
        class="root"
        :class="{ on: activeRoot?.id === c.id }"
        @click="activeRoot = c"
      >{{ c.name }}</text>
    </view>
    <view class="right">
      <text class="all" @click="pick(activeRoot?.id, activeRoot?.name)">全部{{ activeRoot?.name || "" }}</text>
      <view class="grid">
        <text
          v-for="ch in (activeRoot?.children || [])"
          :key="ch.id"
          class="child"
          @click="pick(ch.id, ch.name)"
        >{{ ch.name }}</text>
        <text v-if="!(activeRoot?.children || []).length" class="muted empty">暂无子分类，点上方查看全部</text>
      </view>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.cat { display: flex; min-height: 100vh; background: #fff; }
.left { width: 220rpx; background: #f3f5f9; flex-shrink: 0; }
.root {
  display: block; padding: 28rpx 16rpx; font-size: 26rpx; color: #333;
  border-left: 6rpx solid transparent;
}
.root.on {
  background: #fff; color: #2769dc; font-weight: 700;
  border-left-color: #2769dc;
}
.right { flex: 1; padding: 20rpx; }
.all {
  display: block; color: #2769dc; font-size: 28rpx; font-weight: 600;
  margin-bottom: 20rpx;
}
.grid { display: flex; flex-wrap: wrap; gap: 16rpx; }
.child {
  width: calc(50% - 8rpx); box-sizing: border-box;
  background: #f7f8fa; padding: 20rpx 12rpx; border-radius: 8rpx;
  font-size: 24rpx; text-align: center;
}
.empty { padding: 40rpx 0; width: 100%; text-align: center; }
</style>

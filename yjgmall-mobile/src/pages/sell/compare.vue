<script setup lang="ts">
import { ref } from "vue";
import { onLoad } from "@dcloudio/uni-app";
import { api, mediaUrl } from "@/utils/request";
import { errMsg } from "@/utils/auth";

const items = ref<any[]>([]);
const loading = ref(true);
const errorTip = ref("");

onLoad(async (q) => {
  const ids = String(q?.ids || "");
  if (!ids) {
    errorTip.value = "请从供应列表勾选后对比";
    loading.value = false;
    return;
  }
  try {
    const res = await api<any>("/sell/compare", { query: { ids } });
    items.value = res?.data || [];
    if (!items.value.length) errorTip.value = "暂无可对比的供应";
  } catch (e: any) {
    errorTip.value = errMsg(e, "请从供应列表勾选后对比");
  } finally {
    loading.value = false;
  }
});

function goDetail(id: number | string) {
  uni.navigateTo({ url: `/pages/sell/detail?id=${id}` });
}
</script>

<template>
  <view class="page">
    <view v-if="loading" class="center"><u-loading-icon color="#2769dc" /></view>
    <view v-else-if="errorTip && !items.length" class="card tip">{{ errorTip }}</view>
    <scroll-view v-else scroll-x class="scroller">
      <view class="table">
        <view class="tr head">
          <view class="th label">项目</view>
          <view v-for="item in items" :key="item.id" class="th" @click="goDetail(item.id)">
            <image v-if="item.thumb" class="thumb" :src="mediaUrl(item.thumb)" mode="aspectFit" />
            <text class="ttl">{{ item.title }}</text>
          </view>
        </view>
        <view class="tr">
          <view class="td label">价格</view>
          <view v-for="item in items" :key="'p' + item.id" class="td">
            {{ item.price ? `¥${item.price}/${item.unit || ""}` : "面议" }}
          </view>
        </view>
        <view class="tr">
          <view class="td label">品牌</view>
          <view v-for="item in items" :key="'b' + item.id" class="td">{{ item.brand || "-" }}</view>
        </view>
        <view class="tr">
          <view class="td label">地区</view>
          <view v-for="item in items" :key="'a' + item.id" class="td">{{ item.area || "-" }}</view>
        </view>
        <view class="tr">
          <view class="td label">起订量</view>
          <view v-for="item in items" :key="'m' + item.id" class="td">{{ item.min_amount || "-" }}</view>
        </view>
        <view class="tr">
          <view class="td label">公司</view>
          <view v-for="item in items" :key="'c' + item.id" class="td">
            {{ item.user?.company_name || item.company || "-" }}
          </view>
        </view>
      </view>
    </scroll-view>
  </view>
</template>

<style lang="scss" scoped>
.center { padding: 80rpx; text-align: center; }
.tip { margin: 20rpx; padding: 28rpx; color: #c2410c; font-size: 26rpx; }
.scroller { width: 100%; white-space: nowrap; }
.table { display: inline-block; min-width: 100%; background: #fff; }
.tr { display: flex; border-bottom: 1rpx solid #f1f4f7; }
.head { background: #f7f9fc; }
.th, .td {
  width: 220rpx; flex-shrink: 0; padding: 16rpx 12rpx;
  font-size: 24rpx; white-space: normal; word-break: break-all; box-sizing: border-box;
}
.label { width: 140rpx; color: #666; font-weight: 600; background: #fafbfc; }
.thumb { width: 100%; height: 100rpx; background: #f7f8fa; margin-bottom: 8rpx; }
.ttl { color: #2769dc; font-weight: 600; font-size: 24rpx; display: block; }
</style>

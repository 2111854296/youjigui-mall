const fs = require("fs");
const path = require("path");

const map = {
  photo: "图库",
  video: "视频",
  group: "团购",
  exhibit: "展会",
  brand: "品牌馆",
  know: "知道",
  club: "商圈",
};

const pcRoot = path.resolve(__dirname, "../../yjgmall-web/pages");
const h5Root = path.resolve(__dirname, "../src/pages");

function pc(label) {
  return `<script setup lang="ts">
definePageMeta({ layout: 'channel' })
const title = '${label}'
useHead({ title })
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <span class="on">{{ title }}</span>
        </div>
      </div>
    </div>
    <div class="section">
      <div class="container">
        <div class="list-panel">
          <div class="lp-hd"><h1>{{ title }}</h1></div>
          <p class="empty">该频道暂未开放，功能已保留，后续按需开启。</p>
          <p style="text-align:center;margin-top:12px">
            <NuxtLink class="btn-soft-link" to="/">返回首页</NuxtLink>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
`;
}

function h5(label) {
  return `<script setup lang="ts">
const title = "${label}";
function goHome() {
  uni.switchTab({ url: "/pages/index/index" });
}
</script>

<template>
  <view class="page">
    <view class="card empty">
      <text class="title">{{ title }}暂未开放</text>
      <text class="tip muted">功能已保留，后续按需开启。</text>
      <u-button type="primary" color="#2769dc" text="返回首页" @click="goHome" />
    </view>
  </view>
</template>

<style lang="scss" scoped>
.empty {
  margin: 40rpx 24rpx;
  padding: 48rpx 32rpx;
  display: flex;
  flex-direction: column;
  gap: 20rpx;
  align-items: stretch;
}
.title { font-size: 32rpx; font-weight: 700; text-align: center; }
.tip { font-size: 26rpx; text-align: center; line-height: 1.5; }
</style>
`;
}

for (const [k, label] of Object.entries(map)) {
  fs.mkdirSync(path.join(pcRoot, k), { recursive: true });
  fs.writeFileSync(path.join(pcRoot, k, "index.vue"), pc(label));
  fs.writeFileSync(path.join(pcRoot, k, "[id].vue"), pc(label));
  fs.mkdirSync(path.join(h5Root, k), { recursive: true });
  fs.writeFileSync(path.join(h5Root, k, "index.vue"), h5(label));
  fs.writeFileSync(path.join(h5Root, k, "detail.vue"), h5(label));
  console.log("ok", k);
}

<script setup lang="ts">
/** 对齐 PC sitemap：主导航 + 页脚实际露出的入口 */
const links = [
  { title: "首页", path: "/pages/index/index", tab: true },
  { title: "商城", path: "/pages/mall/index" },
  { title: "企业库", path: "/pages/companies/index", tab: true },
  { title: "资讯", path: "/pages/news/index" },
  { title: "供应", path: "/pages/sell/index", tab: true },
  { title: "采购", path: "/pages/buy/index", tab: true },
  { title: "行情报价", path: "/pages/quotes/index" },
  { title: "企业招聘", path: "/pages/job/index" },
  { title: "积分换礼", path: "/pages/gift/index" },
  { title: "广告服务", path: "/pages/ads/index" },
  { title: "网站公告", path: "/pages/announce/index" },
  { title: "会员中心", path: "/pages/member/index", tab: true },
  { title: "网站留言", path: "/pages/guestbook/index" },
  { title: "客服中心", path: "/pages/ask/index" },
  { title: "忘记密码", path: "/pages/forgot-password/index" },
  { title: "VIP服务", path: "/pages/cms/page?slug=vip" },
  { title: "帮助中心", path: "/pages/cms/page?slug=help" },
  { title: "交易安全", path: "/pages/cms/page?slug=safety" },
  { title: "关于我们", path: "/pages/cms/page?slug=about" },
  { title: "联系方式", path: "/pages/cms/page?slug=contact" },
];

function go(l: { path: string; tab?: boolean }) {
  if (l.tab) uni.switchTab({ url: l.path });
  else uni.navigateTo({ url: l.path });
}
</script>

<template>
  <view class="page">
    <view class="card grid">
      <text
        v-for="(l, i) in links"
        :key="i"
        class="link"
        @click="go(l)"
      >{{ l.title }}</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.grid {
  margin: 20rpx; padding: 24rpx;
  display: grid; grid-template-columns: 1fr 1fr; gap: 20rpx 16rpx;
}
.link { color: #2769dc; font-size: 28rpx; padding: 8rpx 0; }
</style>

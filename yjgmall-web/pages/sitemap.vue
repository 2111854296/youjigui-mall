<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '网站地图' })
const links = [
  { title: '首页', to: '/' },
  { title: '商城', to: '/mall' },
  { title: '企业库', to: '/companies' },
  { title: '资讯', to: '/news' },
  { title: '供应', to: '/sell' },
  { title: '采购', to: '/buy' },
  { title: '行情报价', to: '/quotes' },
  { title: '企业招聘', to: '/job' },
  { title: '积分换礼', to: '/gift' },
  { title: '广告服务', to: '/ads' },
  { title: '网站公告', to: '/announce' },
  { title: '会员中心', to: '/member' },
  { title: '网站留言', to: '/guestbook' },
  { title: '客服中心', to: '/ask' },
  { title: '忘记密码', to: '/forgot-password' },
  { title: 'VIP服务', to: '/pages/vip' },
  { title: '帮助中心', to: '/pages/help' },
  { title: '交易安全', to: '/pages/safety' },
  { title: '关于我们', to: '/pages/about' },
]
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <span class="on">网站地图</span>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="list-panel">
          <div class="lp-hd">
            <h1>网站地图</h1>
          </div>
          <div class="sitemap-grid">
            <NuxtLink v-for="l in links" :key="l.to" :to="l.to">{{ l.title }}</NuxtLink>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.sitemap-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px 16px;
}
.sitemap-grid a {
  font-size: 14px;
  color: var(--ink);
  text-decoration: none;
  padding: 8px 0;
  border-bottom: 1px dashed var(--line);
}
.sitemap-grid a:hover { color: var(--blue); }
@media (max-width: 768px) {
  .sitemap-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

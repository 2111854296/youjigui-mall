<script setup lang="ts">
const { show } = useMobileAppModal()
</script>

<template>
  <div class="mega-strip">
    <div class="container inner">
      <div class="group">
        <b>安全交易</b>
        <NuxtLink to="/pages/safety">买家保障</NuxtLink>
        <NuxtLink to="/member/grade">商家认证</NuxtLink>
        <NuxtLink to="/pages/safety">投诉举报</NuxtLink>
      </div>
      <div class="group">
        <b>常用工具</b>
        <NuxtLink to="/member">商机助理</NuxtLink>
        <NuxtLink to="/member/favorites">商机收藏</NuxtLink>
        <NuxtLink to="/member/friends">我的商友</NuxtLink>
        <NuxtLink to="/member/spread">排名推广</NuxtLink>
        <button type="button" class="linkish" @click="show">手机企商</button>
      </div>
      <div class="group">
        <b>资讯社区</b>
        <NuxtLink to="/news">商业资讯</NuxtLink>
        <NuxtLink to="/quotes">最新报价</NuxtLink>
      </div>
      <div class="group">
        <b>商机市场</b>
        <NuxtLink to="/sell">供应</NuxtLink>
        <NuxtLink to="/buy">采购</NuxtLink>
        <NuxtLink to="/mall">商城</NuxtLink>
        <NuxtLink to="/companies">企业库</NuxtLink>
      </div>
    </div>
    <MobileAppModal />
  </div>
</template>

<style scoped>
.mega-strip {
  background: #fff;
  border-bottom: 1px solid var(--line);
  font-size: 12px;
  color: var(--muted);
}
.inner {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 28px;
  padding: 8px 0;
  align-items: center;
}
.group {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px 12px;
}
.group b {
  color: var(--ink);
  font-weight: 700;
  margin-right: 2px;
}
.group a {
  color: inherit;
  text-decoration: none;
}
.group a:hover { color: var(--blue); }
.linkish {
  border: 0;
  background: transparent;
  padding: 0;
  font: inherit;
  color: inherit;
  cursor: pointer;
}
.linkish:hover { color: var(--blue); }
</style>

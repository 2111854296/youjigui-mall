<script setup lang="ts">
const { user, logout } = useAuth()
const open = ref<string | null>(null)

async function onLogout() {
  await logout()
  await navigateTo('/')
}
</script>

<template>
  <!-- 结构对齐设计稿 .topbar；高度/字号来自 portal-design.css -->
  <header class="topbar">
    <div class="container">
      <div class="tb-l">
        <template v-if="user">
          <span>欢迎，<NuxtLink to="/member">{{ user.name }}（{{ user.username }}）</NuxtLink><VipHover><MemberCorner :user="user" /></VipHover></span>
          <NuxtLink to="/member">商务中心</NuxtLink>
          <NuxtLink to="/ads">推广中心</NuxtLink>
          <a href="#" @click.prevent="onLogout">退出登录</a>
        </template>
        <template v-else>
          <NuxtLink to="/login">请登录</NuxtLink>
          <NuxtLink to="/register">免费注册</NuxtLink>
        </template>
      </div>
      <div class="tb-r">
        <div class="dd" @mouseenter="open='biz'" @mouseleave="open=null">
          <NuxtLink class="tb-ico" :to="user ? '/member' : '/login?redirect=/member'">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/></svg>
            用户中心 ▾
          </NuxtLink>
          <div v-show="open==='biz'" class="dd-menu">
            <div class="dd-col">
              <b>采购</b>
              <NuxtLink to="/member/orders">发出的订单</NuxtLink>
              <NuxtLink to="/member/purchases/create">发布询价单</NuxtLink>
              <NuxtLink to="/member/purchases">管理询价单</NuxtLink>
            </div>
            <div class="dd-col">
              <b>销售</b>
              <NuxtLink to="/member/seller-orders">已卖出货品</NuxtLink>
              <NuxtLink to="/member/supplies/create">发布供应</NuxtLink>
              <NuxtLink to="/member/supplies">管理供应</NuxtLink>
            </div>
            <div class="dd-col">
              <b>工具</b>
              <NuxtLink to="/member/shop">管理旺铺</NuxtLink>
              <NuxtLink to="/member/favorites">商机收藏</NuxtLink>
              <NuxtLink to="/member/spread">排名推广</NuxtLink>
            </div>
          </div>
        </div>
        <VipHover>
          <NuxtLink class="vip" to="/member/grade">★ VIP会员 · 尊享推广权益</NuxtLink>
        </VipHover>
        <NuxtLink class="tb-ico" to="/ask">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 01-8.5 8.3c-1.5 0-2.9-.4-4.1-1L3 20l1.3-4.1a8 8 0 01-1.3-4.4A8.4 8.4 0 0111.5 3.2 8.4 8.4 0 0121 11.5z"/></svg>
          客服中心
        </NuxtLink>
        <NuxtLink class="tb-ico" to="/pages/help">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M9.2 9a2.8 2.8 0 015.4 1c0 1.9-2.8 2.4-2.8 4"/><path d="M12 17.2h.01"/></svg>
          帮助中心
        </NuxtLink>
        <span class="tel">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v2a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h2a2 2 0 012 1.7c.1.9.4 1.8.7 2.6a2 2 0 01-.5 2.1L7.1 9.6a16 16 0 006 6l1.2-1.2a2 2 0 012.1-.5c.8.3 1.7.6 2.6.7A2 2 0 0122 16.9z"/></svg>
          ****
        </span>
      </div>
    </div>
  </header>
</template>

<style scoped>
/* 下拉菜单（设计稿无），其余走 portal-design .topbar */
.dd { position: relative; }
.dd-menu {
  position: absolute;
  right: 0;
  top: 100%;
  background: #fff;
  border: 1px solid var(--line);
  display: flex;
  min-width: 420px;
  z-index: 80;
  box-shadow: var(--shadow-card);
  padding: 14px;
  border-radius: 10px;
}
.dd-col { flex: 1; padding: 0 12px; border-right: 1px solid #f0f0f0; }
.dd-col:last-child { border-right: 0; }
.dd-col b { display: block; margin-bottom: 8px; color: var(--ink); font-size: 13.5px; font-weight: 700; }
.dd-col a { display: block; margin: 7px 0; font-size: 12.5px; color: var(--ink-2); }
.dd-col a:hover { color: var(--blue); }
.vip {
  display: inline-flex;
  align-items: center;
}
</style>

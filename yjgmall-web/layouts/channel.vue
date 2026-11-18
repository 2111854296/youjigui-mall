<template>
  <!-- 与首页/资讯同壳：门户顶栏 + 全宽主区（页面内自管 container） -->
  <div class="home-shell">
    <AppTopbar />
    <AppHeader />
    <AppNav variant="portal" />
    <main class="home-main">
      <slot />
    </main>
    <AppFooter />
    <HomeServiceRail />
  </div>
</template>

<style scoped>
.home-shell {
  display: block;
  min-height: 100vh;
  background: var(--bg);
}
.home-main {
  display: block;
  width: 100%;
  padding-bottom: 8px;
  /* 给右侧悬浮客服栏留空，避免详情侧栏被挡住裁切 */
  padding-right: 76px;
  box-sizing: border-box;
}
@media (max-width: 900px) {
  .home-main {
    padding-right: 0;
  }
}
</style>

<template>
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
  padding-right: 76px;
  box-sizing: border-box;
}
@media (max-width: 900px) {
  .home-main {
    padding-right: 0;
  }
}
</style>

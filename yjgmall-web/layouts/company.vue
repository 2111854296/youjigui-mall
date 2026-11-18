<template>
  <div class="shell">
    <div class="bar">
      <div class="inner">
        <NuxtLink to="/">← 返回主页</NuxtLink>
        <div class="bar-right">
          <NuxtLink to="/companies">企业库</NuxtLink>
          <NuxtLink to="/member">商务中心</NuxtLink>
        </div>
      </div>
    </div>
    <main class="body">
      <slot />
    </main>
    <footer class="foot">
      <div class="inner">企业主页 · 有机硅商城</div>
    </footer>
  </div>
</template>

<style scoped>
.shell {
  min-height: 100vh;
  background: var(--bg, #f3f5f9);
  display: flex;
  flex-direction: column;
}
.bar {
  background: #fff;
  border-bottom: 1px solid #F1F4F7;
  font-size: 13px;
}
.bar .inner,
.foot .inner,
.body {
  width: min(100% - 24px, var(--max, 1200px));
  margin: 0 auto;
}
.bar .inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  padding: 10px 0;
}
.bar a { color: #666; font-weight: 600; }
.bar a:hover { color: var(--brand, #2769dc); }
.bar-right { display: flex; gap: 16px; }
.body {
  flex: 1;
  padding: 12px 0 24px;
}
.foot {
  border-top: 1px solid #F1F4F7;
  background: #fff;
  color: #999;
  font-size: 12px;
  text-align: center;
}
.foot .inner { padding: 16px 0; }
</style>

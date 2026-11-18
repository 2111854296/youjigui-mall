<script setup lang="ts">
const config = useRuntimeConfig()

const { data } = await useAsyncData('site-settings-public', () =>
  $fetch(`${config.public.apiBase}/site`),
  { default: () => ({ data: {} }) },
)

const site = computed(() => (data.value as any)?.data || {})
const siteName = computed(() => site.value.site_name || '有机硅商城')
</script>

<template>
  <!-- 暂时仅有机硅商城单 logo（联名双标暂不上） -->
  <div class="head-main">
    <div class="container">
      <div class="logo">
        <NuxtLink to="/">
          <img class="lg-yjg" src="/img/logo-yjg.png" :alt="siteName">
        </NuxtLink>
      </div>
      <AppSearchBox />
      <div class="h-actions">
        <NuxtLink class="btn btn--primary" to="/member/supplies/create">免费发布</NuxtLink>
        <!-- 对齐示范 index.html：亮蓝字 + 浅蓝底（勿用 btn--ghost，会被全局 .btn 白字盖掉） -->
        <NuxtLink class="btn btn--join" to="/register">企业入驻</NuxtLink>
      </div>
    </div>
  </div>
</template>


<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '友情链接' })
const config = useRuntimeConfig()
const { data } = await useAsyncData('links-page', () => $fetch(`${config.public.apiBase}/links`))
const items = computed(() => (data.value as any)?.data || [])
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <span class="on">友情链接</span>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="list-panel">
          <div class="lp-hd">
            <h1>友情链接</h1>
          </div>
          <div class="link-wrap">
            <a
              v-for="item in items"
              :key="item.id"
              :href="item.url"
              target="_blank"
              rel="noopener"
            >{{ item.name }}</a>
            <p v-if="!items.length" class="empty">暂无友链</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.link-wrap {
  display: flex;
  gap: 12px 20px;
  flex-wrap: wrap;
}
.link-wrap a {
  font-size: 14px;
  color: var(--ink);
  text-decoration: none;
}
.link-wrap a:hover { color: var(--blue); }
</style>

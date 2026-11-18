<script setup lang="ts">
definePageMeta({ layout: 'home' })
useHead({ title: '资讯排行' })
const config = useRuntimeConfig()
const { data } = await useAsyncData('news-rank', () => $fetch(`${config.public.apiBase}/news/rank`))
const items = computed(() => (data.value as any)?.data || [])
const fallback = '/img/logo-yjg.png'
function thumbOf(item: any) {
  const t = String(item?.thumb || '')
  if (t && !/\.svg($|\?)/i.test(t) && !/placehold/i.test(t)) return t
  return fallback
}
</script>

<template>
  <div class="news-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <NuxtLink to="/news">资讯</NuxtLink>
          <span class="on">排行</span>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="list-panel">
          <div class="lp-hd">
            <h1>点击排行榜</h1>
          </div>
          <NuxtLink
            v-for="(item, idx) in items"
            :key="item.id"
            class="lp-item"
            :to="`/news/${item.id}`"
          >
            <img :src="thumbOf(item)" :alt="item.title" loading="lazy">
            <div>
              <div class="tt">{{ Number(idx) + 1 }}. {{ item.title }}</div>
              <div class="meta">{{ item.views }} 阅</div>
            </div>
          </NuxtLink>
          <p v-if="!items.length" class="empty">暂无数据</p>
        </div>
      </div>
    </div>
  </div>
</template>

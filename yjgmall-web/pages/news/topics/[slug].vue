<script setup lang="ts">
definePageMeta({ layout: 'home' })
const route = useRoute()
const config = useRuntimeConfig()
const slug = route.params.slug as string
const { data, error } = await useAsyncData(`topic-${slug}`, () =>
  $fetch(`${config.public.apiBase}/news/topics/${slug}`),
)
const topic = computed(() => (data.value as any)?.topic)
const items = computed(() => (data.value as any)?.data || [])
const meta = computed(() => (data.value as any)?.meta || null)
useHead(() => ({ title: topic.value?.name || '专题' }))

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
          <NuxtLink to="/news/topics">专题</NuxtLink>
          <span class="on">{{ topic?.name || '详情' }}</span>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div v-if="error" class="blk empty">专题不存在</div>
        <div v-else class="list-panel">
          <div class="lp-hd">
            <h1>{{ topic?.name }}</h1>
          </div>
          <p v-if="topic?.summary" class="sum" style="margin:0 0 12px;color:var(--ink-2);font-size:13px;line-height:1.6">
            {{ topic.summary }}
          </p>
          <NuxtLink
            v-for="item in items"
            :key="item.id"
            class="lp-item"
            :to="`/news/${item.id}`"
          >
            <img :src="thumbOf(item)" :alt="item.title" loading="lazy">
            <div>
              <div class="tt">{{ item.title }}</div>
              <div class="meta">{{ item.published_at?.slice(0, 10) }}</div>
            </div>
          </NuxtLink>
          <p v-if="!items.length" class="empty">暂无文章</p>
          <AppPager :meta="meta" />
        </div>
      </div>
    </div>
  </div>
</template>

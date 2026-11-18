<script setup lang="ts">
definePageMeta({ layout: 'home' })
useHead({ title: '资讯专题' })
const config = useRuntimeConfig()
const { data } = await useAsyncData('news-topics', () => $fetch(`${config.public.apiBase}/news/topics`))
const items = computed(() => (data.value as any)?.data || [])
</script>

<template>
  <div class="news-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <NuxtLink to="/news">资讯</NuxtLink>
          <span class="on">专题</span>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="list-panel">
          <div class="lp-hd">
            <h1>专题汇总</h1>
            <span class="meta">共 {{ items.length }} 个专题</span>
          </div>
          <NuxtLink
            v-for="item in items"
            :key="item.id"
            class="lp-item"
            :to="`/news/topics/${item.slug}`"
          >
            <div>
              <div class="tt">{{ item.name }}</div>
              <div class="sum">{{ item.summary }}</div>
              <div class="meta">文章 {{ item.articles_count }}</div>
            </div>
          </NuxtLink>
          <p v-if="!items.length" class="empty">暂无专题</p>
        </div>
      </div>
    </div>
  </div>
</template>

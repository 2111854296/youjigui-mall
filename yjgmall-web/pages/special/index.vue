<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '专题' })
const config = useRuntimeConfig()
const { data } = await useAsyncData('special', () => $fetch(`${config.public.apiBase}/special`))
const items = computed(() => (data.value as any)?.data || [])
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <span class="on">专题</span>
          <NuxtLink to="/news">资讯</NuxtLink>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>专题提示：</b>
        精选行业主题内容合集，便于集中了解热点与深度解读。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="list-panel">
          <div class="lp-hd"><h1>专题频道</h1></div>
          <NuxtLink v-for="item in items" :key="item.id" class="lp-item" :to="`/special/${item.slug || item.id}`">
            <div class="body">
              <div class="tt">{{ item.name }}</div>
              <div class="sum">{{ item.summary }}</div>
            </div>
          </NuxtLink>
          <p v-if="!items.length" class="empty">暂无专题</p>
        </div>
      </div>
    </div>
  </div>
</template>

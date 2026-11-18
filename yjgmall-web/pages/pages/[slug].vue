<script setup lang="ts">
definePageMeta({ layout: 'channel' })
const route = useRoute()
const config = useRuntimeConfig()
const slug = route.params.slug as string

const { data, error } = await useAsyncData(`page-${slug}`, () =>
  $fetch(`${config.public.apiBase}/pages/${slug}`),
)

const page = computed(() => (data.value as any)?.data)
useHead(() => ({ title: page.value?.title || '单页' }))
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <span class="on">{{ page?.title || slug }}</span>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div v-if="error" class="blk empty">页面不存在</div>
        <div v-else-if="page" class="dt-card">
          <div class="dt-sum">
            <h1>{{ page.title }}</h1>
          </div>
          <div class="dt-body" v-html="page.content" />
        </div>
      </div>
    </div>
  </div>
</template>

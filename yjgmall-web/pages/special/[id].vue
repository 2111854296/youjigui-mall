<script setup lang="ts">
definePageMeta({ layout: 'channel' })
const route = useRoute()
const config = useRuntimeConfig()
const id = route.params.id as string
const { data, error } = await useAsyncData(`special-${id}`, () => $fetch(`${config.public.apiBase}/special/${id}`))
const item = computed(() => (data.value as any)?.data)
const items = computed(() => item.value?.items || [])
useHead(() => ({ title: item.value?.name || '专题详情' }))

function typeLabel(t: string) {
  return ({ article: '资讯', news: '资讯', product: '商品', mall: '商品', supply: '供应', sell: '供应', company: '企业', download: '下载' } as Record<string, string>)[t] || t
}
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <NuxtLink to="/special">专题</NuxtLink>
          <span class="on">{{ item?.name || '详情' }}</span>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>专题提示：</b>
        专题内容聚合自站内频道，点击条目可查看原文详情。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div v-if="error" class="blk empty">专题不存在</div>
        <template v-else-if="item">
          <div class="dt-card">
            <div class="dt-sum">
              <h1>{{ item.name }}</h1>
              <p v-if="item.summary" class="meta">{{ item.summary }}</p>
            </div>
            <div class="dt-body" v-html="item.content" />
          </div>

          <div v-if="items.length" class="list-panel" style="margin-top:14px">
            <div class="lp-hd"><h2>专题条目</h2></div>
            <div v-for="row in items" :key="row.id" class="lp-item">
              <div class="body">
                <div class="tt">
                  <span class="tag elite">{{ typeLabel(row.item_type) }}</span>
                  <NuxtLink v-if="row.link" :to="row.link">{{ row.title }}</NuxtLink>
                  <span v-else>{{ row.title }}</span>
                </div>
                <div v-if="row.summary" class="sum">{{ row.summary }}</div>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

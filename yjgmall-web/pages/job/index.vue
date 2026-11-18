<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '招聘' })
const route = useRoute()
const config = useRuntimeConfig()
const page = computed(() => Number(route.query.page || 1))
const { data } = await useAsyncData(() => `jobs-${page.value}`, () =>
  $fetch(`${config.public.apiBase}/jobs`, { query: { page: page.value } }), { watch: [page] })
const items = computed(() => (data.value as any)?.data || [])
const meta = computed(() => (data.value as any)?.meta || null)
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <NuxtLink to="/jobs">招聘专区</NuxtLink>
          <span class="on">职位列表</span>
          <NuxtLink to="/member/jobs">发布职位</NuxtLink>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>招聘提示：</b>
        行业岗位信息汇总，投递前请核实企业资质与岗位要求。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="list-panel">
          <div class="lp-hd">
            <h1>招聘信息</h1>
            <NuxtLink class="btn-soft-link" to="/member/jobs">发布职位</NuxtLink>
          </div>
          <NuxtLink v-for="item in items" :key="item.id" class="lp-item" :to="`/job/${item.id}`">
            <div class="body">
              <div class="tt">{{ item.title }}</div>
              <div class="sum">{{ item.company_name || '-' }} · {{ item.area || '地区不限' }}</div>
              <div class="foot">
                <span>浏览 {{ item.views || 0 }}</span>
                <span class="price ask">{{ item.salary || '薪资面议' }}</span>
              </div>
            </div>
          </NuxtLink>
          <p v-if="!items.length" class="empty">暂无招聘</p>
          <div class="pager-wrap"><AppPager :meta="meta" /></div>
        </div>
      </div>
    </div>
  </div>
</template>

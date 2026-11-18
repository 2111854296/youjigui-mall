<script setup lang="ts">
definePageMeta({ layout: 'channel' })
const route = useRoute()
const config = useRuntimeConfig()
const id = route.params.id as string
const { data, error } = await useAsyncData(`invest-${id}`, () =>
  $fetch(`${config.public.apiBase}/invest/${id}`),
)
const item = computed(() => (data.value as any)?.data)
useHead(() => ({ title: item.value?.title || '招商详情' }))
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <NuxtLink to="/invest">招商合作</NuxtLink>
          <span class="on">{{ item?.title || '详情' }}</span>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>招商提示：</b>
        项目信息仅供参考，合作细节以双方协商为准。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div v-if="error" class="blk empty">项目不存在</div>
        <div v-else-if="item" class="dt-card">
          <div class="dt-sum">
            <h1>{{ item.title }}</h1>
            <p class="meta">{{ item.area }} · {{ item.amount }} · 浏览 {{ item.views }}</p>
            <p v-if="item.summary" style="margin:0 0 12px;font-size:14px;color:var(--ink-2);line-height:1.6">{{ item.summary }}</p>
            <p v-if="item.contact_name" style="margin:0 0 12px;font-size:14px"><b>联系：</b>{{ item.contact_name }} {{ item.contact_phone }}</p>
            <div class="dt-acts">
              <NuxtLink class="btn" to="/invest">提交合作意向</NuxtLink>
            </div>
          </div>
          <div class="dt-body" style="margin-top:16px" v-html="item.content" />
        </div>
      </div>
    </div>
  </div>
</template>

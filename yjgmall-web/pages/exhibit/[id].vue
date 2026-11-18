<script setup lang="ts">
definePageMeta({ layout: 'channel' })
const title = '展会'
useHead({ title })
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <span class="on">{{ title }}</span>
        </div>
      </div>
    </div>
    <div class="section">
      <div class="container">
        <div class="list-panel">
          <div class="lp-hd"><h1>{{ title }}</h1></div>
          <p class="empty">该频道暂未开放，功能已保留，后续按需开启。</p>
          <p style="text-align:center;margin-top:12px">
            <NuxtLink class="btn-soft-link" to="/">返回首页</NuxtLink>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

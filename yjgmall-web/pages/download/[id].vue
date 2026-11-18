<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '下载详情' })
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <NuxtLink to="/download">下载中心</NuxtLink>
          <span class="on">详情</span>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="list-panel">
          <p class="empty">下载中心暂未开放。</p>
          <p style="text-align:center;margin-top:12px">
            <NuxtLink class="btn-soft-link" to="/">返回首页</NuxtLink>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '下载中心' })
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <span class="on">下载中心</span>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="list-panel">
          <div class="lp-hd">
            <h1>资料下载</h1>
          </div>
          <p class="empty">下载中心暂未开放，资料功能保留，后续按需开启。</p>
          <p style="text-align:center;margin-top:12px">
            <NuxtLink class="btn-soft-link" to="/">返回首页</NuxtLink>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

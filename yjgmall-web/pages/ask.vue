<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '客服中心' })
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <span class="on">客服中心</span>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="dt-card">
          <div class="dt-h">客服中心</div>
          <div class="dt-body">
            <p>工作时间：周一至周五 9:00–18:00</p>
            <p>财富热线 / 客服电话：请见联系我们页面</p>
            <div class="dt-acts">
              <NuxtLink class="btn" to="/guestbook">在线留言</NuxtLink>
              <NuxtLink class="btn-soft-link" to="/pages/help">帮助中心</NuxtLink>
              <NuxtLink class="btn-soft-link" to="/pages/contact">联系我们</NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

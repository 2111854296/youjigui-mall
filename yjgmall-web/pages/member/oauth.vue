<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '一键登录' })
await navigateTo('/member', { replace: true })
</script>

<template>
  <div class="panel">
    <p class="muted">第三方登录暂未开放，已返回会员中心。</p>
  </div>
</template>

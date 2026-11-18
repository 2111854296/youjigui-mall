<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '手机端' })
const { mobileUrl, qrSrc, show } = useMobileAppModal()

onMounted(() => {
  // 旧书签 /mobile 也直接弹出扫码，不再展示 Destoon 频道目录
  show()
})
</script>

<template>
  <div class="mobile-gate">
    <h1>有机硅商城 · 手机端</h1>
    <p class="muted">微信或手机浏览器扫码访问 H5</p>
    <div class="qr-box">
      <img :src="qrSrc" width="200" height="200" alt="手机端二维码">
    </div>
    <p class="url">{{ mobileUrl }}</p>
    <p>
      <a class="btn btn--primary" :href="mobileUrl" target="_blank" rel="noopener">打开手机版</a>
    </p>
    <p class="back"><NuxtLink to="/">返回完整版首页</NuxtLink></p>
    <MobileAppModal />
  </div>
</template>

<style scoped>
.mobile-gate {
  max-width: 420px;
  margin: 40px auto;
  padding: 24px;
  text-align: center;
}
.muted { color: var(--ink-3); font-size: 13px; }
.qr-box {
  display: inline-flex;
  margin: 16px 0 10px;
  padding: 10px;
  border: 1px solid var(--line);
  border-radius: 12px;
}
.url { font-size: 12px; color: var(--ink-3); word-break: break-all; }
.back { margin-top: 16px; }
.back a { color: var(--blue); text-decoration: none; }
</style>

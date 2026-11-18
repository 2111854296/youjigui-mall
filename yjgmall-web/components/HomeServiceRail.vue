<script setup lang="ts">
/** 像素级：右侧栏结构/SVG/文案来自设计稿；色值/阴影按实拍细化 */
const showTop = ref(false)
const { show: showMobileApp } = useMobileAppModal()

function onScroll() {
  showTop.value = typeof window !== 'undefined' && window.scrollY > 480
}
function scrollTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

onMounted(() => {
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
})
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll))
</script>

<template>
  <div class="service-bar" aria-label="快捷服务">
    <NuxtLink class="sv-item" to="/pages/help">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M9.2 9a2.8 2.8 0 015.4 1c0 1.9-2.8 2.4-2.8 4"/><path d="M12 17.2h.01"/></svg>
      帮助中心
    </NuxtLink>
    <NuxtLink class="sv-item" to="/ask">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 01-8.5 8.3c-1.5 0-2.9-.4-4.1-1L3 20l1.3-4.1a8 8 0 01-1.3-4.4A8.4 8.4 0 0111.5 3.2 8.4 8.4 0 0121 11.5z"/></svg>
      在线客服
    </NuxtLink>
    <NuxtLink class="sv-item" to="/pages/contact" title="微信客服">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"><path d="M9.5 10.2c.2-2.8 2.7-5 5.7-5 3.2 0 5.8 2.4 5.8 5.4 0 2.9-2.4 5.3-5.5 5.5l-1.6 1.7-.3-1.8c-.2 0-.4.1-.6.1-3 0-5.4-2.3-5.4-5.1"/><path d="M3 14.2c0-2.6 2.3-4.7 5.1-4.7.4 0 .8 0 1.2.1"/><path d="M7.2 16.8l-.2 1.4 1.3-1.3"/></svg>
      微信客服
    </NuxtLink>
    <NuxtLink class="sv-item" to="/guestbook">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6.5A2.5 2.5 0 016.5 4h11A2.5 2.5 0 0120 6.5v7a2.5 2.5 0 01-2.5 2.5H11l-4 3v-3H6.5A2.5 2.5 0 014 13.5v-7z"/><path d="M8 9h8M8 12h5"/></svg>
      投诉建议
    </NuxtLink>
    <NuxtLink class="sv-item" to="/member/cart">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h2l1.4 9.2a2 2 0 002 1.7h8.3a2 2 0 002-1.6L20 7H7"/><circle cx="9.5" cy="19.5" r="1.4"/><circle cx="17" cy="19.5" r="1.4"/></svg>
      购物车
    </NuxtLink>
    <NuxtLink class="sv-item" to="/member">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/></svg>
      用户中心
    </NuxtLink>
    <button type="button" class="sv-item sv-btn" @click="showMobileApp">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18h2"/></svg>
      APP
    </button>
    <a
      v-show="showTop"
      class="sv-item"
      href="#"
      @click.prevent="scrollTop"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5.5 11.5 12 5l6.5 6.5"/></svg>
      顶部
    </a>
    <NuxtLink class="sv-consult" to="/ask">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 01-8.5 8.3c-1.5 0-2.9-.4-4.1-1L3 20l1.3-4.1a8 8 0 01-1.3-4.4A8.4 8.4 0 0111.5 3.2 8.4 8.4 0 0121 11.5z"/></svg>
      <span>在线咨询</span>
    </NuxtLink>
    <MobileAppModal />
  </div>
</template>

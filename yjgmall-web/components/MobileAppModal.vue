<script setup lang="ts">
import { setPreferPc } from '~/utils/mobileGate'

const { open, mobileUrl, qrSrc, hide } = useMobileAppModal()

const h5Home = computed(() => {
  if (import.meta.client) return `${window.location.origin}/h5/#/pages/index/index`
  return 'https://g.aifeili.cn/h5/#/pages/index/index'
})

function openH5() {
  setPreferPc(false)
  hide()
  if (import.meta.client) window.location.href = h5Home.value
}

function onKey(e: KeyboardEvent) {
  if (e.key === 'Escape' && open.value) hide()
}
onMounted(() => {
  if (import.meta.client) window.addEventListener('keydown', onKey)
})
onBeforeUnmount(() => {
  if (import.meta.client) {
    window.removeEventListener('keydown', onKey)
    document.body.style.overflow = ''
  }
})
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="mapp-overlay"
      role="dialog"
      aria-modal="true"
      aria-labelledby="mapp-title"
      @click.self="hide"
    >
      <div class="mapp-modal">
        <button type="button" class="mapp-x" aria-label="关闭" @click="hide">×</button>
        <h3 id="mapp-title">手机端访问</h3>
        <p class="lead">微信或手机浏览器扫码，进入有机硅商城 H5；手机访问本站会自动跳转。</p>
        <div class="qr-box">
          <img :src="qrSrc" width="220" height="220" alt="手机端二维码">
        </div>
        <p class="url">{{ mobileUrl }}</p>
        <button type="button" class="btn btn--primary" @click="openH5">直接打开手机版</button>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.mapp-overlay {
  position: fixed;
  inset: 0;
  z-index: 1300;
  background: rgba(15, 28, 52, .48);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
}
.mapp-modal {
  position: relative;
  width: min(360px, 100%);
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 24px 64px rgba(15, 35, 70, .22);
  padding: 28px 24px 24px;
  text-align: center;
}
.mapp-x {
  position: absolute;
  top: 10px;
  right: 12px;
  border: 0;
  background: transparent;
  font-size: 26px;
  line-height: 1;
  color: var(--ink-3);
  cursor: pointer;
}
.mapp-modal h3 {
  margin: 0 0 8px;
  font-size: 18px;
  font-weight: 800;
  color: var(--ink);
}
.lead {
  margin: 0 0 16px;
  font-size: 13px;
  color: var(--ink-3);
  line-height: 1.45;
}
.qr-box {
  display: inline-flex;
  padding: 10px;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: #fff;
}
.qr-box img { display: block; }
.url {
  margin: 12px 0 16px;
  font-size: 12px;
  color: var(--ink-3);
  word-break: break-all;
}
.mapp-modal .btn {
  text-decoration: none;
  display: inline-flex;
}
</style>

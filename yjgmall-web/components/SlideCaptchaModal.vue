<script setup lang="ts">
const open = defineModel<boolean>({ default: false })

const emit = defineEmits<{
  (e: 'verified', token: string): void
  (e: 'cancel'): void
}>()

const captchaRef = ref<{ reload: () => void } | null>(null)

watch(open, (v) => {
  if (v) {
    nextTick(() => captchaRef.value?.reload?.())
  }
})

function onVerified(token: string) {
  open.value = false
  emit('verified', token)
}

function close() {
  if (!open.value) return
  open.value = false
  emit('cancel')
}

function onKey(e: KeyboardEvent) {
  if (e.key === 'Escape') close()
}

watch(open, (v) => {
  if (!import.meta.client) return
  if (v) {
    document.addEventListener('keydown', onKey)
    document.body.style.overflow = 'hidden'
  } else {
    document.removeEventListener('keydown', onKey)
    document.body.style.overflow = ''
  }
})

onBeforeUnmount(() => {
  if (!import.meta.client) return
  document.removeEventListener('keydown', onKey)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <div v-if="open" class="scm-mask" @click.self="close">
      <div class="scm-box" role="dialog" aria-modal="true" aria-label="安全验证">
        <button type="button" class="scm-x" aria-label="关闭" @click="close">×</button>
        <SlideCaptcha
          ref="captchaRef"
          :auto-load="false"
          @verified="onVerified"
        />
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.scm-mask {
  position: fixed;
  inset: 0;
  z-index: 10050;
  background: rgba(15, 23, 42, 0.48);
  display: grid;
  place-items: center;
  padding: 20px;
  animation: scm-fade 0.18s ease-out;
}
.scm-box {
  position: relative;
  width: min(360px, 100%);
  background: #fff;
  border-radius: 14px;
  padding: 22px 20px 18px;
  box-shadow: 0 20px 50px rgba(15, 23, 42, 0.22);
  animation: scm-up 0.2s ease-out;
}
.scm-x {
  position: absolute;
  top: 10px;
  right: 12px;
  width: 32px;
  height: 32px;
  border: 0;
  background: transparent;
  color: #94a3b8;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
  border-radius: 8px;
  font-family: inherit;
}
.scm-x:hover {
  color: #475569;
  background: #f1f5f9;
}
@keyframes scm-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}
@keyframes scm-up {
  from { opacity: 0; transform: translateY(10px) scale(0.98); }
  to { opacity: 1; transform: none; }
}
</style>

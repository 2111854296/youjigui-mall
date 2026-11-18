<script setup lang="ts">
const open = defineModel<boolean>({ default: false })

const props = withDefaults(
  defineProps<{
    title?: string
    message?: string
  }>(),
  {
    title: '会员专享内容',
    message: '历史年份行情为会员专享。开通企业会员后，即可查阅更多年份走势与行情数据。',
  },
)

const emit = defineEmits<{
  (e: 'join'): void
  (e: 'cancel'): void
}>()

function close() {
  if (!open.value) return
  open.value = false
  emit('cancel')
}

function join() {
  open.value = false
  emit('join')
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
    <div v-if="open" class="vg-mask" @click.self="close">
      <div class="vg-box" role="dialog" aria-modal="true" :aria-label="props.title">
        <button type="button" class="vg-x" aria-label="关闭" @click="close">×</button>
        <div class="vg-hero" aria-hidden="true">
          <span class="vg-badge">VIP</span>
        </div>
        <h3 class="vg-title">{{ props.title }}</h3>
        <p class="vg-msg">{{ props.message }}</p>
        <ul class="vg-points">
          <li>更长周期价格走势</li>
          <li>历史年份行情查阅</li>
          <li>企业曝光与专属权益</li>
        </ul>
        <div class="vg-acts">
          <button type="button" class="vg-btn vg-btn--ghost" @click="close">稍后再说</button>
          <button type="button" class="vg-btn vg-btn--primary" @click="join">开通会员</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
.vg-mask {
  position: fixed;
  inset: 0;
  z-index: 10060;
  background: rgba(15, 28, 52, 0.48);
  display: grid;
  place-items: center;
  padding: 20px;
  animation: vg-fade 0.18s ease-out;
}
.vg-box {
  position: relative;
  width: min(400px, 100%);
  background: #fff;
  border-radius: 16px;
  padding: 28px 26px 22px;
  box-shadow: 0 22px 56px rgba(15, 35, 70, 0.22);
  animation: vg-up 0.22s ease-out;
  overflow: hidden;
}
.vg-box::before {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  height: 4px;
  background: linear-gradient(90deg, #1d4fd7, #3f86f9);
}
.vg-x {
  position: absolute;
  top: 12px;
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
.vg-x:hover {
  color: #475569;
  background: #f1f5f9;
}
.vg-hero {
  display: flex;
  justify-content: center;
  margin-bottom: 14px;
}
.vg-badge {
  display: inline-grid;
  place-items: center;
  min-width: 56px;
  height: 56px;
  padding: 0 14px;
  border-radius: 16px;
  background: linear-gradient(135deg, #1d4fd7, #3f86f9);
  color: #fff;
  font-size: 15px;
  font-weight: 800;
  letter-spacing: 0.06em;
  box-shadow: 0 10px 24px rgba(36, 104, 242, 0.28);
}
.vg-title {
  margin: 0;
  text-align: center;
  font-size: 20px;
  font-weight: 800;
  color: #13294b;
  letter-spacing: 0.01em;
}
.vg-msg {
  margin: 10px 0 0;
  text-align: center;
  font-size: 13.5px;
  line-height: 1.65;
  color: #5a6b85;
}
.vg-points {
  list-style: none;
  margin: 16px 0 0;
  padding: 12px 14px;
  background: #f6f9fe;
  border-radius: 12px;
  border: 1px solid #e9eef6;
}
.vg-points li {
  position: relative;
  padding: 5px 0 5px 18px;
  font-size: 13px;
  color: #1f2a44;
  font-weight: 600;
}
.vg-points li::before {
  content: "";
  position: absolute;
  left: 0;
  top: 50%;
  width: 8px;
  height: 8px;
  margin-top: -4px;
  border-radius: 50%;
  background: #2468f2;
}
.vg-acts {
  display: grid;
  grid-template-columns: 1fr 1.2fr;
  gap: 10px;
  margin-top: 18px;
}
.vg-btn {
  height: 42px;
  border-radius: 11px;
  border: 0;
  font: inherit;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}
.vg-btn--ghost {
  background: #f1f5f9;
  color: #5a6b85;
}
.vg-btn--ghost:hover {
  background: #e8eef6;
  color: #13294b;
}
.vg-btn--primary {
  background: linear-gradient(135deg, #1d4fd7, #3f86f9);
  color: #fff;
  box-shadow: 0 8px 18px rgba(36, 104, 242, 0.28);
}
.vg-btn--primary:hover {
  filter: brightness(1.04);
}
@keyframes vg-fade {
  from { opacity: 0; }
  to { opacity: 1; }
}
@keyframes vg-up {
  from { opacity: 0; transform: translateY(12px) scale(0.98); }
  to { opacity: 1; transform: none; }
}
</style>

<script setup lang="ts">
const props = withDefaults(defineProps<{ autoLoad?: boolean }>(), { autoLoad: true })

const { api } = useApi()

const emit = defineEmits<{
  (e: 'verified', token: string): void
  (e: 'reset'): void
}>()

const loading = ref(true)
const checking = ref(false)
const ok = ref(false)
const err = ref('')
const captchaKey = ref('')
const bg = ref('')
const slice = ref('')
const pieceY = ref(0)
const bgW = ref(310)
const bgH = ref(155)
const piece = ref(47)

const dragging = ref(false)
const startX = ref(0)
const offsetX = ref(0)
const trackRef = ref<HTMLElement | null>(null)

const maxX = computed(() => Math.max(0, bgW.value - piece.value))
const tip = computed(() => {
  if (ok.value) return '验证通过'
  if (checking.value) return '校验中…'
  if (dragging.value) return '松开完成验证'
  return '向右拖动滑块完成拼图'
})

async function load() {
  loading.value = true
  err.value = ''
  ok.value = false
  offsetX.value = 0
  captchaKey.value = ''
  emit('reset')
  try {
    const res = await api<{ data: any }>('/captcha/slide', { auth: false })
    const d = (res as any)?.data || {}
    captchaKey.value = d.captcha_key || ''
    bg.value = d.bg || ''
    slice.value = d.slice || ''
    pieceY.value = Number(d.y || 0)
    bgW.value = Number(d.bg_w || 310)
    bgH.value = Number(d.bg_h || 155)
    piece.value = Number(d.piece || 47)
  } catch (e: any) {
    err.value = e?.data?.message || '验证码加载失败'
  } finally {
    loading.value = false
  }
}

function clamp(n: number) {
  return Math.min(maxX.value, Math.max(0, n))
}

function onPointerDown(e: PointerEvent) {
  if (ok.value || loading.value || checking.value || !captchaKey.value) return
  dragging.value = true
  startX.value = e.clientX - offsetX.value
  ;(e.currentTarget as HTMLElement)?.setPointerCapture?.(e.pointerId)
}

function onPointerMove(e: PointerEvent) {
  if (!dragging.value) return
  offsetX.value = clamp(e.clientX - startX.value)
}

async function onPointerUp() {
  if (!dragging.value) return
  dragging.value = false
  await submit()
}

async function submit() {
  if (ok.value || checking.value) return
  checking.value = true
  err.value = ''
  try {
    const res = await api<{ data: { captcha_token: string } }>('/captcha/slide/check', {
      method: 'POST',
      auth: false,
      body: { captcha_key: captchaKey.value, x: Math.round(offsetX.value) },
    })
    const token = (res as any)?.data?.captcha_token || ''
    if (!token) throw new Error('no token')
    ok.value = true
    emit('verified', token)
  } catch (e: any) {
    err.value = e?.data?.message || '验证失败，请重试'
    offsetX.value = 0
    emit('reset')
    await load()
  } finally {
    checking.value = false
  }
}

onMounted(() => {
  if (props.autoLoad) load()
})

defineExpose({ reload: load, reset: load })
</script>

<template>
  <div class="slide-cap">
    <div class="sc-hd">
      <span>安全验证</span>
      <button type="button" class="sc-refresh" :disabled="loading || checking" @click="load">刷新</button>
    </div>

    <div v-if="loading" class="sc-loading">验证码加载中…</div>
    <div v-else class="sc-panel" :style="{ width: bgW + 'px' }">
      <div class="sc-bg" :style="{ height: bgH + 'px' }">
        <img :src="bg" alt="" draggable="false">
        <img
          class="sc-slice"
          :src="slice"
          alt=""
          draggable="false"
          :style="{ left: offsetX + 'px', top: pieceY + 'px', width: piece + 'px', height: piece + 'px' }"
        >
      </div>

      <div
        ref="trackRef"
        class="sc-track"
        :class="{ on: dragging, ok, bad: !!err && !ok }"
      >
        <div class="sc-fill" :style="{ width: offsetX + piece / 2 + 'px' }" />
        <div
          class="sc-btn"
          :style="{ transform: `translateX(${offsetX}px)` }"
          @pointerdown.prevent="onPointerDown"
          @pointermove.prevent="onPointerMove"
          @pointerup.prevent="onPointerUp"
          @pointercancel.prevent="onPointerUp"
        >
          <span v-if="ok">✓</span>
          <span v-else>››</span>
        </div>
        <div class="sc-tip">{{ tip }}</div>
      </div>
    </div>
    <p v-if="err" class="sc-err">{{ err }}</p>
  </div>
</template>

<style scoped>
.slide-cap {
  margin-top: 4px;
}
.sc-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 13px;
  font-weight: 600;
  color: #334155;
}
.sc-refresh {
  border: 0;
  background: transparent;
  color: #2468f2;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
  font-family: inherit;
}
.sc-refresh:disabled { opacity: .5; cursor: default; }
.sc-loading {
  height: 120px;
  display: grid;
  place-items: center;
  background: #f5f7fb;
  border-radius: 10px;
  color: #94a3b8;
  font-size: 13px;
}
.sc-panel { max-width: 100%; }
.sc-bg {
  position: relative;
  overflow: hidden;
  border-radius: 10px;
  background: #e8eef6;
  user-select: none;
}
.sc-bg > img:first-child {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  pointer-events: none;
}
.sc-slice {
  position: absolute;
  left: 0;
  top: 0;
  pointer-events: none;
  filter: drop-shadow(0 2px 4px rgba(0,0,0,.35));
}
.sc-track {
  position: relative;
  margin-top: 10px;
  height: 42px;
  border-radius: 21px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  user-select: none;
  touch-action: none;
}
.sc-track.on { border-color: #93c5fd; }
.sc-track.ok { border-color: #22c55e; background: #ecfdf5; }
.sc-track.bad { border-color: #fca5a5; }
.sc-fill {
  position: absolute;
  left: 0; top: 0; bottom: 0;
  background: rgba(36, 104, 242, .15);
  pointer-events: none;
}
.sc-track.ok .sc-fill { background: rgba(34, 197, 94, .18); }
.sc-btn {
  position: absolute;
  left: 0; top: 0;
  width: 42px; height: 42px;
  border-radius: 50%;
  background: #fff;
  border: 1px solid #cbd5e1;
  box-shadow: 0 2px 8px rgba(15, 23, 42, .12);
  display: grid;
  place-items: center;
  cursor: grab;
  color: #2468f2;
  font-weight: 800;
  font-size: 16px;
  z-index: 2;
}
.sc-track.on .sc-btn { cursor: grabbing; border-color: #2468f2; }
.sc-track.ok .sc-btn { color: #16a34a; border-color: #22c55e; cursor: default; }
.sc-tip {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  font-size: 13px;
  color: #94a3b8;
  pointer-events: none;
  padding-left: 36px;
}
.sc-track.ok .sc-tip { color: #16a34a; font-weight: 600; }
.sc-err {
  margin: 8px 0 0;
  font-size: 12.5px;
  color: #dc2626;
}
</style>

<script setup lang="ts">
const props = withDefaults(defineProps<{
  rows: any[]
  pageSize?: number
}>(), {
  pageSize: 8,
})

const PAGE_STEP_MS = 10000
const pos = ref(0)
const animate = ref(true)
const hover = ref(false)
const root = ref<HTMLElement | null>(null)
let timer: ReturnType<typeof setInterval> | null = null

const pageCount = computed(() => {
  const n = props.rows?.length || 0
  if (!n) return 0
  return Math.ceil(n / props.pageSize)
})

const pages = computed(() => {
  const rows = props.rows || []
  if (!rows.length) return [] as any[][]
  const out: any[][] = []
  for (let i = 0; i < rows.length; i += props.pageSize) out.push(rows.slice(i, i + props.pageSize))
  if (out.length <= 1) return out
  return out.concat([out[0]])
})

const trackStyle = computed(() => {
  const style: Record<string, string> = { '--shift': String(pos.value) }
  if (!animate.value) style.transition = 'none'
  return style
})

function tick() {
  if (hover.value || pageCount.value <= 1) return
  pos.value = pos.value >= pageCount.value ? 0 : pos.value + 1
}

function onEnd(e: TransitionEvent) {
  if (e.propertyName !== 'transform' || e.target !== e.currentTarget) return
  if (pageCount.value <= 1 || pos.value !== pageCount.value) return
  animate.value = false
  pos.value = 0
  requestAnimationFrame(() => {
    requestAnimationFrame(() => { animate.value = true })
  })
}

function syncHeight() {
  const el = root.value
  if (!el) return
  const page = el.querySelector<HTMLElement>('.roll-page')
  if (!page) return
  const h = page.offsetHeight
  el.style.height = `${h}px`
  el.style.setProperty('--page-h', `${h}px`)
}

onMounted(() => {
  nextTick(() => {
    syncHeight()
    requestAnimationFrame(syncHeight)
  })
  timer = setInterval(tick, PAGE_STEP_MS)
  window.addEventListener('resize', syncHeight)
})
onBeforeUnmount(() => {
  if (timer) clearInterval(timer)
  window.removeEventListener('resize', syncHeight)
})
watch(() => props.rows, () => {
  pos.value = 0
  animate.value = true
  nextTick(syncHeight)
})
</script>

<template>
  <div
    ref="root"
    class="roll-viewport"
    @mouseenter="hover = true"
    @mouseleave="hover = false"
  >
    <div class="roll-track" :style="trackStyle" @transitionend="onEnd">
      <ul v-for="(page, pi) in pages" :key="pi" class="roll-page">
        <li v-for="(row, i) in page" :key="`${pi}-${row.id ?? row.key ?? i}`">
          <slot :row="row" />
        </li>
      </ul>
    </div>
  </div>
</template>

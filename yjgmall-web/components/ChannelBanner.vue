<script setup lang="ts">
import { isExternalAdLink, isInternalAdLink, normalizeAdLink } from '~/utils/adLink'

const props = withDefaults(defineProps<{
  position: string
  size?: 'normal' | 'large'
  fallbacks?: Array<{ title: string; image: string; link?: string }>
}>(), {
  size: 'large',
  fallbacks: () => [],
})

const config = useRuntimeConfig()
const ads = ref<any[]>([])
const idx = ref(0)
const ready = ref(false)
const paused = ref(false)
let timer: ReturnType<typeof setInterval> | null = null

const defaultFallbacks: Array<{ title: string; image: string; link?: string }> = []

onMounted(async () => {
  try {
    const data = await $fetch<any>(`${config.public.apiBase}/ads`, {
      query: { position: props.position },
    })
    ads.value = (data?.data || []).filter((a: any) => usableImage(a.image))
  } catch {
    ads.value = []
  }
  ready.value = true
  armTimer()
})

function clearTimer() {
  if (!timer) return
  clearInterval(timer)
  timer = null
}

function armTimer() {
  clearTimer()
  if (paused.value || slides.value.length < 2) return
  timer = setInterval(() => step(1), 4000)
}

function step(dir: number) {
  const n = slides.value.length
  if (n < 2) return
  idx.value = (idx.value + dir + n) % n
}

function onEnter() {
  paused.value = true
  clearTimer()
}

function onLeave() {
  paused.value = false
  armTimer()
}

onBeforeUnmount(clearTimer)

function usableImage(src: unknown) {
  const s = String(src || '')
  if (!s) return false
  if (/\.svg($|\?)/i.test(s)) return false
  if (/placehold\.co/i.test(s)) return false
  return true
}

const slides = computed(() => {
  if (ads.value.length) {
    return ads.value.map((a) => ({
      id: a.id,
      title: a.title,
      image: a.image,
      link: normalizeAdLink(a.link),
    }))
  }
  // 无真实广告不造假轮播
  const fb = props.fallbacks?.length ? props.fallbacks : defaultFallbacks
  return fb.map((b, i) => ({
    id: `fb-${i}`,
    title: b.title,
    image: b.image,
    link: normalizeAdLink(b.link),
  }))
})

const cur = computed(() => slides.value[idx.value] || null)

function onImgError(e: Event) {
  const el = e.target as HTMLImageElement | null
  if (!el || el.dataset.fallback === '1') return
  el.dataset.fallback = '1'
  if (slides.value.length > 1) {
    idx.value = (idx.value + 1) % slides.value.length
  }
}
</script>

<template>
  <div v-if="ready && cur" class="channel-banner" :class="size" @mouseenter="onEnter" @mouseleave="onLeave">
    <NuxtLink v-if="isInternalAdLink(cur.link)" :to="cur.link" class="slide" :title="cur.title || ''">
      <img :src="cur.image" :alt="cur.title || ''" loading="lazy" @error="onImgError">
    </NuxtLink>
    <a
      v-else-if="isExternalAdLink(cur.link)"
      :href="cur.link"
      class="slide"
      target="_blank"
      rel="noopener noreferrer"
      :title="cur.title || ''"
    >
      <img :src="cur.image" :alt="cur.title || ''" loading="lazy" @error="onImgError">
    </a>
    <div v-else class="slide">
      <img :src="cur.image" :alt="cur.title || ''" loading="lazy" @error="onImgError">
    </div>
    <div v-if="slides.length > 1" class="dots">
      <button
        v-for="(s, i) in slides"
        :key="s.id || i"
        type="button"
        :class="{ on: idx === i }"
        @click="idx = i"
      >{{ Number(i) + 1 }}</button>
    </div>
    <button
      v-if="slides.length > 1"
      type="button"
      class="bn-nav bn-prev"
      aria-label="上一张"
      @click.stop.prevent="step(-1)"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
    </button>
    <button
      v-if="slides.length > 1"
      type="button"
      class="bn-nav bn-next"
      aria-label="下一张"
      @click.stop.prevent="step(1)"
    >
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
    </button>
  </div>
</template>

<style scoped>
.channel-banner {
  position: relative;
  width: 100%;
  max-width: 1240px;
  background: #f3f5f8;
  border: 1px solid #e5e5e5;
  overflow: hidden;
}
.slide { display: block; line-height: 0; cursor: pointer; }
.slide img {
  width: 100%;
  height: 280px;
  object-fit: cover;
  display: block;
}
.channel-banner.large {
  height: 350px;
}
.channel-banner.large .slide,
.channel-banner.large .slide img {
  width: 100%;
  height: 350px;
}
.dots {
  position: absolute;
  right: 12px;
  bottom: 12px;
  display: flex;
  gap: 4px;
  z-index: 2;
}
.dots button {
  width: 24px;
  height: 22px;
  border: 0;
  background: rgba(0,0,0,.45);
  color: #fff;
  font-size: 12px;
  cursor: pointer;
}
.dots button.on { background: var(--brand); }
.bn-nav {
  position: absolute;
  top: 50%;
  z-index: 3;
  transform: translateY(-50%);
  width: 40px;
  height: 56px;
  padding: 0;
  border: 0;
  cursor: pointer;
  display: grid;
  place-items: center;
  color: #fff;
  background: rgba(15, 28, 52, .38);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
  transition: background .18s ease, transform .18s ease;
}
.bn-nav svg { width: 20px; height: 20px; display: block; }
.bn-prev { left: 0; border-radius: 0 10px 10px 0; }
.bn-next { right: 0; border-radius: 10px 0 0 10px; }
.bn-nav:hover { background: rgba(15, 28, 52, .58); }
.bn-prev:hover { transform: translateY(-50%) translateX(1px); }
.bn-next:hover { transform: translateY(-50%) translateX(-1px); }
@media (max-width: 700px) {
  .slide img, .channel-banner.large .slide img { height: 160px; }
}
</style>

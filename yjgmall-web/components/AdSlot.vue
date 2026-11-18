<script setup lang="ts">
import { isExternalAdLink, isInternalAdLink, normalizeAdLink } from '~/utils/adLink'

const props = defineProps<{ position: string; title?: string }>()
const config = useRuntimeConfig()
const ads = ref<any[]>([])

onMounted(async () => {
  try {
    const data = await $fetch<any>(`${config.public.apiBase}/ads`, {
      query: { position: props.position },
    })
    ads.value = (data?.data || []).filter((a: any) => usableImage(a.image) || displayText(a))
  } catch {
    ads.value = []
  }
})

function usableImage(src: unknown) {
  const s = String(src || '')
  if (!s) return false
  if (/\.svg($|\?)/i.test(s)) return false
  if (/placehold/i.test(s)) return false
  return true
}

function displayText(ad: any) {
  const c = String(ad?.content || '').trim()
  if (!c || /^origin:/i.test(c)) return ''
  return c
}

function displayTitle(ad: any) {
  const t = String(ad?.title || '').trim()
  if (!t || /^轮播\s*\d+$/i.test(t)) return ''
  return t
}

function hrefOf(ad: any) {
  return normalizeAdLink(ad?.link)
}
</script>

<template>
  <div v-if="ads.length" class="panel ad-slot">
    <div v-if="title" class="cat-nav" style="font-size:13px">{{ title }}</div>
    <div class="body">
      <template v-for="ad in ads" :key="ad.id">
        <NuxtLink
          v-if="isInternalAdLink(hrefOf(ad))"
          :to="hrefOf(ad)"
          class="ad-item"
          :title="displayTitle(ad) || undefined"
        >
          <img v-if="usableImage(ad.image)" :src="ad.image" :alt="displayTitle(ad) || ''" loading="lazy" decoding="async">
          <strong v-if="displayTitle(ad)">{{ displayTitle(ad) }}</strong>
          <div v-if="displayText(ad)" class="muted note">{{ displayText(ad) }}</div>
        </NuxtLink>
        <a
          v-else-if="isExternalAdLink(hrefOf(ad))"
          :href="hrefOf(ad)"
          class="ad-item"
          target="_blank"
          rel="noopener noreferrer"
          :title="displayTitle(ad) || undefined"
        >
          <img v-if="usableImage(ad.image)" :src="ad.image" :alt="displayTitle(ad) || ''" loading="lazy" decoding="async">
          <strong v-if="displayTitle(ad)">{{ displayTitle(ad) }}</strong>
          <div v-if="displayText(ad)" class="muted note">{{ displayText(ad) }}</div>
        </a>
        <div v-else class="ad-item">
          <img v-if="usableImage(ad.image)" :src="ad.image" :alt="displayTitle(ad) || ''" loading="lazy" decoding="async">
          <strong v-if="displayTitle(ad)">{{ displayTitle(ad) }}</strong>
          <div v-if="displayText(ad)" class="muted note">{{ displayText(ad) }}</div>
        </div>
      </template>
    </div>
  </div>
</template>

<style scoped>
.ad-item {
  display: block;
  padding: 10px 0;
  border-bottom: 1px dashed #eee;
}
.ad-item:last-child { border-bottom: 0; }
.ad-item img {
  width: 100%;
  height: auto;
  display: block;
  margin-bottom: 6px;
}
.note { font-size: 12px; margin-top: 4px; }
</style>

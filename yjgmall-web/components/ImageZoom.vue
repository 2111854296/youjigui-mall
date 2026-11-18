<script setup lang="ts">
defineProps<{ src: string; alt?: string }>()
defineEmits<{ error: [e: Event] }>()

const LENS = 0.42
const zoom = reactive({
  on: false,
  x: 0,
  y: 0,
  w: 0,
  h: 0,
  boxW: 0,
  boxH: 0,
  paneW: 0,
  paneH: 0,
  paneLeft: 0,
  paneTop: 0,
})

function canZoom() {
  return window.matchMedia('(hover: hover) and (pointer: fine)').matches
}

function onZoomMove(e: MouseEvent) {
  if (!canZoom()) return
  const el = e.currentTarget as HTMLElement
  const rect = el.getBoundingClientRect()
  if (rect.width < 8 || rect.height < 8) return
  const lw = rect.width * LENS
  const lh = rect.height * LENS
  const x = Math.max(0, Math.min(e.clientX - rect.left - lw / 2, rect.width - lw))
  const y = Math.max(0, Math.min(e.clientY - rect.top - lh / 2, rect.height - lh))
  zoom.on = true
  zoom.x = x
  zoom.y = y
  zoom.w = lw
  zoom.h = lh
  zoom.boxW = rect.width
  zoom.boxH = rect.height
  const cap = Math.min(680, window.innerWidth - 24, window.innerHeight - 24)
  zoom.paneW = Math.max(rect.width, Math.min(Math.round(rect.width * 1.85), cap))
  zoom.paneH = Math.max(rect.height, Math.min(Math.round(rect.height * 1.85), cap))
  const gap = 14
  let left = rect.right + gap
  if (left + zoom.paneW > window.innerWidth - 8) left = Math.max(8, rect.left - zoom.paneW - gap)
  zoom.paneLeft = left
  zoom.paneTop = Math.max(8, Math.min(rect.top, window.innerHeight - zoom.paneH - 8))
}

function onZoomLeave() {
  zoom.on = false
}
</script>

<template>
  <div class="zoom-hit" @mousemove="onZoomMove" @mouseleave="onZoomLeave">
    <img :src="src" :alt="alt || ''" @error="$emit('error', $event)">
    <div
      v-show="zoom.on"
      class="zoom-lens"
      :style="{ left: zoom.x + 'px', top: zoom.y + 'px', width: zoom.w + 'px', height: zoom.h + 'px' }"
    />
    <div
      v-show="zoom.on"
      class="zoom-pane"
      :style="{ left: zoom.paneLeft + 'px', top: zoom.paneTop + 'px', width: zoom.paneW + 'px', height: zoom.paneH + 'px' }"
    >
      <img
        :src="src"
        alt=""
        :style="{
          width: (zoom.paneW / LENS) + 'px',
          height: (zoom.paneH / LENS) + 'px',
          transform: `translate(${-(zoom.x * zoom.paneW) / ((zoom.boxW || 1) * LENS)}px, ${-(zoom.y * zoom.paneH) / ((zoom.boxH || 1) * LENS)}px)`,
        }"
      >
    </div>
  </div>
</template>

<style scoped>
.zoom-hit {
  position: relative;
  width: 100%;
  height: 100%;
  cursor: zoom-in;
}
.ch-portal .dt-gallery .zoom-hit > img {
  width: 100%;
  height: 100%;
  max-width: none;
  max-height: none;
  object-fit: cover;
}
.zoom-lens {
  position: absolute;
  z-index: 2;
  background: rgba(255, 92, 110, .28);
  border: 1px solid rgba(255, 70, 90, .55);
  pointer-events: none;
}
.zoom-pane {
  position: fixed;
  z-index: 80;
  overflow: hidden;
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 12px;
  box-shadow: 0 10px 28px rgba(19, 41, 75, .18);
  pointer-events: none;
}
.ch-portal .dt-gallery .zoom-pane img {
  position: absolute;
  left: 0;
  top: 0;
  max-width: none;
  max-height: none;
  object-fit: cover;
}
@media (max-width: 860px) {
  .zoom-lens, .zoom-pane { display: none !important; }
  .zoom-hit { cursor: default; }
}
</style>

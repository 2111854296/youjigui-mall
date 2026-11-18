<script setup lang="ts">
const props = defineProps<{ grade?: string | null; label?: string | null }>()

const srcOf: Record<string, string> = {
  silver: '/img/member/silver.png',
  gold: '/img/member/gold.png',
  diamond: '/img/member/diamond.png',
  normal: '/img/member/normal.png',
}

const src = computed(() => srcOf[String(props.grade || '')] || '')
</script>

<template>
  <img v-if="src" class="member-mark" :src="src" :alt="label || ''" :title="label || ''">
</template>

<style scoped>
.member-mark {
  display: inline-block;
  height: 16px;
  width: auto;
  vertical-align: middle;
  flex-shrink: 0;
}
</style>

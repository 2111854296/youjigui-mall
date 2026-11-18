<script setup lang="ts">
import type { AuthUser } from '~/composables/useAuth'
import { memberTier, memberTierLabel } from '~/utils/memberTier'

const props = defineProps<{ user?: AuthUser | null; large?: boolean }>()

const mark = computed(() => {
  if (!props.user) return null
  const tier = memberTier(props.user)
  return {
    grade: tier === 'free' ? 'normal' : tier,
    label: memberTierLabel(tier),
    src: tier === 'free' ? '/img/member/normal.png' : `/img/member/${tier}.png`,
  }
})
</script>

<template>
  <img
    v-if="mark"
    class="mbadge"
    :class="{ large }"
    :src="mark.src"
    :alt="mark.label"
    :title="mark.label"
  >
</template>

<style scoped>
.mbadge {
  display: inline-block;
  height: 15px;
  width: auto;
  max-width: none;
  margin-left: 6px;
  vertical-align: -3px;
}
.mbadge.large {
  height: 20px;
  vertical-align: -4px;
}
</style>

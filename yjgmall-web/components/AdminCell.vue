<script setup lang="ts">
import { fieldEnumLabel } from '~/utils/statusLabel'

const props = defineProps<{
  value: any
  path: string
  /** 字段名 → { 枚举码 → 中文 }，来自资源 meta */
  maps?: Record<string, Record<string, string>> | null
}>()

const ENUM_FIELDS = new Set([
  'status', 'member_type', 'group_code', 'level', 'type', 'module', 'position',
  'is_active', 'is_featured', 'is_certified', 'is_top', 'is_admin', 'is_best', 'is_vip',
])

const text = computed(() => {
  const raw = props.path.split('.').reduce((acc: any, k) => (acc == null ? acc : acc[k]), props.value)
  if (raw === true) return '是'
  if (raw === false) return '否'
  if (raw == null || raw === '') return '—'

  const leaf = props.path.includes('.') ? props.path.split('.').pop()! : props.path
  const hasMap = !!(props.maps?.[leaf] || props.maps?.[props.path])
  if (hasMap || ENUM_FIELDS.has(leaf)) {
    return fieldEnumLabel(props.path, raw, props.maps)
  }

  // 值本身像英文枚举码时也尽量翻译
  const s = String(raw)
  if (/^[a-z][a-z0-9_]*$/i.test(s) && s === s.toLowerCase()) {
    const labeled = fieldEnumLabel(leaf, s, props.maps)
    if (labeled !== s) return labeled
  }

  return s
})
</script>

<template>
  <span>{{ text }}</span>
</template>

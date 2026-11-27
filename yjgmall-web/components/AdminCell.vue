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

/** 仅状态类字段上色，避免误伤名称/类型等列 */
const STATUS_COLOR_FIELDS = new Set(['status', 'is_active', 'audit_status'])

/** 与后台 ab-tag 语义对齐：通过绿 / 待审橙 / 拒绝红 / 启用蓝 / 停用红 */
const STATUS_TONE: Record<string, string> = {
  启用: 'admin-status-on',
  停用: 'admin-status-off',
  已通过: 'admin-status-ok',
  通过: 'admin-status-ok',
  正常: 'admin-status-ok',
  已发布: 'admin-status-ok',
  已完成: 'admin-status-ok',
  已回复: 'admin-status-ok',
  开放中: 'admin-status-ok',
  已付款: 'admin-status-ok',
  待审核: 'admin-status-wait',
  待审: 'admin-status-wait',
  处理中: 'admin-status-wait',
  草稿: 'admin-status-wait',
  已拒绝: 'admin-status-no',
  拒绝: 'admin-status-no',
  已禁用: 'admin-status-no',
  已取消: 'admin-status-no',
  已下架: 'admin-status-off-muted',
  下架: 'admin-status-off-muted',
  已隐藏: 'admin-status-off-muted',
  已关闭: 'admin-status-off-muted',
  已过期: 'admin-status-off-muted',
}

const toneClass = computed(() => {
  const leaf = props.path.includes('.') ? props.path.split('.').pop()! : props.path
  if (!STATUS_COLOR_FIELDS.has(leaf)) return ''
  return STATUS_TONE[text.value] || ''
})
</script>

<template>
  <span :class="toneClass">{{ text }}</span>
</template>

<style scoped>
.admin-status-on {
  color: #2468f2;
  font-weight: 600;
}
.admin-status-off,
.admin-status-no {
  color: #e23b32;
  font-weight: 600;
}
.admin-status-ok {
  color: #157a3a;
  font-weight: 600;
}
.admin-status-wait {
  color: #b45309;
  font-weight: 600;
}
.admin-status-off-muted {
  color: #64748b;
  font-weight: 600;
}
</style>

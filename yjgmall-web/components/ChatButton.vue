<script setup lang="ts">
/**
 * 站内直连交谈已关闭：保留组件避免各页报错，不再发起会话。
 * 业务请走询盘，由官方后台审核后再转发企业。
 */
withDefaults(defineProps<{
  peerId?: number | null
  label?: string
  buttonClass?: string
  /** 为 true 时显示一句灰色提示，默认完全隐藏 */
  showHint?: boolean
}>(), {
  label: '在线交谈',
  buttonClass: 'btn',
  showHint: false,
})
</script>

<template>
  <span v-if="showHint" class="chat-off-hint">请通过询盘留言，由平台审核后转达</span>
</template>

<style scoped>
.chat-off-hint {
  font-size: 12px;
  color: var(--ink-3, #8a93a3);
  line-height: 1.4;
}
</style>

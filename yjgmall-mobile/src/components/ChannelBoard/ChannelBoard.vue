<script setup lang="ts">
withDefaults(defineProps<{
  title: string
  rows: { id: string | number; title: string; meta?: string }[]
  hideMore?: boolean
}>(), { hideMore: false })

const emit = defineEmits<{ more: []; open: [id: string | number] }>()
</script>

<template>
  <view v-if="rows.length" class="board card">
    <view class="board-hd">
      <text class="board-title">{{ title }}</text>
      <text v-if="!hideMore" class="more" @click="emit('more')">更多</text>
    </view>
    <view v-for="r in rows" :key="r.id" class="row" @click="emit('open', r.id)">
      <text class="title">{{ r.title }}</text>
      <text v-if="r.meta" class="meta muted">{{ r.meta }}</text>
    </view>
  </view>
</template>

<style lang="scss" scoped>
.board { margin: 16rpx 20rpx; padding: 8rpx 20rpx 12rpx; }
.board-hd {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16rpx 0 8rpx;
}
.board-title { font-size: 30rpx; font-weight: 700; color: #1f2937; }
.more { font-size: 24rpx; color: #2769dc; }
.row { padding: 18rpx 0; border-top: 1rpx solid #f1f5f9; }
.title { font-size: 28rpx; font-weight: 600; display: block; line-height: 1.45; color: #1f2937; }
.meta { font-size: 22rpx; display: block; margin-top: 6rpx; }
</style>

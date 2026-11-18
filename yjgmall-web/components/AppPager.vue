<script setup lang="ts">
import { compactPageItems } from '~/utils/pager'

const props = defineProps<{
  meta?: {
    current_page?: number
    last_page?: number
    total?: number
  } | null
  pending?: boolean
}>()

const route = useRoute()
const router = useRouter()

/** 以 URL 为准，避免请求未返回时仍按旧 meta 算页码导致点了像卡住 */
const last = computed(() => Math.max(1, Number(props.meta?.last_page || 1)))
const current = computed(() => {
  const fromRoute = Number(route.query.page || 0)
  if (Number.isFinite(fromRoute) && fromRoute >= 1) {
    return Math.min(last.value, Math.max(1, Math.floor(fromRoute)))
  }
  return Math.max(1, Number(props.meta?.current_page || 1))
})
const show = computed(() => !!props.meta && last.value > 1)

const pageItems = computed(() => compactPageItems(current.value, last.value))

const jumpInput = ref('')
watch(current, (p) => { jumpInput.value = String(p) }, { immediate: true })

let navigatingTo: number | null = null

function pageQuery(page: number) {
  const p = Math.min(last.value, Math.max(1, Math.floor(page)))
  return { path: route.path, query: { ...route.query, page: p === 1 ? undefined : p } }
}

function goPage(page: number) {
  const target = Math.min(last.value, Math.max(1, Math.floor(page)))
  if (target === current.value) return
  if (target < 1 || target > last.value) return
  if (navigatingTo === target) return
  navigatingTo = target
  router.push(pageQuery(target)).finally(() => {
    if (navigatingTo === target) navigatingTo = null
  })
}

function submitJump() {
  const n = Number(String(jumpInput.value).trim())
  if (!Number.isFinite(n)) {
    jumpInput.value = String(current.value)
    return
  }
  goPage(Math.round(n))
}
</script>

<template>
  <nav
    v-if="show"
    class="pager app-pager"
    :class="{ 'is-pending': pending }"
    aria-label="分页"
  >
    <button
      v-if="current > 1"
      type="button"
      class="pg-btn"
      :disabled="pending"
      @click="goPage(current - 1)"
    >‹ 上一页</button>
    <span v-else class="pg-btn is-disabled">‹ 上一页</span>

    <template v-for="(item, i) in pageItems" :key="`${item}-${i}`">
      <span v-if="item === '…'" class="pg-ellipsis" aria-hidden="true">…</span>
      <span
        v-else-if="item === current"
        class="pg-num current"
        aria-current="page"
      >{{ item }}</span>
      <button
        v-else
        type="button"
        class="pg-num"
        :disabled="pending"
        @click="goPage(item as number)"
      >{{ item }}</button>
    </template>

    <button
      v-if="current < last"
      type="button"
      class="pg-btn pg-next"
      :disabled="pending"
      @click="goPage(current + 1)"
    >下一页 ›</button>
    <span v-else class="pg-btn pg-next is-disabled">下一页 ›</span>

    <form class="pg-jump" @submit.prevent="submitJump">
      <span>到第</span>
      <input
        v-model="jumpInput"
        type="text"
        inputmode="numeric"
        pattern="[0-9]*"
        aria-label="跳转页码"
        :disabled="pending"
        @focus="($event.target as HTMLInputElement).select()"
      >
      <span>页</span>
      <button type="submit" :disabled="pending">确定</button>
    </form>
  </nav>
</template>

<style scoped>
.app-pager {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin: 16px 0 8px;
}
.app-pager.is-pending {
  opacity: 0.72;
}
.pg-num,
.pg-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
  height: 36px;
  padding: 0 12px;
  border: 1px solid var(--line, #e9eef6);
  border-radius: 8px;
  background: #fff;
  color: var(--ink, #13294b);
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
  line-height: 1;
  cursor: pointer;
  font-family: inherit;
  transition: background .15s, border-color .15s, color .15s;
}
.pg-num:hover:not(:disabled),
.pg-btn:hover:not(:disabled) {
  border-color: var(--blue, #2468f2);
  color: var(--blue, #2468f2);
}
.pg-num:disabled,
.pg-btn:disabled {
  cursor: wait;
  opacity: 0.7;
}
.pg-num.current {
  background: var(--blue, #2468f2);
  border-color: var(--blue, #2468f2);
  color: #fff;
  font-weight: 700;
  pointer-events: none;
}
.pg-btn.pg-next {
  padding: 0 14px;
  font-weight: 700;
}
.pg-btn.is-disabled {
  opacity: .4;
  pointer-events: none;
}
.pg-ellipsis {
  min-width: 24px;
  text-align: center;
  color: var(--ink-3, #98a5bc);
  font-size: 14px;
  letter-spacing: .08em;
  user-select: none;
}
.pg-jump {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-left: 6px;
  font-size: 13.5px;
  color: var(--ink-2, #5a6b85);
  font-weight: 500;
}
.pg-jump input {
  width: 48px;
  height: 36px;
  border: 1px solid var(--line, #e9eef6);
  border-radius: 8px;
  text-align: center;
  font-size: 14px;
  font-weight: 600;
  color: var(--ink, #13294b);
  background: #fff;
  outline: none;
}
.pg-jump input:focus {
  border-color: var(--blue, #2468f2);
  box-shadow: 0 0 0 3px rgba(36, 104, 242, .12);
}
.pg-jump button {
  height: 36px;
  padding: 0 12px;
  border: 1px solid var(--line, #e9eef6);
  border-radius: 8px;
  background: #F6F9FE;
  color: var(--blue, #2468f2);
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
}
.pg-jump button:hover:not(:disabled) {
  background: var(--blue, #2468f2);
  border-color: var(--blue, #2468f2);
  color: #fff;
}
.pg-jump button:disabled,
.pg-jump input:disabled {
  cursor: wait;
  opacity: 0.7;
}
@media (max-width: 640px) {
  .pg-jump { width: 100%; justify-content: center; margin-left: 0; margin-top: 4px; }
}
</style>

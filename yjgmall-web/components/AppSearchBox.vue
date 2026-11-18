<script setup lang="ts">
const route = useRoute()
const type = ref(String(route.query.type || 'all'))
const q = ref(String(route.query.q || ''))
const open = ref(false)
const root = ref<HTMLElement | null>(null)

const modes = [
  { key: 'all', label: '综合' },
  { key: 'sell', label: '产品' },
  { key: 'company', label: '名企推荐' },
  { key: 'buy', label: '采购' },
]

const hotWords = [
  { word: 'DMC', module: 'all', hot: true },
  { word: '生胶', module: 'all' },
  { word: '二甲基硅油', module: 'all' },
  { word: '甲基硅油', module: 'all' },
  { word: '混炼胶', module: 'all' },
  { word: '气相白炭黑', module: 'all' },
]

const currentLabel = computed(() => modes.find(m => m.key === type.value)?.label || '综合')

function toggleOpen(e?: Event) {
  e?.stopPropagation()
  open.value = !open.value
}

function pickMode(key: string) {
  type.value = key === 'mall' ? 'sell' : key
  open.value = false
}

function onSearch() {
  if (!q.value.trim()) return
  const t = type.value === 'mall' ? 'sell' : (type.value || 'all')
  navigateTo({ path: '/search', query: { type: t, q: q.value.trim() } })
}

function onDocPointer(e: Event) {
  const el = root.value
  if (!el) return
  if (!el.contains(e.target as Node)) open.value = false
}

onMounted(() => {
  document.addEventListener('pointerdown', onDocPointer, true)
})
onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', onDocPointer, true)
})

watch(() => route.query.type, (v) => {
  const raw = String(v || 'all')
  type.value = raw === 'mall' ? 'sell' : raw
})
watch(() => route.query.q, (v) => {
  q.value = String(v || '')
})

</script>

<template>
  <!-- 类名对齐设计稿 .h-search；样式来自 portal-design.css -->
  <div ref="root" class="h-search" :class="{ 'is-open': open }">
    <form class="bar" @submit.prevent="onSearch">
      <div
        class="cat"
        :class="{ open }"
        role="button"
        tabindex="0"
        aria-haspopup="listbox"
        :aria-expanded="open"
        @click="toggleOpen"
        @keydown.enter.prevent="toggleOpen"
        @keydown.space.prevent="toggleOpen"
      >
        {{ currentLabel }}
        <svg viewBox="0 0 12 12" width="11" height="11" aria-hidden="true"><path d="M2.5 4.2l3.5 3.6 3.5-3.6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <ul v-show="open" class="drop-list" role="listbox" @click.stop>
          <li
            v-for="m in modes"
            :key="m.key"
            role="option"
            :aria-selected="type === m.key"
            :class="{ active: type === m.key }"
            @click="pickMode(m.key)"
          >{{ m.label }}</li>
        </ul>
      </div>
      <input
        v-model="q"
        type="text"
        placeholder="请输入产品名、型号、牌号、品牌等，如：DMC、107胶、白炭黑"
        required
      >
      <button type="submit" class="go">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
        搜全站
      </button>
    </form>
    <div class="h-hot">
      <span>热门搜索：</span>
      <NuxtLink
        v-for="word in hotWords"
        :key="word.word"
        :class="{ red: word.hot }"
        :to="{ path: '/search', query: { type: word.module || 'all', q: word.word } }"
      >{{ word.word }}</NuxtLink>
    </div>
  </div>
</template>

<style scoped>
.cat { position: relative; z-index: 2; }
.drop-list {
  position: absolute;
  left: -2px;
  top: calc(100% + 4px);
  z-index: 300;
  margin: 0;
  padding: 4px 0;
  list-style: none;
  width: 100px;
  background: #fff;
  border: 1px solid var(--blue);
  box-shadow: 0 10px 28px rgba(19, 41, 75, .16);
  border-radius: 8px;
}
.drop-list li {
  padding: 10px 0;
  text-align: center;
  cursor: pointer;
  font-size: 13.5px;
  font-weight: 500;
  color: var(--ink);
}
.drop-list li:hover,
.drop-list li.active {
  background: #eef4ff;
  color: var(--blue);
  font-weight: 600;
}
.h-search .bar { overflow: visible; }
.h-search .go {
  border: 0;
  cursor: pointer;
  font-family: inherit;
  border-radius: 0 8px 8px 0;
}
.h-search .cat { border-radius: 8px 0 0 8px; }
</style>

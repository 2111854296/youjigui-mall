<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '网站公告' })
const config = useRuntimeConfig()
const route = useRoute()
const router = useRouter()
const { api, token } = useApi()
const { data } = await useAsyncData('announcements-page', () =>
  $fetch(`${config.public.apiBase}/announcements`),
)
onMounted(() => {
  if (!token.value) return
  api('/member/notice-seen', { method: 'POST', body: { type: 'announce' } }).catch(() => {})
})
const items = computed(() => (data.value as any)?.data || [])
const activeId = computed(() => {
  const raw = route.query.id
  const v = Array.isArray(raw) ? raw[0] : raw
  const n = Number(v)
  return Number.isFinite(n) && n > 0 ? n : 0
})
const active = computed(() => {
  if (!activeId.value) return null
  return items.value.find((a: any) => Number(a.id) === activeId.value) || null
})

function openItem(a: any) {
  router.push({ path: '/announce', query: { id: String(a.id) } })
}
function backList() {
  router.push({ path: '/announce' })
}

/** 纯文本公告保留换行；已是 HTML 则直接渲染 */
function bodyHtml(raw: unknown) {
  const s = String(raw || '').trim()
  if (!s) return '暂无正文'
  if (/<[a-z][\s\S]*>/i.test(s)) return s
  return s
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\n/g, '<br>')
}
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <span class="on">网站公告</span>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div v-if="active" class="dt-card">
          <a href="#" class="btn-soft-link" style="margin-bottom:12px" @click.prevent="backList">« 返回列表</a>
          <div class="dt-sum">
            <h1>{{ active.title }}</h1>
            <p class="meta">{{ active.created_at?.slice?.(0, 16) }}</p>
          </div>
          <div class="dt-body announce-body" v-html="bodyHtml(active.content)" />
          <p v-if="active.link" style="margin-top:14px">
            <a :href="active.link" target="_blank" rel="noopener">相关链接</a>
          </p>
        </div>
        <div v-else class="list-panel">
          <div class="lp-hd">
            <h1>网站公告</h1>
            <span class="meta">共 {{ items.length }} 条</span>
          </div>
          <a
            v-for="a in items"
            :key="a.id"
            href="#"
            class="lp-item"
            @click.prevent="openItem(a)"
          >
            <div class="body">
              <div class="tt">{{ a.title }}</div>
              <div class="foot">
                <span>{{ a.created_at?.slice?.(0, 10) }}</span>
              </div>
            </div>
          </a>
          <p v-if="!items.length" class="empty">暂无公告</p>
          <p v-else-if="activeId && !active" class="empty">未找到该公告，可能已下架</p>
          <p style="margin-top:16px;font-size:13px;color:var(--ink-2)">
            需要帮助？查看 <NuxtLink to="/pages/help">帮助中心</NuxtLink>
            或 <NuxtLink to="/guestbook">网站留言</NuxtLink>
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.announce-body :deep(p) { margin: 0 0 0.85em; line-height: 1.75; }
.announce-body :deep(p:last-child) { margin-bottom: 0; }
.announce-body :deep(ul),
.announce-body :deep(ol) { margin: 0 0 0.85em; padding-left: 1.4em; }
.announce-body :deep(h2),
.announce-body :deep(h3) { margin: 1.1em 0 0.5em; font-weight: 800; line-height: 1.35; }
.announce-body :deep(blockquote) {
  margin: 0.8em 0;
  padding: 8px 12px;
  border-left: 3px solid #f0d4b5;
  background: #fff8f0;
  color: #6b4a2e;
}
.announce-body :deep(a) { color: var(--blue, #2468f2); }
.announce-body :deep(hr) { margin: 1em 0; border: 0; border-top: 1px solid var(--line, #e9eef6); }
</style>

<script setup lang="ts">
definePageMeta({ layout: 'channel' })
const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const type = computed(() => {
  const raw = String(route.query.type || 'all')
  return ['all', 'sell', 'buy', 'mall', 'company', 'news'].includes(raw) ? raw : 'all'
})
const q = computed(() => String(route.query.q || ''))
const page = computed(() => Number(route.query.page || 1))

const tabs = [
  { key: 'all', label: '综合' },
  { key: 'sell', label: '供应' },
  { key: 'buy', label: '采购' },
  { key: 'mall', label: '商城' },
  { key: 'company', label: '企业' },
  { key: 'news', label: '资讯' },
]

const typeLabel = computed(() => tabs.find((t) => t.key === type.value)?.label || type.value)

useHead(() => ({ title: q.value ? `搜索：${q.value}` : '搜索' }))

const { data, error } = await useAsyncData(
  () => `search-${type.value}-${q.value}-${page.value}`,
  () =>
    $fetch(`${config.public.apiBase}/search`, {
      query: { type: type.value, q: q.value, page: page.value },
    }),
  { watch: [type, q, page] },
)

const items = computed(() => ((data.value as any)?.data || []) as any[])
const meta = computed(() => (data.value as any)?.meta || null)
const totalCount = computed(() => meta.value?.total ?? 0)

const SAFE_THUMB = '/img/logo-yjg.png'

function channelOf(item: any) {
  return String(item?._channel || type.value)
}

function linkOf(item: any) {
  const ch = channelOf(item)
  if (ch === 'buy') return `/buy/${item.id}`
  if (ch === 'mall') return `/mall/${item.id}`
  if (ch === 'company') return `/companies/${item.slug || item.id}`
  if (ch === 'news') return `/news/${item.id}`
  return `/sell/${item.id}`
}

function titleOf(item: any) {
  return item.title || item.name
}

function thumbOf(item: any, _i = 0) {
  const t = String(item?.thumb || item?.logo || '')
  if (!t || /\/img\/products\/p\d+/i.test(t) || /\.svg($|\?)/i.test(t) || /placehold/i.test(t)) return SAFE_THUMB
  return t
}

function subOf(item: any) {
  const ch = channelOf(item)
  if (ch === 'mall') {
    return item.price ? `¥${item.price}` : '面议'
  }
  if (ch === 'buy') {
    return `${item.quantity || '-'} ${item.unit || ''} · ${item.budget ? `预算¥${item.budget}` : '预算面议'} · ${item.area || ''}`
  }
  if (ch === 'company') {
    return [item.area, item.business].filter(Boolean).join(' · ') || '企业资料'
  }
  if (ch === 'news') {
    return `${(item.published_at || '').slice(0, 10)} · ${item.views || 0}阅`
  }
  return `${item.price ? `¥${item.price}/${item.unit || ''}` : '面议'} · ${item.area || '-'} · ${item.user?.company_name || item.user?.name || ''}`
}

function channelLabel(item: any) {
  return String(item?._channel_label || '')
}

function setType(key: string) {
  router.push({ path: '/search', query: { type: key, q: q.value || undefined, page: undefined } })
}
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <span class="on">搜索结果</span>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>搜索提示：</b>
        默认综合检索全站；也可切换供应、采购、商城、企业与资讯频道。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="list-panel">
          <div class="lp-hd">
            <h1>搜索「{{ q || '全部' }}」</h1>
            <span class="meta">共 {{ totalCount }} 条 · {{ typeLabel }}</span>
          </div>
          <div class="blk" style="box-shadow:none;padding:0 0 12px;margin-bottom:4px">
            <div class="b-head" style="border:0;margin:0;padding:0">
              <div class="sub-tabs">
                <button
                  v-for="t in tabs"
                  :key="t.key"
                  type="button"
                  :class="{ on: type === t.key }"
                  @click="setType(t.key)"
                >{{ t.label }}</button>
              </div>
            </div>
          </div>
          <div v-if="error" class="empty">搜索失败</div>
          <NuxtLink
            v-for="(item, i) in items"
            :key="`${channelOf(item)}-${item.id}`"
            class="lp-item"
            :to="linkOf(item)"
          >
            <div class="thumb">
              <img :src="thumbOf(item, Number(i))" :alt="titleOf(item)" loading="lazy">
            </div>
            <div class="body">
              <div class="tt">
                <span v-if="channelLabel(item)" class="ch-tag">{{ channelLabel(item) }}</span>
                <MemberBadge v-if="item.user?.member_badge" :grade="item.user.member_grade" :label="item.user.member_badge" />
                {{ titleOf(item) }}
              </div>
              <div class="sum">{{ subOf(item) }}</div>
            </div>
          </NuxtLink>
          <p v-if="!items.length && !error" class="empty">未找到相关结果，试试换个关键词或频道</p>
          <div class="pager-wrap">
            <AppPager :meta="meta" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.ch-tag {
  display: inline-block;
  margin-right: 6px;
  padding: 1px 6px;
  border-radius: 4px;
  background: #eef4ff;
  color: var(--blue);
  font-size: 12px;
  font-weight: 700;
  vertical-align: 1px;
}
</style>

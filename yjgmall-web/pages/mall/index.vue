<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '优质产品' })

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()
const page = computed(() => Number(route.query.page || 1))
const q = computed(() => (route.query.q as string) || '')
const category = computed(() => route.query.category as string | undefined)
const tab = computed(() => (route.query.tab as string) || 'all')
const sort = computed(() => (route.query.sort as string) || '')
const draftQ = ref(q.value)

watch(q, (v) => { draftQ.value = v })

const { data, pending } = await useAsyncData(
  () => `mall-${page.value}-${q.value}-${category.value}-${tab.value}-${sort.value}`,
  () =>
    $fetch(`${config.public.apiBase}/mall`, {
      query: {
        page: page.value,
        q: q.value || undefined,
        category: category.value || undefined,
        tab: tab.value === 'all' ? undefined : tab.value,
        sort: sort.value || undefined,
      },
    }),
  { watch: [page, q, category, tab, sort] },
)

const items = computed(() => (data.value as any)?.data || [])
const meta = computed(() => (data.value as any)?.meta || null)
const floors = computed(() => (data.value as any)?.floors || [])
const categories = computed(() => (data.value as any)?.categories || [])
const brands = computed(() => (data.value as any)?.brands || [])
const hot = computed(() => (data.value as any)?.hot || [])
const filterLabel = computed(() => (data.value as any)?.filter_label || '')
const activeRoot = computed(() => (data.value as any)?.active_root_id)
const showFloors = computed(() => !q.value && !category.value && tab.value === 'all' && !sort.value && page.value === 1)
const hasFilter = computed(() => !!(q.value || category.value || (tab.value && tab.value !== 'all') || sort.value))

function catOn(c: any) {
  const id = String(category.value || '')
  if (!id) return false
  if (String(c.id) === id) return true
  if (activeRoot.value != null && String(activeRoot.value) === String(c.id)) return true
  return (c.children || []).some((ch: any) => String(ch.id) === id)
}

const SAFE_THUMB = '/img/logo-yjg.png'

function productThumb(p: any, _i = 0) {
  const t = String(p?.thumb || '')
  if (!t || /\/img\/products\/p\d+/i.test(t) || /\.svg($|\?)/i.test(t) || /placehold/i.test(t)) return SAFE_THUMB
  return t
}

function brandLogo(b: any, _i = 0) {
  const t = String(b?.logo || '')
  if (!t || /\/img\/products\/p\d+/i.test(t) || /\.svg($|\?)/i.test(t) || /placehold/i.test(t)) return SAFE_THUMB
  return t
}

function patchQuery(extra: Record<string, any>) {
  const next: Record<string, any> = {
    q: q.value || undefined,
    category: category.value || undefined,
    tab: tab.value === 'all' ? undefined : tab.value,
    sort: sort.value || undefined,
    ...extra,
  }
  Object.keys(next).forEach((k) => {
    if (next[k] === '' || next[k] === undefined) delete next[k]
  })
  router.push({ path: '/mall', query: next })
}

function search() {
  patchQuery({ q: draftQ.value || undefined, page: undefined })
}

const listTitle = computed(() => {
  if (filterLabel.value && hasFilter.value) return filterLabel.value
  if (q.value) return `搜索「${q.value}」`
  if (category.value) return '分类商品'
  if (tab.value === 'new') return '新品上市'
  if (tab.value === 'hot') return '本月热销'
  if (tab.value === 'deal') return '今日特价'
  return '全部商品'
})
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <button type="button" :class="{ on: tab === 'all' && !sort }" @click="patchQuery({ tab: undefined, sort: undefined, page: undefined })">全部商品</button>
          <button type="button" :class="{ on: tab === 'new' }" @click="patchQuery({ tab: 'new', sort: undefined, page: undefined })">新品上市</button>
          <button type="button" :class="{ on: tab === 'hot' }" @click="patchQuery({ tab: 'hot', sort: undefined, page: undefined })">热销优选</button>
          <button type="button" :class="{ on: tab === 'deal' }" @click="patchQuery({ tab: 'deal', sort: undefined, page: undefined })">今日特价</button>
          <button type="button" :class="{ on: sort === 'price_asc' }" @click="patchQuery({ sort: 'price_asc', tab: undefined, page: undefined })">价格从低到高</button>
          <button type="button" :class="{ on: sort === 'sales' }" @click="patchQuery({ sort: 'sales', tab: undefined, page: undefined })">销量优先</button>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>选品提示：</b>
        精选有机硅标品与热销货源，按分类比价下单，快速匹配规格与供货商家。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="sec-head">
          <div>
            <h2>优质产品</h2>
            <p class="sub">标品选购 · 热销优选，覆盖中间体、硅油、硅胶制品等核心品类</p>
          </div>
          <NuxtLink class="more-link" to="/sell">查看供应大厅 ›</NuxtLink>
        </div>

        <div class="banner-slot">
          <ChannelBanner position="mall_top" size="large" />
        </div>

        <div v-if="hot.length" class="blk" style="margin-bottom:16px">
          <div class="hot-strip">
            <b>热销推荐</b>
            <NuxtLink v-for="p in hot.slice(0, 10)" :key="'h'+p.id" :to="`/mall/${p.id}`">{{ p.title }}</NuxtLink>
            <NuxtLink class="more" to="/mall?tab=hot">更多 ›</NuxtLink>
          </div>
        </div>

        <div v-if="showFloors && brands.length" class="blk" style="margin-bottom:16px">
          <div class="b-head">
            <b>知名品牌</b>
            <NuxtLink class="more-link" to="/mall?tab=hot">更多 ›</NuxtLink>
          </div>
          <div class="brand-grid">
            <NuxtLink
              v-for="(b, i) in brands"
              :key="b.id || i"
              class="brand-item"
              :to="{ path: '/mall', query: { q: b.name } }"
            >
              <img :src="brandLogo(b, Number(i))" :alt="b.name" loading="lazy">
              <span>{{ b.name }}</span>
            </NuxtLink>
          </div>
        </div>

        <template v-if="showFloors">
          <PortalFloor
            v-for="(floor, idx) in floors"
            :key="floor.category?.id || idx"
            :floor="floor"
            base-path="/mall"
          />
        </template>

        <div class="ch-layout" style="margin-top:16px">
          <aside class="blk side-nav">
            <div class="sn-head">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
              商品分类
            </div>
            <div class="sn-body">
              <NuxtLink to="/mall" :class="{ on: !category }">全部商品</NuxtLink>
              <template v-for="c in categories" :key="c.id">
                <NuxtLink
                  :class="{ on: catOn(c) }"
                  :to="{ path: '/mall', query: { category: c.id } }"
                >{{ c.name }}</NuxtLink>
                <NuxtLink
                  v-for="ch in c.children || []"
                  :key="ch.id"
                  class="child"
                  :class="{ on: String(category) === String(ch.id) }"
                  :to="{ path: '/mall', query: { category: ch.id } }"
                >{{ ch.name }}</NuxtLink>
              </template>
            </div>
          </aside>

          <div style="min-width:0;display:flex;flex-direction:column;gap:14px">
            <div class="blk">
              <div class="b-head">
                <b>筛选与搜索</b>
                <NuxtLink v-if="hasFilter" class="more-link" to="/mall">清除筛选 ›</NuxtLink>
              </div>
              <div class="filter-tools">
                <form class="kw" @submit.prevent="search">
                  <input v-model="draftQ" placeholder="搜商品名称 / 型号">
                  <button type="submit">搜索</button>
                </form>
                <div class="chips">
                  <button type="button" class="chip" :class="{ active: tab === 'all' && !sort }" @click="patchQuery({ tab: undefined, sort: undefined, page: undefined })">全部</button>
                  <button type="button" class="chip" :class="{ active: tab === 'new' }" @click="patchQuery({ tab: 'new', sort: undefined, page: undefined })">新品</button>
                  <button type="button" class="chip" :class="{ active: tab === 'hot' }" @click="patchQuery({ tab: 'hot', sort: undefined, page: undefined })">热销</button>
                  <button type="button" class="chip" :class="{ active: tab === 'deal' }" @click="patchQuery({ tab: 'deal', sort: undefined, page: undefined })">特价</button>
                  <button type="button" class="chip" :class="{ active: sort === 'price_asc' }" @click="patchQuery({ sort: 'price_asc', tab: undefined, page: undefined })">价格↑</button>
                  <button type="button" class="chip" :class="{ active: sort === 'price_desc' }" @click="patchQuery({ sort: 'price_desc', tab: undefined, page: undefined })">价格↓</button>
                </div>
              </div>
            </div>

            <div class="list-panel">
              <div class="lp-hd">
                <h2>{{ listTitle }}</h2>
                <span v-if="meta?.total != null" class="meta">共 {{ meta.total }} 件</span>
              </div>
              <p v-if="pending" class="empty">加载中…</p>
              <div v-else class="goods-grid">
                <div
                  v-for="(item, i) in items"
                  :key="item.id"
                  class="g-card"
                >
                  <NuxtLink class="g-img" :to="`/mall/${item.id}`"><img :src="productThumb(item, Number(item.id || i))" :alt="item.title" loading="lazy"></NuxtLink>
                  <NuxtLink class="g-name" :to="`/mall/${item.id}`">{{ item.title }}</NuxtLink>
                  <NuxtLink class="g-price" :to="`/mall/${item.id}`"><small>¥</small>{{ item.price }}</NuxtLink>
                  <div class="g-meta">
                    <NuxtLink
                      v-if="(item.company_slug || item.user?.company_slug) && (item.company_name || item.user?.company_name)"
                      class="g-co"
                      :to="`/companies/${item.company_slug || item.user.company_slug}`"
                    >{{ item.company_name || item.user.company_name }}</NuxtLink>
                    <span v-else class="g-co">{{ item.company_name || item.user?.company_name || '—' }}</span>
                    <span>已售 {{ item.sales || 0 }}</span>
                  </div>
                </div>
              </div>
              <p v-if="!pending && !items.length" class="empty">该分类下暂无商品</p>
              <div class="pager-wrap">
                <AppPager :meta="meta" />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

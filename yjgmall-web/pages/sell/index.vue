<script setup lang="ts">
definePageMeta({ layout: 'channel', scrollToTop: false })
useHead({ title: '好物商城' })

const route = useRoute()
const router = useRouter()
const config = useRuntimeConfig()

const page = computed(() => Number(route.query.page || 1))
const category = computed(() => route.query.category as string | undefined)
const q = computed(() => (route.query.q as string) || '')
const area = computed(() => (route.query.area as string) || '')
const sort = computed(() => (route.query.sort as string) || 'refresh')
const days = computed(() => (route.query.days as string) || '')
const view = computed(() => (route.query.view as string) || 'list')
const elite = computed(() => route.query.elite === '1')
const vip = computed(() => route.query.vip === '1')
const priced = computed(() => route.query.priced === '1')

const { data } = await useAsyncData(
  () => `sell-${page.value}-${category.value}-${q.value}-${area.value}-${sort.value}-${days.value}-${view.value}-${elite.value}-${vip.value}-${priced.value}`,
  () =>
    $fetch(`${config.public.apiBase}/sell`, {
      query: {
        page: page.value,
        category: category.value,
        q: q.value || undefined,
        area: area.value || undefined,
        sort: sort.value,
        days: days.value || undefined,
        view: view.value,
        elite: elite.value ? 1 : undefined,
        vip: vip.value ? 1 : undefined,
        priced: priced.value ? 1 : undefined,
      },
    }),
  { watch: [page, category, q, area, sort, days, view, elite, vip, priced] },
)

const items = computed(() => (data.value as any)?.data || [])
const meta = computed(() => (data.value as any)?.meta || null)
const categories = computed(() => (data.value as any)?.categories || [])
const areas = computed(() => (data.value as any)?.filters?.areas || [])
const floors = computed(() => (data.value as any)?.floors || [])
const eliteItems = computed(() => (data.value as any)?.elite || [])
const eliteAds = computed(() => ((data.value as any)?.elite_ads || []) as any[])
const eliteRow = computed(() => {
  const bySlot = new Map<number, any>()
  for (const a of eliteAds.value) {
    const slot = Number(a?.slot)
    if (slot >= 1 && slot <= 4 && !bySlot.has(slot)) bySlot.set(slot, a)
  }
  const supplies = [...eliteItems.value]
  const row: any[] = []
  let si = 0
  for (let slot = 1; slot <= 4; slot++) {
    const ad = bySlot.get(slot)
    if (ad) {
      const link = String(ad.link || '').trim()
      const usable = link && link !== '#' && link !== '/'
      row.push({
        kind: 'ad',
        id: ad.id,
        title: ad.title || '广告',
        image: ad.image,
        link: usable ? link : '',
        internal: usable && link.startsWith('/') && !link.startsWith('//'),
      })
    } else if (supplies[si]) {
      row.push({ kind: 'supply', ...supplies[si++] })
    }
  }
  return row
})
const filterLabel = computed(() => String((data.value as any)?.filter_label || ''))

const draftQ = ref(q.value)
watch(q, (v) => { draftQ.value = v })
const selected = ref<number[]>([])

const SAFE_THUMB = '/img/logo-yjg.png'

/** 大厅态：展示推荐 + 分类楼层；筛选态只保留侧栏 + 列表 */
const showFloors = computed(() =>
  !category.value && !q.value && !area.value && !days.value
  && !elite.value && !vip.value && !priced.value && page.value === 1 && view.value === 'list',
)

const isFiltered = computed(() => !showFloors.value)

function findCatName(id: string | undefined): string {
  if (!id) return ''
  for (const c of categories.value) {
    if (String(c.id) === id) return String(c.name || '')
    for (const ch of c.children || []) {
      if (String(ch.id) === id) return String(ch.name || '')
    }
  }
  return filterLabel.value || ''
}

const currentCatName = computed(() => findCatName(category.value))

const listTitle = computed(() => {
  if (currentCatName.value) return currentCatName.value
  if (q.value) return `搜索「${q.value}」`
  if (elite.value) return '为您推荐'
  if (vip.value) return 'VIP供应'
  if (priced.value) return '标价货源'
  if (view.value === 'pic') return '图片供应'
  if (area.value) return `${area.value}供应`
  return '供应列表'
})

const contextText = computed(() => {
  const parts: string[] = []
  if (currentCatName.value) parts.push(currentCatName.value)
  if (q.value) parts.push(`关键词「${q.value}」`)
  if (area.value) parts.push(area.value)
  if (elite.value) parts.push('仅推荐')
  if (vip.value) parts.push('仅 VIP')
  if (priced.value) parts.push('仅标价')
  if (view.value === 'pic') parts.push('图片模式')
  if (days.value) parts.push(`${days.value}天内`)
  return parts.length ? parts.join(' · ') : '筛选结果'
})

const dayOptions = [
  { v: '', label: '不限' },
  { v: '1', label: '1天内' },
  { v: '3', label: '3天内' },
  { v: '7', label: '7天内' },
  { v: '15', label: '15天内' },
  { v: '30', label: '30天内' },
]

const sortOptions = [
  { v: 'refresh', label: '默认' },
  { v: 'price_desc', label: '价格↓' },
  { v: 'price_asc', label: '价格↑' },
  { v: 'vip', label: 'VIP优先' },
  { v: 'hits', label: '人气' },
]

const regionShortcuts = ['广东', '浙江', '江苏', '山东', '安徽', '上海', '福建', '江西', '湖北', '四川']

function patchQuery(extra: Record<string, any>) {
  const next: Record<string, any> = {
    category: category.value,
    q: q.value || undefined,
    area: area.value || undefined,
    sort: sort.value || undefined,
    days: days.value || undefined,
    view: view.value !== 'list' ? view.value : undefined,
    elite: elite.value ? '1' : undefined,
    vip: vip.value ? '1' : undefined,
    priced: priced.value ? '1' : undefined,
    ...extra,
  }
  Object.keys(next).forEach((k) => {
    if (next[k] === '' || next[k] === undefined || next[k] === false) delete next[k]
  })
  router.push({ path: '/sell', query: next })
}

function applySearch() {
  patchQuery({ q: draftQ.value || undefined, page: undefined })
}

function setView(v: string) {
  if (v === 'vip') patchQuery({ view: 'list', vip: '1', priced: undefined, elite: undefined, page: undefined })
  else if (v === 'priced') patchQuery({ view: 'list', priced: '1', vip: undefined, page: undefined })
  else if (v === 'pic') patchQuery({ view: 'pic', priced: undefined, vip: undefined, page: undefined })
  else if (v === 'elite') patchQuery({ view: 'list', elite: '1', vip: undefined, priced: undefined, page: undefined })
  else patchQuery({ view: 'list', priced: undefined, vip: undefined, elite: undefined, page: undefined })
}

function goHub() {
  router.push({ path: '/sell' })
}

function selectCategory(id?: string | number) {
  patchQuery({
    category: id != null ? String(id) : undefined,
    page: undefined,
  })
}

function toggle(id: number) {
  if (selected.value.includes(id)) selected.value = selected.value.filter((x) => x !== id)
  else if (selected.value.length < 5) selected.value = [...selected.value, id]
}

function goCompare() {
  if (selected.value.length < 2) return
  router.push({ path: '/sell/compare', query: { ids: selected.value.join(',') } })
}

function thumbOf(item: any, _i = 0) {
  const t = String(item?.thumb || '')
  if (!t || /\/img\/products\/p\d+/i.test(t) || t.includes('.svg') || /placehold/i.test(t)) return SAFE_THUMB
  return t
}

const activeView = computed(() => {
  if (elite.value) return 'elite'
  if (vip.value) return 'vip'
  if (priced.value) return 'priced'
  if (view.value === 'pic') return 'pic'
  return 'list'
})

function catActive(c: any) {
  const id = String(category.value || '')
  if (!id) return false
  if (String(c.id) === id) return true
  return (c.children || []).some((ch: any) => String(ch.id) === id)
}

function scrollToBrowse() {
  if (!import.meta.client) return
  const el = document.getElementById('sell-browse')
  if (!el) return
  const top = el.getBoundingClientRect().top + window.scrollY - 56
  window.scrollTo({ top: Math.max(0, top), behavior: 'smooth' })
}

/** 从首页子分类进来时，页头、频道条和蓝色提示先滚出画面，直接停在产品分类。 */
function scrollPastTop() {
  if (!import.meta.client) return
  const el = document.getElementById('sell-start')
  if (!el) return
  const nav = document.querySelector('.menu-bar')
  const navH = nav ? nav.getBoundingClientRect().height : 48
  const top = el.getBoundingClientRect().top + window.scrollY - navH
  window.scrollTo({ top: Math.max(0, top), behavior: 'auto' })
}

/** 进入筛选态时滚到列表区，避免「推荐/楼层突然消失」的错乱感 */
const browseReady = ref(false)
function landOnList() {
  if (!isFiltered.value) return
  scrollPastTop()
  requestAnimationFrame(() => scrollPastTop())
  window.setTimeout(scrollPastTop, 60)
  window.setTimeout(scrollPastTop, 280)
}
onMounted(() => {
  nextTick(() => {
    browseReady.value = true
    landOnList()
  })
})
if (import.meta.client) {
  watch(() => route.fullPath, () => nextTick(() => landOnList()), { flush: 'post' })
}
watch(
  () => [category.value, q.value, elite.value, vip.value, priced.value, view.value, area.value, days.value].join('|'),
  async () => {
    if (!browseReady.value || showFloors.value) return
    await nextTick()
    scrollToBrowse()
  },
)
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <button type="button" :class="{ on: activeView === 'list' && !isFiltered }" @click="goHub()">全部供应</button>
          <button type="button" :class="{ on: activeView === 'priced' }" @click="setView('priced')">标价货源</button>
          <button type="button" :class="{ on: activeView === 'pic' }" @click="setView('pic')">图片模式</button>
          <button type="button" :class="{ on: activeView === 'vip' }" @click="setView('vip')">VIP供应</button>
          <button type="button" :class="{ on: activeView === 'elite' }" @click="setView('elite')">推荐</button>
          <NuxtLink to="/buy">求购大厅</NuxtLink>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>供应提示：</b>
        <template v-if="isFiltered">
          当前正在查看筛选结果；推荐供应与分类楼层仅在大厅展示，可点「返回大厅」继续逛楼层。
        </template>
        <template v-else>
          按分类浏览企业现货，支持地区、更新时间与多货源对比，发布供应请登录商务中心。
        </template>
      </div>
    </div>

    <div id="sell-start" class="section">
      <div class="container">
        <div class="sec-head">
          <div>
            <h2>好物商城</h2>
            <p class="sub">
              <template v-if="isFiltered">已进入筛选列表 · {{ contextText }}</template>
              <template v-else>企业现货 · 询盘报价，按品类与地区快速匹配供应商</template>
            </p>
          </div>
          <NuxtLink class="more-link" to="/member/supplies/create">免费发布供应 ›</NuxtLink>
        </div>

        <!-- 筛选态：状态条，说明楼层为何隐藏 -->
        <div v-if="isFiltered" class="blk sell-ctx" style="margin-bottom:16px">
          <div class="sell-ctx-inner">
            <div class="sell-ctx-l">
              <span class="sell-ctx-k">正在浏览</span>
              <strong>{{ contextText }}</strong>
              <span v-if="meta?.total != null" class="sell-ctx-n">共 {{ meta.total }} 条</span>
            </div>
            <div class="sell-ctx-r">
              <button type="button" class="btn-soft" @click="goHub">返回大厅</button>
              <button type="button" class="btn-go" @click="scrollToBrowse">查看列表</button>
            </div>
          </div>
        </div>

        <!-- 大厅：推荐 + 楼层 -->
        <template v-if="showFloors && eliteRow.length">
          <div class="blk" style="margin-bottom:16px">
            <div class="b-head">
              <b>为您推荐</b>
              <span class="b-acts">
                <NuxtLink class="more-link" to="/ads?place=sell_elite_1">购买广告位</NuxtLink>
                <NuxtLink class="more-link" :to="{ path: '/sell', query: { elite: '1' } }">更多 ›</NuxtLink>
              </span>
            </div>
            <div class="goods-grid">
              <template v-for="(item, i) in eliteRow" :key="(item.kind || 's') + '-' + (item.id || i)">
                <a
                  v-if="item.kind === 'ad' && item.link && !item.internal"
                  class="g-card"
                  :href="item.link"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  <div class="g-img">
                    <img :src="item.image" :alt="item.title" loading="lazy">
                    <em class="ad-mark">广告</em>
                  </div>
                  <div class="g-name">{{ item.title }}</div>
                </a>
                <NuxtLink
                  v-else-if="item.kind === 'ad' && item.internal"
                  class="g-card"
                  :to="item.link"
                >
                  <div class="g-img">
                    <img :src="item.image" :alt="item.title" loading="lazy">
                    <em class="ad-mark">广告</em>
                  </div>
                  <div class="g-name">{{ item.title }}</div>
                </NuxtLink>
                <div v-else-if="item.kind === 'ad'" class="g-card">
                  <div class="g-img">
                    <img :src="item.image" :alt="item.title" loading="lazy">
                    <em class="ad-mark">广告</em>
                  </div>
                  <div class="g-name">{{ item.title }}</div>
                </div>
                <NuxtLink
                  v-else
                  class="g-card"
                  :to="`/sell/${item.id}`"
                >
                  <div class="g-img"><img :src="thumbOf(item, Number(i))" :alt="item.title" loading="lazy"></div>
                  <div class="g-name"><span class="tag elite">荐</span>{{ item.title }}</div>
                  <div class="g-price">{{ item.price ? `¥${item.price}` : '面议' }}<small v-if="item.unit">/{{ item.unit }}</small></div>
                  <div class="g-meta">
                    <span>{{ item.area || '地区不限' }}</span>
                    <span class="g-co">{{ item.user?.company_name || item.user?.name || '' }}</span>
                  </div>
                </NuxtLink>
              </template>
            </div>
          </div>
        </template>

        <template v-if="showFloors">
          <PortalFloor
            v-for="(floor, idx) in floors"
            :key="floor.category?.id || idx"
            :floor="floor"
            base-path="/sell"
          />
        </template>

        <!-- 侧栏 + 筛选 + 列表（筛选后滚到此处） -->
        <div id="sell-browse" class="ch-layout sell-browse" style="margin-top:16px">
          <aside class="blk side-nav">
            <div class="sn-head">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
              供应分类
            </div>
            <div class="sn-body">
              <a
                href="/sell"
                :class="{ on: !category }"
                @click.prevent="goHub()"
              >全部分类</a>
              <template v-for="c in categories" :key="c.id">
                <a
                  href="#"
                  :class="{ on: catActive(c) }"
                  @click.prevent="selectCategory(c.id)"
                >{{ c.name }}</a>
                <a
                  v-for="ch in c.children || []"
                  :key="ch.id"
                  href="#"
                  class="child"
                  :class="{ on: String(category) === String(ch.id) }"
                  @click.prevent="selectCategory(ch.id)"
                >{{ ch.name }}</a>
              </template>
            </div>
          </aside>

          <div class="main-col" style="min-width:0;display:flex;flex-direction:column;gap:14px">
            <div class="blk">
              <div class="b-head">
                <b>筛选条件</b>
                <button v-if="isFiltered" type="button" class="more-link" style="background:none;border:0;cursor:pointer;font-family:inherit" @click="goHub">清除筛选 · 返回大厅 ›</button>
                <span v-else class="hint">地区 / 时间 / 排序</span>
              </div>
              <div class="filter-tools">
                <form class="kw" @submit.prevent="applySearch">
                  <input v-model="draftQ" placeholder="搜供应关键词 / 型号">
                  <button type="submit">搜索</button>
                </form>
                <button class="btn-soft" type="button" :disabled="selected.length < 2" @click="goCompare">
                  对比（{{ selected.length }}/5）
                </button>
              </div>
              <div class="filter-row">
                <b>地区</b>
                <button type="button" class="chip" :class="{ active: !area }" @click="patchQuery({ area: undefined, page: undefined })">全部</button>
                <button
                  v-for="r in regionShortcuts"
                  :key="r"
                  type="button"
                  class="chip"
                  :class="{ active: area === r }"
                  @click="patchQuery({ area: r, page: undefined })"
                >{{ r }}</button>
                <select
                  :value="area"
                  @change="patchQuery({ area: ($event.target as HTMLSelectElement).value || undefined, page: undefined })"
                >
                  <option value="">更多地区…</option>
                  <option v-for="a in areas" :key="a" :value="a">{{ a }}</option>
                </select>
              </div>
              <div class="filter-row">
                <b>更新</b>
                <button
                  v-for="d in dayOptions"
                  :key="'d'+d.v"
                  type="button"
                  class="chip"
                  :class="{ active: days === d.v }"
                  @click="patchQuery({ days: d.v || undefined, page: undefined })"
                >{{ d.label }}</button>
              </div>
              <div class="filter-row">
                <b>排序</b>
                <button
                  v-for="s in sortOptions"
                  :key="s.v"
                  type="button"
                  class="chip"
                  :class="{ active: sort === s.v }"
                  @click="patchQuery({ sort: s.v, page: undefined })"
                >{{ s.label }}</button>
              </div>
            </div>

            <div class="list-panel">
              <div class="lp-hd">
                <h2>{{ listTitle }}</h2>
                <span v-if="meta?.total != null" class="meta">共 {{ meta.total }} 条</span>
              </div>

              <div v-if="view === 'pic'" class="goods-grid">
                <template v-for="(item, i) in items" :key="item.is_slot ? 'slot-' + item.slot_page : item.id">
                  <div v-if="item.is_slot" class="g-card slot-card">
                    <NuxtLink :to="item.slot_to" class="g-img slot-img" aria-label="推广位">
                      <span class="hi">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                      </span>
                    </NuxtLink>
                    <NuxtLink :to="item.slot_to" class="g-name" style="text-decoration:none;color:inherit">
                      <span class="tag promo">推广</span>广告位 · 虚位以待
                    </NuxtLink>
                    <div class="g-meta"><span>第 {{ item.slot_page }} 页第 1 条</span><span class="g-co">立即咨询</span></div>
                  </div>
                  <div v-else class="g-card" style="cursor:default">
                    <label class="pick" @click.stop>
                      <input type="checkbox" :checked="selected.includes(item.id)" @change="toggle(item.id)">
                    </label>
                    <NuxtLink :to="`/sell/${item.id}`" style="text-decoration:none;color:inherit;display:contents">
                      <div class="g-img"><img :src="thumbOf(item, i)" :alt="item.title" loading="lazy"></div>
                      <div class="g-name">
                        <span v-if="item.is_promoted" class="tag promo">推广</span>
                        <MemberBadge v-if="item.user?.member_badge" :grade="item.user.member_grade" :label="item.user.member_badge" />
                        <span v-if="item.is_elite" class="tag elite">荐</span>
                        <span v-if="item.level > 0" class="tag vip">VIP</span>
                        {{ item.title }}
                      </div>
                      <div class="g-price">{{ item.price ? `¥${item.price}` : '面议' }}<small v-if="item.unit && item.price">/{{ item.unit }}</small></div>
                      <div class="g-meta">
                        <span>{{ item.area || '地区不限' }}</span>
                        <span class="g-co">{{ item.user?.company_name || item.user?.name || '-' }}</span>
                      </div>
                    </NuxtLink>
                  </div>
                </template>
                <p v-if="!items.length" class="empty" style="grid-column:1/-1">暂无供应信息</p>
              </div>

              <template v-else>
                <template v-for="(item, i) in items" :key="item.is_slot ? 'slot-' + item.slot_page : item.id">
                  <div v-if="item.is_slot" class="lp-item slot-row">
                    <NuxtLink class="thumb slot-thumb" :to="item.slot_to" aria-label="推广位">
                      <span class="hi">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                      </span>
                    </NuxtLink>
                    <div class="body">
                      <NuxtLink class="tt" :to="item.slot_to" style="text-decoration:none">
                        <span class="tag promo">推广</span>广告位 · 虚位以待
                      </NuxtLink>
                      <div class="sum">供应列表第 {{ item.slot_page }} 页第 1 条，购买后展示您的供应信息</div>
                      <div class="foot">
                        <NuxtLink class="slot-go" :to="item.slot_to">立即咨询</NuxtLink>
                      </div>
                    </div>
                  </div>
                  <div v-else class="lp-item" style="align-items:center">
                    <label class="pick" style="position:static;margin-right:4px;flex-shrink:0">
                      <input type="checkbox" :checked="selected.includes(item.id)" @change="toggle(item.id)">
                    </label>
                    <NuxtLink class="thumb" :to="`/sell/${item.id}`">
                      <img :src="thumbOf(item, i)" :alt="item.title" loading="lazy">
                    </NuxtLink>
                    <div class="body">
                      <NuxtLink class="tt" :to="`/sell/${item.id}`" style="text-decoration:none">
                        <span v-if="item.is_promoted" class="tag promo">推广</span>
                        <MemberBadge v-if="item.user?.member_badge" :grade="item.user.member_grade" :label="item.user.member_badge" />
                        <span v-if="item.is_elite" class="tag elite">荐</span>
                        <span v-if="item.level > 0" class="tag vip">VIP</span>
                        <span v-if="item.is_urgent" class="tag urg">急</span>
                        {{ item.title }}
                      </NuxtLink>
                      <div class="sum">{{ item.user?.company_name || item.user?.name || '供应商' }} · 起订 {{ item.min_amount || '-' }} · 供货 {{ item.amount || '-' }}</div>
                      <div class="foot">
                        <span>{{ item.area || '地区不限' }}</span>
                        <span>{{ item.refreshed_at?.slice(0, 10) || '-' }}</span>
                        <span class="price" :class="{ ask: !item.price }">{{ item.price ? `¥${item.price}/${item.unit || ''}` : '面议' }}</span>
                      </div>
                    </div>
                  </div>
                </template>
                <p v-if="!items.length" class="empty">暂无供应信息</p>
              </template>

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

<style scoped>
.sell-ctx { padding: 12px 16px; }
.b-acts { display: flex; align-items: center; gap: 14px; }
.g-card .g-img { position: relative; }
.ad-mark {
  position: absolute;
  top: 6px;
  left: 6px;
  z-index: 2;
  background: #e10600;
  color: #fff;
  font-style: normal;
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
  padding: 3px 6px;
  border-radius: 4px;
}
.slot-thumb, .slot-img {
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8faff;
  border: 1.5px dashed #b9cdf6;
  color: var(--blue);
}
.slot-thumb .hi, .slot-img .hi {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #eaf1fe;
  display: grid;
  place-items: center;
}
.slot-thumb svg, .slot-img svg { width: 18px; height: 18px; display: block; }
.slot-go {
  margin-left: auto;
  font-size: 13px;
  font-weight: 700;
  color: var(--blue);
  text-decoration: none;
}
.slot-go:hover { color: var(--blue-deep); }
.slot-card {
  border: 1.5px dashed #b9cdf6;
  background: #f8faff;
  box-shadow: none;
}
.sell-ctx-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}
.sell-ctx-l {
  display: flex;
  align-items: baseline;
  gap: 10px;
  flex-wrap: wrap;
  min-width: 0;
}
.sell-ctx-k {
  font-size: 12px;
  font-weight: 700;
  color: var(--blue);
  background: #EEF4FF;
  padding: 3px 10px;
  border-radius: 100px;
  flex-shrink: 0;
}
.sell-ctx-l strong {
  font-size: 15px;
  font-weight: 800;
  color: var(--ink);
}
.sell-ctx-n {
  font-size: 12.5px;
  color: var(--ink-3);
}
.sell-ctx-r {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}
.sell-browse {
  scroll-margin-top: 60px;
}
</style>

<script setup lang="ts">
/**
 * 频道楼层 · 对齐首页 portal-design
 * sell/mall → mat-row 左侧渐变 + 商品卡
 * companies → company-grid 名企卡
 * buy → supply-box 列表
 */
import { FLOOR_GROUPS } from '~/utils/floorGroups'
import { floorLeftImage, companyShortName, floorSideGrad, FLOOR_SLOGANS } from '~/utils/floorMeta'

const props = withDefaults(defineProps<{
  floor: any
  basePath?: '/mall' | '/sell' | '/companies' | '/buy'
  listPath?: string
  moreLabel?: string
  nameSuffix?: string
}>(), {
  basePath: '/sell',
  listPath: '',
  moreLabel: '查看更多',
  nameSuffix: '',
})

const catName = computed(() => props.floor?.category?.name || '楼层')
const catId = computed(() => props.floor?.category?.id)
const channel = computed(() => props.basePath.replace('/', '') as 'sell' | 'mall' | 'buy' | 'companies')

const sideGrad = computed(() => floorSideGrad(props.floor))

const titleHtml = computed(() => {
  const n = `${String(catName.value || '').trim()}${props.nameSuffix || ''}`
  if (!n) return ''
  if (n.includes('/')) return n.replace(/\s*\/\s*/, '<br>/ ')
  return n
})

const navPath = computed(() => props.listPath || props.basePath)
const featTo = computed(() =>
  catId.value
    ? { path: navPath.value, query: { category: catId.value } }
    : { path: navPath.value },
)

const rawItems = computed<any[]>(() => {
  const f = props.floor || {}
  let list: any[]
  if (props.basePath === '/companies') list = Array.isArray(f.companies) ? f.companies : []
  else if (props.basePath === '/mall') list = Array.isArray(f.products) ? f.products : (Array.isArray(f.items) ? f.items : [])
  else if (props.basePath === '/buy') list = Array.isArray(f.items) ? f.items : []
  else list = Array.isArray(f.items) ? f.items : (Array.isArray(f.supplies) ? f.supplies : [])
  if (props.basePath === '/sell') list = mergeEliteSlots(list, f.elite_ads || [])
  if (Array.isArray(f.ads) && f.ads.length) list = mergeSampleAds(list, f.ads)
  return list
})

function mergeSampleAds(products: any[], ads: any[]) {
  const slots: Array<any | null> = [null, null, null, null, null]
  for (const ad of ads) {
    const slot = Number(ad?.slot)
    if (slot < 1 || slot > 5 || !ad?.image || slots[slot - 1]) continue
    slots[slot - 1] = {
      id: `ad-${ad.id}`,
      title: ad.title || '推广',
      thumb: ad.image,
      link: String(ad.link || ''),
      is_ad: true,
    }
  }
  let i = 0
  const head: any[] = []
  for (let n = 0; n < 5; n++) {
    if (slots[n]) head.push(slots[n])
    else if (products[i]) head.push(products[i++])
  }
  return [...head, ...products.slice(i)]
}

function mergeEliteSlots(supplies: any[], ads: any[]) {
  if (!Array.isArray(ads) || !ads.length) return supplies
  const slots: Array<any | null> = [null, null, null, null, null]
  for (const ad of ads) {
    const slot = Number(ad?.slot)
    if (slot < 1 || slot > 5 || !ad?.image || slots[slot - 1]) continue
    slots[slot - 1] = {
      id: `ad-${ad.id}`,
      title: ad.title || '广告',
      thumb: ad.image,
      link: String(ad.link || ''),
      is_ad: true,
    }
  }
  let i = 0
  for (let n = 0; n < 5; n++) {
    if (slots[n]) continue
    if (supplies[i]) slots[n] = supplies[i++]
  }
  return [...slots.filter(Boolean), ...supplies.slice(i)]
}

function itemTitle(it: any) {
  return String(it?.title || it?.name || '').trim()
}
function itemTo(it: any) {
  if (it?.is_ad) {
    const link = String(it.link || '').trim()
    if (link && link !== '#' && link !== '/') return link
    return featTo.value
  }
  if (props.basePath === '/companies') return `/companies/${it.slug || it.id}`
  if (props.basePath === '/mall') return `/mall/${it.id}`
  if (props.basePath === '/buy') return `/buy/${it.id}`
  return `/sell/${it.id}`
}
function shortTag(raw: string) {
  const s = String(raw || '').trim()
  if (!s) return ''
  const code = s.match(/[（(]([^）)]{1,8})[）)]/)
  if (code?.[1]) return code[1]
  return s.length > 8 ? s.slice(0, 8) : s
}

const sideTags = computed(() => {
  const kids = props.floor?.children || []
  if (kids.length) {
    return kids.slice(0, 8).map((ch: any) => ({
      id: ch.id,
      name: shortTag(ch.name),
      to: { path: navPath.value, query: { category: ch.id } },
    }))
  }
  const slogans = FLOOR_SLOGANS[catName.value] || []
  return slogans.slice(0, 4).map((s, i) => ({
    id: `s${i}`,
    name: shortTag(s),
    to: featTo.value,
  }))
})

const goodsLimit = computed(() => {
  switch (props.basePath) {
    case '/mall': return 5
    case '/buy': return 8
    case '/companies': return 4
    default: return 5
  }
})

function stockOf(it: any) {
  const raw = it?.stock ?? it?.amount ?? it?.quantity
  if (raw != null && raw !== '') {
    const t = String(raw).trim()
    if (/\D/.test(t)) return t
    const u = /台/.test(String(it.unit || '')) ? '台' : (it.unit || '吨')
    return `${t}${u}`
  }
  return '现货'
}
function pickupOf(it: any) {
  return String(it?.pickup || it?.delivery || '').trim() || '配送'
}

function companyChips(it: any) {
  const area = String(it?.area || '').trim()
  const mode = String(it?.business_mode || it?.company_type || '').trim()
  const raw = it?.tags
  const fromTags = (Array.isArray(raw) ? raw : String(raw || '').split(/[,，、|/]/))
    .map((s: any) => String(s).trim())
    .filter(Boolean)
  const fromBiz = fromTags.length
    ? []
    : String(it?.business || '').split(/[,，、;；|/]/).map((s) => s.trim()).filter((s) => s.length > 1 && s.length <= 8)
  const out: string[] = []
  const push = (s: string) => {
    const t = s.replace(/\s+/g, '')
    if (!t || t === '地区不限' || out.includes(t) || t.length > 8) return
    out.push(t)
  }
  if (area && area !== '地区不限') push(area)
  if (mode) push(mode)
  for (const t of [...fromTags, ...fromBiz]) push(t)
  return out.slice(0, 4)
}

const pageIndex = ref(0)
const pageAnimate = ref(true)
const paused = ref(false)
let pageTimer: ReturnType<typeof setInterval> | null = null
const PAGE_SIZE = 5
const PAGE_STEP_MS = 3600

function mapCard(it: any, i: number) {
    const title = itemTitle(it)
    const thumb = String(it.thumb || it.logo || '').trim()
    const hasPrice = it.price != null && it.price !== ''
    const demoImg = /\/img\/products\/p\d+\./i.test(thumb) || /\.svg($|\?)/i.test(thumb) || /placehold/i.test(thumb)
    return {
      key: `${props.basePath}-${it.id || i}`,
      id: it.id,
      title,
      shortName: companyShortName(it) || title,
      thumb: thumb && !demoImg ? thumb : '/img/logo-yjg.png',
      to: itemTo(it),
      price: hasPrice ? String(it.price) : '',
      unit: it.unit || '',
      area: String(it.area || it.region || '').trim() || '地区不限',
      stock: stockOf(it),
      pickup: pickupOf(it),
      business: String(it.business || it.intro || '').slice(0, 40),
      company: String(it.company_name || it.user?.company_name || '').trim(),
      companyTo: (it.company_slug || it.user?.company_slug)
        ? `/companies/${it.company_slug || it.user.company_slug}`
        : '',
      chips: props.basePath === '/companies' ? companyChips(it) : [],
      vip: !!(it.is_certified || it.vip || it.user?.is_vip),
      memberBadge: String(it.member_badge || it.user?.member_badge || ''),
      memberGrade: String(it.member_grade || it.user?.member_grade || ''),
      urgent: !!it.is_urgent,
      hot: !!it.is_hot,
      promoted: !!it.is_ad,
      qty: it.quantity ? `${it.quantity}${it.unit || ''}` : '',
      budget: it.budget ? `¥${it.budget}` : '',
    }
}

const mappedGoods = computed(() => rawItems.value.map(mapCard))
const goods = computed(() => mappedGoods.value.slice(0, goodsLimit.value))
const carouselPages = computed(() => {
  if (props.basePath !== '/mall') return [] as ReturnType<typeof mapCard>[][]
  const pages: ReturnType<typeof mapCard>[][] = []
  for (let i = 0; i < mappedGoods.value.length; i += PAGE_SIZE) {
    pages.push(mappedGoods.value.slice(i, i + PAGE_SIZE))
  }
  return pages
})
const slidePages = computed(() => {
  const pages = carouselPages.value
  if (pages.length <= 1) return pages
  return pages.concat([pages[0]])
})
const trackStyle = computed(() => {
  const style: Record<string, string> = { '--shift': String(pageIndex.value) }
  if (!pageAnimate.value) style.transition = 'none'
  return style
})

function tickPage() {
  const count = carouselPages.value.length
  if (paused.value || count <= 1) return
  pageIndex.value = pageIndex.value >= count ? 0 : pageIndex.value + 1
}

function onPageEnd(e: TransitionEvent) {
  if (e.propertyName !== 'transform' || e.target !== e.currentTarget) return
  if (pageIndex.value !== carouselPages.value.length) return
  pageAnimate.value = false
  pageIndex.value = 0
  requestAnimationFrame(() => {
    requestAnimationFrame(() => { pageAnimate.value = true })
  })
}

const floorRoot = ref<HTMLElement | null>(null)
let unbindPageWidth = () => {}

onMounted(() => {
  unbindPageWidth = bindMatPageWidth(floorRoot.value)
  if (props.basePath !== '/mall') return
  pageTimer = setInterval(tickPage, PAGE_STEP_MS)
})
onBeforeUnmount(() => {
  unbindPageWidth()
  if (!pageTimer) return
  clearInterval(pageTimer)
  pageTimer = null
})

const emptyTip = computed(() => {
  if (props.basePath === '/buy') return '该分类暂无采购信息'
  if (props.basePath === '/companies') return '该分类暂无入驻企业'
  if (props.basePath === '/mall') return '该分类暂无商品'
  return '该分类暂无供应'
})

function onImgErr(e: Event) {
  const el = e.target as HTMLImageElement
  if (!el || el.dataset.fb) return
  el.dataset.fb = '1'
  el.src = '/img/logo-yjg.png'
}

// keep FLOOR_GROUPS referenced for tree fallbacks in sideTags when needed
void FLOOR_GROUPS
</script>

<template>
  <!-- 供应 / 商城：首页材料楼层 -->
  <section
    v-if="channel === 'sell' || channel === 'mall'"
    ref="floorRoot"
    class="portal-floor pf-mat"
  >
    <div class="mat-row" @mouseenter="paused = true" @mouseleave="paused = false">
      <div class="mat-side" :style="{ background: sideGrad }">
        <b v-html="titleHtml"></b>
        <div class="m-tags">
          <NuxtLink
            v-for="tag in sideTags"
            :key="tag.id"
            :to="tag.to"
          >{{ tag.name }}</NuxtLink>
        </div>
        <NuxtLink class="more-btn" :to="featTo">{{ moreLabel }}</NuxtLink>
      </div>
      <div
        v-if="channel === 'mall' && slidePages.length"
        class="mat-viewport"
        :class="{ 'is-pages': carouselPages.length > 1 }"
      >
        <div class="mat-pages" :style="trackStyle" @transitionend="onPageEnd">
          <div v-for="(page, pi) in slidePages" :key="'p' + pi" class="mat-page">
            <div v-for="g in page" :key="g.key + '-' + pi" class="mat-card">
              <NuxtLink class="m-img" :to="g.to">
                <img :src="g.thumb" :alt="g.title" loading="lazy" @error="onImgErr">
                <em v-if="g.promoted" class="m-promo">推广</em>
              </NuxtLink>
              <NuxtLink class="m-name" :to="g.to">{{ g.title }}</NuxtLink>
              <NuxtLink class="m-price" :class="{ inquire: !g.price }" :to="g.to">
                <template v-if="g.price">¥{{ g.price }}<small v-if="g.unit">/{{ g.unit }}</small></template>
                <template v-else>面议</template>
              </NuxtLink>
              <NuxtLink v-if="g.company && g.companyTo" class="m-co" :to="g.companyTo" :title="g.company">{{ g.company }}</NuxtLink>
              <div v-else-if="g.company" class="m-co" :title="g.company">{{ g.company }}</div>
              <NuxtLink class="m-meta" :to="g.to">
                <span><i>发货地</i><em>{{ g.area }}</em></span>
                <span><i>库存</i><em>{{ g.stock }}</em></span>
                <span><i>提货</i><em>{{ g.pickup }}</em></span>
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
      <div v-else-if="goods.length" class="mat-grid">
        <div
          v-for="g in goods"
          :key="g.key"
          class="mat-card"
        >
          <NuxtLink class="m-img" :to="g.to">
            <img :src="g.thumb" :alt="g.title" loading="lazy" @error="onImgErr">
            <em v-if="g.promoted" class="m-promo">推广</em>
          </NuxtLink>
          <NuxtLink class="m-name" :to="g.to">{{ g.title }}</NuxtLink>
          <NuxtLink class="m-price" :class="{ inquire: !g.price }" :to="g.to">
            <template v-if="g.price">¥{{ g.price }}<small v-if="g.unit">/{{ g.unit }}</small></template>
            <template v-else>面议</template>
          </NuxtLink>
          <NuxtLink v-if="g.company && g.companyTo" class="m-co" :to="g.companyTo" :title="g.company">{{ g.company }}</NuxtLink>
          <div v-else-if="g.company" class="m-co" :title="g.company">{{ g.company }}</div>
          <NuxtLink class="m-meta" :to="g.to">
            <span><i>发货地</i><em>{{ g.area }}</em></span>
            <span><i>库存</i><em>{{ g.stock }}</em></span>
            <span><i>提货</i><em>{{ g.pickup }}</em></span>
          </NuxtLink>
        </div>
      </div>
      <p v-else class="pf-empty">{{ emptyTip }}</p>
    </div>
  </section>

  <!-- 名企：首页名企卡 -->
  <section
    v-else-if="channel === 'companies'"
    class="portal-floor pf-co"
  >
    <div class="pf-sec">
      <div class="pf-sec-hd is-tone" :style="{ background: sideGrad }">
        <h3>{{ catName }}</h3>
        <span class="muted">共 {{ rawItems.length }} 家</span>
        <NuxtLink class="more-link" :to="featTo">{{ moreLabel }} ›</NuxtLink>
      </div>
      <div v-if="sideTags.length" class="pf-kids">
        <NuxtLink v-for="tag in sideTags" :key="tag.id" :to="tag.to">{{ tag.name }}</NuxtLink>
      </div>
      <div v-if="goods.length" class="company-grid">
        <NuxtLink
          v-for="g in goods"
          :key="g.key"
          class="company"
          :to="g.to"
        >
          <div class="c-top">
            <div class="c-left">
              <div class="photo">
                <img :src="g.thumb" :alt="g.title" loading="lazy" @error="onImgErr">
              </div>
              <MemberBadge
                v-if="g.memberGrade && g.memberGrade !== 'normal'"
                class="c-grade"
                :grade="g.memberGrade"
                :label="g.memberBadge"
              />
            </div>
            <div class="c-info">
              <b>
                <span :title="g.title">{{ g.shortName }}</span>
                <i v-if="g.vip" class="vip">名企</i>
              </b>
              <div class="tags">
                <span v-for="t in g.chips" :key="t">{{ t }}</span>
              </div>
            </div>
          </div>
          <p class="desc">{{ g.business || '暂无企业简介' }}</p>
          <span class="shop">进入店铺</span>
        </NuxtLink>
      </div>
      <p v-else class="pf-empty">{{ emptyTip }}</p>
    </div>
  </section>

  <!-- 求购：首页供求列表 -->
  <section
    v-else
    class="portal-floor pf-buy"
  >
    <div class="supply-box buy">
      <div class="s-head">
        <b>{{ catName }}</b>
        <NuxtLink class="more-link" :to="featTo">{{ moreLabel }} ›</NuxtLink>
      </div>
      <div v-if="sideTags.length" class="pf-kids">
        <NuxtLink v-for="tag in sideTags" :key="tag.id" :to="tag.to">{{ tag.name }}</NuxtLink>
      </div>
      <RollList v-if="mappedGoods.length" :rows="mappedGoods" :page-size="3">
        <template #default="{ row }">
          <NuxtLink class="t" :to="row.to">
            <span v-if="row.hot" class="hot">热</span><span v-if="row.urgent" class="urg">急</span>{{ row.title }}
          </NuxtLink>
          <span class="tag">{{ row.area }}</span>
        </template>
      </RollList>
      <p v-else class="pf-empty">{{ emptyTip }}</p>
    </div>
  </section>
</template>

<style scoped>
.portal-floor { margin-bottom: 20px; }
.pf-empty {
  padding: 28px 16px;
  text-align: center;
  color: var(--ink-3);
  font-size: 13.5px;
  margin: 0;
  grid-column: 1 / -1;
}

/* —— 材料楼层（复用 portal-design .mat-* 语义，scoped 内补齐） —— */
.mat-row {
  display: grid;
  grid-template-columns: 170px 1fr;
  border-radius: var(--r-lg, 16px);
  overflow: hidden;
  background: var(--surface, #fff);
  box-shadow: var(--shadow-card);
}
.mat-side {
  color: #fff;
  padding: 20px 16px 22px;
  display: flex;
  flex-direction: column;
  min-height: 220px;
  box-sizing: border-box;
}
.mat-side b {
  font-size: 20px;
  font-weight: 800;
  line-height: 1.35;
  letter-spacing: .02em;
  color: #fff;
}
.mat-side .m-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 10px;
  margin-top: 10px;
  flex: 0 0 auto;
}
.mat-side .m-tags a {
  font-size: 13px;
  color: rgba(255, 255, 255, .88) !important;
  text-decoration: none;
  line-height: 1.35;
  font-weight: 500;
}
.mat-side .m-tags a:hover {
  color: #fff !important;
  text-decoration: underline;
}
.mat-side .more-btn {
  display: inline-block;
  align-self: flex-start;
  border: 1px solid rgba(255, 255, 255, .65);
  font-size: 12.5px;
  font-weight: 600;
  padding: 5px 16px;
  border-radius: 100px;
  color: #fff !important;
  margin-top: auto;
  padding-top: 5px;
  text-decoration: none;
}
.mat-side .m-tags + .more-btn {
  /* 标签与按钮之间再留一点空隙 */
  margin-top: auto;
  transform: translateY(4px);
}
.mat-side .more-btn:hover {
  background: rgba(255, 255, 255, .12);
  color: #fff !important;
}
.mat-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 14px;
  padding: 20px;
  min-width: 0;
}
.mat-card {
  border: 1px solid var(--line);
  border-radius: 10px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 7px;
  transition: all .18s;
  background: #fff;
  text-decoration: none;
  color: inherit;
  min-width: 0;
}
.mat-card:hover {
  border-color: var(--blue);
  box-shadow: var(--shadow-pop);
  transform: translateY(-2px);
}
.mat-card .m-img {
  width: 100%;
  margin: 0;
  aspect-ratio: 1;
  border-radius: 8px;
  overflow: hidden;
  background: #EEF2F8;
  position: relative;
  padding: 0;
}
.mat-card .m-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.m-promo {
  position: absolute;
  top: 6px;
  left: 6px;
  background: #e11d48;
  color: #fff;
  font-style: normal;
  font-size: 12px;
  line-height: 1;
  padding: 3px 6px;
  border-radius: 4px;
}
.mat-card .m-name {
  font-size: 14px;
  font-weight: 700;
  color: var(--ink);
  line-height: 1.4;
  text-align: center;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 2.8em;
}
.mat-card a { text-decoration: none; color: inherit; }
.mat-card:hover .m-name { color: var(--blue); }
.mat-card .m-co {
  font-size: 12.5px;
  font-weight: 600;
  color: var(--ink-2, #5c6b80);
  text-align: center;
  line-height: 1.35;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.mat-card a.m-co:hover { color: var(--blue); }
.mat-card .m-price {
  font-size: 15.5px;
  font-weight: 800;
  color: var(--up);
  text-align: center;
}
.mat-card .m-price small {
  font-size: 11px;
  font-weight: 500;
}
.mat-card .m-price.inquire {
  font-size: 14px;
  font-weight: 700;
  color: var(--blue-ink);
}
.mat-card .m-meta {
  display: flex;
  justify-content: space-between;
  gap: 6px;
  padding: 7px 2px 0;
  border-top: 1px dashed var(--line);
}
.mat-card .m-meta span {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 2px;
  font-size: 12px;
  font-weight: 600;
  color: var(--ink);
  line-height: 1.35;
  overflow: hidden;
}
.mat-card .m-meta span:nth-child(2) { align-items: center; text-align: center; }
.mat-card .m-meta span:last-child { align-items: flex-end; text-align: right; }
.mat-card .m-meta em {
  font-style: normal;
  display: block;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.mat-card .m-meta i {
  font-style: normal;
  font-size: 10px;
  color: var(--ink-3);
  font-weight: 400;
}

/* —— 名企楼层 —— */
.pf-co .pf-sec {
  background: #fff;
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-card);
  padding: 16px 18px 18px;
}
.pf-sec-hd {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--line);
}
.pf-sec-hd.is-tone {
  margin: -16px -18px 14px;
  padding: 12px 18px;
  border-bottom: 0;
  border-radius: var(--r-lg) var(--r-lg) 0 0;
  color: #fff;
}
.pf-sec-hd h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 800;
  color: var(--ink);
  display: flex;
  align-items: center;
  gap: 8px;
}
.pf-sec-hd.is-tone h3 {
  color: #fff;
}
.pf-kids {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin: -4px 0 12px;
}
.pf-kids a {
  font-size: 13px;
  color: var(--blue);
  text-decoration: none;
  background: var(--brand-tint, #eef4ff);
  border-radius: 100px;
  padding: 4px 12px;
}
.pf-buy .pf-kids { margin: 8px 0 0; }
.pf-sec-hd h3::before {
  content: "";
  width: 4px;
  height: 16px;
  border-radius: 2px;
  background: var(--blue);
}
.pf-sec-hd.is-tone h3::before {
  background: rgba(255, 255, 255, .92);
}
.pf-sec-hd .muted {
  font-size: 12.5px;
  color: var(--ink-3);
}
.pf-sec-hd.is-tone .muted {
  color: rgba(255, 255, 255, .82);
}
.pf-sec-hd .more-link {
  margin-left: auto;
  color: var(--blue);
  font-size: 13.5px;
  font-weight: 600;
  text-decoration: none;
}
.pf-sec-hd.is-tone .more-link {
  color: #fff;
  opacity: .95;
}
.pf-sec-hd.is-tone .more-link:hover {
  opacity: 1;
  text-decoration: underline;
  text-underline-offset: 2px;
}
.company-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}
.company {
  background: var(--surface);
  border-radius: var(--r-lg);
  padding: 14px;
  display: flex;
  flex-direction: column;
  transition: all .2s;
  box-shadow: var(--shadow-card);
  border: 1px solid transparent;
  text-decoration: none;
  color: inherit;
  min-width: 0;
  min-height: 0;
  overflow: hidden;
}
.company:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-pop);
  border-color: var(--blue);
}
.company .c-top {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  min-width: 0;
}
.company .photo {
  width: 52px;
  height: 52px;
  border-radius: 10px;
  flex-shrink: 0;
  overflow: hidden;
  background: #EEF2F8;
}
.company .photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.company .c-info { min-width: 0; flex: 1; overflow: hidden; }
.company .c-info b {
  font-size: 14px;
  font-weight: 700;
  line-height: 1.35;
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 4px;
  color: var(--ink);
  margin: 0;
  min-width: 0;
}
.company .c-info b > span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 100%;
  min-width: 0;
  flex: 1;
}
.company .c-info .vip {
  display: inline-flex;
  font-size: 10px;
  font-weight: 700;
  color: var(--up);
  background: #FEECEA;
  padding: 1px 8px;
  border-radius: 100px;
  font-style: normal;
  flex-shrink: 0;
}
.company .c-info .sub {
  font-size: 12px;
  color: var(--ink-3);
  margin-top: 4px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.company .c-info .tags {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 4px;
  margin-top: 6px;
  height: calc(2 * (11px * 1.35 + 4px) + 4px);
  overflow: hidden;
  align-content: start;
  flex-shrink: 0;
}
.company .c-info .tags span {
  font-size: 11px;
  color: var(--blue);
  background: #eef4ff;
  padding: 2px 6px;
  border-radius: 4px;
  font-weight: 600;
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  line-height: 1.35;
}
.company .desc {
  font-size: 12.5px;
  color: var(--ink-2);
  margin: 10px 0 12px;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  flex: 1;
}
.company .shop {
  margin-top: auto;
  display: flex;
  justify-content: center;
  align-items: center;
  background: #F0F5FE;
  color: var(--blue);
  font-weight: 700;
  font-size: 13px;
  border-radius: 9px;
  padding: 9px 0;
  transition: all .18s;
}
.company:hover .shop {
  background: var(--blue);
  color: #fff;
}

/* —— 求购楼层 —— */
.pf-buy .supply-box {
  background: var(--surface);
  border-radius: var(--r-lg);
  padding: 20px;
  box-shadow: var(--shadow-card);
}
.pf-buy .s-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
  padding-bottom: 10px;
  border-bottom: 1px solid var(--line);
}
.pf-buy .s-head b {
  font-size: 16px;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--ink);
  margin: 0;
}
.pf-buy .s-head b::before {
  content: "";
  width: 4px;
  height: 15px;
  border-radius: 2px;
  background: var(--up);
}
.pf-buy .s-head .more-link {
  color: var(--blue);
  font-size: 13.5px;
  font-weight: 600;
  text-decoration: none;
}
.pf-buy ul {
  list-style: none;
  margin: 0;
  padding: 0;
}
.pf-buy li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 4px;
  border-bottom: 1px dashed var(--line);
}
.pf-buy li:last-child { border-bottom: none; }
.pf-buy li .t {
  font-size: 14px;
  font-weight: 600;
  color: var(--ink);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  text-decoration: none;
  min-width: 0;
}
.pf-buy li .t:hover { color: var(--blue); }
.pf-buy li .urg {
  display: inline-block;
  margin-right: 6px;
  font-size: 10px;
  font-weight: 700;
  color: #fff;
  background: var(--up);
  padding: 1px 5px;
  border-radius: 4px;
  vertical-align: 1px;
}
.pf-buy li .hot {
  display: inline-block;
  margin-right: 6px;
  font-size: 10px;
  font-weight: 700;
  color: #fff;
  background: #ff6a00;
  padding: 1px 5px;
  border-radius: 4px;
  vertical-align: 1px;
}
.pf-buy li .tag {
  font-size: 11px;
  font-weight: 700;
  color: var(--up);
  background: #FEECEA;
  padding: 2px 10px;
  border-radius: 100px;
  flex-shrink: 0;
}

@media (max-width: 1280px) {
  .company-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}
@media (max-width: 1100px) {
  .mat-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .company-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (max-width: 900px) {
  .company-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 760px) {
  .mat-row { grid-template-columns: 1fr; }
  .mat-side { min-height: 0; }
  .mat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); padding: 14px; }
  .company-grid { grid-template-columns: 1fr; }
}
</style>

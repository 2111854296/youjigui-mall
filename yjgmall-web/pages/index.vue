<script setup lang="ts">
definePageMeta({ layout: 'home' })
useHead({
  title: '首页',
  meta: [
    { property: 'og:title', content: '有机硅商城 - 领先的有机硅电商综合服务平台' },
    { property: 'og:url', content: 'https://g.aifeili.cn/' },
  ],
  link: [{ rel: 'canonical', href: 'https://g.aifeili.cn/' }],
})

import { FLOOR_GROUPS } from '~/utils/floorGroups'
import { FLOOR_HIGHLIGHT, FLOOR_SLOGANS, companyShortName, catFlyoutGroups } from '~/utils/floorMeta'
import { isExternalAdLink, isInternalAdLink, normalizeAdLink } from '~/utils/adLink'
import { categoryIconSvg } from '~/utils/categoryIcons'
import { buildQuoteProducts } from '~/utils/quoteCatalog'
import { SAFE_PLACEHOLDER, isDemoProductImg } from '~/utils/safeMedia'

/** 无图默认用站点 logo */
const SAFE_IMG = SAFE_PLACEHOLDER

const FLOOR_SIDE_GRAD: Record<string, string> = {
  '有机硅单体/中间体': 'var(--brand-grad)',
  '硅油': 'linear-gradient(160deg,#F59E0B,#F97316)',
  '硅橡胶': 'linear-gradient(160deg,#10B981,#34D399)',
  '有机硅助剂/填料': 'linear-gradient(160deg,#8B5CF6,#A78BFA)',
  '机械设备': 'linear-gradient(160deg,#06B6D4,#3B82F6)',
  '硅烷': 'linear-gradient(160deg,#F04B3E,#FB7185)',
  '胶粘剂': 'linear-gradient(160deg,#2952CC,#4A7BF7)',
  '硅树脂': 'linear-gradient(160deg,#DB2777,#F472B6)',
}

const FLOOR_LEFT_IMG: Record<string, string> = {
  '有机硅单体/中间体': '/file/upload/201707/18/165732731.jpg',
  '硅油': '/file/upload/201707/28/175710281.jpg',
  '硅橡胶': '/file/upload/201707/28/175757871.jpg',
  '有机硅助剂/填料': '/file/upload/201707/28/175340481.jpg',
  '机械设备': '/file/upload/201707/28/175650671.jpg',
  '硅烷': '/file/upload/201707/28/175508761.jpg',
  '胶粘剂': '/file/upload/201707/28/175218661.jpg',
  '硅树脂': '/file/upload/201707/28/175437121.jpg',
}

const imgMap: Record<string, string> = {
  '/img/product.svg': SAFE_IMG,
  '/img/logo-yjg.png': SAFE_IMG,
  '/img/floor-mono.svg': FLOOR_LEFT_IMG['有机硅单体/中间体'],
  '/img/floor-oil.svg': FLOOR_LEFT_IMG['硅油'],
  '/img/floor-rubber.svg': FLOOR_LEFT_IMG['硅橡胶'],
  '/img/floor-addon.svg': FLOOR_LEFT_IMG['有机硅助剂/填料'],
  '/img/floor-equip.svg': FLOOR_LEFT_IMG['机械设备'],
  '/img/floor-silane.svg': FLOOR_LEFT_IMG['硅烷'],
  '/img/floor-glue.svg': FLOOR_LEFT_IMG['胶粘剂'],
  '/img/floor-resin.svg': FLOOR_LEFT_IMG['硅树脂'],
}

function resolveImg(path?: string | null, fallback: string = SAFE_IMG) {
  if (!path) return fallback
  if (isDemoProductImg(path)) return fallback
  if (imgMap[path]) return imgMap[path]
  if (/\.svg($|\?)/i.test(path) || /placehold|via\.placeholder/i.test(path)) return fallback
  if (path.startsWith('http') || path.startsWith('/') || path.startsWith('data:')) return path
  return fallback
}

function thumbByIndex(_i: number) {
  return SAFE_IMG
}

function onImgError(e: Event) {
  const el = e.target as HTMLImageElement | null
  if (!el || el.dataset.fallback === '1') return
  el.dataset.fallback = '1'
  el.src = SAFE_IMG
}


const { user, fetchMe } = useAuth()
const { api } = useApi()
const config = useRuntimeConfig()
const favBusy = ref<Record<string, boolean>>({})
const route = useRoute()
const { data: homeLive } = await useAsyncData(
  'home-live',
  () => $fetch(`${config.public.apiBase}/home`).catch(() => null),
)
const { data: siteLive } = await useAsyncData(
  'site-public',
  () => $fetch(`${config.public.apiBase}/site`).catch(() => null),
)
const home = computed<any>(() => {
  // 只用接口数据；禁止回落本地 home.json 演示供应/企业
  return (homeLive.value as any)?.data || {}
})

const bannerIndex = ref(0)
const bannerPaused = ref(false)
const activeCatId = ref<number | null>(null)
const eliteTab = ref(0)

const navCats = computed(() => home.value.categories || [])

watch(navCats, (cats) => {
  if (!cats.length) {
    activeCatId.value = null
    return
  }
  const ids = cats.map((c: any) => c.id)
  if (activeCatId.value == null || !ids.includes(activeCatId.value)) {
    activeCatId.value = cats[0].id
  }
}, { immediate: true })

const activeCat = computed(() =>
  navCats.value.find((c: any) => c.id === activeCatId.value) || navCats.value[0] || null,
)

const activeFlyout = computed(() => flyoutL3(activeCat.value))

/**
 * 预渲染分类 + 子类面板。悬停用纯 CSS :hover（不改任何 ref），
 * 避免 Vue 重渲染导致卡顿 / 高亮跟不上鼠标。
 */
const flyoutPanels = computed(() =>
  navCats.value.map((cat: any, ci: number) => ({
    id: Number(cat.id),
    name: String(cat.name || ''),
    to: categoryNavTo(cat.id, cat),
    iconHtml: catIconHtml(cat.name, ci, cat.icon),
    groups: flyoutL3(cat),
  })),
)

function selectCat(cat: any) {
  if (!cat?.id) return
  activeCatId.value = Number(cat.id)
}

/** 鼠标离开分类栏时清掉按钮焦点，避免弹层/高亮残留 */
function onCatWrapLeave() {
  if (!import.meta.client) return
  const ae = document.activeElement as HTMLElement | null
  if (ae?.closest?.('.cat-wrap')) ae.blur()
}

/** 广告标题：优先后台标题，否则从链接推断企业名 */
function adDisplayLabel(a: any, idx: number) {
  const title = String(a?.title || '').trim()
  if (title && !/^轮播\s*\d+$/i.test(title)) return title
  const link = normalizeAdLink(a?.link)
  const slug = (link.match(/\/companies\/([^/?#]+)/) || [])[1]
  if (slug) {
    for (const f of floors.value || []) {
      for (const c of f.companies || []) {
        if (String(c.slug) === slug || String(c.id) === slug) {
          return companyShortName(c) || String(c.name || slug)
        }
      }
    }
    try {
      return decodeURIComponent(slug)
    } catch {
      return slug
    }
  }
  try {
    if (/^https?:\/\//i.test(link)) {
      return new URL(link).hostname.replace(/^www\./i, '')
    }
  } catch { /* ignore */ }
  return `品牌展位${idx + 1}`
}

/** 分类面板广告图：只用来源是首页分类展位的图，每类最多 3 张 */
function portalAdCards(catIndex = 0) {
  const portal = ((home.value.ads?.home_portal || []) as any[]).filter((a: any) => usableBanner(a))
  if (!portal.length) return []
  const start = (Math.max(0, catIndex) * 3) % portal.length
  const picked: any[] = []
  for (let i = 0; i < 3 && picked.length < portal.length; i++) {
    const a = portal[(start + i) % portal.length]
    if (!a || picked.some((p) => p.id === a.id)) continue
    picked.push(a)
  }
  return picked.map((a: any, i: number) => ({
    id: a.id,
    label: adDisplayLabel(a, i),
    thumb: resolveImg(a.image, thumbByIndex(i)),
    link: normalizeAdLink(a.link),
    isAd: true,
    fromAd: true,
    company: '',
    companyTo: '',
  }))
}

function supplyCompany(s: any) {
  const name = String(s?.company_name || s?.user?.company_name || '').trim()
  if (!name || name === '管理员') return { company: '', companyTo: '' }
  const slug = String(s?.company_slug || s?.user?.company_slug || '').trim()
  const cid = s?.company_id || s?.user?.company_id
  const key = slug || (cid != null && cid !== '' ? String(cid) : '')
  return { company: name, companyTo: key ? `/companies/${key}` : '' }
}

function findSupplyForCard(card: any, supplies: any[]) {
  const link = String(card?.link || '')
  const idMatch = link.match(/\/sell\/(\d+)/)
  if (idMatch) {
    const hit = supplies.find((s) => String(s?.id) === idMatch[1])
    if (hit) return hit
  }
  const label = String(card?.label || '').trim()
  if (!label) return null
  return supplies.find((s) => String(s?.title || s?.name || '').trim() === label) || null
}

/** 分类详情面板：广告展位图（品牌推荐文字链已去掉） */
const catPanel = computed(() => {
  const cat = activeCat.value
  if (!cat) return null
  const groups = activeFlyout.value
  const children = (cat.children || []) as any[]
  const floor = (floors.value || []).find((f: any) => f?.category?.id === cat.id)
    || (floors.value || []).find((f: any) => String(f?.category?.name || '') === String(cat.name || ''))

  const supplies = (floor?.supplies || floor?.products || []) as any[]

  const catIndex = Math.max(0, navCats.value.findIndex((c: any) => c.id === cat.id))
  let cards = portalAdCards(catIndex)
  // 广告图不够 3 张时，空位用供应图补上，不从首页轮播里拆
  if (cards.length < 3) {
    const used = new Set(cards.map((c) => String(c.id)))
    for (const s of supplies) {
      if (cards.length >= 3) break
      const label = String(s.title || s.name || '').trim()
      if (!label || used.has(String(s.id))) continue
      used.add(String(s.id))
      cards.push({
        id: s.id,
        label,
        thumb: resolveImg(s.thumb || s.image || s.cover, thumbByIndex(cards.length)),
        link: `/sell/${s.id}`,
        isAd: false,
        fromAd: false,
        ...supplyCompany(s),
      })
    }
  }

  cards = cards.map((c) => {
    if (c.company) return c
    const s = findSupplyForCard(c, supplies)
    return s ? { ...c, ...supplyCompany(s) } : c
  })

  const subItems = (children.length
    ? children.map((c: any) => ({ name: c.name as string, to: categoryNavTo(c.id, c) }))
    : groups.map((g: any) => ({ name: g.title as string, to: g.to }))
  ).slice(0, 8)

  const hotItems = (
    supplies.length
      ? supplies.map((s: any) => ({
          name: String(s.title || s.name || ''),
          to: floorCardTo(s),
        }))
      : cards.map((n) => ({ name: n.label, to: n.link }))
  ).filter((x: any) => x.name).slice(0, 8)

  return {
    primary: cards.slice(0, 3),
    subItems,
    hotItems,
  }
})

function allCategoryNodes() {
  const out: any[] = []
  for (const c of home.value.categories || []) {
    out.push(c)
    for (const ch of c.children || []) {
      out.push(ch)
      for (const n of ch.children || []) out.push(n)
    }
  }
  return out
}

function findCategoryByName(name: string) {
  const n = String(name || '').trim()
  if (!n) return null
  const all = allCategoryNodes()
  let hit = all.find((c: any) => String(c.name) === n)
  if (hit) return hit
  const code = n.match(/[（(]([^）)]+)[）)]/)
  if (code?.[1]) {
    hit = all.find((c: any) => String(c.name) === code[1])
    if (hit) return hit
  }
  hit = all
    .filter((c: any) => String(c.name || '').length >= 2)
    .sort((a: any, b: any) => String(b.name).length - String(a.name).length)
    .find((c: any) => n.includes(String(c.name)) || String(c.name).includes(n))
  return hit || null
}

function categoryNavTo(nameOrId: any, fallback?: any) {
  if (nameOrId != null && String(nameOrId).match(/^\d+$/)) {
    return { path: '/sell', query: { category: String(nameOrId) } }
  }
  const hit = findCategoryByName(String(nameOrId || '')) || fallback || null
  if (hit?.id != null) return { path: '/sell', query: { category: String(hit.id) } }
  return { path: '/sell' }
}

function flyoutL3(cat: any) {
  if (!cat) return [] as any[]
  const children = cat.children || []
  const childByName = new Map<string, any>()
  for (const c of children) childByName.set(String(c.name || ''), c)

  const groups = FLOOR_GROUPS[cat.name] || catFlyoutGroups(cat.name)
  if (groups.length) {
    const used = new Set<number>()
    const rows = groups.map((g: any) => {
      const match = childByName.get(g.title)
        || [...childByName.values()].find((c: any) =>
          String(g.title).includes(c.name) || String(c.name).includes(g.title),
        )
        || findCategoryByName(g.title)
      if (match?.id != null) used.add(Number(match.id))
      const l2 = match || cat
      const nested = match?.children || []
      const items = nested.length
        ? nested.map((n: any) => ({
            name: n.name as string,
            to: categoryNavTo(n.id, n),
          }))
        : (g.items || []).map((name: string) => ({
            name,
            to: categoryNavTo(name, l2),
          }))
      return {
        title: g.title as string,
        to: categoryNavTo(match?.id ?? cat.id, match || cat),
        items,
      }
    })
    for (const c of children) {
      if (used.has(Number(c.id))) continue
      rows.push({
        title: c.name as string,
        to: categoryNavTo(c.id, c),
        items: (c.children || []).map((n: any) => ({
          name: n.name as string,
          to: categoryNavTo(n.id, n),
        })),
      })
    }
    return rows
  }

  return children.map((c: any) => ({
    title: c.name as string,
    to: categoryNavTo(c.id, c),
    items: (c.children || []).map((n: any) => ({
      name: n.name as string,
      to: categoryNavTo(n.id, n),
    })),
  }))
}

const displayNameFull = computed(() => {
  const u = user.value
  if (!u) return ''
  // 登录后固定展示用户名（用户名 > 姓名 > 企业名 > 手机号）
  const raw = String(u.username || u.name || u.company_name || u.mobile || '').trim()
  if (!raw || /^null|undefined$/i.test(raw)) return '会员'
  return raw
})

const displayName = computed(() => {
  const raw = displayNameFull.value
  if (!raw) return ''
  if (raw.length > 8) return raw.slice(0, 8) + '...'
  return raw
})

const loginSubText = computed(() =>
  user.value
    ? ''
    : '登录后发布信息、查看企业与联系供应商',
)

type PendingTip = { key: string; count: number; text: string; to: string }
const pendingItems = ref<PendingTip[]>([])
const pendingTotal = computed(() => pendingItems.value.reduce((n, i) => n + Number(i.count || 0), 0))
const pendingBadge = computed(() => (pendingTotal.value > 99 ? '99+' : String(pendingTotal.value)))
const idleSlogan = '供应、求购、订单、推广等功能一个平台全搞定！'

async function loadPending() {
  if (!user.value) {
    pendingItems.value = []
    return
  }
  try {
    const res = await api<any>('/member/dashboard')
    const list = res?.data?.pending || []
    pendingItems.value = (Array.isArray(list) ? list : []).filter((i) => Number(i?.count) > 0)
  } catch {
    /* 保留上一次结果，避免接口抖动把提示清掉 */
  }
}

function catIconHtml(name: string, i = 0, icon?: string) {
  return categoryIconSvg(icon, name, i)
}

function usableBanner(b: any) {
  const img = String(b?.image || '')
  if (!img || /\.svg($|\?)/i.test(img) || /placehold/i.test(img)) return false
  return true
}

const heroBanners = computed(() => {
  const ads = (home.value.ads?.home_banner || []).filter(usableBanner)
  const list = (ads.length ? ads : (home.value.banners || [])).filter(usableBanner)
  // 无真实广告则空轮播，不伪造活动卡
  return list.map((b: any) => ({
    ...b,
    image: resolveImg(b.image),
    link: normalizeAdLink(b.link),
  }))
})

const banners = computed(() => heroBanners.value)
/** 只渲染当前一张，避免叠层里「看不见但可点」的旧链接抢点击 */
const currentBanner = computed(() => banners.value[bannerIndex.value] || null)

watch(banners, (list) => {
  if (!list.length) {
    bannerIndex.value = 0
    return
  }
  // 首屏优先停在「有有效跳转」的广告，避免空链接轮播占首帧
  const hit = list.findIndex((b: any) => isInternalAdLink(b.link) || isExternalAdLink(b.link))
  if (hit >= 0 && !(isInternalAdLink(list[bannerIndex.value]?.link) || isExternalAdLink(list[bannerIndex.value]?.link))) {
    bannerIndex.value = hit
  } else if (bannerIndex.value >= list.length) {
    bannerIndex.value = 0
  }
}, { immediate: true })

function prevBanner() {
  const n = banners.value.length
  if (!n) return
  bannerIndex.value = (bannerIndex.value - 1 + n) % n
}
function nextBanner() {
  const n = banners.value.length
  if (!n) return
  bannerIndex.value = (bannerIndex.value + 1) % n
}

const floors = computed(() => home.value.floors || [])
const midSupplies = computed(() => (home.value.supplies || []).slice(0, 10))
const midBuys = computed(() => (home.value.buys || home.value.purchases || []).slice(0, 10))

/** 设计稿 stat-card：入驻企业 / 挂牌产品 / 大产品品类 */
const homeStats = computed(() => {
  const s = home.value.stats || {}
  return {
    companies: s.companies ?? s.company_count ?? 0,
    products: s.products ?? s.product_count ?? s.supplies ?? s.supply_count ?? midSupplies.value.length ?? 0,
    categories: s.categories ?? s.category_count ?? navCats.value.length ?? 8,
  }
})

/** 首页公告一律进本站详情；真正的 http(s) 外链才直接跳出 */
function announceHref(a: { id?: number | string; link?: string | null }) {
  const link = String(a?.link || '').trim()
  if (/^https?:\/\//i.test(link)) return link
  const id = Number(a?.id)
  return Number.isFinite(id) && id > 0 ? `/announce?id=${id}` : '/announce'
}

function fmtStat(n: number | string) {
  const v = Number(n) || 0
  return v.toLocaleString('en-US')
}

const matList = ref<HTMLElement | null>(null)

watch(user, () => { void loadPending() }, { immediate: true })

onMounted(() => {
  void fetchMe()
  const pendingTimer = setInterval(() => { void loadPending() }, 20000)
  const onVis = () => { if (document.visibilityState === 'visible') void loadPending() }
  document.addEventListener('visibilitychange', onVis)
  const timer = setInterval(() => {
    if (bannerPaused.value || !banners.value.length) return
    bannerIndex.value = (bannerIndex.value + 1) % banners.value.length
  }, 4200)
  startFloorTimer()
  startListTimer()
  nextTick(syncRollHeights)
  window.addEventListener('resize', syncRollHeights)
  const unbindPageWidth = bindMatPageWidth(matList.value)
  document.addEventListener('visibilitychange', onFloorVisibility)
  onBeforeUnmount(() => {
    clearInterval(timer)
    clearInterval(pendingTimer)
    if (flyoutCloseTimer) clearTimeout(flyoutCloseTimer)
    document.removeEventListener('visibilitychange', onVis)
    stopFloorTimer()
    stopListTimer()
    unbindPageWidth()
    window.removeEventListener('resize', syncRollHeights)
    document.removeEventListener('visibilitychange', onFloorVisibility)
  })
})

function fmtDate(v?: string) {
  if (!v) return ''
  return String(v).slice(5, 10)
}

function floorGroups(floor: any) {
  const live = (floor?.supplies || floor?.products || floor?.items || []) as any[]
  if (live.length) {
    const titles = live
      .map((it) => String(it?.title || it?.name || '').trim())
      .filter(Boolean)
    const mid = Math.ceil(Math.min(titles.length, 12) / 2)
    const groups: { title: string; items: { name: string; id?: any; to?: any }[] }[] = [
      {
        title: '热门供应',
        items: titles.slice(0, mid).map((name, i) => ({
          name,
          id: live[i]?.id,
          to: live[i]?.id != null ? `/sell/${live[i].id}` : categoryNavTo(name, floor?.category),
        })),
      },
    ]
    if (titles.length > mid) {
      groups.push({
        title: '更多供应',
        items: titles.slice(mid, mid + mid).map((name, i) => ({
          name,
          id: live[mid + i]?.id,
          to: live[mid + i]?.id != null ? `/sell/${live[mid + i].id}` : categoryNavTo(name, floor?.category),
        })),
      })
    }
    const children = floor?.children || []
    if (children.length) {
      groups.push({
        title: '子分类',
        items: children.slice(0, 12).map((c: any) => ({
          name: String(c.name || c),
          to: categoryNavTo(c.id ?? c.name, c),
        })),
      })
    }
    return groups
  }
  if (floor?.groups?.length) {
    return floor.groups.map((g: any) => ({
      title: g.title,
      items: (g.items || []).map((it: any) => (typeof it === 'string' ? { name: it } : it)),
    }))
  }
  const named = FLOOR_GROUPS[floor?.category?.name || '']
  if (named?.length) {
    return named.map(g => ({
      title: g.title,
      items: g.items.map(name => ({ name })),
    }))
  }
  const children = floor?.children || []
  if (!children.length) return []
  return [{ title: floor.category?.name || '分类', items: children }]
}

/** 设计方案行情品种（有接口数据则合并覆盖） */
const quoteProducts = computed(() => buildQuoteProducts(home.value.quote_products || []))

function floorCardTo(s: any) {
  if (s?._to) return s._to
  if (s?._kw) return categoryNavTo(s.title)
  if (s?.id != null) return `/sell/${s.id}`
  return categoryNavTo(s?.title || '')
}

function floorCards(floor: any) {
  const limit = 5
  const promos = ((floor?.promos || []) as any[]).filter((s) => s?.id)
  const promoIds = new Set(promos.map((s) => s.id))
  const supplies = ((floor?.supplies || []) as any[]).filter((s) => !promoIds.has(s.id)).slice(0, limit)
  // 无真实供应/推广时不补分类名+演示图，避免空库仍显示假商品卡
  return [...promos, ...supplies].slice(0, limit).map((s: any, i: number) => ({
    ...s,
    thumb: resolveImg(s.thumb, SAFE_IMG),
    _kw: false,
  }))
}

function tipFor(s: any) {
  if (s?.price != null && s.price !== '') {
    const n = Number(String(s.price).replace(/,/g, ''))
    const main = Number.isFinite(n)
      ? n.toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
      : String(s.price)
    let u = String(s.unit || '吨').trim().replace(/^元\/?/, '')
    if (!u) u = '吨'
    return { main, small: ` 元/${u}（含税）`, empty: false }
  }
  return { main: '面议', small: '', empty: true }
}

/** 分类面板 brand 行需要纯字符串（会 .replace），材料价签用 tipFor 对象 */
function tipTextFor(s: any) {
  const p = tipFor(s)
  if (p.empty) return '厂家直供，现货可询'
  return `${p.main}${p.small}`
}

function areaOf(s: any) {
  return String(s?.area || s?.region || '').trim() || '地区不限'
}

function stockOf(s: any) {
  const raw = s?.stock ?? s?.amount ?? s?.quantity
  if (raw != null && raw !== '') {
    const t = String(raw).trim()
    if (/\D/.test(t)) return t
    const u = /台/.test(String(s.unit || '')) ? '台' : '吨'
    return `${t} ${u}`
  }
  return '现货'
}

function pickupOf(s: any) {
  const v = String(s?.pickup || s?.delivery || '').trim()
  if (v) return v
  return '配送'
}

function floorSideTitleHtml(name: string) {
  const n = String(name || '').trim()
  if (!n) return ''
  if (n.includes('/')) return n.replace(/\s*\/\s*/, '<br>/ ')
  return n
}

/** 取短标签名：优先括号内代号（DMC/D4），过长则截断 */
function shortSideTag(raw: string) {
  const s = String(raw || '').trim()
  if (!s) return ''
  const code = s.match(/[（(]([^）)]{1,8})[）)]/)
  if (code?.[1]) return code[1]
  return s.length > 8 ? s.slice(0, 8) : s
}

/** 左侧标签：子分类 + FLOOR_GROUPS 组名/短项，凑满一排 */
function floorSideTags(floor: any) {
  const catName = String(floor?.category?.name || '')
  const children = (floor?.children || floor?.category?.children || []) as any[]
  const out: { id?: any; name: string }[] = []
  const used = new Set<string>()
  const push = (name: string, id?: any) => {
    const n = shortSideTag(name)
    if (!n || used.has(n)) return
    used.add(n)
    out.push({ id, name: n })
  }

  for (const c of children) push(String(c?.name || ''), c?.id)

  for (const g of FLOOR_GROUPS[catName] || []) {
    push(g.title)
    for (const it of g.items || []) {
      push(it)
      if (out.length >= 10) break
    }
    if (out.length >= 10) break
  }

  return out.slice(0, 10)
}

const floorPos = ref<Record<string, number>>({})
const floorManual = ref<Record<string, boolean>>({})
const floorHover = ref<Record<string, boolean>>({})
const floorAnimate = ref<Record<string, boolean>>({})
let floorTimer: ReturnType<typeof setInterval> | null = null

function floorKey(floor: any) {
  return String(floor?.category?.id ?? floor?.category?.name ?? '')
}

function tagTokenMatch(code: string, title: string) {
  return new RegExp(`(?:^|[^A-Za-z0-9])${code}(?![A-Za-z0-9])`, 'i').test(title)
}

/** 子分类名对标题打分。代号按整词匹配，避免 D4 命中 MD40。 */
function childMatchScore(name: string, title: string) {
  const text = String(title || '')
  const label = String(name || '').trim()
  if (!label || !text) return 0
  let score = 0
  if (/^[A-Za-z][A-Za-z0-9]*$/.test(label)) {
    if (tagTokenMatch(label, text)) score = 100 + label.length
    return score
  }
  const latins = label.match(/[A-Za-z][A-Za-z0-9]*/g) || []
  for (const code of latins) {
    if (tagTokenMatch(code, text)) score = Math.max(score, 100 + code.length)
  }
  if (label.length >= 2 && text.includes(label)) score = Math.max(score, 80 + label.length)
  const stem = label.replace(/(单体|中间体|硅油|硅橡胶|硅树脂|密封胶|灌封胶|催化剂|偶联剂|交联剂)$/u, '')
  if (stem && stem !== label && stem.length >= 2 && text.includes(stem)) {
    const weak = stem.length <= 2
      && /环体|环硅氧烷|环四|环五|环三|乳液|钻|硅油|密封胶|灌封胶|混炼胶|捏合|反应釜|树脂/.test(text)
      && !text.includes(label)
    if (!weak) score = Math.max(score, 40 + stem.length)
  }
  return score
}

function supplyToCard(s: any, i: number) {
  return {
    ...s,
    thumb: resolveImg(s.thumb, thumbByIndex(i)),
    _kw: false,
  }
}

/** 每个子分类 5 个位子：买下的广告占指定位，其余用该类供应补上 */
function floorBuckets(floor: any) {
  const children = (floor?.children || []) as Array<{ id?: number; name?: string }>
  const promos = ((floor?.promos || []) as any[]).filter((s) => s?.id)
  const promoIds = new Set(promos.map((s) => s.id))
  const supplies = ((floor?.supplies || []) as any[]).filter((s) => !promoIds.has(s.id))
  if (!children.length || (!supplies.length && !promos.length)) return [] as Array<{ id?: number; name: string; items: any[] }>

  const slots = new Map<string, Array<any | null>>()
  const ensure = (ch: { id?: number; name?: string }) => {
    const key = String(ch.id ?? ch.name)
    if (!slots.has(key)) slots.set(key, [null, null, null, null, null])
    return slots.get(key)!
  }
  const placed = new Set<number>()
  for (const ch of children) ensure(ch)

  for (const p of promos) {
    const cid = Number(p.category_id || 0)
    const slot = Number(p.promo_slot || 0)
    if (cid <= 0 || slot < 1 || slot > 5) continue
    const ch = children.find((c) => Number(c.id) === cid)
    if (!ch) continue
    const row = ensure(ch)
    if (row[slot - 1]) continue
    row[slot - 1] = supplyToCard({ ...p, is_promoted: true }, 0)
    placed.add(Number(p.id))
  }
  for (const p of promos) {
    if (placed.has(Number(p.id)) || Number(p.category_id || 0) > 0) continue
    const title = String(p?.title || '')
    let best: { id?: number; name?: string } | null = null
    let score = 0
    for (const ch of children) {
      const n = childMatchScore(String(ch?.name || ''), title)
      if (n > score) {
        score = n
        best = ch
      }
    }
    if (!best) best = children[0]
    const row = ensure(best)
    const empty = row.findIndex((x) => !x)
    if (empty < 0) continue
    row[empty] = supplyToCard({ ...p, is_promoted: true }, 0)
    placed.add(Number(p.id))
  }

  const usedSupply = new Set<number>()
  const takeSupply = (ch: { id?: number; name?: string }, pred: (s: any) => boolean) => {
    const row = ensure(ch)
    for (const s of supplies) {
      if (usedSupply.has(Number(s.id)) || !pred(s)) continue
      const empty = row.findIndex((x) => !x)
      if (empty < 0) return
      row[empty] = supplyToCard(s, 0)
      usedSupply.add(Number(s.id))
    }
  }
  for (const ch of children) {
    takeSupply(ch, (s) => Number(s.category_id) === Number(ch.id))
  }
  for (const ch of children) {
    takeSupply(ch, (s) => childMatchScore(String(ch.name || ''), String(s.title || '')) > 0)
  }

  return children.map((ch) => ({
    id: ch.id,
    name: shortSideTag(String(ch.name || '')) || String(ch.name || ''),
    items: (slots.get(String(ch.id ?? ch.name)) || []).filter(Boolean),
  })).filter((b) => b.name && b.items.length)
}

const PAGE_STEP_MS = 10000

function floorPages(floor: any) {
  const pages = floorBuckets(floor)
  if (pages.length <= 1) return pages
  return pages.concat([{ ...pages[0], clone: true }])
}

function floorTrackStyle(floor: any) {
  const key = floorKey(floor)
  const style: Record<string, string> = { '--shift': String(floorPos.value[key] || 0) }
  if (floorAnimate.value[key] === false) style.transition = 'none'
  return style
}

function pauseFloor(floor: any) {
  floorHover.value = { ...floorHover.value, [floorKey(floor)]: true }
}

function resumeFloor(floor: any) {
  const key = floorKey(floor)
  const next = { ...floorHover.value }
  delete next[key]
  floorHover.value = next
}

function floorFocus(floor: any) {
  const count = floorBuckets(floor).length
  if (!count) return 0
  return (floorPos.value[floorKey(floor)] || 0) % count
}

function pickFloorTag(floor: any, index: number) {
  const key = floorKey(floor)
  const animate = { ...floorAnimate.value }
  delete animate[key]
  floorAnimate.value = animate
  floorPos.value = { ...floorPos.value, [key]: index < 0 ? 0 : index }
  floorManual.value = { ...floorManual.value, [key]: true }
}

function tickFloors() {
  const next = { ...floorPos.value }
  let changed = false
  for (const floor of floors.value) {
    const key = floorKey(floor)
    const count = floorBuckets(floor).length
    if (count <= 1 || floorManual.value[key] || floorHover.value[key]) continue
    const current = next[key] || 0
    next[key] = current >= count ? 0 : current + 1
    changed = true
  }
  if (changed) floorPos.value = next
}

function onFloorTrackEnd(floor: any, e: TransitionEvent) {
  if (e.propertyName !== 'transform' || e.target !== e.currentTarget) return
  const key = floorKey(floor)
  const count = floorBuckets(floor).length
  if (count <= 1 || (floorPos.value[key] || 0) !== count) return
  floorAnimate.value = { ...floorAnimate.value, [key]: false }
  floorPos.value = { ...floorPos.value, [key]: 0 }
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      const animate = { ...floorAnimate.value }
      delete animate[key]
      floorAnimate.value = animate
    })
  })
}

function startFloorTimer() {
  if (floorTimer || (import.meta.client && document.hidden)) return
  floorTimer = setInterval(tickFloors, PAGE_STEP_MS)
}

function stopFloorTimer() {
  if (!floorTimer) return
  clearInterval(floorTimer)
  floorTimer = null
}

function onFloorVisibility() {
  if (document.hidden) {
    stopFloorTimer()
    stopListTimer()
  } else {
    startFloorTimer()
    startListTimer()
  }
}

function peerIdOf(s: any): number | null {
  const id = s?.user_id ?? s?.user?.id ?? s?.member_id ?? null
  return id != null && Number.isFinite(Number(id)) ? Number(id) : null
}

/** 真实供应（非楼层占位词条） */
function isRealSupply(s: any) {
  return !s?._kw && s?.id != null && Number.isFinite(Number(s.id))
}

/** 关注：先收藏该供应，再进收藏列表 */
async function onMatFav(s: any, e?: Event) {
  e?.preventDefault?.()
  e?.stopPropagation?.()
  if (!user.value) {
    return navigateTo({ path: '/login', query: { redirect: route.fullPath || '/' } })
  }
  const key = String(s?.id ?? '')
  if (favBusy.value[key]) return
  favBusy.value = { ...favBusy.value, [key]: true }
  try {
    if (isRealSupply(s)) {
      try {
        await api('/member/favorites', {
          method: 'POST',
          body: { type: 'supply', id: Number(s.id) },
        })
      } catch {
        /* 已收藏等也继续进列表 */
      }
    }
    await navigateTo('/member/favorites')
  } finally {
    favBusy.value = { ...favBusy.value, [key]: false }
  }
}

function shopName(s: any) {
  if (s?._kw) return ''
  const name = String(s?.company_name || s?.user?.company_name || '').trim()
  if (!name || name === '管理员') return ''
  return name
}

/** 旺铺路径键：优先 slug，其次企业 id */
function merchantShopKey(s: any): string | null {
  const slug = s?.company_slug || s?.user?.company_slug
  if (slug) return String(slug)
  const cid = s?.company_id || s?.user?.company_id
  if (cid != null && Number.isFinite(Number(cid))) return String(cid)
  return null
}

/** 留言：进该商家旺铺联系页并打开留言询盘（不是供应详情、也不是全站留言板） */
function matMsgTo(s: any) {
  const key = merchantShopKey(s)
  if (key) return `/companies/${key}?tab=contact&inquiry=1`
  const to = s?.username || s?.user?.username || peerIdOf(s)
  if (isRealSupply(s)) return `/sell/${s.id}?inquiry=1`
  return floorCardTo(s)
}

/** 联系方式：进该商家旺铺「联系方式」页 */
function matContactTo(s: any) {
  const key = merchantShopKey(s)
  if (key) return `/companies/${key}?tab=contact`
  if (isRealSupply(s)) return `/sell/${s.id}`
  return floorCardTo(s)
}

function floorSideGrad(floor: any) {
  const name = String(floor?.category?.name || '')
  return FLOOR_SIDE_GRAD[name] || 'var(--brand-grad, var(--grad-blue))'
}

function floorSlogans(floor: any) {
  const name = String(floor?.category?.name || '')
  return FLOOR_SLOGANS[name] || [FLOOR_HIGHLIGHT[name] || name].filter(Boolean)
}

function quoteTag(title: string) {
  if (/DMC|中间体|D4/.test(title)) return '中间体报价'
  if (/107|硅油/.test(title)) return '硅油、107胶报价'
  if (/生胶|混炼|硅橡胶/.test(title)) return '硅橡胶报价'
  return '行业报价'
}

const ELITE_TAB_ALIAS: Record<string, string> = {
  '有机硅单体/中间体': '原料区',
  '硅油': '硅油区',
  '硅橡胶': '制品区',
  '有机硅助剂/填料': '助剂区',
  '机械设备': '设备展示',
  '硅烷': '硅烷区',
  '胶粘剂': '胶粘剂',
  '硅树脂': '树脂区',
}
const eliteTabs = computed(() =>
  floors.value.map((f: any) => {
    const full = String(f?.category?.name || '')
    return {
      id: f?.category?.id,
      name: ELITE_TAB_ALIAS[full] || full,
      fullName: full,
    }
  }).filter((t: any) => t.id && t.name),
)

watch(eliteTabs, (tabs) => {
  if (eliteTab.value >= tabs.length) eliteTab.value = 0
})

/** 经营模式优先（贸易商/制造商），其次公司类型；无真实字段不编造 */
function eliteTypeOf(c: any) {
  const mode = String(c?.business_mode || '')
    .split(/[,|，]/)
    .map((s: string) => s.trim())
    .filter(Boolean)[0] || ''
  if (mode) return mode
  return String(c?.company_type || '').trim()
}

function eliteScaleOf(c: any) {
  return String(c?.scale || '').trim()
}

function parseEliteTags(raw: any): string[] {
  if (Array.isArray(raw)) {
    return raw.map((x) => String(x || '').trim()).filter((s) => s.length >= 1 && s.length <= 12).slice(0, 4)
  }
  return String(raw || '')
    .split(/[,|，、;；]+/)
    .map((s) => s.trim())
    .filter((s) => s.length >= 1 && s.length <= 12)
    .slice(0, 4)
}

/** 仅用后台/企业填写的 tags；没有则只用真实地区，不前端编造关键词 */
function eliteTagsOf(c: any) {
  const fromDb = parseEliteTags(c?.tags)
  if (fromDb.length) return fromDb
  const area = String(c?.area || c?.address || '').trim()
  if (!area) return []
  return [area.length > 6 ? area.slice(0, 6) : area]
}

function eliteDescOf(c: any) {
  const biz = String(c?.business || c?.intro || c?.description || '').trim().replace(/\s+/g, ' ')
  if (!biz) return ''
  return biz.length > 56 ? `${biz.slice(0, 56)}…` : biz
}

function mapEliteCo(c: any, idx: number, _catName: string) {
  const type = eliteTypeOf(c)
  const scale = eliteScaleOf(c)
  const address = String(c.area || c.address || '').trim()
  return {
    id: c.id,
    slug: c.slug,
    name: String(c.name || companyShortName(c)),
    shortName: companyShortName(c),
    logo: resolveImg(c.logo || c.thumb || c.image, thumbByIndex(idx)),
    // 名企推荐展位统一角标；企业 VIP 指数仍存 vip_level，可在后台/旺铺信用区查看
    vip: true,
    memberGrade: String(c.member_grade || c.user?.member_grade || 'normal'),
    memberBadge: String(c.member_badge || c.user?.member_badge || '普通会员'),
    type,
    scale,
    meta: [type, scale].filter(Boolean).join(' · '),
    tags: eliteTagsOf(c),
    desc: eliteDescOf(c),
    address: address || '',
  }
}

const eliteCompanies = computed(() => {
  const tabs = eliteTabs.value
  const fi = Math.min(eliteTab.value, Math.max(tabs.length - 1, 0))
  const floor = floors.value[fi]
  const catName = String(floor?.category?.name || tabs[fi]?.fullName || '')
  return (floor?.companies || []).slice(0, 6).map((c: any, i: number) => mapEliteCo(c, i, catName))
})

/** 企业不足时最多补 2 个招租卡，避免半屏空广告位 */
const ELITE_HIRE_COPY = [
  { title: '广告位 · 虚位以待', desc: '首页名企推荐位招租<br>VIP 会员优先展示' },
  { title: '广告位 · 虚位以待', desc: '名企推荐黄金展位招租<br>日均曝光 100,000+' },
]

const eliteHireSlots = computed(() => {
  const n = eliteCompanies.value.length
  const need = n >= 8 ? 0 : Math.min(2, Math.max(0, 8 - n))
  return Array.from({ length: need }, (_, i) => ({
    ...ELITE_HIRE_COPY[i % ELITE_HIRE_COPY.length],
    key: `hire-${i}`,
  }))
})

const NEWS_CHIPS = [
  { key: 'all', label: '全部' },
  { key: 'intermediate', label: '中间体报价', re: /DMC|D4|中间体|单体/i },
  { key: 'oil', label: '硅油·107胶报价', re: /硅油|107/i },
  { key: 'rubber', label: '硅橡胶报价', re: /硅橡胶|生胶|混炼/i },
  { key: 'market', label: '市场综述', re: /市场|综述|行情|动态|周报|月报/i },
] as const
const newsChip = ref<(typeof NEWS_CHIPS)[number]['key']>('all')

const newsList = computed(() => {
  const rows = (home.value.articles || home.value.quotes || []) as any[]
  const chip = NEWS_CHIPS.find((c) => c.key === newsChip.value)
  const filtered = (!chip || chip.key === 'all' || !('re' in chip))
    ? rows
    : rows.filter((a) => (chip as any).re.test(String(a.title || '')))
  return filtered
})

const listCarouselOn = computed(() => String((siteLive.value as any)?.data?.home_list_carousel ?? '1') !== '0')
const SUPPLY_PAGE = 10
const NEWS_PAGE = 8
const supplyRows = computed(() => (home.value.supplies || []) as any[])
const buyRows = computed(() => (home.value.buys || home.value.purchases || []) as any[])
const rankRows = computed(() => ((home.value.rank_articles || home.value.articles || []) as any[]).map((a, i) => ({
  ...a,
  rankNo: i + 1,
  top: i < 3,
})))
const listPos = ref<Record<string, number>>({})
const listAnimate = ref<Record<string, boolean>>({})
const listHover = ref<Record<string, boolean>>({})
let listTimer: ReturnType<typeof setInterval> | null = null

function chunkRows(rows: any[], size: number) {
  const pages: any[][] = []
  for (let i = 0; i < rows.length; i += size) pages.push(rows.slice(i, i + size))
  return pages
}
function rollSource(rows: any[], size: number) {
  if (!rows.length) return []
  if (!listCarouselOn.value) return [rows.slice(0, size)]
  return chunkRows(rows, size)
}
function rollView(rows: any[], size: number) {
  const pages = rollSource(rows, size)
  if (pages.length <= 1) return pages
  return pages.concat([pages[0]])
}
const supplyPages = computed(() => rollView(supplyRows.value, SUPPLY_PAGE))
const buyPages = computed(() => rollView(buyRows.value, SUPPLY_PAGE))
const newsPages = computed(() => rollView(newsList.value, NEWS_PAGE))
const rankPages = computed(() => rollView(rankRows.value, NEWS_PAGE))
const supplyPageCount = computed(() => rollSource(supplyRows.value, SUPPLY_PAGE).length)
const buyPageCount = computed(() => rollSource(buyRows.value, SUPPLY_PAGE).length)
const newsPageCount = computed(() => rollSource(newsList.value, NEWS_PAGE).length)
const rankPageCount = computed(() => rollSource(rankRows.value, NEWS_PAGE).length)

function listTrack(key: string) {
  const style: Record<string, string> = { '--shift': String(listPos.value[key] || 0) }
  if (listAnimate.value[key] === false) style.transition = 'none'
  return style
}
function pauseList(key: string) {
  listHover.value = { ...listHover.value, [key]: true }
}
function resumeList(key: string) {
  const next = { ...listHover.value }
  delete next[key]
  listHover.value = next
}
function tickLists() {
  if (!listCarouselOn.value) return
  const specs: [string, number][] = [
    ['supply', supplyPageCount.value],
    ['buy', buyPageCount.value],
    ['news', newsPageCount.value],
    ['rank', rankPageCount.value],
  ]
  const next = { ...listPos.value }
  let changed = false
  for (const [key, count] of specs) {
    if (count <= 1 || listHover.value[key]) continue
    const current = next[key] || 0
    next[key] = current >= count ? 0 : current + 1
    changed = true
  }
  if (changed) listPos.value = next
}
function onListEnd(key: string, count: number, e: TransitionEvent) {
  if (e.propertyName !== 'transform' || e.target !== e.currentTarget) return
  if (count <= 1 || (listPos.value[key] || 0) !== count) return
  listAnimate.value = { ...listAnimate.value, [key]: false }
  listPos.value = { ...listPos.value, [key]: 0 }
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      const animate = { ...listAnimate.value }
      delete animate[key]
      listAnimate.value = animate
    })
  })
}
function syncRollHeights() {
  if (!import.meta.client) return
  document.querySelectorAll<HTMLElement>('.roll-viewport').forEach((el) => {
    const page = el.querySelector<HTMLElement>('.roll-page')
    if (!page) return
    const h = page.offsetHeight
    el.style.height = `${h}px`
    el.style.setProperty('--page-h', `${h}px`)
  })
}
function startListTimer() {
  if (listTimer || (import.meta.client && document.hidden)) return
  listTimer = setInterval(tickLists, PAGE_STEP_MS)
}
function stopListTimer() {
  if (!listTimer) return
  clearInterval(listTimer)
  listTimer = null
}
watch(newsChip, () => {
  listPos.value = { ...listPos.value, news: 0 }
  nextTick(syncRollHeights)
})
watch([supplyRows, buyRows, newsList, rankRows, listCarouselOn], () => nextTick(syncRollHeights))
</script>

<template>
  <div class="portal-home">
    <h1 class="sr-only">有机硅商城 - 领先的有机硅电商综合服务平台</h1>
    <div class="container">
      <!-- Portal: 248 | 1fr | 264 -->
      <section v-if="home.categories?.length" class="portal">
        <div class="cat-wrap" @mouseleave="onCatWrapLeave">
          <aside class="cat-side">
            <div class="cs-head">
              <span class="cs-ico" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
              </span>
              全部产品分类
            </div>
            <div
              v-for="cat in flyoutPanels"
              :key="cat.id"
              class="cat-item"
              :class="{ on: Number(cat.id) === Number(activeCatId) }"
            >
              <button type="button" @click="selectCat(cat)">
                <span class="cs-ico" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" v-html="cat.iconHtml"></svg>
                </span>
                <span class="cs-label">{{ cat.name }}</span>
              </button>
              <div class="cat-flyout" aria-hidden="true">
                <NuxtLink class="fly-hd" :to="cat.to" tabindex="-1">
                  {{ cat.name }}
                  <span aria-hidden="true">›</span>
                </NuxtLink>
                <div class="fly-body">
                  <template v-if="cat.groups.length">
                    <div v-for="(g, gi) in cat.groups" :key="cat.id + '-g' + gi" class="fly-group">
                      <NuxtLink class="fly-l2" :to="g.to" tabindex="-1">{{ g.title }}</NuxtLink>
                      <div v-if="g.items?.length" class="fly-l3">
                        <NuxtLink
                          v-for="(it, ii) in g.items"
                          :key="cat.id + '-i' + gi + '-' + ii"
                          :to="it.to"
                          tabindex="-1"
                        >{{ it.name }}</NuxtLink>
                      </div>
                    </div>
                  </template>
                  <p v-else class="fly-empty">暂无子分类，点击上方进入本类供应</p>
                </div>
              </div>
            </div>
          </aside>
        </div>

        <div class="portal-mid">
          <div class="banner-carousel" @mouseenter="bannerPaused = true" @mouseleave="bannerPaused = false">
            <template v-if="currentBanner">
              <NuxtLink
                v-if="isInternalAdLink(currentBanner.link)"
                :key="'in-' + (currentBanner.id || bannerIndex)"
                class="slide img-slide on"
                :to="currentBanner.link"
                :title="currentBanner.title || ''"
              >
                <span class="ad-tag">广告</span>
                <img :src="currentBanner.image" :alt="currentBanner.title || ''" @error="onImgError">
              </NuxtLink>
              <a
                v-else-if="isExternalAdLink(currentBanner.link)"
                :key="'ex-' + (currentBanner.id || bannerIndex)"
                class="slide img-slide on"
                :href="currentBanner.link"
                target="_blank"
                rel="noopener noreferrer"
                :title="currentBanner.title || ''"
              >
                <span class="ad-tag">广告</span>
                <img :src="currentBanner.image" :alt="currentBanner.title || ''" @error="onImgError">
              </a>
              <div
                v-else
                :key="'na-' + (currentBanner.id || bannerIndex)"
                class="slide img-slide on"
                :title="currentBanner.title || ''"
              >
                <span class="ad-tag">广告</span>
                <img :src="currentBanner.image" :alt="currentBanner.title || ''" @error="onImgError">
              </div>
            </template>
            <div class="dots">
              <button
                v-for="(b, i) in banners"
                :key="'d' + i"
                type="button"
                :class="{ on: i === bannerIndex }"
                @click="bannerIndex = i"
              >{{ i + 1 }}</button>
            </div>
            <button
              v-if="banners.length > 1"
              type="button"
              class="bn-nav bn-prev"
              aria-label="上一张广告"
              @click.stop.prevent="prevBanner"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 6l-6 6 6 6"/></svg>
            </button>
            <button
              v-if="banners.length > 1"
              type="button"
              class="bn-nav bn-next"
              aria-label="下一张广告"
              @click.stop.prevent="nextBanner"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
            </button>
          </div>

          <div v-if="activeCat && catPanel" class="cat-detail">
            <div class="cd-title">
              <b>{{ activeCat.name }}</b>
            </div>
            <div class="cat-detail-body cat-panel on">
              <div class="cat-group brand-rec">
                <span class="g-t">品牌推荐</span>
              </div>
              <div class="prod-grid">
                <template v-for="(c, i) in catPanel.primary" :key="'p' + (c.id || i)">
                  <NuxtLink
                    v-if="isInternalAdLink(c.link)"
                    class="prod-card is-ad"
                    :to="c.link"
                    :title="c.label || undefined"
                  >
                    <span class="p-img">
                      <img :src="c.thumb" :alt="c.label || '广告'" loading="lazy" @error="onImgError">
                      <em v-if="c.fromAd" class="ad-mark">广告</em>
                    </span>
                    <b>{{ c.label }}</b>
                    <span v-if="c.company" class="p-co" :title="c.company">{{ c.company }}</span>
                  </NuxtLink>
                  <a
                    v-else-if="isExternalAdLink(c.link)"
                    class="prod-card is-ad"
                    :href="c.link"
                    target="_blank"
                    rel="noopener noreferrer"
                    :title="c.label || undefined"
                  >
                    <span class="p-img">
                      <img :src="c.thumb" :alt="c.label || '广告'" loading="lazy" @error="onImgError">
                      <em v-if="c.fromAd" class="ad-mark">广告</em>
                    </span>
                    <b>{{ c.label }}</b>
                    <span v-if="c.company" class="p-co" :title="c.company">{{ c.company }}</span>
                  </a>
                  <div v-else class="prod-card is-ad">
                    <span class="p-img">
                      <img :src="c.thumb" :alt="c.label || '广告'" loading="lazy" @error="onImgError">
                      <em v-if="c.fromAd" class="ad-mark">广告</em>
                    </span>
                    <b>{{ c.label }}</b>
                    <span v-if="c.company" class="p-co" :title="c.company">{{ c.company }}</span>
                  </div>
                </template>
              </div>
              <div class="portal-buy-row">
                <NuxtLink to="/ads?place=home_portal">购买展位</NuxtLink>
              </div>

              <div v-if="catPanel.subItems.length" class="cat-group">
                <span class="g-t">子分类</span>
                <div class="g-l">
                  <NuxtLink
                    v-for="(it, ii) in catPanel.subItems"
                    :key="'s' + ii"
                    :to="it.to"
                  >{{ it.name }}</NuxtLink>
                </div>
              </div>
              <div v-if="catPanel.hotItems.length" class="cat-group">
                <span class="g-t">热门供应</span>
                <div class="g-l">
                  <NuxtLink
                    v-for="(it, ii) in catPanel.hotItems"
                    :key="'h' + ii"
                    :to="it.to"
                  >{{ it.name }}</NuxtLink>
                </div>
              </div>
            </div>
          </div>
        </div>

        <aside class="side-col">
          <div class="login-card">
            <div class="lc-t">
              <template v-if="user">您好，欢迎回来 <span class="lc-who"><NuxtLink class="lc-name" to="/member" :title="displayNameFull">{{ displayName }}</NuxtLink><VipHover><MemberCorner :user="user" large /></VipHover></span></template>
              <template v-else>您好，欢迎来到有机硅商城</template>
            </div>
            <div class="lc-s">
              <template v-if="!user">{{ loginSubText }}</template>
              <template v-else-if="pendingItems.length">
                您有<template v-for="(item, i) in pendingItems" :key="item.key"><span v-if="i">、</span><NuxtLink :to="item.to">{{ item.text }}</NuxtLink></template>待处理！
              </template>
              <template v-else>{{ idleSlogan }}</template>
            </div>
            <div v-if="!user" class="lc-btns">
              <NuxtLink class="btn btn--light" to="/login">登录</NuxtLink>
              <NuxtLink class="btn btn--outline" to="/register">注册</NuxtLink>
            </div>
            <div v-else class="lc-btns">
              <NuxtLink class="btn btn--light" to="/member">商务中心</NuxtLink>
              <NuxtLink class="btn btn--light lc-msg" to="/member/messages">
                消息中心
                <em v-if="pendingTotal > 0" class="lc-badge">{{ pendingBadge }}</em>
              </NuxtLink>
            </div>
            <div class="lc-btns">
              <NuxtLink class="btn btn--light lc-publish" to="/member/supplies/create">
                <b>免费发布供应 / 求购</b>
                <span>直达数万行业买家</span>
              </NuxtLink>
            </div>
          </div>

          <div class="notice-card">
            <div class="n-head">
              <b>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
                网站公告
              </b>
              <NuxtLink class="more-link" to="/announce">更多 ›</NuxtLink>
            </div>
            <ul>
              <li v-for="a in (home.announcements || []).slice(0, 10)" :key="a.id">
                <NuxtLink :to="announceHref(a)">{{ a.title }}</NuxtLink>
              </li>
              <li v-if="!(home.announcements || []).length">
                <NuxtLink to="/announce">VIP会员可享首页推荐位优先展示</NuxtLink>
              </li>
              <li v-if="!(home.announcements || []).length">
                <NuxtLink to="/pages/help">如何让您发布的信息排名提前</NuxtLink>
              </li>
              <li v-if="!(home.announcements || []).length">
                <NuxtLink to="/pages/help">忘记了登录名密码该如何找回</NuxtLink>
              </li>
              <li v-if="!(home.announcements || []).length">
                <NuxtLink to="/pages/contact">联系方式变更怎么办？</NuxtLink>
              </li>
            </ul>
          </div>

          <div class="stat-card">
            <div class="st">
              <b class="num">{{ fmtStat(homeStats.companies) }}+</b>
              <span>入驻企业</span>
            </div>
            <div class="st">
              <b class="num">{{ fmtStat(homeStats.products) }}+</b>
              <span>挂牌产品</span>
            </div>
            <div class="st">
              <b class="num">{{ homeStats.categories }}</b>
              <span>大产品品类</span>
            </div>
          </div>
        </aside>
      </section>

      <!-- 价格行情 -->
      <PriceTrendChart :products="quoteProducts" />

      <!-- 名企推荐 -->
      <section v-if="eliteTabs.length" id="company" class="section">
        <div class="sec-head">
          <div>
            <h2>名企推荐</h2>
            <p class="sub">精选优质供应商，按经营领域分区展示，品质有保障</p>
          </div>
          <NuxtLink class="more-link" to="/companies">更多企业 ›</NuxtLink>
        </div>
        <div class="chips elite-chips">
          <button
            v-for="(t, i) in eliteTabs"
            :key="t.id"
            type="button"
            class="chip"
            :class="{ active: eliteTab === i }"
            @click="eliteTab = i"
          >{{ t.name }}</button>
        </div>
        <div class="company-grid">
          <NuxtLink
            v-for="c in eliteCompanies"
            :key="'ec' + c.id"
            class="company"
            :to="`/companies/${c.slug || c.id}`"
          >
            <div class="c-top">
              <div class="c-left">
                <div class="photo">
                  <img :src="c.logo" :alt="c.name" loading="lazy" @error="onImgError">
                </div>
                <MemberBadge
                  v-if="c.memberGrade && c.memberGrade !== 'normal'"
                  class="c-grade"
                  :grade="c.memberGrade"
                  :label="c.memberBadge"
                />
              </div>
              <div class="c-info">
                <b>
                  <span :title="c.name">{{ c.shortName || c.name }}</span>
                  <i v-if="c.vip" class="vip">名企</i>
                </b>
                <div class="sub">{{ c.meta || '\u00a0' }}</div>
                <div class="tags">
                  <span v-for="(tg, ti) in (c.tags?.length ? c.tags : ['优质供应商', '行业推荐']).slice(0, 4)" :key="ti">{{ tg }}</span>
                </div>
              </div>
            </div>
            <p class="desc">{{ c.desc || '精选优质供应商，欢迎进入店铺了解产品与合作详情。' }}</p>
            <span class="shop">进入店铺</span>
          </NuxtLink>
          <div
            v-for="h in eliteHireSlots"
            :key="h.key"
            class="company hire"
          >
            <span class="hi">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
            </span>
            <b>{{ h.title }}</b>
            <p v-html="h.desc"></p>
            <NuxtLink to="/ads?place=company_top&from=elite">立即咨询 →</NuxtLink>
          </div>
        </div>
      </section>

      <!-- 材料产品楼层（设计图菜单「产品分类」锚点 #products） -->
      <section id="products" class="section">
        <div class="sec-head">
          <div>
            <h2>材料产品</h2>
            <p class="sub">八大品类材料产品一站式展示，点击查看详情与更多</p>
          </div>
          <NuxtLink class="more-link" to="/sell">查看更多 ›</NuxtLink>
        </div>

        <div ref="matList" class="mat-list">
          <div
            v-for="(floor, fi) in floors"
            :key="floor.category?.id || fi"
            class="mat-row"
            @mouseenter="pauseFloor(floor)"
            @mouseleave="resumeFloor(floor)"
          >
            <div
              class="mat-side"
              :style="{ background: floorSideGrad(floor) }"
            >
              <b v-html="floorSideTitleHtml(floor.category?.name)"></b>
              <div class="m-tags">
                <template v-if="floorBuckets(floor).length">
                  <button
                    v-for="(tag, ti) in floorBuckets(floor)"
                    :key="tag.id || ti"
                    type="button"
                    class="m-tag"
                    :class="{ on: floorFocus(floor) === ti }"
                    :aria-pressed="floorFocus(floor) === ti"
                    @click="pickFloorTag(floor, ti)"
                  >{{ tag.name }}</button>
                </template>
                <template v-else>
                  <NuxtLink
                    v-for="(tag, ti) in floorSideTags(floor)"
                    :key="tag.id || ti"
                    :to="categoryNavTo(tag.id ?? tag.name, floor.category)"
                  >{{ tag.name }}</NuxtLink>
                </template>
              </div>
              <div class="mat-side-acts">
                <NuxtLink
                  class="more-btn"
                  :to="categoryNavTo(floor.category?.id, floor.category)"
                >查看更多</NuxtLink>
                <NuxtLink
                  v-if="floor.promo_code"
                  class="more-btn"
                  :to="'/ads'"
                >购买推广位</NuxtLink>
              </div>
            </div>

            <div v-if="floorBuckets(floor).length" class="mat-viewport" :class="{ 'is-pages': floorBuckets(floor).length > 1 }">
              <div
                class="mat-pages"
                :style="floorTrackStyle(floor)"
                @transitionend="onFloorTrackEnd(floor, $event)"
              >
                <div
                  v-for="(page, pi) in floorPages(floor)"
                  :key="`${floor.category?.id || fi}-p${pi}`"
                  class="mat-page"
                >
                <div
                  v-for="(s, si) in page.items"
                  :key="`${floor.category?.id || fi}-${pi}-${si}-${s.id}`"
                  class="mat-card"
                >
                      <NuxtLink class="mat-main" :to="floorCardTo(s)">
                        <div class="m-img">
                          <img :src="s.thumb" :alt="s.title" loading="lazy" @error="onImgError">
                          <em v-if="s.is_promoted" class="m-promo">推广</em>
                        </div>
                        <div class="m-name" :title="s.title">{{ s.title }}</div>
                        <div
                          class="m-price"
                          :class="{ inquire: !(s.price != null && s.price !== '') }"
                        >
                          <template v-if="s.price != null && s.price !== ''">
                            {{ tipFor(s).main }}<small>{{ tipFor(s).small }}</small>
                          </template>
                          <template v-else>面议</template>
                        </div>
                      </NuxtLink>
                      <div v-if="s.member_badge || shopName(s)" class="m-seller">
                        <MemberBadge v-if="s.member_badge" :grade="s.member_grade" :label="s.member_badge" />
                        <NuxtLink v-if="shopName(s) && merchantShopKey(s)" class="m-co" :to="`/companies/${merchantShopKey(s)}`" :title="shopName(s)">{{ shopName(s) }}</NuxtLink>
                        <div v-else-if="shopName(s)" class="m-co" :title="shopName(s)">{{ shopName(s) }}</div>
                      </div>
                      <NuxtLink class="mat-main" :to="floorCardTo(s)">
                        <div class="m-meta">
                          <span><i>发货地</i>{{ areaOf(s) }}</span>
                          <span><i>库存</i>{{ stockOf(s) }}</span>
                          <span><i>提货</i>{{ pickupOf(s) }}</span>
                        </div>
                      </NuxtLink>
                      <div class="m-ops">
                        <button
                          type="button"
                          class="op-fav"
                          :disabled="!!favBusy[String(s.id)]"
                          @click="onMatFav(s, $event)"
                        >关注</button>
                        <NuxtLink class="op-msg" :to="matMsgTo(s)">留言</NuxtLink>
                        <NuxtLink class="op-tel" :to="matContactTo(s)">联系方式</NuxtLink>
                      </div>
                </div>
                </div>
              </div>
            </div>
            <div v-else-if="floorCards(floor).length" class="mat-grid">
              <div
                v-for="(s, si) in floorCards(floor)"
                :key="s.id || si"
                class="mat-card"
              >
                <NuxtLink class="mat-main" :to="floorCardTo(s)">
                  <div class="m-img">
                    <img :src="s.thumb" :alt="s.title" loading="lazy" @error="onImgError">
                    <em v-if="s.is_promoted" class="m-promo">推广</em>
                  </div>
                  <div class="m-name" :title="s.title">{{ s.title }}</div>
                  <div
                    class="m-price"
                    :class="{ inquire: !(s.price != null && s.price !== '') }"
                  >
                    <template v-if="s.price != null && s.price !== ''">
                      {{ tipFor(s).main }}<small>{{ tipFor(s).small }}</small>
                    </template>
                    <template v-else>面议</template>
                  </div>
                </NuxtLink>
                <div v-if="s.member_badge || shopName(s)" class="m-seller">
                  <MemberBadge v-if="s.member_badge" :grade="s.member_grade" :label="s.member_badge" />
                  <NuxtLink v-if="shopName(s) && merchantShopKey(s)" class="m-co" :to="`/companies/${merchantShopKey(s)}`" :title="shopName(s)">{{ shopName(s) }}</NuxtLink>
                  <div v-else-if="shopName(s)" class="m-co" :title="shopName(s)">{{ shopName(s) }}</div>
                </div>
                <NuxtLink class="mat-main" :to="floorCardTo(s)">
                  <div class="m-meta">
                    <span><i>发货地</i>{{ areaOf(s) }}</span>
                    <span><i>库存</i>{{ stockOf(s) }}</span>
                    <span><i>提货</i>{{ pickupOf(s) }}</span>
                  </div>
                </NuxtLink>
                <div class="m-ops">
                  <button
                    type="button"
                    class="op-fav"
                    :disabled="!!favBusy[String(s.id)]"
                    @click="onMatFav(s, $event)"
                  >关注</button>
                  <NuxtLink class="op-msg" :to="matMsgTo(s)">留言</NuxtLink>
                  <NuxtLink class="op-tel" :to="matContactTo(s)">联系方式</NuxtLink>
                </div>
              </div>
            </div>
            <div v-else class="mat-empty">
              <p>暂无供应</p>
              <NuxtLink :to="categoryNavTo(floor.category?.id, floor.category)">去分类看看 ›</NuxtLink>
            </div>
          </div>
        </div>
      </section>

      <!-- 供求信息 -->
      <section id="supply" class="section">
        <div class="sec-head">
          <div>
            <h2>供求信息</h2>
            <p class="sub">最新供应与求购实时更新，供需对接更高效</p>
          </div>
        </div>
        <div class="supply-grid">
          <div class="supply-box">
            <div class="s-head">
              <b>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M4 12h10M4 17h14"/><path d="M16 10l4 2-4 2"/></svg>
                最新供应
              </b>
              <NuxtLink to="/sell" class="more-link">更多 ›</NuxtLink>
            </div>
            <div
              v-if="supplyPages.length"
              class="roll-viewport"
              @mouseenter="pauseList('supply')"
              @mouseleave="resumeList('supply')"
            >
              <div class="roll-track" :style="listTrack('supply')" @transitionend="onListEnd('supply', supplyPageCount, $event)">
                <ul v-for="(page, pi) in supplyPages" :key="'sp' + pi" class="roll-page">
                  <li v-for="s in page" :key="'sw' + pi + '-' + s.id">
                    <NuxtLink class="t" :to="`/sell/${s.id}`">
                      <span v-if="s.is_hot" class="hot">热</span>{{ s.title }}
                    </NuxtLink>
                    <span class="tag">{{ areaOf(s) }}</span>
                  </li>
                </ul>
              </div>
            </div>
            <ul v-else>
              <li class="empty">暂无供应信息</li>
            </ul>
          </div>
          <div class="supply-box buy">
            <div class="s-head">
              <b>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.2-3.2"/><path d="M8.5 11h5M11 8.5v5"/></svg>
                最新求购
              </b>
              <NuxtLink to="/buy" class="more-link">更多 ›</NuxtLink>
            </div>
            <div
              v-if="buyPages.length"
              class="roll-viewport"
              @mouseenter="pauseList('buy')"
              @mouseleave="resumeList('buy')"
            >
              <div class="roll-track" :style="listTrack('buy')" @transitionend="onListEnd('buy', buyPageCount, $event)">
                <ul v-for="(page, pi) in buyPages" :key="'bp' + pi" class="roll-page">
                  <li v-for="b in page" :key="'bw' + pi + '-' + b.id">
                    <NuxtLink class="t" :to="`/buy/${b.id}`">
                      <span v-if="b.is_hot" class="hot">热</span>{{ b.title }}
                    </NuxtLink>
                    <span class="tag">{{ areaOf(b) }}</span>
                  </li>
                </ul>
              </div>
            </div>
            <ul v-else>
              <li class="empty">暂无求购信息</li>
            </ul>
          </div>
        </div>
      </section>

      <!-- 行业资讯 -->
      <section id="news" class="section">
        <div class="sec-head">
          <div>
            <h2>行业资讯</h2>
            <p class="sub">有机硅市场动态、行情分析与技术解读</p>
          </div>
          <NuxtLink class="more-link" to="/news">更多资讯 ›</NuxtLink>
        </div>
        <div class="news-grid">
          <div class="news-main">
            <div class="news-cats">
              <button
                v-for="chip in NEWS_CHIPS"
                :key="chip.key"
                type="button"
                class="chip"
                :class="{ active: newsChip === chip.key }"
                @click="newsChip = chip.key"
              >{{ chip.label }}</button>
            </div>
            <div
              v-if="newsPages.length"
              class="roll-viewport"
              @mouseenter="pauseList('news')"
              @mouseleave="resumeList('news')"
            >
              <div class="roll-track" :style="listTrack('news')" @transitionend="onListEnd('news', newsPageCount, $event)">
                <ul v-for="(page, pi) in newsPages" :key="'np' + pi" class="roll-page">
                  <li v-for="a in page" :key="'na' + pi + '-' + a.id" class="news-item">
                    <span class="cate">资讯</span>
                    <NuxtLink class="t" :to="`/news/${a.id}`">{{ a.title }}</NuxtLink>
                    <time>{{ fmtDate(a.published_at) }}</time>
                  </li>
                </ul>
              </div>
            </div>
            <ul v-else>
              <li class="empty">暂无资讯</li>
            </ul>
          </div>
          <div class="news-rank">
            <div class="rk-title">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 01-10 0V4z"/><path d="M7 6H5a2 2 0 00-2 2c0 2.2 1.8 3 4 3M17 6h2a2 2 0 012 2c0 2.2-1.8 3-4 3"/></svg>
              资讯排行榜
            </div>
            <div
              v-if="rankPages.length"
              class="roll-viewport"
              @mouseenter="pauseList('rank')"
              @mouseleave="resumeList('rank')"
            >
              <div class="roll-track" :style="listTrack('rank')" @transitionend="onListEnd('rank', rankPageCount, $event)">
                <ol v-for="(page, pi) in rankPages" :key="'rp' + pi" class="roll-page">
                  <li v-for="a in page" :key="'rk' + pi + '-' + a.id" class="rank-item" :class="{ top: a.top }">
                    <i class="no">{{ a.rankNo }}</i>
                    <NuxtLink class="t" :to="`/news/${a.id}`">{{ a.title }}</NuxtLink>
                    <em class="h">{{ a.views || '' }}</em>
                  </li>
                </ol>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- CTA -->
      <section class="section">
        <div class="cta">
          <div class="cta-in">
            <div>
              <h3>入驻有机硅商城，让生意快一步</h3>
              <p>免费发布供应/求购信息，精准触达数万有机硅行业买家</p>
            </div>
            <div class="btns">
              <NuxtLink class="btn btn--accent" to="/register">免费入驻 / 发布</NuxtLink>
              <NuxtLink class="btn btn--ghost" to="/member/grade">升级 VIP 会员</NuxtLink>
            </div>
          </div>
        </div>
      </section>

      <!-- 友情链接 -->
      <div v-if="(home.links || []).length" class="friend-bar">
        <b>友情链接</b>
        <div class="friend-links">
          <a
            v-for="l in home.links"
            :key="l.id"
            :href="l.url"
            target="_blank"
            rel="noopener"
          >{{ l.name }}</a>
        </div>
        <NuxtLink class="more" to="/link">申请链接 ›</NuxtLink>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Vue-specific helpers only — layout/chrome lives in portal-design.css */
.portal-home {
  color: var(--ink);
}

/* 分类按钮样式全部交给 portal-design.css；勿 overflow，否则裁切右侧 flyout */
.cat-side .cat-item > button {
  cursor: pointer;
}
.lc-who {
  display: inline-flex;
  align-items: center;
  max-width: 100%;
  min-width: 0;
  white-space: nowrap;
  vertical-align: middle;
}
.lc-who .lc-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  min-width: 0;
  font-weight: 800;
  color: #fff;
  text-decoration: none;
  cursor: pointer;
}
.lc-who .lc-name:hover {
  text-decoration: underline;
  text-underline-offset: 2px;
  opacity: 0.92;
}
.lc-who :deep(.vip-hover),
.lc-who :deep(.mbadge) {
  flex-shrink: 0;
}

/* Image banner slides (design .slide is text-overlay oriented) */
.banner-carousel .img-slide {
  padding: 0;
  display: block;
  pointer-events: none;
}
.banner-carousel .img-slide.on {
  pointer-events: auto;
}
.banner-carousel .img-slide img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.cat-detail-body { min-width: 0; }
.cd-title a { text-decoration: none; }
.cd-title a.more-link { color: var(--blue); font-weight: 600; }
.prod-card { text-decoration: none; color: inherit; }
.prod-card.is-ad .p-img {
  position: relative;
  aspect-ratio: 16 / 9;
  overflow: hidden;
}
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
.portal-buy-row {
  display: flex;
  justify-content: flex-end;
  margin-top: 6px;
}
.portal-buy-row a {
  font-size: 12px;
  color: var(--blue);
  text-decoration: none;
}
.portal-buy-row a:hover { text-decoration: underline; }
.prod-card.is-ad .p-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.prod-card.is-ad b {
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.cat-group .g-l a { text-decoration: none; }

.company .photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.company { text-decoration: none; color: inherit; }
.company.hire a { text-decoration: none; }

.mat-side { min-height: 220px; color: #fff; }
.mat-side b { color: #fff; font-weight: 800; }
.mat-side .m-tags {
  flex: 0 0 auto;
  align-content: flex-start;
  gap: 6px 10px;
}
.mat-side .m-tags a {
  color: rgba(255, 255, 255, 0.88) !important;
  text-decoration: none;
  font-weight: 500;
  line-height: 1.35;
}
.mat-side .m-tags a:hover { color: #fff !important; text-decoration: underline; }
.mat-side .more-btn {
  text-decoration: none;
  color: #fff !important;
  border-color: rgba(255, 255, 255, 0.65);
}
.mat-side .more-btn:first-of-type { margin-top: auto; }
.mat-side .more-btn + .more-btn { margin-top: 8px; }
.mat-side .more-btn:hover { background: rgba(255, 255, 255, 0.12); color: #fff !important; }
.mat-main {
  display: flex;
  flex-direction: column;
  gap: 7px;
  color: inherit;
  text-decoration: none;
  flex: 1;
  min-width: 0;
}
.mat-card .m-img {
  position: relative;
  width: 100%;
  max-width: 100%;
  margin: 0;
  aspect-ratio: 1 / 1;
  max-height: 180px;
  border-radius: 8px;
  overflow: hidden;
  background: #EEF2F8;
  padding: 0;
  flex-shrink: 0;
}
.m-promo {
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
.mat-card .m-img img {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  max-width: 100%;
  max-height: 100%;
  object-fit: cover;
  object-position: center;
  display: block;
}
.mat-card .m-name { color: var(--ink); }
.mat-card .m-seller {
  justify-content: flex-start;
  gap: 4px;
  padding: 0;
}
.mat-card .m-co {
  flex: 1 1 auto;
  min-width: 0;
  text-align: left;
}
.mat-card a.m-co:hover { color: var(--blue); }
.mat-card:hover .m-name { color: var(--blue); }
.mat-card .m-price {
  color: var(--up);
  font-size: 15px;
  font-weight: 800;
  line-height: 1.35;
  text-align: center;
}
.mat-card .m-price small {
  font-size: 10.5px;
  font-weight: 500;
  color: var(--up);
}
.mat-card .m-price.inquire {
  font-size: 14px;
  font-weight: 700;
}
.mat-card .m-meta span { color: var(--ink); }
.mat-card .m-meta i { color: var(--ink-3); }
.mat-card .op-fav,
.mat-card .op-msg,
.mat-card .op-tel {
  text-decoration: none;
}
.mat-card .op-tel {
  color: #fff !important;
}
.mat-card .op-tel:hover {
  color: #fff !important;
  background: #c9372b !important;
  border-color: #c9372b !important;
}
.m-ops :deep(.chat-btn-wrap) {
  flex: 1;
  min-width: 0;
  display: flex;
  gap: 0;
}
.m-ops :deep(.chat-btn-wrap .btn) {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 30px;
  padding: 0 6px;
  box-sizing: border-box;
  border-radius: 100px;
  font-size: 12px;
  font-weight: 600;
  line-height: 1;
  color: #fff !important;
  background: var(--up) !important;
  border: 1px solid var(--up) !important;
  box-shadow: none !important;
}
.m-ops :deep(.chat-btn-wrap .btn:hover) {
  background: #c9372b !important;
  border-color: #c9372b !important;
}
.m-ops :deep(.tip) { display: none; }

.supply-box ul {
  list-style: none;
  margin: 0;
  padding: 0;
}
.supply-box li.empty {
  color: var(--ink-3);
  justify-content: center;
  padding: 24px 0;
}
.news-main ul,
.news-rank ol {
  list-style: none;
  margin: 0;
  padding: 0;
}
.news-item .empty,
li.empty {
  color: var(--ink-3);
  justify-content: center;
  padding: 24px 0;
}
.cta .btn { text-decoration: none; filter: none; }
.cta .btn--accent {
  color: var(--blue-deep) !important;
  background: #fff !important;
}
.cta .btn--accent:hover {
  color: var(--blue) !important;
  background: #F0F5FE !important;
}

.friend-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 28px;
  padding: 12px 16px;
  background: #fff;
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-card);
  font-size: 13px;
}
.friend-bar b { color: var(--ink-2); }
.friend-links {
  display: flex;
  flex-wrap: wrap;
  gap: 10px 14px;
  flex: 1;
}
.friend-links a { color: var(--ink-2); }
.friend-links a:hover { color: var(--blue); }
.friend-bar .more {
  margin-left: auto;
  color: var(--ink-3);
}

.elite-chips {
  margin-bottom: 0;
}
.chips .chip {
  appearance: none;
  border: 0;
  cursor: pointer;
  box-shadow: var(--shadow-card);
}
.cta .btn--ghost {
  background: transparent;
}
</style>

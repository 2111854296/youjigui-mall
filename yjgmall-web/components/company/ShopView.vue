<script setup lang="ts">
/**
 * 企业主页：Destoon 模块齐全，视觉对齐官网首页（蓝白 B2B）
 */
const props = defineProps<{
  item: any
  template: 'atelier' | 'forge' | 'ledger'
}>()

const route = useRoute()
const router = useRouter()

type Tab =
  | 'home' | 'about' | 'products' | 'news' | 'honors' | 'jobs' | 'credit' | 'contact'
  | `page-${string | number}`

const tab = computed<Tab>(() => String(route.query.tab || 'home') as Tab)
function goTab(t: Tab) {
  router.replace({ path: route.path, query: t === 'home' ? {} : { tab: t } })
}

const siteQ = ref('')
function searchSite() {
  if (!siteQ.value.trim()) return
  goTab('products')
}

const kw = computed(() => {
  if (tab.value !== 'products' && tab.value !== 'home') return ''
  return siteQ.value.trim().toLowerCase()
})
function matchTitle(title: string) {
  if (!kw.value) return true
  return String(title || '').toLowerCase().includes(kw.value)
}

const supplies = computed(() => (props.item?.supplies || []).filter((s: any) => matchTitle(s.title)))
const products = computed(() => (props.item?.products || []).filter((p: any) => matchTitle(p.title)))
const newsList = computed(() => props.item?.news || [])
const honorList = computed(() => props.item?.honors || [])
const jobList = computed(() => props.item?.jobs || [])
const linkList = computed(() => props.item?.links || [])

const showcase = computed(() => {
  const a = supplies.value.slice(0, 8).map((s: any, i: number) => ({
    key: `s${s.id}`, to: `/sell/${s.id}`, title: s.title,
    price: s.price ? `¥${s.price}` : '面议', thumb: s.thumb, i,
  }))
  if (a.length >= 8) return a
  return [
    ...a,
    ...products.value.slice(0, 8 - a.length).map((p: any, i: number) => ({
      key: `p${p.id}`, to: `/mall/${p.id}`, title: p.title,
      price: p.price ? `¥${p.price}` : '面议', thumb: p.thumb, i: i + 4,
    })),
  ]
})

function fullSrc(src?: string | null) {
  const t = String(src || '').trim()
  if (!t) return ''
  return t.replace(/\.thumb\.(jpe?g|png|gif|webp)(\?.*)?$/i, '$2')
}
const fallback = '/img/logo-yjg.png'
function media(src?: string | null, _i = 0) {
  const t = fullSrc(src)
  if (t && !/\.svg($|\?)/i.test(t) && !/placehold/i.test(t) && !/\/img\/products\/p\d+/i.test(t)) return t
  return fallback
}
function onImgErr(e: Event, raw?: string | null) {
  const el = e.target as HTMLImageElement
  if (!el || el.dataset.fallbackTried) return
  el.dataset.fallbackTried = '1'
  const rawSrc = String(raw || '').trim()
  if (rawSrc && rawSrc !== el.getAttribute('src')) {
    el.src = rawSrc
    return
  }
  if (/\.(jpe?g|png|gif|webp)$/i.test(el.src) && !/\.thumb\./i.test(el.src)) {
    el.src = el.src.replace(/\.(jpe?g|png|gif|webp)(\?.*)?$/i, '.thumb.$1$2')
  }
}
function onBannerErr(e: Event) {
  const el = e.target as HTMLImageElement
  if (!el || el.dataset.fb) return
  el.dataset.fb = '1'
  if (!el.src.includes(DEFAULT_SHOP_BANNER)) {
    el.src = DEFAULT_SHOP_BANNER
    return
  }
  el.src = '/img/banner/co2.jpg'
}

const DEFAULT_SHOP_BANNER = '/img/banner/co1.png'
const shopBanner = computed(() => {
  const raw = String(props.item?.banner || '').trim()
  if (!raw || /\/company-skin\//i.test(raw)) return ''
  return media(raw, 0)
})

/** 厂房环境图：一屏 4 格，最多 8 张，底部可拖滑块 */
const ENV_VISIBLE = 4
const envTrack = ref<HTMLElement | null>(null)
const envProgress = ref(0)
const envCanScroll = ref(false)
const envScrubbing = ref(false)

const envSlides = computed(() => {
  const raw = Array.isArray(props.item?.env_images) ? props.item.env_images : []
  const urls = raw
    .map((u: any) => String(u || '').trim())
    .filter(Boolean)
    .slice(0, 8)
    .map((u: string, i: number) => ({ key: `e${i}`, src: media(u, i), empty: false }))
  if (!urls.length) {
    const first = String(props.item?.banner || '').trim() && !/\/company-skin\//i.test(String(props.item?.banner || ''))
      ? media(props.item.banner, 0)
      : (props.item?.logo ? media(props.item.logo, 0) : DEFAULT_SHOP_BANNER)
    return [
      { key: 'e0', src: first, empty: false },
      { key: 'e1', src: '', empty: true },
      { key: 'e2', src: '', empty: true },
      { key: 'e3', src: '', empty: true },
    ]
  }
  while (urls.length < ENV_VISIBLE) {
    urls.push({ key: `p${urls.length}`, src: '', empty: true })
  }
  return urls
})

const envThumbPct = computed(() => {
  const n = Math.max(ENV_VISIBLE, envSlides.value.filter((s) => !s.empty).length || ENV_VISIBLE)
  if (n <= ENV_VISIBLE) return 100
  return Math.max(32, (ENV_VISIBLE / n) * 100)
})

function syncEnvScroll() {
  const el = envTrack.value
  if (!el) return
  const max = el.scrollWidth - el.clientWidth
  envCanScroll.value = max > 4
  envProgress.value = max > 0 ? el.scrollLeft / max : 0
}

function scrollEnv(dir: -1 | 1) {
  const el = envTrack.value
  if (!el) return
  const step = Math.max(120, el.clientWidth * 0.55)
  el.scrollBy({ left: dir * step, behavior: 'smooth' })
}

function scrubTo(clientX: number, bar: HTMLElement) {
  const el = envTrack.value
  if (!el) return
  const rect = bar.getBoundingClientRect()
  const ratio = Math.min(1, Math.max(0, (clientX - rect.left) / Math.max(1, rect.width)))
  const max = el.scrollWidth - el.clientWidth
  // 直接跟手，不要一块一块跳
  el.scrollLeft = max * ratio
  syncEnvScroll()
}

function onEnvScrubDown(e: MouseEvent | TouchEvent) {
  const bar = e.currentTarget as HTMLElement
  if (!bar) return
  envScrubbing.value = true
  const clientX = 'touches' in e ? e.touches[0]?.clientX : (e as MouseEvent).clientX
  if (clientX != null) scrubTo(clientX, bar)

  const move = (ev: MouseEvent | TouchEvent) => {
    const x = 'touches' in ev ? ev.touches[0]?.clientX : (ev as MouseEvent).clientX
    if (x != null) scrubTo(x, bar)
  }
  const up = () => {
    envScrubbing.value = false
    window.removeEventListener('mousemove', move)
    window.removeEventListener('mouseup', up)
    window.removeEventListener('touchmove', move)
    window.removeEventListener('touchend', up)
  }
  window.addEventListener('mousemove', move)
  window.addEventListener('mouseup', up)
  window.addEventListener('touchmove', move, { passive: true })
  window.addEventListener('touchend', up)
}

onMounted(() => {
  nextTick(syncEnvScroll)
  window.addEventListener('resize', syncEnvScroll, { passive: true })
  window.addEventListener('keydown', onPreviewKey)
})
onBeforeUnmount(() => {
  window.removeEventListener('resize', syncEnvScroll)
  window.removeEventListener('keydown', onPreviewKey)
})
watch(envSlides, () => nextTick(syncEnvScroll))

const preview = ref<{ src: string; list: string[]; index: number } | null>(null)

function openPreview(src?: string | null, list?: string[]) {
  const current = String(src || '').trim()
  if (!current) return
  const urls = (list && list.length ? list : [current]).map((u) => String(u || '').trim()).filter(Boolean)
  const index = Math.max(0, urls.indexOf(current))
  preview.value = { src: urls[index] || current, list: urls.length ? urls : [current], index }
}
function closePreview() {
  preview.value = null
}
function stepPreview(dir: number) {
  const p = preview.value
  if (!p || p.list.length < 2) return
  const index = (p.index + dir + p.list.length) % p.list.length
  preview.value = { src: p.list[index], list: p.list, index }
}
function onPreviewKey(e: KeyboardEvent) {
  if (!preview.value) return
  if (e.key === 'Escape') closePreview()
  else if (e.key === 'ArrowLeft') stepPreview(-1)
  else if (e.key === 'ArrowRight') stepPreview(1)
}
function onEnvClick(slide: { src?: string; empty?: boolean }) {
  if (slide.empty || !slide.src) return
  const list = envSlides.value.filter((s) => !s.empty && s.src).map((s) => s.src)
  openPreview(slide.src, list)
}
function onRichImg(e: MouseEvent) {
  const el = e.target as HTMLElement | null
  if (!el || el.tagName !== 'IMG') return
  const src = (el as HTMLImageElement).currentSrc || (el as HTMLImageElement).src
  if (src) openPreview(src)
}

const activeNews = ref<any>(null)
watch(tab, (t) => {
  if (t !== 'news') activeNews.value = null
})

async function openNews(n: any) {
  goTab('news')
  await nextTick()
  activeNews.value = n
}

/** Destoon 模块导航，空数据也不隐藏入口 */
const navItems = computed(() => {
  const list: { key: Tab; label: string }[] = [
    { key: 'home', label: '首页' },
    { key: 'about', label: '公司介绍' },
    { key: 'products', label: '供应产品' },
    { key: 'news', label: '企业新闻' },
    { key: 'honors', label: '荣誉资质' },
    { key: 'jobs', label: '人才招聘' },
    { key: 'credit', label: '诚信档案' },
    { key: 'contact', label: '联系方式' },
  ]
  for (const p of props.item?.pages || []) {
    list.push({ key: `page-${p.id}` as Tab, label: p.title })
  }
  return list
})

function hasVal(v: unknown) {
  const s = String(v ?? '').trim()
  return !!s && s !== '-' && s !== '0' && s !== '0.00'
}
function pipeList(raw?: string | null) {
  return String(raw || '').split('|').map(s => s.trim()).filter(Boolean)
}
const sellTags = computed(() => pipeList(props.item?.sell_products))
const buyTags = computed(() => pipeList(props.item?.buy_products))
const capitalText = computed(() => {
  if (hasVal(props.item?.capital_display)) return String(props.item.capital_display)
  const cap = String(props.item?.capital || '').trim()
  const unit = String(props.item?.capital_unit || '').trim()
  if (!cap) return ''
  return unit ? cap + unit : cap
})
const tagline = computed(() => {
  const biz = String(props.item?.business || '').split(/[,，、]/)[0]?.trim()
  return biz || props.item?.area || '有机硅材料供应'
})
const introShort = computed(() => {
  const t = String(props.item?.intro || '').trim()
  if (!t) return ''
  return t.length > 180 ? `${t.slice(0, 180)}…` : t
})
const currentPage = computed(() => {
  if (!String(tab.value).startsWith('page-')) return null
  const id = String(tab.value).replace('page-', '')
  return (props.item?.pages || []).find((p: any) => String(p.id) === id) || null
})
</script>

<template>
  <div class="shop">
    <header class="brand-bar">
      <div class="brand-name">{{ item.name }}</div>
      <span class="brand-tag">企业主页</span>
    </header>

    <header class="mast">
      <div class="who">
        <div class="mark">
          <img class="zoomable" :src="media(item.logo)" :alt="item.name" @error="onImgErr($event, item.logo)" @click="openPreview(media(item.logo))">
        </div>
        <div class="who-txt">
          <h1>{{ item.name }}</h1>
          <p>{{ tagline }}</p>
        </div>
      </div>
      <div class="tools">
        <form class="find" @submit.prevent="searchSite">
          <input v-model="siteQ" placeholder="查找产品" aria-label="查找产品">
          <button type="submit">搜索</button>
        </form>
        <a class="cta" href="#" @click.prevent="goTab('contact')">在线询价</a>
      </div>
    </header>

    <nav class="tabs">
      <a
        v-for="n in navItems"
        :key="n.key"
        href="#"
        :class="{ on: tab === n.key }"
        @click.prevent="goTab(n.key)"
      >{{ n.label }}</a>
    </nav>

    <div class="main">
      <template v-if="tab === 'home'">
        <section v-if="shopBanner" class="ad-banner">
          <img class="zoomable" :src="shopBanner" :alt="`${item.name || ''} 广告图`" @error="onImgErr($event, item.banner)" @click="openPreview(shopBanner)">
        </section>
        <section class="env-gallery" aria-label="厂房环境图">
          <div class="env-viewport">
            <button
              v-show="envCanScroll"
              type="button"
              class="env-nav env-prev"
              aria-label="上一组"
              @click.stop="scrollEnv(-1)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <path d="M15 5l-7 7 7 7" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </button>
            <div
              ref="envTrack"
              class="env-track"
              @scroll.passive="syncEnvScroll"
            >
              <div
                v-for="(slide, idx) in envSlides"
                :key="slide.key"
                class="env-cell"
                :class="{ empty: slide.empty, zoom: !slide.empty && slide.src }"
                @click="onEnvClick(slide)"
              >
                <img
                  v-if="!slide.empty && slide.src"
                  :src="slide.src"
                  :alt="`${item.name || ''} 厂房环境 ${idx + 1}`"
                  loading="lazy"
                  draggable="false"
                  @error="onBannerErr"
                >
                <div v-else class="env-ph">
                  <span>厂房环境图</span>
                  <em>待上传</em>
                </div>
              </div>
            </div>
            <button
              v-show="envCanScroll"
              type="button"
              class="env-nav env-next"
              aria-label="下一组"
              @click.stop="scrollEnv(1)"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
                <path d="M9 5l7 7-7 7" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </button>
          </div>
          <div
            class="env-scrub"
            :class="{ on: envScrubbing, active: envCanScroll }"
            role="slider"
            :aria-valuenow="Math.round(envProgress * 100)"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-label="滑动浏览环境图"
            @mousedown.prevent="onEnvScrubDown"
            @touchstart.prevent="onEnvScrubDown"
          >
            <i
              class="env-scrub-thumb"
              :style="{
                width: envThumbPct + '%',
                left: (envCanScroll ? envProgress * (100 - envThumbPct) : 0) + '%',
              }"
            />
          </div>
          <p v-if="envCanScroll" class="env-hint">左右滑动或拖动下方滑块，可查看更多厂房环境图</p>
        </section>

        <section class="panel intro-sec">
          <div class="sec-hd">
            <h3>公司简介</h3>
            <a href="#" @click.prevent="goTab('about')">更多 &gt;</a>
          </div>
          <p class="intro-txt">{{ introShort || tagline }}</p>
          <div class="hero-actions">
            <a class="cta" href="#" @click.prevent="goTab('products')">供应产品</a>
            <a class="ghost" href="#" @click.prevent="goTab('about')">公司介绍</a>
            <a class="ghost" href="#" @click.prevent="goTab('jobs')">人才招聘</a>
          </div>
        </section>

        <section class="panel">
          <div class="sec-hd">
            <h3>供应产品</h3>
            <a href="#" @click.prevent="goTab('products')">更多 &gt;</a>
          </div>
          <div v-if="showcase.length" class="tiles">
            <NuxtLink v-for="g in showcase" :key="g.key" class="tile" :to="g.to">
              <div class="tile-pic">
                <img class="zoomable" :src="media(g.thumb, g.i)" :alt="g.title" loading="lazy" @error="onImgErr($event, g.thumb)" @click.prevent.stop="openPreview(media(g.thumb, g.i), showcase.map((row) => media(row.thumb, row.i)))">
              </div>
              <div class="tile-body">
                <h4>{{ g.title }}</h4>
                <span>{{ g.price }}</span>
              </div>
            </NuxtLink>
          </div>
          <p v-else class="empty">暂无产品，入驻后发布的供应将显示在这里</p>
        </section>

        <div class="dual">
          <section class="panel">
            <div class="sec-hd">
              <h3>企业新闻</h3>
              <a href="#" @click.prevent="goTab('news')">更多 &gt;</a>
            </div>
            <ul v-if="newsList.length" class="rows">
              <li v-for="n in newsList.slice(0, 6)" :key="n.id">
                <a href="#" @click.prevent="openNews(n)">{{ n.title }}</a>
                <em>{{ n.created_at?.slice?.(0, 10) }}</em>
              </li>
            </ul>
            <p v-else class="empty">暂无企业新闻</p>
          </section>
          <section class="panel">
            <div class="sec-hd">
              <h3>人才招聘</h3>
              <a href="#" @click.prevent="goTab('jobs')">更多 &gt;</a>
            </div>
            <ul v-if="jobList.length" class="rows">
              <li v-for="j in jobList.slice(0, 6)" :key="j.id">
                <NuxtLink :to="`/job/${j.id}`">{{ j.title }}</NuxtLink>
                <em>{{ j.salary || j.area || '面议' }}</em>
              </li>
            </ul>
            <p v-else class="empty">暂无招聘职位</p>
          </section>
        </div>

        <section class="panel">
          <div class="sec-hd">
            <h3>荣誉资质</h3>
            <a href="#" @click.prevent="goTab('honors')">更多 &gt;</a>
          </div>
          <div v-if="honorList.length" class="tiles tiles-sm">
            <div v-for="(h, i) in honorList.slice(0, 6)" :key="h.id" class="tile">
              <div class="tile-pic">
                <img class="zoomable" :src="media(h.image, Number(i) + 5)" :alt="h.title" loading="lazy" @click="openPreview(media(h.image, Number(i) + 5), honorList.slice(0, 6).map((row, n) => media(row.image, Number(n) + 5)))">
              </div>
              <div class="tile-body"><h4>{{ h.title }}</h4></div>
            </div>
          </div>
          <p v-else class="empty">暂无荣誉资质</p>
        </section>

        <div class="dual">
          <section class="panel">
            <div class="sec-hd">
              <h3>诚信档案</h3>
              <a href="#" @click.prevent="goTab('credit')">查看 &gt;</a>
            </div>
            <div class="prose tight">
              <p>信用指数 <b>{{ item.credit?.credit_score ?? 0 }}</b></p>
              <p>{{ item.credit?.is_certified ? '企业已认证' : '普通会员' }}</p>
            </div>
          </section>
          <section class="panel">
            <div class="sec-hd">
              <h3>联系方式</h3>
              <a href="#" @click.prevent="goTab('contact')">询价 &gt;</a>
            </div>
            <div class="prose tight">
              <p v-if="hasVal(item.contact)">联系人 {{ item.contact }}</p>
              <p v-if="hasVal(item.phone)">电话 {{ item.phone }}</p>
              <p v-if="hasVal(item.email)">邮箱 {{ item.email }}</p>
              <p v-if="hasVal(item.area) || hasVal(item.address)">{{ item.address || item.area }}</p>
              <p v-if="!hasVal(item.contact) && !hasVal(item.phone)" class="empty">暂无联系资料</p>
            </div>
          </section>
        </div>

        <section v-if="linkList.length" class="panel">
          <div class="sec-hd"><h3>友情链接</h3></div>
          <div class="link-row">
            <a v-for="l in linkList" :key="l.id" :href="l.url" target="_blank" rel="noopener">{{ l.name }}</a>
          </div>
        </section>
      </template>

      <section v-else-if="tab === 'about'" class="panel">
        <div class="sec-hd"><h3>公司介绍</h3></div>
        <div class="prose facts">
          <p v-if="hasVal(item.company_type)"><b>公司类型</b> {{ item.company_type }}</p>
          <p v-if="hasVal(item.business_mode)"><b>经营模式</b> {{ item.business_mode }}</p>
          <p v-if="hasVal(item.scale)"><b>公司规模</b> {{ item.scale }}</p>
          <p v-if="hasVal(item.reg_year)"><b>成立年份</b> {{ item.reg_year }}</p>
          <p v-if="capitalText"><b>注册资本</b> {{ capitalText }}</p>
          <p v-if="hasVal(item.business)"><b>经营范围</b> {{ item.business }}</p>
          <div v-if="sellTags.length" class="tag-row">
            <b>销售产品</b>
            <span v-for="t in sellTags" :key="'s'+t" class="tag">{{ t }}</span>
          </div>
          <div v-if="buyTags.length" class="tag-row">
            <b>采购产品</b>
            <span v-for="t in buyTags" :key="'b'+t" class="tag">{{ t }}</span>
          </div>
        </div>
        <div class="prose" @click="onRichImg">
          <p v-if="item.intro">{{ item.intro }}</p>
          <div v-if="item.content" v-html="item.content" />
          <p v-if="!item.intro && !item.content && !capitalText && !hasVal(item.business)" class="empty">暂无介绍</p>
        </div>
      </section>

      <section v-else-if="tab === 'products'" class="panel">
        <div class="sec-hd"><h3>供应产品</h3></div>
        <div v-if="supplies.length || products.length" class="tiles">
          <NuxtLink v-for="(s, i) in supplies" :key="'s'+s.id" class="tile" :to="`/sell/${s.id}`">
            <div class="tile-pic">
              <img class="zoomable" :src="media(s.thumb, Number(i))" :alt="s.title" loading="lazy" @error="onImgErr($event, s.thumb)" @click.prevent.stop="openPreview(media(s.thumb, Number(i)), supplies.map((row, n) => media(row.thumb, Number(n))))">
            </div>
            <div class="tile-body">
              <h4>{{ s.title }}</h4>
              <span>{{ s.price ? `¥${s.price}` : '面议' }}</span>
            </div>
          </NuxtLink>
          <NuxtLink v-for="(p, i) in products" :key="'p'+p.id" class="tile" :to="`/mall/${p.id}`">
            <div class="tile-pic">
              <img class="zoomable" :src="media(p.thumb, Number(i) + 3)" :alt="p.title" loading="lazy" @error="onImgErr($event, p.thumb)" @click.prevent.stop="openPreview(media(p.thumb, Number(i) + 3), products.map((row, n) => media(row.thumb, Number(n) + 3)))">
            </div>
            <div class="tile-body">
              <h4>{{ p.title }}</h4>
              <span>{{ p.price ? `¥${p.price}` : '面议' }}</span>
            </div>
          </NuxtLink>
        </div>
        <p v-else class="empty">暂无产品</p>
      </section>

      <section v-else-if="tab === 'news'" class="panel">
        <div class="sec-hd"><h3>企业新闻</h3></div>
        <div v-if="activeNews" class="prose">
          <a class="back" href="#" @click.prevent="activeNews = null">← 返回列表</a>
          <h4>{{ activeNews.title }}</h4>
          <div v-html="activeNews.content || '暂无正文'" />
        </div>
        <ul v-else-if="newsList.length" class="rows">
          <li v-for="n in newsList" :key="n.id">
            <a href="#" @click.prevent="activeNews = n">{{ n.title }}</a>
            <em>{{ n.created_at?.slice?.(0, 10) }}</em>
          </li>
        </ul>
        <p v-else class="empty">暂无企业新闻</p>
      </section>

      <section v-else-if="tab === 'honors'" class="panel">
        <div class="sec-hd"><h3>荣誉资质</h3></div>
        <div v-if="honorList.length" class="tiles">
          <div v-for="(h, i) in honorList" :key="h.id" class="tile">
            <div class="tile-pic">
              <img class="zoomable" :src="media(h.image, Number(i) + 5)" :alt="h.title" loading="lazy" @click="openPreview(media(h.image, Number(i) + 5), honorList.map((row, n) => media(row.image, Number(n) + 5)))">
            </div>
            <div class="tile-body">
              <h4>{{ h.title }}</h4>
              <span class="honor-meta">
                {{ h.authority || '—' }}
                · {{ h.issued_at || '—' }}
                · 到期 {{ h.expires_at || '永久' }}
              </span>
            </div>
          </div>
        </div>
        <p v-else class="empty">暂无荣誉资质</p>
      </section>

      <section v-else-if="tab === 'jobs'" class="panel">
        <div class="sec-hd"><h3>人才招聘</h3></div>
        <ul v-if="jobList.length" class="rows">
          <li v-for="j in jobList" :key="j.id">
            <NuxtLink :to="`/job/${j.id}`">{{ j.title }}</NuxtLink>
            <em>{{ j.salary || j.area || '面议' }}</em>
          </li>
        </ul>
        <p v-else class="empty">暂无招聘职位</p>
      </section>

      <section v-else-if="tab === 'credit'" class="panel">
        <div class="sec-hd"><h3>诚信档案</h3></div>
        <div class="prose">
          <p>信用指数 {{ item.credit?.credit_score ?? 0 }}</p>
          <p>{{ item.credit?.is_certified ? '企业已认证' : '普通会员' }}</p>
          <p v-if="item.credit?.vip_years">会员年限 {{ item.credit.vip_years }} 年</p>
          <p v-if="item.credit?.member_since">注册时间 {{ String(item.credit.member_since).slice(0, 10) }}</p>
        </div>
      </section>

      <section v-else-if="tab === 'contact'" class="panel">
        <div class="sec-hd"><h3>联系方式</h3></div>
        <div class="prose">
          <p v-if="hasVal(item.contact)"><b>联系人</b> {{ item.contact }}</p>
          <p v-if="hasVal(item.phone)"><b>电话</b> {{ item.phone }}</p>
          <p v-if="hasVal(item.fax)"><b>传真</b> {{ item.fax }}</p>
          <p v-if="hasVal(item.email)"><b>邮箱</b> {{ item.email }}</p>
          <p v-if="hasVal(item.address) || hasVal(item.area)"><b>地址</b> {{ item.address || item.area }}</p>
          <p v-if="hasVal(item.zip)"><b>邮编</b> {{ item.zip }}</p>
          <p v-if="hasVal(item.website)"><b>网站</b> {{ item.website }}</p>
        </div>
        <div class="form-wrap">
          <InquiryForm type="company" :id="item.id" :default-title="item.name" />
        </div>
      </section>

      <section v-else-if="currentPage" class="panel">
        <div class="sec-hd"><h3>{{ currentPage.title }}</h3></div>
        <div class="prose" @click="onRichImg" v-html="currentPage.content || ''" />
      </section>
    </div>

    <div v-if="preview" class="pic-view" @click.self="closePreview">
      <button type="button" class="pic-x" aria-label="关闭" @click="closePreview">×</button>
      <button v-if="preview.list.length > 1" type="button" class="pic-nav prev" aria-label="上一张" @click="stepPreview(-1)">‹</button>
      <img :src="preview.src" alt="" @click.stop>
      <button v-if="preview.list.length > 1" type="button" class="pic-nav next" aria-label="下一张" @click="stepPreview(1)">›</button>
    </div>
  </div>
</template>

<style scoped>
.pic-view {
  position: fixed;
  inset: 0;
  z-index: 90;
  background: rgba(15, 23, 42, 0.78);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px 72px;
}
.pic-view img {
  max-width: min(1080px, 100%);
  max-height: 86vh;
  object-fit: contain;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.35);
}
.pic-x, .pic-nav {
  position: absolute;
  border: 0;
  background: rgba(255, 255, 255, 0.92);
  color: #1e293b;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.pic-x {
  top: 16px;
  right: 16px;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  font-size: 24px;
  line-height: 1;
}
.pic-nav {
  top: 50%;
  width: 42px;
  height: 42px;
  margin-top: -21px;
  border-radius: 50%;
  font-size: 28px;
}
.pic-nav.prev { left: 16px; }
.pic-nav.next { right: 16px; }
.shop {
  font-family: var(--font);
  color: var(--text);
  padding-bottom: 8px;
}
.brand-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  background: var(--brand, #2769dc);
  color: #fff;
  padding: 12px 16px;
  margin-bottom: 0;
}
.brand-name {
  font-size: 18px;
  font-weight: 800;
  letter-spacing: 0.5px;
  line-height: 1.3;
}
.brand-tag {
  margin-left: auto;
  font-size: 12px;
  font-weight: 600;
  opacity: 0.9;
  border: 1px solid rgba(255,255,255,.35);
  padding: 2px 8px;
}
.mast {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  background: #fff;
  border: 1px solid #F1F4F7;
  border-top: 0;
  padding: 16px 18px;
}
.who { display: flex; gap: 14px; align-items: center; min-width: 0; }
.mark {
  width: 64px; height: 64px;
  background: #f5f7fa;
  border: 1px solid #eef1f6;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.mark img { max-width: 52px; max-height: 52px; object-fit: contain; }
.who h1 {
  margin: 0;
  font-size: 22px;
  font-weight: 800;
  color: #111;
  line-height: 1.3;
}
.who p { margin: 6px 0 0; font-size: 13px; color: #666; }
.tools { display: flex; gap: 8px; align-items: center; flex-shrink: 0; }
.find {
  display: flex;
  border: 1px solid #d9e2ef;
  background: #fff;
}
.find input {
  width: 160px; height: 34px; border: 0;
  padding: 0 10px; font: inherit; color: #111; outline: none;
}
.find button {
  height: 34px; padding: 0 14px; border: 0;
  background: var(--brand); color: #fff; font-weight: 700; cursor: pointer;
}
.find button:hover { background: var(--brand-dark); }
.cta {
  display: inline-flex; align-items: center; justify-content: center;
  height: 34px; padding: 0 16px;
  border-radius: 8px;
  background: var(--brand); color: #fff !important;
  font-weight: 700; font-size: 13px;
}
.cta:hover { background: var(--brand-dark); color: #fff !important; }
.ghost {
  display: inline-flex; align-items: center;
  height: 34px; padding: 0 14px;
  border-radius: 8px;
  border: 1px solid #d9e2ef; background: #fff;
  color: #333; font-weight: 700; font-size: 13px;
}
.ghost:hover { color: var(--brand); border-color: #c5d6f5; background: #eef4ff; }

.tabs {
  display: flex;
  flex-wrap: wrap;
  background: var(--nav-bg, #2769dc);
  margin: 0 0 12px;
  border-radius: 12px;
  overflow: hidden;
}
.tabs a {
  padding: 11px 16px;
  color: #fff !important;
  font-size: 14px;
  font-weight: 600;
  white-space: nowrap;
}
.tabs a:hover,
.tabs a.on {
  background: rgba(0, 0, 0, .14);
  color: #fff !important;
}

.main { min-width: 0; }
.panel {
  background: #fff;
  border: 1px solid #F1F4F7;
  border-radius: 12px;
  overflow: hidden;
  margin-bottom: 12px;
}
.sec-hd {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 14px;
  border-bottom: 1px solid #F1F4F7;
}
.sec-hd h3 {
  margin: 0;
  font-size: 16px;
  font-weight: 800;
  color: #111;
  position: relative;
  padding-left: 10px;
}
.sec-hd h3::before {
  content: '';
  position: absolute;
  left: 0; top: 3px; bottom: 3px;
  width: 3px;
  border-radius: 2px;
  background: var(--brand, #2769dc);
}
.sec-hd a { color: #666; font-size: 13px; }
.sec-hd a:hover { color: var(--brand); }

.intro-txt {
  margin: 0;
  padding: 14px 16px 0;
  font-size: 15px;
  line-height: 1.75;
  color: #333;
}
.hero-actions {
  display: flex; flex-wrap: wrap; gap: 8px;
  padding: 12px 16px 16px;
}

.ad-banner {
  background: #f4f6fa;
  border: 1px solid #F1F4F7;
  border-radius: 12px;
  margin: 0 0 12px;
  overflow: hidden;
  height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.ad-banner img {
  display: block;
  width: 100%;
  height: 100%;
  max-width: 100%;
  object-fit: contain;
  object-position: center;
}

.env-gallery {
  margin: 0 0 14px;
  background: #fff;
  border: 1px solid #eef2f7;
  border-radius: 10px;
  overflow: hidden;
  padding: 10px 10px 8px;
}
.env-viewport {
  position: relative;
}
.env-track {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  scroll-behavior: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  touch-action: pan-x;
}
.env-track::-webkit-scrollbar { display: none; }
.env-cell {
  flex: 0 0 calc((100% - 24px) / 4);
  width: calc((100% - 24px) / 4);
  height: 160px;
  background: #eef2f7;
  border-radius: 8px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #e4eaf2;
  box-sizing: border-box;
}
.env-cell img {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  transition: transform .4s ease;
  user-select: none;
  pointer-events: none;
}
.env-cell.zoom { cursor: zoom-in; }
.env-cell:hover img { transform: scale(1.03); }
.env-cell.empty {
  background: linear-gradient(160deg, #f4f7fb 0%, #e8eef6 100%);
}
.env-ph {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  font-size: 13px;
  color: #94a3b8;
  letter-spacing: .04em;
  user-select: none;
}
.env-ph em {
  font-style: normal;
  font-size: 11.5px;
  color: #b0bccf;
}
.env-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 2;
  width: 32px;
  height: 52px;
  border: 0;
  padding: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(15, 28, 52, .48);
  color: #fff;
  cursor: pointer;
  opacity: 0;
  pointer-events: none;
  transition: opacity .2s ease, background .2s ease;
}
.env-nav svg { width: 18px; height: 18px; display: block; }
.env-prev { left: 4px; border-radius: 6px; }
.env-next { right: 4px; border-radius: 6px; }
.env-gallery:hover .env-nav,
.env-nav:focus-visible {
  opacity: 1;
  pointer-events: auto;
}
.env-nav:hover { background: rgba(15, 28, 52, .68); }
.env-scrub {
  position: relative;
  height: 6px;
  margin: 10px 2px 2px;
  background: #e2e8f0;
  border-radius: 999px;
  cursor: default;
}
.env-scrub.active { cursor: grab; }
.env-scrub.on { cursor: grabbing; }
.env-scrub-thumb {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0;
  min-width: 48px;
  background: var(--brand, #2769dc);
  border-radius: 999px;
  pointer-events: none;
  box-shadow: 0 1px 4px rgba(39, 105, 220, .35);
  transition: left .08s linear;
}
.env-scrub.on .env-scrub-thumb { transition: none; }
.env-hint {
  margin: 6px 2px 0;
  font-size: 12px;
  color: #94a3b8;
  text-align: center;
}
@media (max-width: 720px) {
  .env-cell {
    flex-basis: calc((100% - 8px) / 2);
    width: calc((100% - 8px) / 2);
    height: 128px;
  }
  .env-nav { opacity: .78; pointer-events: auto; }
}

.tiles {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0;
}
.tiles-sm { grid-template-columns: repeat(6, minmax(0, 1fr)); }
.tile {
  min-width: 0;
  padding: 12px;
  border-right: 1px solid #eef1f6;
  border-bottom: 1px solid #eef1f6;
  color: #111;
  background: #fff;
}
.tile:nth-child(4n) { border-right: 0; }
.tiles-sm .tile:nth-child(4n) { border-right: 1px solid #eef1f6; }
.tiles-sm .tile:nth-child(6n) { border-right: 0; }
.tile:hover { background: #f8faff; }
.tile-pic {
  height: 110px;
  background: #f5f7fa;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 10px;
  overflow: hidden;
}
.zoomable { cursor: zoom-in; }
.tile-pic img {
  max-width: 100%; max-height: 100%;
  width: auto; height: auto;
  object-fit: contain;
}
.tile-body h4 {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  line-height: 1.4;
  max-height: 2.8em;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.tile:hover .tile-body h4 { color: var(--brand); }
.tile-body span {
  display: inline-block;
  margin-top: 6px;
  color: #c2410c;
  font-size: 13px;
  font-weight: 700;
}

.dual {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 12px;
}
.dual .panel { margin-bottom: 0; }
.prose { padding: 12px 16px 16px; font-size: 15px; line-height: 1.75; color: #222; }
.prose.tight { padding-top: 10px; }
.prose :deep(img) { max-width: 100%; height: auto; object-fit: contain; display: block; margin: 12px 0; }
.prose b { color: #888; font-weight: 600; margin-right: 8px; }
.prose h4 { margin: 8px 0 12px; font-size: 18px; }
.form-wrap { padding: 0 16px 16px; }
.back { color: var(--brand); font-weight: 700; font-size: 13px; }

.rows { list-style: none; margin: 0; padding: 4px 14px 10px; }
.rows li {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px dashed #f0f0f0;
  font-size: 14px;
}
.rows a {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: #1a1a1a;
  font-weight: 500;
}
.rows a:hover { color: var(--brand); }
.rows em { color: #888; font-style: normal; font-size: 12px; flex-shrink: 0; }
.link-row { display: flex; flex-wrap: wrap; gap: 8px 16px; padding: 10px 16px 14px; }
.link-row a { color: var(--brand); font-weight: 600; font-size: 14px; }
.empty { color: #888; padding: 16px; margin: 0; font-size: 13px; }
.prose.facts { padding-bottom: 8px; border-bottom: 1px dashed #eef1f6; margin-bottom: 4px; }
.tag-row {
  display: flex; flex-wrap: wrap; align-items: center; gap: 6px 8px;
  padding: 4px 0; font-size: 14px;
}
.tag-row b { color: #888; font-weight: 600; margin-right: 4px; }
.tag {
  display: inline-block; padding: 2px 8px; border-radius: 4px;
  background: #f1f5f9; color: #334155; font-size: 12px;
}
.honor-meta { display: block; margin-top: 4px; font-size: 12px; color: #888; }

@media (max-width: 960px) {
  .mast { flex-wrap: wrap; }
  .tiles, .tiles-sm, .dual { grid-template-columns: 1fr 1fr; }
  .tile:nth-child(4n) { border-right: 1px solid #eef1f6; }
  .tile:nth-child(2n) { border-right: 0; }
  .tiles-sm .tile:nth-child(6n) { border-right: 1px solid #eef1f6; }
}
</style>

<script setup lang="ts">
definePageMeta({ layout: 'channel' })
const route = useRoute()
const config = useRuntimeConfig()
const { api, token } = useApi('front')
const { user } = useAuth()
const id = route.params.id as string
const msg = ref('')
const loading = ref(false)
const reviewForm = reactive({ rating: 5, content: '' })
const reviewMsg = ref('')

const { data, error, refresh } = await useAsyncData(`mall-${id}`, () =>
  $fetch(`${config.public.apiBase}/mall/${id}`, {
    headers: token.value ? { Authorization: `Bearer ${token.value}` } : {},
  }),
)
const { data: reviewData, refresh: refreshReviews } = await useAsyncData(`mall-reviews-${id}`, () =>
  $fetch(`${config.public.apiBase}/mall/${id}/reviews`),
)

const item = computed(() => (data.value as any)?.data)
const shopName = computed(() => String(item.value?.company_name || item.value?.user?.company_name || '').trim())
const shopTo = computed(() => {
  const slug = item.value?.company_slug || item.value?.user?.company_slug
  return slug ? `/companies/${slug}` : ''
})
const sellerId = computed(() => item.value?.company_user_id || item.value?.user?.id)
const badgeUser = computed(() => {
  const u = item.value?.user
  if (!u && !shopName.value) return null
  return {
    vip_grade_code: String(u?.member_grade || u?.vip_grade_code || ''),
    level: Number(u?.member_level || u?.level || 0),
    vip_expire_at: null,
  }
})
const nameBox = ref<HTMLElement | null>(null)
const nameLink = ref<any>(null)
const nameSpan = ref<HTMLElement | null>(null)
function fitShopName() {
  const box = nameBox.value
  const raw = nameLink.value?.$el || nameSpan.value
  const el = raw as HTMLElement | undefined
  if (!box || !el) return
  el.style.transform = 'none'
  const avail = box.clientWidth
  const need = el.scrollWidth
  if (avail > 0 && need > avail) el.style.transform = `scale(${avail / need})`
}
let nameObserver: ResizeObserver | null = null
watch(shopName, () => nextTick(fitShopName))
const reviewsMeta = computed(() => (data.value as any)?.reviews_meta || (reviewData.value as any)?.meta || {})
const reviews = computed(() => (reviewData.value as any)?.data || [])
const reviewCount = computed(() => Number(reviewsMeta.value.count || reviewsMeta.value.total || 0))
const reviewAvg = computed(() => {
  const n = Number(reviewsMeta.value.avg_rating)
  return n > 0 ? n.toFixed(1) : ''
})
const priceTiers = computed(() => {
  const rows = item.value?.price_tiers
  return Array.isArray(rows) ? rows.filter((t) => t && t.price != null && t.min_qty != null) : []
})
function tierMatches(tier: any, n: number) {
  const min = Number(tier.min_qty)
  const max = tier.max_qty == null || tier.max_qty === '' ? null : Number(tier.max_qty)
  return n >= min && (max == null || n <= max)
}
const activeTier = computed(() => priceTiers.value.find((t) => tierMatches(t, qty.value)) || null)
const priceText = computed(() => {
  const tier = activeTier.value
  const n = Number(tier ? tier.price : item.value?.price)
  return Number.isFinite(n) ? n.toFixed(2) : ''
})
function tierLabel(tier: any) {
  const unit = item.value?.unit ? String(item.value.unit) : ''
  const min = Number(tier.min_qty)
  const max = tier.max_qty == null || tier.max_qty === '' ? null : Number(tier.max_qty)
  return max == null ? `≥${min}${unit}` : `${min}~${max}${unit}`
}
const shipArea = computed(() => String(item.value?.company_area || '').trim())
const shopProducts = computed(() => {
  const rows = item.value?.shop_products
  return Array.isArray(rows) ? rows.filter((p) => p && p.id) : []
})
function shopProductCompany(p: any) {
  return String(p?.company_name || p?.user?.company_name || shopName.value || '会员商家').trim()
}
function shopProductPrice(p: any) {
  const n = Number(p?.price)
  if (!Number.isFinite(n) || n <= 0) return '面议'
  const unit = String(p?.unit || '').trim()
  return `¥${n.toFixed(2)}${unit ? '/' + unit : ''}`
}
function shopProductVipSrc(p: any) {
  const code = String(p?.user?.member_grade || badgeUser.value?.vip_grade_code || 'normal').toLowerCase()
  const map: Record<string, string> = {
    silver: '/img/member/silver.png',
    gold: '/img/member/gold.png',
    diamond: '/img/member/diamond.png',
  }
  return map[code] || '/img/member/normal.png'
}
function shopProductVipLabel(p: any) {
  const code = String(p?.user?.member_grade || badgeUser.value?.vip_grade_code || '').toLowerCase()
  const map: Record<string, string> = { silver: '白银会员', gold: '黄金会员', diamond: '星钻会员' }
  return map[code] || '普通会员'
}
const detailImages = computed(() => {
  const rows = item.value?.detail_images
  if (!Array.isArray(rows)) return []
  return rows.map((src) => cleanImg(String(src || ''))).filter(Boolean).slice(0, 5)
})
/** 一屏两张：按页切分，便于整屏横向滑动 */
const detailPicPages = computed(() => {
  const list = detailImages.value
  const pages: string[][] = []
  for (let i = 0; i < list.length; i += 2) pages.push(list.slice(i, i + 2))
  return pages
})
const detailPicsEl = ref<HTMLElement | null>(null)
const detailPicPage = ref(0)
function onDetailPicsScroll() {
  const el = detailPicsEl.value
  if (!el || !el.clientWidth) return
  detailPicPage.value = Math.min(
    detailPicPages.value.length - 1,
    Math.max(0, Math.round(el.scrollLeft / el.clientWidth)),
  )
}
function goDetailPicPage(i: number) {
  const el = detailPicsEl.value
  if (!el) return
  const next = Math.min(detailPicPages.value.length - 1, Math.max(0, i))
  detailPicPage.value = next
  el.scrollTo({ left: next * el.clientWidth, behavior: 'smooth' })
}
watch(detailImages, () => {
  detailPicPage.value = 0
  const el = detailPicsEl.value
  if (el) el.scrollLeft = 0
})

type DetailSpec = { name: string; value: string }
type DetailSection = { title: string; body: string }

function decodeDetail(html: string) {
  return html
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<\/p>/gi, '\n')
    .replace(/<[^>]+>/g, '')
    .replace(/&nbsp;|&#160;/gi, ' ')
    .replace(/&emsp;/gi, ' ')
    .replace(/&le;/gi, '≤')
    .replace(/&ge;/gi, '≥')
    .replace(/&lt;/gi, '<')
    .replace(/&gt;/gi, '>')
    .replace(/&amp;/gi, '&')
    .replace(/&quot;/gi, '"')
    .replace(/&mdash;/gi, '—')
    .replace(/&#(\d+);/g, (_, n) => String.fromCharCode(Number(n)))
}

function tidyDetail(text: string) {
  let t = text.replace(/\u00a0/g, ' ').replace(/\r/g, '')
  const idx = t.search(/[\u4e00-\u9fff]/)
  if (idx > 0 && /font|Helvetica|PingFang|sans-serif|inkMac|Microsoft/i.test(t.slice(0, idx))) {
    t = t.slice(idx)
  }
  return t.replace(/[ \t]{2,}/g, ' ').replace(/\n{3,}/g, '\n\n').trim()
}

function extractSpecs(body: string): DetailSpec[] {
  const parts = body
    .split(/[\n；;]+/)
    .map((s) => s.replace(/^\s*\d+\s*[、.．]\s*/, '').trim())
    .filter((s) => s.length > 1 && s.length < 90)
  const specs: DetailSpec[] = []
  for (const line of parts) {
    const m = line.match(/^([^:：]{1,28})[:：]\s*(.+)$/)
    if (m && m[2].trim().length <= 60) {
      let name = m[1].replace(/\s+/g, '')
      let value = m[2].trim().replace(/[。；;]\s*$/, '')
      const unit = value.match(/^（([^）]{1,24})）[:：]\s*(.+)$/)
      if (unit) {
        name = `${name}（${unit[1]}）`
        value = unit[2].trim()
      }
      if (name && value) specs.push({ name, value })
      continue
    }
    const c = line.match(/^([^≤≥]{1,28}?)\s*([≤≥])\s*(.+)$/)
    if (c && c[3].trim().length <= 24) {
      specs.push({
        name: c[1].replace(/\s+/g, ''),
        value: `${c[2]}${c[3].trim()}`.replace(/[。；;]\s*$/, ''),
      })
    }
  }
  return specs.length >= 2 ? specs : []
}

function leftoverProse(body: string, specs: DetailSpec[]) {
  const parts = body.split(/[\n。]+/).map((s) => s.trim()).filter(Boolean)
  const kept = parts.filter((part) => {
    const hit = specs.some((s) => part.includes(s.value) && part.includes(s.name.slice(0, Math.min(2, s.name.length))))
    return !hit && part.replace(/\s/g, '').length >= 18
  })
  return tidyDetail(kept.join('\n'))
}

const detailView = computed(() => {
  const raw = String(item.value?.content || item.value?.summary || '')
  const text = tidyDetail(decodeDetail(raw))
  const chunks = text.split(/【([^】]{1,20})】/)
  const blocks: { title: string; raw: string }[] = []
  if (chunks.length === 1) {
    if (chunks[0].trim()) blocks.push({ title: '', raw: chunks[0] })
  } else {
    if (chunks[0].trim()) blocks.push({ title: '', raw: chunks[0] })
    for (let i = 1; i < chunks.length; i += 2) {
      blocks.push({ title: (chunks[i] || '').trim(), raw: chunks[i + 1] || '' })
    }
  }
  const specs: DetailSpec[] = []
  const prose: DetailSection[] = []
  for (const block of blocks) {
    const found = extractSpecs(block.raw)
    if (found.length) {
      specs.push(...found)
      const rest = leftoverProse(block.raw, found)
      if (rest) prose.push({ title: block.title, body: rest })
    } else {
      const body = tidyDetail(block.raw)
      if (body) prose.push({ title: block.title, body })
    }
  }
  const meta: DetailSpec[] = []
  if (item.value?.category?.name) meta.push({ name: '分类', value: item.value.category.name })
  if (item.value?.unit) meta.push({ name: '单位', value: item.value.unit })
  if (shopName.value) meta.push({ name: '商家', value: shopName.value })
  if (shipArea.value) meta.push({ name: '发货地', value: shipArea.value })
  const highlights = specs.filter((s) => s.value.length <= 18).slice(0, 6)
  const used = new Set(highlights.map((s) => `${s.name}\0${s.value}`))
  const rows = [...specs.filter((s) => !used.has(`${s.name}\0${s.value}`)), ...meta]
  return { highlights, rows, prose, hasSpecs: specs.length > 0 || meta.length > 0 }
})
const qty = ref(1)
watch(item, () => { qty.value = 1 })

function setQty(next: number) {
  const stock = Number(item.value?.stock ?? 0)
  let n = Math.max(1, Math.floor(Number(next) || 1))
  if (stock > 0) n = Math.min(n, stock)
  qty.value = n
}
useHead(() => ({ title: item.value?.title || '商品详情' }))

const fallback = '/img/logo-yjg.png'
function cleanImg(src?: string | null) {
  const t = String(src || '').trim()
  if (!t || /\.svg($|\?)/i.test(t) || /placehold/i.test(t) || /\/img\/products\/p\d+/i.test(t)) return ''
  return t
}
const gallery = computed(() => {
  const list = [item.value?.thumb, item.value?.thumb1, item.value?.thumb2].map(cleanImg).filter(Boolean)
  return list.length ? list : [fallback]
})
const active = ref(0)
const paused = ref(false)
const LENS = 0.42
const zoom = reactive({ on: false, x: 0, y: 0, w: 0, h: 0, boxW: 0, boxH: 0, paneW: 0, paneH: 0, paneLeft: 0, paneTop: 0 })
watch(gallery, () => { active.value = 0 })

function canZoom() {
  return window.matchMedia('(hover: hover) and (pointer: fine)').matches
}

function onZoomMove(e: MouseEvent) {
  if (!canZoom()) return
  const el = e.currentTarget as HTMLElement
  const rect = el.getBoundingClientRect()
  const lw = rect.width * LENS
  const lh = rect.height * LENS
  const x = Math.max(0, Math.min(e.clientX - rect.left - lw / 2, rect.width - lw))
  const y = Math.max(0, Math.min(e.clientY - rect.top - lh / 2, rect.height - lh))
  zoom.on = true
  zoom.x = x
  zoom.y = y
  zoom.w = lw
  zoom.h = lh
  zoom.boxW = rect.width
  zoom.boxH = rect.height
  const cap = Math.min(680, window.innerWidth - 24, window.innerHeight - 24)
  zoom.paneW = Math.max(rect.width, Math.min(Math.round(rect.width * 1.85), cap))
  zoom.paneH = Math.max(rect.height, Math.min(Math.round(rect.height * 1.85), cap))
  const gap = 14
  let left = rect.right + gap
  if (left + zoom.paneW > window.innerWidth - 8) left = Math.max(8, rect.left - zoom.paneW - gap)
  zoom.paneLeft = left
  zoom.paneTop = Math.max(8, Math.min(rect.top, window.innerHeight - zoom.paneH - 8))
}

function onZoomLeave() {
  zoom.on = false
}

function step(dir: number) {
  const n = gallery.value.length
  if (n < 2) return
  active.value = (active.value + dir + n) % n
}

let slideTimer: ReturnType<typeof setInterval> | null = null
onMounted(() => {
  slideTimer = setInterval(() => {
    if (!paused.value) step(1)
  }, 4000)
  nextTick(fitShopName)
  if (nameBox.value && typeof ResizeObserver !== 'undefined') {
    nameObserver = new ResizeObserver(() => fitShopName())
    nameObserver.observe(nameBox.value)
  }
})
onBeforeUnmount(() => {
  if (slideTimer) clearInterval(slideTimer)
  nameObserver?.disconnect()
})

function onImgErr(e: Event) {
  const el = e.target as HTMLImageElement
  if (!el || el.dataset.fb) return
  el.dataset.fb = '1'
  el.src = fallback
}

async function addCart() {
  if (!user.value) {
    return navigateTo({ path: '/login', query: { redirect: route.fullPath } })
  }
  loading.value = true
  msg.value = ''
  try {
    await api(`/member/cart/${id}`, { method: 'POST', body: { quantity: qty.value } })
    await appOk('已加入购物车。', '操作成功')
  } catch (e: any) {
    await appFail(apiErrorText(e, '加入失败'), '操作失败')
  } finally {
    loading.value = false
  }
}

async function submitReview() {
  if (!user.value) return navigateTo({ path: '/login', query: { redirect: route.fullPath } })
  reviewMsg.value = ''
  try {
    await api(`/mall/${id}/reviews`, { method: 'POST', body: { ...reviewForm } })
    reviewForm.content = ''
    await refreshReviews()
    await refresh()
    await appOk('评价已提交。', '提交成功')
  } catch (e: any) {
    await appFail(apiErrorText(e, '评价失败'), '提交失败')
  }
}
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <NuxtLink to="/mall">优质产品</NuxtLink>
          <span class="on">商品详情</span>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div v-if="item?.owner_preview" class="preview-bar">这是效果预览。未上架或还没审核通过时，只有你能打开，访客看不到。</div>
        <div v-if="error" class="blk empty">商品不存在</div>
        <div v-else-if="item" class="dt-layout">
          <div class="dt-main">
            <div class="dt-card">
              <div class="dt-media">
                <div
                  class="dt-gallery is-slide"
                  :class="{ 'is-zoom': zoom.on }"
                  @mouseenter="paused = true"
                  @mouseleave="paused = false"
                >
                  <div class="main" @mousemove="onZoomMove" @mouseleave="onZoomLeave">
                    <img :src="gallery[active]" :alt="item.title" @error="onImgErr">
                    <div
                      v-show="zoom.on"
                      class="zoom-lens"
                      :style="{ left: zoom.x + 'px', top: zoom.y + 'px', width: zoom.w + 'px', height: zoom.h + 'px' }"
                    />
                    <template v-if="gallery.length > 1">
                      <button type="button" class="slide-nav prev" aria-label="上一张" @click="step(-1)">‹</button>
                      <button type="button" class="slide-nav next" aria-label="下一张" @click="step(1)">›</button>
                    </template>
                  </div>
                  <div
                    v-show="zoom.on"
                    class="zoom-pane"
                    :style="{ left: zoom.paneLeft + 'px', top: zoom.paneTop + 'px', width: zoom.paneW + 'px', height: zoom.paneH + 'px' }"
                  >
                    <img
                      :src="gallery[active]"
                      alt=""
                      :style="{
                        width: (zoom.paneW / LENS) + 'px',
                        height: (zoom.paneH / LENS) + 'px',
                        transform: `translate(${-(zoom.x * zoom.paneW) / ((zoom.boxW || 1) * LENS)}px, ${-(zoom.y * zoom.paneH) / ((zoom.boxH || 1) * LENS)}px)`,
                      }"
                    >
                  </div>
                </div>
                <div class="dt-sum tb-buy">
                  <div class="tb-head">
                    <h1>{{ item.title }}</h1>
                    <div class="tb-sub">
                      <InquiryForm type="product" :id="item.id" :default-title="item.title" button-text="询价" button-class="tb-text" />
                      <FavoriteButton type="product" :id="item.id" />
                    </div>
                  </div>
                  <div class="tb-tags">
                    <span>已售 {{ item.sales ?? 0 }}</span>
                    <span>库存 {{ item.stock ?? 0 }}{{ item.unit ? item.unit : '' }}</span>
                    <span v-if="reviewCount">{{ reviewAvg }}分 · {{ reviewCount }}条评价</span>
                    <span v-else>暂无评价</span>
                  </div>
                  <div v-if="priceTiers.length" class="tier-board">
                    <div
                      v-for="tier in priceTiers"
                      :key="`${tier.min_qty}-${tier.max_qty}`"
                      class="tier-cell"
                      :class="{ on: activeTier === tier }"
                    >
                      <b>¥{{ Number(tier.price).toFixed(2) }}</b>
                      <span>{{ tierLabel(tier) }}</span>
                    </div>
                  </div>
                  <div v-else class="tb-price">
                    <span class="k">售价</span>
                    <b><small>¥</small>{{ priceText }}</b>
                    <em v-if="item.unit">/{{ item.unit }}</em>
                  </div>
                  <div class="tb-rows">
                    <div v-if="item.category?.name" class="tb-row">
                      <span class="k">分类</span>
                      <span>{{ item.category.name }}</span>
                    </div>
                    <div v-if="shopName" class="tb-row">
                      <span class="k">商家</span>
                      <NuxtLink v-if="shopTo" :to="shopTo">{{ shopName }}</NuxtLink>
                      <span v-else>{{ shopName }}</span>
                    </div>
                    <div v-if="shipArea" class="tb-row">
                      <span class="k">发货</span>
                      <span>{{ shipArea }}</span>
                    </div>
                    <div class="tb-row">
                      <span class="k">数量</span>
                      <div class="qty">
                        <button type="button" aria-label="减少" @click="setQty(qty - 1)">−</button>
                        <input :value="qty" inputmode="numeric" aria-label="数量" @change="setQty(Number(($event.target as HTMLInputElement).value))">
                        <button type="button" aria-label="增加" @click="setQty(qty + 1)">+</button>
                        <span class="hint">库存 {{ item.stock ?? 0 }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="tb-acts">
                    <button class="btn-cart" type="button" :disabled="loading" @click="addCart">
                      {{ loading ? '加入中…' : '加入购物车' }}
                    </button>
                    <NuxtLink class="btn-buy" to="/member/cart">去结算</NuxtLink>
                  </div>
                  <div class="tb-sub">
                    <span class="muted" style="font-size:12px">如需议价请通过旺铺询盘，由平台审核后转达</span>
                  </div>
                  <div v-if="msg" class="alert alert-success">{{ msg }}</div>
                </div>
              </div>
            </div>

            <div class="dt-card">
              <div class="dt-h">商品详情</div>
              <template v-if="detailView.hasSpecs">
                <h3 class="spec-title">参数信息</h3>
                <div v-if="detailView.highlights.length" class="spec-board">
                  <div v-for="(s, i) in detailView.highlights" :key="'h' + i" class="spec-tile">
                    <b>{{ s.value }}</b>
                    <span>{{ s.name }}</span>
                  </div>
                </div>
                <div v-if="detailView.rows.length" class="spec-table">
                  <div v-for="(s, i) in detailView.rows" :key="'r' + i" class="spec-line">
                    <span>{{ s.name }}</span>
                    <b>{{ s.value }}</b>
                  </div>
                </div>
              </template>
              <section v-for="(sec, i) in detailView.prose" :key="'p' + i" class="spec-sec">
                <h3 v-if="sec.title" class="prose-title">{{ sec.title }}</h3>
                <p class="spec-prose">{{ sec.body }}</p>
              </section>
              <p v-if="!detailView.hasSpecs && !detailView.prose.length" class="spec-prose">暂无详情</p>
            </div>

            <div v-if="detailImages.length" class="dt-card">
              <div class="dt-h dt-h-row">
                <span>详情图片</span>
                <span v-if="detailPicPages.length > 1" class="detail-pics-meta">{{ detailPicPage + 1 }} / {{ detailPicPages.length }}</span>
              </div>
              <div class="detail-pics-wrap">
                <button
                  v-if="detailPicPages.length > 1"
                  type="button"
                  class="detail-pics-nav prev"
                  :disabled="detailPicPage <= 0"
                  aria-label="上一屏"
                  @click="goDetailPicPage(detailPicPage - 1)"
                >‹</button>
                <div
                  ref="detailPicsEl"
                  class="detail-pics"
                  @scroll.passive="onDetailPicsScroll"
                >
                  <div
                    v-for="(page, pi) in detailPicPages"
                    :key="'dp-' + pi"
                    class="detail-pics-page"
                  >
                    <figure v-for="(src, i) in page" :key="src + i" class="detail-pic">
                      <img :src="src" alt="">
                    </figure>
                  </div>
                </div>
                <button
                  v-if="detailPicPages.length > 1"
                  type="button"
                  class="detail-pics-nav next"
                  :disabled="detailPicPage >= detailPicPages.length - 1"
                  aria-label="下一屏"
                  @click="goDetailPicPage(detailPicPage + 1)"
                >›</button>
              </div>
              <div v-if="detailPicPages.length > 1" class="detail-pics-dots" role="tablist" aria-label="详情图片分页">
                <button
                  v-for="(_, i) in detailPicPages"
                  :key="'dot-' + i"
                  type="button"
                  role="tab"
                  :class="{ on: detailPicPage === i }"
                  :aria-selected="detailPicPage === i"
                  @click="goDetailPicPage(i)"
                />
              </div>
            </div>

            <div class="dt-card">
              <div class="dt-h">商品评价</div>
              <div v-for="r in reviews" :key="r.id" class="rv">
                <b>{{ r.user?.name || '会员' }}</b>
                <span class="muted"> · {{ r.rating }}星 · {{ r.created_at?.slice?.(0, 16) }}</span>
                <p>{{ r.content }}</p>
              </div>
              <p v-if="!reviews.length" class="empty" style="padding:12px 0">暂无评价</p>
              <form class="rv-form" @submit.prevent="submitReview">
                <div v-if="reviewMsg" class="alert" :class="reviewMsg.includes('提交') ? 'alert-success' : 'alert-error'">{{ reviewMsg }}</div>
                <div class="form-row">
                  <label>评分</label>
                  <select v-model.number="reviewForm.rating">
                    <option :value="5">5星</option>
                    <option :value="4">4星</option>
                    <option :value="3">3星</option>
                    <option :value="2">2星</option>
                    <option :value="1">1星</option>
                  </select>
                </div>
                <div class="form-row">
                  <label>内容</label>
                  <textarea v-model="reviewForm.content" required rows="3" />
                </div>
                <button class="btn-go" type="submit">发表评价</button>
              </form>
            </div>
          </div>

          <aside class="dt-side">
            <div class="seller">
              <div class="sh">商家服务</div>
              <div class="sb">
                <div ref="nameBox" class="sn">
                  <NuxtLink v-if="shopTo" ref="nameLink" class="sn-fit" :to="shopTo">{{ shopName || '商家' }}</NuxtLink>
                  <span v-else ref="nameSpan" class="sn-fit">{{ shopName || '商家' }}</span>
                </div>
                <div v-if="badgeUser" class="badge-line">
                  <MemberCorner :user="badgeUser as any" />
                </div>
                <p><em>服务</em>正品保障 · 售后支持</p>
                <p><em>物流</em>按商家约定发货</p>
                <div class="dt-acts">
                  <NuxtLink v-if="shopTo" class="btn" :to="`${shopTo}?inquiry=1`">联系商家 / 询盘</NuxtLink>
                  <NuxtLink v-if="shopTo" class="btn-soft-link" :to="shopTo">进入旺铺</NuxtLink>
                  <NuxtLink class="btn-soft-link" to="/mall">继续选购</NuxtLink>
                </div>
              </div>
            </div>
            <div class="tips">
              <b>购买提示</b>
              <ul>
                <li>下单前请确认规格与库存</li>
                <li>大额采购可通过询盘议价</li>
                <li>收货后请及时确认并评价</li>
              </ul>
            </div>
            <div v-if="shopProducts.length" class="shop-more">
              <div class="sh">本店商品</div>
              <div class="shop-more-list">
                <NuxtLink
                  v-for="p in shopProducts"
                  :key="p.id"
                  class="sp-card"
                  :to="`/mall/${p.id}`"
                  :title="p.title"
                >
                  <span class="sp-img">
                    <img :src="cleanImg(p.thumb) || fallback" :alt="p.title" loading="lazy" @error="onImgErr">
                    <em class="sp-mark">本店</em>
                  </span>
                  <span class="sp-body">
                    <b class="sp-title">{{ p.title }}</b>
                    <span class="sp-co">
                      <span class="sp-co-t" :title="shopProductCompany(p)">{{ shopProductCompany(p) }}</span>
                      <img class="sp-vip" :src="shopProductVipSrc(p)" :alt="shopProductVipLabel(p)" :title="shopProductVipLabel(p)">
                    </span>
                    <strong class="sp-price">{{ shopProductPrice(p) }}</strong>
                  </span>
                </NuxtLink>
              </div>
            </div>
          </aside>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.sn {
  height: 22px;
  overflow: hidden;
  display: flex;
  align-items: center;
  margin-bottom: 8px;
}
.sn-fit {
  display: inline-block;
  white-space: nowrap;
  transform-origin: left center;
  font-size: 15px;
  font-weight: 800;
  line-height: 1.2;
  color: var(--ink);
  text-decoration: none;
}
.badge-line { margin: 0 0 8px; line-height: 0; }
.badge-line :deep(.mbadge) {
  display: block;
  height: 22px;
  width: auto;
  margin: 0;
  object-fit: contain;
}
.shop-more {
  background: #fff;
  border-radius: var(--r-lg);
  box-shadow: var(--shadow-card);
  overflow: hidden;
}
.shop-more .sh {
  padding: 12px 14px;
  font-size: 14.5px;
  font-weight: 800;
  color: #fff;
  background: var(--grad-navy);
}
.shop-more-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 10px;
}
.sp-card {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 9px;
  background: #f8fafd;
  border: 1px solid #e3eaf5;
  border-radius: 12px;
  color: inherit;
  text-decoration: none;
  overflow: hidden;
  transition: transform .18s ease, box-shadow .18s ease;
}
.sp-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(30, 79, 199, 0.12);
}
.sp-img {
  position: relative;
  display: block;
  width: 100%;
  aspect-ratio: 16 / 9;
  border-radius: 8px;
  overflow: hidden;
  background: #eef2f8;
}
.sp-img img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.sp-mark {
  position: absolute;
  top: 6px;
  left: 6px;
  z-index: 2;
  margin: 0;
  background: #e10600;
  color: #fff;
  font-style: normal;
  font-size: 11px;
  font-weight: 700;
  line-height: 1;
  padding: 3px 6px;
  border-radius: 4px;
}
.sp-body {
  display: flex;
  flex-direction: column;
  gap: 3px;
  min-width: 0;
}
.sp-title {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: var(--ink, #1a2333);
  line-height: 1.35;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.sp-co {
  display: flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
}
.sp-co-t {
  flex: 1;
  min-width: 0;
  font-size: 12px;
  font-weight: 600;
  color: var(--ink-2, #6b7285);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.sp-vip {
  height: 16px;
  width: auto;
  flex-shrink: 0;
  object-fit: contain;
}
.sp-price {
  font-size: 14px;
  font-weight: 800;
  color: var(--price, #e11d48);
  line-height: 1.3;
}
.dt-h-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.detail-pics-meta {
  font-size: 12.5px;
  font-weight: 600;
  color: var(--ink-3);
}
.detail-pics-wrap {
  position: relative;
}
.detail-pics {
  display: flex;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  padding: 2px 0;
}
.detail-pics::-webkit-scrollbar { display: none; }
.detail-pics-page {
  flex: 0 0 100%;
  width: 100%;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  scroll-snap-align: start;
  scroll-snap-stop: always;
  box-sizing: border-box;
}
.detail-pic {
  margin: 0;
  width: 100%;
  aspect-ratio: 3 / 4;
  border-radius: 8px;
  overflow: hidden;
  background: #f4f7fb;
  border: 1px solid var(--line);
}
.detail-pic img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.detail-pics-nav {
  position: absolute;
  top: 50%;
  z-index: 2;
  transform: translateY(-50%);
  width: 32px;
  height: 32px;
  border: 0;
  border-radius: 50%;
  background: rgba(19, 41, 75, 0.55);
  color: #fff;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
  display: grid;
  place-items: center;
  padding: 0 0 2px;
}
.detail-pics-nav:disabled {
  opacity: 0.28;
  cursor: default;
}
.detail-pics-nav.prev { left: 6px; }
.detail-pics-nav.next { right: 6px; }
.detail-pics-dots {
  display: flex;
  justify-content: center;
  gap: 6px;
  margin-top: 10px;
}
.detail-pics-dots button {
  width: 7px;
  height: 7px;
  padding: 0;
  border: 0;
  border-radius: 50%;
  background: #c9d5ea;
  cursor: pointer;
}
.detail-pics-dots button.on { background: var(--blue); }
.preview-bar {
  margin-bottom: 12px;
  padding: 10px 14px;
  border-radius: 8px;
  background: #fff7e8;
  color: #8a5a00;
  font-size: 13px;
}
.btn-go {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 40px;
  padding: 0 20px;
  border: 0;
  border-radius: 10px;
  background: var(--blue);
  color: #fff;
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
}
.ch-portal .dt-gallery.is-slide {
  position: relative;
  min-width: 0;
  overflow: visible;
}
.ch-portal .dt-gallery.is-slide.is-zoom { z-index: 40; }
.ch-portal .dt-gallery.is-slide .main { cursor: zoom-in; }
.zoom-lens {
  position: absolute;
  z-index: 2;
  background: rgba(255, 92, 110, .28);
  border: 1px solid rgba(255, 70, 90, .55);
  pointer-events: none;
}
.zoom-pane {
  position: fixed;
  z-index: 80;
  overflow: hidden;
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 12px;
  box-shadow: 0 10px 28px rgba(19, 41, 75, .18);
  pointer-events: none;
}
.zoom-pane img {
  position: absolute;
  left: 0;
  top: 0;
  max-width: none;
  max-height: none;
  object-fit: cover;
}
@media (max-width: 860px) {
  .zoom-lens, .zoom-pane { display: none !important; }
}
.ch-portal .dt-gallery.is-slide .main {
  position: relative;
  width: 100%;
  aspect-ratio: 1;
  height: auto;
}
.ch-portal .dt-gallery.is-slide .main img {
  width: 100%;
  height: 100%;
  max-width: none;
  max-height: none;
  object-fit: cover;
}
.slide-nav {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 32px;
  height: 32px;
  border: 0;
  border-radius: 50%;
  background: rgba(19, 41, 75, .45);
  color: #fff;
  font-size: 22px;
  line-height: 1;
  cursor: pointer;
}
.slide-nav.prev { left: 8px; }
.slide-nav.next { right: 8px; }
.ch-portal .dt-sum.tb-buy {
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.ch-portal .dt-sum.tb-buy h1 {
  margin: 0;
  font-size: 18px;
  line-height: 1.35;
}
.tb-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 6px;
}
.tb-head h1 { flex: 1; min-width: 0; }
.tb-head .tb-sub { margin-top: 2px; flex: 0 0 auto; }
.tb-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin: 0 0 8px;
}
.tb-tags span {
  font-size: 12px;
  color: var(--ink-3);
  background: #f4f7fb;
  border-radius: 4px;
  padding: 1px 8px;
  line-height: 20px;
}
.tb-price {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin: 0;
  padding: 8px 12px;
  border-radius: 8px;
  background: #eef4ff;
}
.tb-price .k {
  font-size: 13px;
  color: var(--blue-ink);
  font-weight: 600;
}
.tb-price b {
  font-size: 26px;
  line-height: 1;
  font-weight: 800;
  color: var(--up);
}
.tb-price small {
  font-size: 15px;
  margin-right: 2px;
}
.tb-price em {
  font-style: normal;
  font-size: 13px;
  color: var(--ink-2);
}
.tier-board { display: flex; gap: 10px; margin: 12px 0; flex-wrap: wrap; }
.tier-cell { min-width: 140px; padding: 10px 14px; border: 1px solid #f0d2c4; background: #fff8f4; border-radius: 4px; }
.tier-cell.on { border-color: #e11d48; background: #fff1f2; }
.tier-cell b { display: block; color: #e11d48; font-size: 20px; }
.tier-cell span { color: #9a3412; font-size: 13px; }
.tb-rows { margin-top: 2px; }
.tb-row {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 32px;
  border-bottom: 1px solid var(--line);
  font-size: 13px;
  color: var(--ink);
}
.tb-row .k {
  flex: 0 0 42px;
  color: var(--ink-3);
  font-weight: 500;
}
.tb-row a {
  color: var(--blue);
  font-weight: 600;
  text-decoration: none;
}
.qty {
  display: flex;
  align-items: center;
  gap: 0;
}
.qty button {
  width: 28px;
  height: 28px;
  border: 1px solid #e5e8ee;
  background: #fff;
  color: var(--ink);
  cursor: pointer;
  font-size: 16px;
  line-height: 1;
}
.qty button:first-child { border-radius: 4px 0 0 4px; }
.qty input {
  width: 48px;
  height: 28px;
  border: 1px solid #e5e8ee;
  border-left: 0;
  border-right: 0;
  text-align: center;
  font-size: 13px;
  font-family: inherit;
}
.qty button:last-of-type { border-radius: 0 4px 4px 0; }
.qty .hint {
  margin-left: 10px;
  font-size: 12px;
  color: #8a93a3;
}
.tb-acts {
  display: flex;
  gap: 10px;
  margin-top: auto;
  padding-top: 10px;
}
.btn-cart,
.btn-buy {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 36px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
  font-family: inherit;
}
.btn-cart {
  border: 1px solid var(--blue);
  background: #fff;
  color: var(--blue);
}
.btn-buy {
  border: 0;
  background: var(--blue);
  color: #fff;
}
.btn-cart:disabled { opacity: .6; cursor: default; }
.tb-sub {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 14px;
  margin-top: 8px;
}
.tb-sub :deep(.btn),
.tb-sub :deep(button) {
  height: auto;
  min-width: 0;
  padding: 0;
  border: 0;
  background: transparent;
  box-shadow: none;
  color: var(--blue);
  font-size: 13px;
  font-weight: 600;
}
.tb-buy .alert { margin-top: 10px; }
.btn-go:disabled { opacity: .6; }
.btn-soft {
  display: inline-flex;
  align-items: center;
  height: 40px;
  padding: 0 16px;
  border-radius: 10px;
  background: #EFF3F9;
  color: var(--ink);
  font-size: 13px;
  font-weight: 600;
  text-decoration: none;
}
.spec-title,
.prose-title {
  margin: 4px 0 14px;
  font-size: 16px;
  font-weight: 800;
  color: var(--ink);
}
.prose-title { margin-top: 22px; }
.spec-board {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px 16px;
  padding: 22px 18px;
  border-radius: 12px;
  background: #f5f6f8;
}
.spec-tile { text-align: center; min-width: 0; }
.spec-tile b {
  display: block;
  font-size: 16px;
  font-weight: 800;
  color: #1f2329;
  line-height: 1.45;
  word-break: break-word;
}
.spec-tile span {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: #9aa0a8;
  line-height: 1.4;
}
.spec-table {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px 28px;
  margin-top: 18px;
}
.spec-line {
  display: flex;
  gap: 12px;
  min-width: 0;
  font-size: 13.5px;
  line-height: 1.5;
}
.spec-line span {
  flex: 0 0 72px;
  color: #9aa0a8;
}
.spec-line b {
  flex: 1;
  min-width: 0;
  font-weight: 500;
  color: #1f2329;
  word-break: break-word;
}
.spec-prose {
  margin: 0;
  font-size: 14.5px;
  line-height: 1.85;
  color: #3a4150;
  white-space: pre-wrap;
}
@media (max-width: 720px) {
  .spec-board,
  .spec-table { grid-template-columns: 1fr; }
}
.rv {
  padding: 12px 0;
  border-bottom: 1px dashed var(--line);
}
.rv:last-of-type { border-bottom: none; }
.rv b { color: var(--ink); font-size: 13.5px; }
.rv .muted { color: var(--ink-3); font-size: 12.5px; }
.rv p { margin: 6px 0 0; font-size: 13.5px; color: var(--ink-2); line-height: 1.5; }
.rv-form { max-width: 480px; margin-top: 16px; }
.rv-form .form-row { margin-bottom: 12px; }
.rv-form label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--ink-2);
  margin-bottom: 6px;
}
.rv-form select,
.rv-form textarea {
  width: 100%;
  border: 1.5px solid var(--line);
  border-radius: 10px;
  padding: 9px 12px;
  font-size: 13.5px;
  font-family: inherit;
  box-sizing: border-box;
}
</style>

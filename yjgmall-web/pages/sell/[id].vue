<script setup lang="ts">
definePageMeta({ layout: 'channel' })
const route = useRoute()
const config = useRuntimeConfig()
const { token } = useApi('front')
const id = route.params.id as string
const inquiryRef = ref<{ open: () => void } | null>(null)

const { data, error } = await useAsyncData(`sell-${id}`, () =>
  $fetch(`${config.public.apiBase}/sell/${id}`, {
    headers: token.value ? { Authorization: `Bearer ${token.value}` } : {},
  }),
)

const item = computed(() => (data.value as any)?.data)
useHead(() => ({ title: item.value?.title || '供应详情' }))

const fallback = '/img/logo-yjg.png'
function cleanImg(src?: string | null) {
  const t = String(src || '').trim()
  if (!t || /\.svg($|\?)/i.test(t) || /placehold/i.test(t) || /\/img\/products\/p\d+/i.test(t)) return ''
  return t
}
const gallery = computed(() => {
  const list = [item.value?.thumb, item.value?.thumb1, item.value?.thumb2]
    .map(cleanImg)
    .filter(Boolean)
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

const contentIsHtml = computed(() => /<[a-z][\s\S]*>/i.test(String(item.value?.content || '')))
const priceText = computed(() => {
  const n = Number(item.value?.price)
  if (!Number.isFinite(n) || n <= 0) return ''
  return n.toFixed(2)
})
const shipArea = computed(() => String(item.value?.area || item.value?.company_area || '').trim())
const specTiles = computed(() => {
  const rows: { name: string; value: string }[] = []
  const push = (name: string, value: any) => {
    const v = String(value ?? '').trim()
    if (v && v !== '-') rows.push({ name, value: v })
  }
  push('起订量', item.value?.min_amount)
  push('供货量', item.value?.amount)
  push('发货期', item.value?.days ? `${item.value.days}天` : '')
  push('品牌', item.value?.brand)
  push('地区', item.value?.area)
  for (const a of item.value?.attrs || []) {
    if (a?.name) push(String(a.name), a.value)
  }
  return rows
})

function onImgErr(e: Event) {
  const el = e.target as HTMLImageElement
  if (!el || el.dataset.fb) return
  el.dataset.fb = '1'
  el.src = fallback
}
function openInquiry() {
  inquiryRef.value?.open()
}

const shopName = computed(() => String(item.value?.user?.company_name || item.value?.company_name || item.value?.user?.name || '会员商家').trim())
const shopTo = computed(() => {
  const slug = item.value?.user?.company_slug || item.value?.company_slug
  return slug ? `/companies/${slug}` : ''
})
const badgeUser = computed(() => {
  const grade = String(item.value?.user?.member_grade || '')
  if (!['silver', 'gold', 'diamond'].includes(grade)) return null
  return { vip_grade_code: grade, level: Number(item.value?.user?.member_level || 0) }
})
const shopProducts = computed(() => {
  const rows = item.value?.shop_products
  return Array.isArray(rows) ? rows.filter((p: any) => p && p.id) : []
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
  const code = String(p?.user?.member_grade || item.value?.user?.member_grade || 'normal').toLowerCase()
  const map: Record<string, string> = {
    silver: '/img/member/silver.png',
    gold: '/img/member/gold.png',
    diamond: '/img/member/diamond.png',
  }
  return map[code] || '/img/member/normal.png'
}
function shopProductVipLabel(p: any) {
  const code = String(p?.user?.member_grade || item.value?.user?.member_grade || '').toLowerCase()
  const map: Record<string, string> = { silver: '白银会员', gold: '黄金会员', diamond: '星钻会员' }
  return map[code] || '普通会员'
}

const nameBox = ref<HTMLElement | null>(null)
const nameLink = ref<any>(null)
const nameSpan = ref<HTMLElement | null>(null)
function fitShopName() {
  const box = nameBox.value
  const el = (nameLink.value?.$el || nameSpan.value) as HTMLElement | undefined
  if (!box || !el) return
  el.style.transform = 'none'
  const avail = box.clientWidth
  const need = el.scrollWidth
  if (avail > 0 && need > avail) el.style.transform = `scale(${avail / need})`
}
let nameObserver: ResizeObserver | null = null
let slideTimer: ReturnType<typeof setInterval> | null = null
watch(shopName, () => nextTick(fitShopName))
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
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <NuxtLink to="/sell">好物商城</NuxtLink>
          <span class="on">供应详情</span>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div v-if="error" class="blk empty">信息不存在或已下架</div>
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
                    <h1>
                      {{ item.title }}
                      <span v-if="item.is_elite" class="dt-tag">荐</span>
                      <span v-if="item.is_urgent" class="dt-tag urg">急</span>
                    </h1>
                    <div class="tb-sub">
                      <FavoriteButton type="supply" :id="item.id" />
                    </div>
                  </div>
                  <div class="tb-tags">
                    <span>{{ item.category?.name || '未分类' }}</span>
                    <span>浏览 {{ item.views ?? 0 }}</span>
                    <span>刷新 {{ item.refreshed_at || '-' }}</span>
                  </div>
                  <div class="tb-price">
                    <span class="k">售价</span>
                    <template v-if="priceText">
                      <b><small>¥</small>{{ priceText }}</b>
                      <em v-if="item.unit">/{{ item.unit }}</em>
                    </template>
                    <b v-else class="ask">面议</b>
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
                    <div v-if="item.min_amount" class="tb-row">
                      <span class="k">起订</span>
                      <span>{{ item.min_amount }}</span>
                    </div>
                    <div v-if="item.amount" class="tb-row">
                      <span class="k">供货</span>
                      <span>{{ item.amount }}</span>
                    </div>
                    <div v-if="item.days" class="tb-row">
                      <span class="k">货期</span>
                      <span>{{ item.days }}天</span>
                    </div>
                    <div v-if="item.brand" class="tb-row">
                      <span class="k">品牌</span>
                      <span>{{ item.brand }}</span>
                    </div>
                  </div>
                  <div class="tb-acts">
                    <button class="btn-cart" type="button" @click="openInquiry">立即询盘</button>
                  </div>
                  <InquiryForm
                    ref="inquiryRef"
                    type="supply"
                    :id="item.id"
                    :default-title="item.title"
                    hide-button
                  />
                </div>
              </div>
            </div>

            <div class="dt-card">
              <div class="dt-h">商品详情</div>
              <template v-if="specTiles.length">
                <h3 class="spec-title">参数信息</h3>
                <div class="spec-board">
                  <div v-for="(s, i) in specTiles.slice(0, 6)" :key="'h' + i" class="spec-tile">
                    <b>{{ s.value }}</b>
                    <span>{{ s.name }}</span>
                  </div>
                </div>
                <div v-if="specTiles.length > 6" class="spec-table">
                  <div v-for="(s, i) in specTiles.slice(6)" :key="'r' + i" class="spec-line">
                    <span>{{ s.name }}</span>
                    <b>{{ s.value }}</b>
                  </div>
                </div>
              </template>
              <section v-if="item.summary" class="spec-sec">
                <h3 class="prose-title">摘要</h3>
                <p class="spec-prose">{{ item.summary }}</p>
              </section>
              <section class="spec-sec">
                <h3 class="prose-title">详细说明</h3>
                <div v-if="contentIsHtml" class="spec-prose html" v-html="item.content" />
                <p v-else class="spec-prose">{{ item.content || '暂无详情' }}</p>
              </section>
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
                  <button class="btn" type="button" @click="openInquiry">联系商家 / 询盘</button>
                  <NuxtLink v-if="shopTo" class="btn-soft-link" :to="shopTo">进入旺铺</NuxtLink>
                  <NuxtLink class="btn-soft-link" to="/sell">继续选购</NuxtLink>
                </div>
              </div>
            </div>
            <div class="tips">
              <b>购买提示</b>
              <ul>
                <li>通过平台询盘更安全，由官方审核后转达企业</li>
                <li>大额采购可通过询盘议价</li>
                <li>警惕脱离平台的私下转账</li>
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
.dt-tag {
  display: inline-block;
  margin-left: 6px;
  padding: 1px 6px;
  border-radius: 4px;
  background: var(--blue);
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  vertical-align: middle;
}
.dt-tag.urg { background: #e11d48; }
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
.tb-price b.ask { font-size: 22px; }
.tb-price small {
  font-size: 15px;
  margin-right: 2px;
}
.tb-price em {
  font-style: normal;
  font-size: 13px;
  color: var(--ink-2);
}
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
.tb-acts {
  display: flex;
  gap: 10px;
  margin-top: auto;
  padding-top: 10px;
}
.tb-acts :deep(.chat-btn-wrap) {
  flex: 1;
  display: flex;
  min-width: 0;
}
.tb-acts :deep(.chat-btn-wrap .tip) {
  display: none;
}
.btn-cart,
.tb-acts :deep(.chat-btn-wrap .btn-buy) {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 36px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
  font-family: inherit;
  box-sizing: border-box;
}
.btn-cart {
  border: 1px solid var(--blue);
  background: #fff;
  color: var(--blue);
}
/* :deep 才能作用到 ChatButton 内部按钮，否则「在线」无背景像纯文字 */
.tb-acts :deep(.chat-btn-wrap .btn-buy) {
  border: 0;
  background: var(--blue) !important;
  color: #fff !important;
}
.tb-acts :deep(.chat-btn-wrap .btn-buy:hover) {
  background: var(--blue-deep, #1443b8) !important;
}
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
.spec-prose.html { white-space: normal; }
@media (max-width: 720px) {
  .spec-board,
  .spec-table { grid-template-columns: 1fr; }
}
</style>

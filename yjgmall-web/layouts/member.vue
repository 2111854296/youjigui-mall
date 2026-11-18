<script setup lang="ts">
type TabKey = 'service' | 'info' | 'trade' | 'shop'
const route = useRoute()
const { user, logout, caps, isEnterprise } = useAuth()

type MenuItem = { to: string; label: string; action?: string; actionTo?: string; need?: keyof typeof caps.value }

const menusAll: Record<TabKey, MenuItem[]> = {
  service: [
    { to: '/member/messages', label: '站内通知', action: '询盘', actionTo: '/member/inquiries' },
    { to: '/member/friends', label: '我的商友', action: '添加', actionTo: '/member/friends' },
    { to: '/member/favorites', label: '我的收藏', action: '查看', actionTo: '/member/favorites' },
    { to: '/member/alerts', label: '贸易提醒', action: '添加', actionTo: '/member/alerts' },
    // 邮件订阅：SMTP 未配时入口隐藏，配好后再打开
    { to: '/member/spread', label: '排名推广', action: '购买', actionTo: '/member/spread', need: 'spread' },
    { to: '/ads', label: '广告预定', action: '购买', actionTo: '/ads', need: 'ads' },
    { to: '/member/profile', label: '修改资料', action: '头像', actionTo: '/member/avatar' },
    { to: '/member/password', label: '修改密码' },
    { to: '/member/validate', label: '会员认证' },
    { to: '/member/grade', label: '会员升级' },
    { to: '/member/vip-pay', label: 'VIP对公付款' },
    { to: '/member/seats', label: '企业席位（HR）' },
    { to: '/member/invite', label: '拉新邀请' },
    { to: '/member/contact-packs', label: '次数包' },
    { to: '/member/ask', label: '客服中心', action: '提问', actionTo: '/member/ask' },
  ],
  info: [
    { to: '/member/supplies', label: '供应管理', action: '发布', actionTo: '/member/supplies/create', need: 'supply' },
    { to: '/member/purchases', label: '采购管理', action: '发布', actionTo: '/member/purchases/create' },
    { to: '/member/products', label: '商品管理', action: '发布', actionTo: '/member/products/create', need: 'product' },
    { to: '/member/articles', label: '技术分享', action: '发布', actionTo: '/member/articles/create', need: 'article' },
    { to: '/member/jobs', label: '招聘管理', action: '发布', actionTo: '/member/jobs/create' },
    { to: '/member/resumes', label: '简历管理', action: '新建', actionTo: '/member/resumes/create' },
    { to: '/member/job-applications', label: '我的投递' },
    { to: '/member/job-received', label: '收到的投递' },
    // 知道/商圈/图库/视频/团购/展会/品牌：暂无主导航，会员入口一并隐藏
    { to: '/member/inquiries', label: '询盘管理' },
    { to: '/member/quotes', label: '收到的报价' },
    { to: '/member/reviews', label: '评价管理' },
  ],
  trade: [
    { to: '/member/orders', label: '我的订单' },
    { to: '/member/seller-orders', label: '售出订单', need: 'seller_orders' },
    { to: '/member/cart', label: '购物车' },
    { to: '/member/addresses', label: '收货地址', action: '添加', actionTo: '/member/addresses' },
    { to: '/member/finance', label: '资金流水', action: '充值', actionTo: '/member/finance' },
    { to: '/member/fund-pay', label: '对公付款' },
    { to: '/member/deposit', label: '保证金' },
    { to: '/member/cash', label: '申请提现' },
    { to: '/member/credit', label: '购买积分' },
  ],
  shop: [
    { to: '/member/shop', label: '旺铺设置' },
    { to: '/member/shop/news', label: '公司新闻' },
    { to: '/member/shop/honors', label: '荣誉资质' },
    { to: '/member/shop/pages', label: '公司单页' },
    { to: '/member/shop/links', label: '友情链接' },
  ],
}

const menus = computed(() => {
  const filter = (items: MenuItem[]) => items.filter((i) => !i.need || caps.value[i.need])
  return {
    service: filter(menusAll.service),
    info: filter(menusAll.info),
    trade: filter(menusAll.trade),
    shop: isEnterprise.value ? menusAll.shop : [],
  }
})

const infoHome = computed(() => (caps.value.supply ? '/member/supplies' : '/member/purchases'))

/** 侧栏分组只跟当前 URL，禁止顶栏先改 tab 再跳转导致「菜单已切、页面没动」 */
function tabFromPath(p: string): TabKey {
  if (/^\/member\/(seller-orders|orders|cart|addresses|finance|fund-pay|deposit|cash|credit|group-orders|group-seller-orders)(\/|$)/.test(p)) {
    return 'trade'
  }
  if (/^\/member\/(products|articles|supplies|purchases|inquiries|quotes|reviews|jobs|know|club|resumes|job-applications|job-received|photo|video|exhibit|brand)(\/|$)/.test(p)
    || /^\/member\/group(\/|$)/.test(p)) {
    return 'info'
  }
  if (p.includes('/member/shop')) {
    return isEnterprise.value ? 'shop' : 'service'
  }
  return 'service'
}

const tab = computed(() => tabFromPath(route.path))
const sideItems = computed(() => menus.value[tab.value] || [])

function scrollHash() {
  const id = String(route.hash || '').replace(/^#/, '')
  if (!id) return
  nextTick(() => {
    setTimeout(() => {
      document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
    }, 50)
  })
}
watch(() => [route.path, route.hash], () => scrollHash(), { immediate: true })

function sideActive(to: string) {
  const [path, hash = ''] = to.split('#')
  if (hash) return route.path === path && route.hash === `#${hash}`
  // 旺铺设置仅匹配首页，避免子页也高亮
  if (path === '/member/shop') {
    return route.path === '/member/shop' || route.path === '/member/shop/'
  }
  return route.path === path || route.path.startsWith(`${path}/`)
}

async function onLogout() {
  await logout()
  navigateTo('/login')
}

function goTab(t: TabKey, to: string) {
  if (t === 'shop' && !isEnterprise.value) return
  if (route.path === to || route.path === `${to}/`) return
  return navigateTo(to)
}

/** 整行可点，避免点到 padding 空白无反应 */
function goSide(to: string, e?: Event) {
  const t = e?.target as HTMLElement | null
  if (t?.closest?.('a.mc-action')) return
  const [path] = to.split('#')
  if (sideActive(to) && !to.includes('#')) return
  return navigateTo(to.includes('#') ? to : path)
}
</script>

<template>
  <div class="mc">
    <header class="mc-top">
      <div class="container mc-top-inner">
        <NuxtLink class="mc-brand" to="/member">商务中心</NuxtLink>
        <nav class="mc-tabs">
          <button type="button" :class="{ active: tab === 'service' }" @click="goTab('service', '/member')">会员服务</button>
          <button type="button" :class="{ active: tab === 'info' }" @click="goTab('info', infoHome)">信息管理</button>
          <button type="button" :class="{ active: tab === 'trade' }" @click="goTab('trade', '/member/orders')">交易管理</button>
          <button v-if="isEnterprise" type="button" :class="{ active: tab === 'shop' }" @click="goTab('shop', '/member/shop')">商铺管理</button>
          <NuxtLink to="/">网站首页</NuxtLink>
        </nav>
        <div class="mc-user">
          <span class="mc-badge">{{ isEnterprise ? '企业' : '个人' }}</span>
          <span>{{ user?.name || user?.username }}</span>
          <button type="button" class="mc-logout" @click="onLogout">退出登录</button>
        </div>
      </div>
    </header>

    <div class="container mc-body">
      <aside class="mc-side">
        <div class="mc-side-title">
          {{ tab === 'service' ? '会员服务' : tab === 'info' ? '信息管理' : tab === 'trade' ? '交易管理' : '商铺管理' }}
        </div>
        <div
          v-for="item in sideItems"
          :key="item.to + item.label"
          class="mc-side-row"
          :class="{ active: sideActive(item.to) }"
          role="link"
          tabindex="0"
          @click="goSide(item.to, $event)"
          @keydown.enter.prevent="goSide(item.to)"
        >
          <NuxtLink
            :to="item.to"
            class="mc-side-link"
            :class="{ 'router-link-active': sideActive(item.to) }"
            @click.stop
          >{{ item.label }}</NuxtLink>
          <NuxtLink
            v-if="item.action"
            class="mc-action"
            :to="item.actionTo || item.to"
            @click.stop
          >{{ item.action }}</NuxtLink>
        </div>
      </aside>
      <main class="mc-main">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.mc {
  /* 商户后台配色对齐前台（侧栏样式本轮不动） */
  --mc-brand: #2468f2;
  --mc-brand-deep: #1443b8;
  --mc-brand-tint: #eef4ff;
  --mc-ink: #13294b;
  min-height: 100vh;
  background: #eef2f8;
  font-size: 14px;
  color: var(--mc-ink);
}
/* 接近原站：几乎通栏，两侧仅留窄边 */
.mc :deep(.container),
.mc-top-inner.container,
.mc-body.container {
  width: calc(100% - 16px);
  max-width: none;
  margin-left: auto;
  margin-right: auto;
}
.mc-top {
  background: linear-gradient(180deg, #2f6fe8, var(--mc-brand-deep));
  color: #fff;
  box-shadow: 0 1px 0 rgba(0,0,0,.08);
}
.mc-top-inner {
  display: flex;
  align-items: center;
  gap: 12px;
  min-height: 46px;
  padding: 0;
}
.mc-brand {
  color: #fff !important;
  font-size: 20px;
  font-weight: 700;
  padding: 12px 10px 12px 0;
  white-space: nowrap;
}
.mc-tabs {
  display: flex;
  flex: 1;
  align-items: stretch;
  gap: 0;
}
.mc-tabs button,
.mc-tabs a {
  appearance: none;
  border: 0;
  background: transparent;
  color: #dce9f8 !important;
  padding: 14px 18px;
  font: inherit;
  font-size: 15px;
  cursor: pointer;
}
.mc-tabs button.active,
.mc-tabs button:hover,
.mc-tabs a:hover {
  background: rgba(0,0,0,.18);
  color: #fff !important;
}
.mc-user {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 14px;
  color: #e8f1fb;
  white-space: nowrap;
}
.mc-badge {
  font-size: 12px;
  padding: 2px 7px;
  border: 1px solid rgba(255,255,255,.45);
  border-radius: 2px;
  opacity: .95;
}
.mc-logout {
  border: 1px solid rgba(255,255,255,.45);
  background: rgba(255,255,255,.08);
  color: #fff;
  padding: 5px 12px;
  border-radius: 8px;
  cursor: pointer;
  font: inherit;
  font-size: 13px;
}
.mc-logout:hover {
  background: rgba(255,255,255,.16);
}
.mc-body {
  display: grid;
  grid-template-columns: 240px minmax(0, 1fr);
  gap: 10px;
  padding: 10px 0 28px;
  align-items: start;
}
.mc-side {
  position: relative;
  z-index: 2;
  background: #fff;
  border: 1px solid #cfd8e3;
  overflow: hidden;
}
.mc-side-title {
  background: linear-gradient(90deg, #edf3fa, #f8fbff);
  border-bottom: 1px solid #cfd8e3;
  padding: 12px 14px;
  font-weight: 700;
  font-size: 15px;
  color: #255d9a;
}
.mc-side-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #f0f3f7;
  padding: 0;
  min-height: 42px;
  font-size: 14px;
  cursor: pointer;
}
.mc-side-row:hover { background: #f8fafc; }
.mc-side-row.active {
  background: #f5f9ff;
}
.mc-side-row .mc-side-link {
  color: #333 !important;
  padding: 11px 8px 11px 14px;
  flex: 1;
  min-width: 0;
  text-decoration: none;
}
.mc-side-row a.router-link-active,
.mc-side-row .mc-side-link.router-link-active {
  color: #255d9a !important;
  font-weight: 600;
}
.mc-action {
  flex: none !important;
  color: #255d9a !important;
  font-size: 13px;
  padding: 8px 12px 8px 8px !important;
  text-decoration: none;
}
.mc-main { min-width: 0; font-size: 14px; }
.mc-main :deep(.mf-form) { max-width: none; }
.mc-main :deep(.panel),
.mc-main :deep(.card) { width: 100%; }
.mc-main :deep(h1),
.mc-main :deep(h2),
.mc-main :deep(.page-title) { font-size: 18px; }
.mc-main :deep(table),
.mc-main :deep(.table),
.mc-main :deep(.list),
.mc-main :deep(.muted) { font-size: 14px; }

/* 内容区按钮/链接统一前台蓝（不改侧栏） */
.mc-main :deep(.btn),
.mc-main :deep(button.btn),
.mc-main :deep(a.btn) {
  background: var(--mc-brand);
  border: 1px solid var(--mc-brand);
  border-radius: 8px;
  color: #fff !important;
  font-weight: 600;
  padding: 8px 16px;
  transition: background .15s, border-color .15s, filter .15s;
}
.mc-main :deep(.btn:hover),
.mc-main :deep(button.btn:hover),
.mc-main :deep(a.btn:hover) {
  background: var(--mc-brand-deep);
  border-color: var(--mc-brand-deep);
  filter: none;
}
.mc-main :deep(.btn-outline),
.mc-main :deep(a.btn-outline),
.mc-main :deep(button.btn-outline) {
  background: #fff !important;
  border: 1px solid var(--mc-brand) !important;
  color: var(--mc-brand) !important;
}
.mc-main :deep(.btn-outline:hover),
.mc-main :deep(a.btn-outline:hover),
.mc-main :deep(button.btn-outline:hover) {
  background: var(--mc-brand-tint) !important;
  color: var(--mc-brand-deep) !important;
}
.mc-main :deep(.btn--primary),
.mc-main :deep(button.btn--primary) {
  background: var(--mc-brand) !important;
  border-color: var(--mc-brand) !important;
  color: #fff !important;
  border-radius: 8px;
}
.mc-main :deep(.btn--ghost),
.mc-main :deep(button.btn--ghost) {
  background: #fff !important;
  border: 1px solid #d7e4fb !important;
  color: var(--mc-brand) !important;
  border-radius: 8px;
}
.mc-main :deep(.panel h3) {
  border-left-color: var(--mc-brand);
  background: linear-gradient(90deg, var(--mc-brand-tint), #fff 48%);
}
.mc-main :deep(.btn-up),
.mc-main :deep(.btn-charge),
.mc-main :deep(a.btn-up),
.mc-main :deep(a.btn-charge) {
  background: var(--mc-brand) !important;
  border-radius: 8px;
  color: #fff !important;
  font-weight: 600;
}
.mc-main :deep(.btn-up:hover),
.mc-main :deep(.btn-charge:hover),
.mc-main :deep(a.btn-up:hover),
.mc-main :deep(a.btn-charge:hover) {
  background: var(--mc-brand-deep) !important;
}
@media (max-width: 900px) {
  .mc :deep(.container),
  .mc-top-inner.container,
  .mc-body.container { width: calc(100% - 12px); }
  .mc-body { grid-template-columns: 1fr; }
  .mc-tabs button, .mc-tabs a { padding: 12px 10px; font-size: 14px; }
}
</style>

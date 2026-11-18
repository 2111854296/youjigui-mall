<script setup lang="ts">
import {
  Odometer, User, Box, ShoppingCart, Goods, OfficeBuilding,
  Document, Picture, Promotion, Setting, SwitchButton,
  ArrowDown, Expand, Fold, ChatDotRound, Star, Wallet,
  Ticket, Collection, Link, Bell, Memo, Postcard,
  DataBoard, Shop, PriceTag, Folder, Medal, Refresh, Grid, Delete,
} from '@element-plus/icons-vue'

useHead({
  link: [
    { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700&family=Noto+Sans+SC:wght@400;500;700&display=swap' },
  ],
})

const route = useRoute()
const { user, logout } = useAuth('admin')
const { api } = useApi('admin')
const collapsed = ref(false)
const menuError = ref('')
const openeds = ref<string[]>([])

const { data: menuRes, pending: menuPending, refresh: refreshMenu } = await useAsyncData(
  'admin-menu',
  async () => {
    try {
      menuError.value = ''
      return await api('/admin/menu')
    } catch (e: any) {
      menuError.value = e?.data?.message || '菜单加载失败'
      return { data: [] }
    }
  },
  { server: false, default: () => ({ data: [] }) },
)
const groups = computed(() => (menuRes.value as any)?.data || [])

function routeFull(path = route.fullPath) {
  const i = path.indexOf('#')
  return i >= 0 ? path.slice(0, i) : path
}

function menuMatch(to: string) {
  const full = routeFull()
  if (to.includes('?')) return full === to || full.startsWith(to + '&')
  const [p] = full.split('?')
  // 无 query 的菜单项：当前 URL 若带筛选 query，优先留给带 query 的兄弟项（如 待审资讯）
  if (p !== to) return false
  if (!full.includes('?')) return true
  return !groups.value.some((g: any) =>
    (g.items || []).some((i: any) => {
      const t = String(i?.to || '')
      return t.includes('?') && (full === t || full.startsWith(t + '&'))
    }),
  )
}

watch(groups, (gs) => {
  if (!gs?.length) return
  const hit = gs.find((g: any) => (g.items || []).some((i: any) => menuMatch(i.to)))
  if (hit && !openeds.value.includes(hit.group)) {
    openeds.value = [...new Set([...openeds.value, hit.group])]
  }
}, { immediate: true })

watch(() => route.fullPath, () => {
  const hit = groups.value.find((g: any) => (g.items || []).some((i: any) => menuMatch(i.to)))
  if (hit && !openeds.value.includes(hit.group)) {
    openeds.value = [...new Set([...openeds.value, hit.group])]
  }
})

const iconMap: Record<string, any> = {
  users: User,
  orders: ShoppingCart,
  inquiries: ChatDotRound,
  'product-reviews': Star,
  withdrawals: Wallet,
  'vip-applications': Ticket,
  'vip-pay-orders': Wallet,
  'ask-tickets': Postcard,
  'support-tickets': Postcard,
  supplies: Box,
  purchases: Collection,
  products: Goods,
  companies: OfficeBuilding,
  'company-vip': Ticket,
  'company-cats': Folder,
  'company-honors': Medal,
  'company-news': Document,
  'company-pages': Memo,
  'company-links': Link,
  'company-templates': Grid,
  'company-refresh': Refresh,
  'company-settings': Setting,
  articles: Document,
  'news-add': Document,
  'news-audit': Document,
  'news-cats': Folder,
  'news-refresh': Refresh,
  'news-settings': Setting,
  banners: Picture,
  ads: Promotion,
  'ad-bookings': Memo,
  categories: Setting,
  pages: Document,
  hotwords: PriceTag,
  'friend-links': Link,
  announcements: Bell,
  quotes: DataBoard,
  brands: Shop,
  recycle: Delete,
}

function itemIcon(key: string) {
  return iconMap[key] || Document
}

const active = computed(() => {
  if (route.path === '/admin') return '/admin'
  for (const g of groups.value) {
    for (const item of g.items || []) {
      if (menuMatch(item.to)) return item.to
    }
  }
  return route.path
})

const title = computed(() => {
  if (route.path === '/admin') return '仪表盘'
  if (route.path === '/admin/settings') return '站点设置'
  if (route.path === '/admin/recycle') return '回收站'
  if (route.path.startsWith('/admin/articles/edit')) return route.query.id ? '修改资讯' : '添加资讯'
  if (route.path.startsWith('/admin/companies/edit')) return route.query.id ? '修改企业' : '添加企业'
  for (const g of groups.value) {
    const hit = (g.items || []).find((i: any) => menuMatch(i.to))
    if (hit) return hit.label
  }
  const key = String(route.params.resource || '')
  for (const g of groups.value) {
    const hit = (g.items || []).find((i: any) => i.key === key)
    if (hit) return hit.label
  }
  return '运营后台'
})

async function onLogout() {
  await logout()
  navigateTo('/admin/login')
}

function go(path: string) {
  navigateTo(path)
}

function badgeText(n: number) {
  return n > 99 ? '99+' : String(n)
}

if (import.meta.client) {
  const kick = () => { refreshMenu() }
  window.addEventListener('admin-menu-refresh', kick)
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') kick()
  })
  const timer = window.setInterval(kick, 30000)
  onUnmounted(() => {
    window.removeEventListener('admin-menu-refresh', kick)
    window.clearInterval(timer)
  })
}
</script>

<template>
  <div class="admin-shell" :class="{ collapsed }">
    <aside class="side">
      <div class="brand">
        <div class="logo">硅</div>
        <div v-show="!collapsed" class="brand-text">
          <b>有机硅商城</b>
          <span>管理后台</span>
        </div>
      </div>

      <div class="side-scroll">
        <el-menu
          :key="collapsed ? 'c' : 'e'"
          :default-active="active"
          :default-openeds="openeds"
          :collapse="collapsed"
          background-color="transparent"
          text-color="#5b6573"
          active-text-color="#2468f2"
          @select="go"
          @open="(i: string) => { if (!openeds.includes(i)) openeds.push(i) }"
          @close="(i: string) => { openeds = openeds.filter((x) => x !== i) }"
        >
          <el-menu-item index="/admin">
            <el-icon><Odometer /></el-icon>
            <template #title>仪表盘</template>
          </el-menu-item>
          <el-menu-item index="/admin/settings">
            <el-icon><Setting /></el-icon>
            <template #title>站点设置</template>
          </el-menu-item>

          <template v-if="menuPending">
            <div class="menu-hint">菜单加载中…</div>
          </template>
          <template v-else-if="menuError">
            <div class="menu-hint err">{{ menuError }}</div>
          </template>

          <el-sub-menu v-for="g in groups" :key="g.group" :index="g.group">
            <template #title>
              <el-icon><Folder /></el-icon>
              <span class="sub-label">
                {{ g.group }}
                <i v-if="g.badge" class="badge parent">{{ badgeText(g.badge) }}</i>
              </span>
            </template>
            <el-menu-item v-for="item in g.items" :key="item.key + item.to" :index="item.to">
              <el-icon><component :is="itemIcon(item.key)" /></el-icon>
              <template #title>
                <span class="mi">{{ item.label }}</span>
                <i v-if="item.badge" class="badge child">{{ badgeText(item.badge) }}</i>
              </template>
            </el-menu-item>
          </el-sub-menu>
        </el-menu>
      </div>
    </aside>

    <div class="main">
      <header class="top">
        <div class="top-left">
          <el-button text circle class="fold-btn" @click="collapsed = !collapsed">
            <el-icon :size="18"><Fold v-if="!collapsed" /><Expand v-else /></el-icon>
          </el-button>
          <div class="crumb">
            <span class="muted">管理后台</span>
            <span class="sep">/</span>
            <strong>{{ title }}</strong>
          </div>
        </div>
        <div class="top-right">
          <el-button text class="home-link" tag="a" href="/" target="_blank">前台首页</el-button>
          <el-dropdown>
            <span class="user-trigger">
              <el-avatar :size="30" class="av">{{ (user?.name || user?.username || 'A').slice(0, 1) }}</el-avatar>
              <span class="uname">{{ user?.name || user?.username }}</span>
              <el-icon><ArrowDown /></el-icon>
            </span>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item @click="onLogout">
                  <el-icon><SwitchButton /></el-icon>退出登录
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </header>
      <main class="body">
        <slot />
      </main>
    </div>
  </div>
</template>

<style scoped>
.admin-shell {
  --side-w: 228px;
  --brand: #2468f2;
  --brand-deep: #1443b8;
  --brand-tint: #eef4ff;
  --ink: #13294b;
  /* Element Plus 主色跟网站蓝 */
  --el-color-primary: #2468f2;
  --el-color-primary-light-3: #5b8ff7;
  --el-color-primary-light-5: #92b4fa;
  --el-color-primary-light-7: #c9d9fc;
  --el-color-primary-light-8: #dde8fd;
  --el-color-primary-light-9: #eef4ff;
  --el-color-primary-dark-2: #1443b8;
  min-height: 100vh;
  display: grid;
  grid-template-columns: var(--side-w) 1fr;
  background: #eef2f8;
  transition: grid-template-columns .2s ease;
  font-family: "Outfit", "Noto Sans SC", "PingFang SC", "Microsoft YaHei", sans-serif;
}
.admin-shell.collapsed { --side-w: 72px; }

.side {
  background: #fff;
  color: var(--ink);
  position: sticky;
  top: 0;
  height: 100vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border-right: 1px solid #e8ecf0;
}
.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 18px 16px;
  border-bottom: 1px solid #eef1f4;
  min-height: 64px;
  flex-shrink: 0;
}
.logo {
  width: 36px; height: 36px; border-radius: 10px;
  background: var(--brand);
  color: #fff;
  display: grid; place-items: center;
  font-weight: 800; font-size: 15px; flex-shrink: 0;
}
.brand-text { display: flex; flex-direction: column; line-height: 1.25; min-width: 0; }
.brand-text b { font-size: 15px; font-weight: 700; }
.brand-text span { font-size: 11px; color: #94a3b8; }
.side-scroll { flex: 1; overflow: auto; padding: 8px 0 16px; }
.menu-hint { padding: 12px 18px; font-size: 12px; color: #94a3b8; }
.menu-hint.err { color: #dc2626; }

.main { min-width: 0; display: flex; flex-direction: column; }
.top {
  height: 56px;
  background: #fff;
  border-bottom: 1px solid #e8ecf0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 18px;
  position: sticky;
  top: 0;
  z-index: 20;
}
.top-left, .top-right { display: flex; align-items: center; gap: 10px; }
.crumb { display: flex; align-items: center; gap: 8px; font-size: 14px; }
.crumb .muted { color: #94a3b8; }
.crumb .sep { color: #cbd5e1; }
.crumb strong { color: var(--ink); font-weight: 600; }
.fold-btn { color: #64748b; }
.home-link { color: #64748b; font-weight: 500; }
.user-trigger {
  display: inline-flex; align-items: center; gap: 8px;
  cursor: pointer; outline: none;
}
.av { background: var(--brand-tint); color: var(--brand-deep); font-weight: 700; }
.uname { font-size: 13px; color: #334155; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.body {
  padding: 16px 18px 28px;
  width: 100%;
  max-width: none;
  box-sizing: border-box;
}

:deep(.el-menu) { border-right: none; }
/* 仅侧栏最外层（仪表盘/站点设置）和父级标题：网站蓝 + 白字。不要套到展开后的子项 */
:deep(.side-scroll > .el-menu > .el-menu-item),
:deep(.el-sub-menu > .el-sub-menu__title) {
  margin: 6px 10px 2px;
  padding-left: 12px !important;
  border-radius: 8px;
  background: var(--brand) !important;
  color: #fff !important;
  font-weight: 700;
}
:deep(.side-scroll > .el-menu > .el-menu-item .el-icon),
:deep(.el-sub-menu > .el-sub-menu__title .el-icon),
:deep(.el-sub-menu > .el-sub-menu__title .el-sub-menu__icon-arrow) {
  color: #fff !important;
}
:deep(.side-scroll > .el-menu > .el-menu-item.is-active),
:deep(.el-sub-menu.is-opened > .el-sub-menu__title),
:deep(.el-sub-menu.is-active > .el-sub-menu__title) {
  background: var(--brand) !important;
  color: #fff !important;
}
:deep(.side-scroll > .el-menu > .el-menu-item:hover),
:deep(.el-sub-menu > .el-sub-menu__title:hover) {
  background: var(--brand-deep) !important;
  color: #fff !important;
}
/* 子菜单项：白底，选中淡蓝高亮 */
:deep(.el-sub-menu .el-menu-item) {
  min-width: auto;
  margin: 0 10px;
  border-radius: 8px;
  background: transparent !important;
  color: #5b6573 !important;
  font-weight: 500;
  position: relative;
}
.sub-label { display: inline-flex; align-items: center; gap: 6px; min-width: 0; }
.badge {
  font-style: normal;
  font-weight: 700;
  line-height: 16px;
  min-width: 16px;
  height: 16px;
  padding: 0 4px;
  border-radius: 999px;
  text-align: center;
  font-size: 11px;
  box-sizing: border-box;
}
.badge.parent { background: #fff; color: var(--brand); }
.badge.child {
  position: absolute;
  top: 2px;
  right: 8px;
  background: #e11d2e;
  color: #fff;
}
:deep(.el-sub-menu .el-menu-item .el-icon) {
  color: #94a3b8 !important;
}
:deep(.el-sub-menu .el-menu-item.is-active) {
  background: var(--brand-tint) !important;
  color: var(--brand-deep) !important;
  font-weight: 600;
}
:deep(.el-sub-menu .el-menu-item.is-active .el-icon) {
  color: var(--brand) !important;
}
:deep(.el-sub-menu .el-menu-item:hover) {
  background: var(--brand-tint) !important;
  color: var(--brand-deep) !important;
}
:deep(.el-sub-menu .el-menu-item:hover .el-icon) {
  color: var(--brand) !important;
}

@media (max-width: 900px) {
  .admin-shell { grid-template-columns: 1fr; }
  .side { position: fixed; z-index: 40; left: 0; transform: translateX(0); }
  .admin-shell.collapsed .side { transform: translateX(-100%); }
  .uname { display: none; }
}
</style>

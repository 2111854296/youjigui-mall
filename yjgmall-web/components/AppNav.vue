<script setup lang="ts">
const props = withDefaults(defineProps<{ variant?: 'default' | 'portal' }>(), {
  variant: 'default',
})

const route = useRoute()
const scrolled = ref(false)

/**
 * 门户菜单：各自独立频道页（非首页锚点、非旧蓝底 default 壳）
 * 价格行情→/quotes  好物商城→/sell
 * 样品专区→/sample  名企推荐→/companies  供需对接→/buy  招聘专区→/jobs  硅业智讯→/news
 * （优质产品导航已按客户要求去掉，/mall 页保留直达）
 */
const portalLinks = [
  { to: '/', label: '首页' },
  { to: '/quotes', label: '价格行情' },
  { to: '/sell', label: '好物商城' },
  { to: '/sample', label: '样品专区' },
  { to: '/companies', label: '名企推荐' },
  { to: '/buy', label: '供需对接' },
  { to: '/jobs', label: '招聘专区' },
  { to: '/news', label: '硅业智讯' },
]

const defaultLinks = [
  { to: '/', label: '首页' },
  { to: '/companies', label: '优质厂家' },
  { to: '/buy', label: '优质采购' },
  { to: '/sell', label: '供应' },
  { to: '/news', label: '资讯' },
  { to: '/quotes', label: '行情' },
]

const links = computed(() => (props.variant === 'portal' ? portalLinks : defaultLinks))

function isActive(link: { to: string }) {
  if (link.to === '/') return route.path === '/'
  return route.path === link.to || route.path.startsWith(`${link.to}/`)
}

function onScroll() {
  scrolled.value = typeof window !== 'undefined' && window.scrollY > 8
}

onMounted(() => {
  onScroll()
  window.addEventListener('scroll', onScroll, { passive: true })
})
onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
})
</script>

<template>
  <!-- 门户：设计稿 .menu-bar；其它页：旧蓝底 .nav.default -->
  <nav
    v-if="variant === 'portal'"
    class="menu-bar"
    :class="{ scrolled }"
  >
    <div class="container">
      <div class="menu">
        <NuxtLink
          v-for="link in links"
          :key="link.label"
          :to="link.to"
          :class="{ active: isActive(link) }"
        >{{ link.label }}</NuxtLink>
      </div>
    </div>
  </nav>
  <nav v-else class="nav default">
    <div class="container nav-inner">
      <NuxtLink class="cat-entry" to="/">有机硅商城</NuxtLink>
      <ul class="menu">
        <li v-for="link in links" :key="link.to">
          <NuxtLink :to="link.to" :class="{ active: isActive(link) }">{{ link.label }}</NuxtLink>
        </li>
      </ul>
      <NuxtLink class="nav-publish" to="/member/supplies/create">免费发布</NuxtLink>
    </div>
  </nav>
</template>

<style scoped>
/* 仅非门户蓝底导航；门户样式全部来自 portal-design.css .menu-bar */
.nav.default {
  background: var(--nav-bg);
  position: sticky;
  top: 0;
  z-index: 40;
}
.nav-inner {
  display: flex;
  align-items: center;
  height: 46px;
  padding: 0 !important;
}
.cat-entry {
  background: #1e60d3;
  color: #fff !important;
  padding: 12px 20px;
  font-weight: 700;
  font-size: 14px;
  white-space: nowrap;
}
.nav.default .menu {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex: 1;
  gap: 0;
}
.nav.default .menu li a {
  display: block;
  padding: 12px 18px;
  font-size: 14px;
  color: #fff !important;
  font-weight: 600;
  background: transparent;
}
.nav.default .menu li a:hover,
.nav.default .menu li a.active {
  background: rgba(0, 0, 0, .14) !important;
}
.nav-publish {
  margin-left: auto;
  padding: 12px 18px;
  color: #fff !important;
  font-weight: 700;
}
</style>

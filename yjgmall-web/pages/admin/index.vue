<script setup lang="ts">
definePageMeta({ layout: 'admin', middleware: 'admin', title: '仪表盘' })
useHead({ title: '仪表盘 - 运营后台' })

const { api } = useApi()
const { data, pending, error } = await useAsyncData('admin-dashboard', () => api('/admin/dashboard'), {
  server: false,
})
const stats = computed(() => (data.value as any)?.data || {})

const { data: menuRes } = await useAsyncData('admin-menu-dash', () => api('/admin/menu'), {
  server: false,
  default: () => ({ data: [] }),
})
const groups = computed(() => (menuRes.value as any)?.data || [])

const cards = computed(() => [
  { label: '会员', value: stats.value.users, sub: `待审 ${stats.value.users_pending ?? 0}`, to: '/admin/r/users?status=pending', tone: '#2563eb' },
  { label: '供应', value: stats.value.supplies, sub: `待审 ${stats.value.supplies_pending ?? 0}`, to: '/admin/r/supplies?status=pending', tone: '#2468f2' },
  { label: '采购', value: stats.value.purchases, sub: `待审 ${stats.value.purchases_pending ?? 0}`, to: '/admin/r/purchases?status=pending', tone: '#0d9488' },
  { label: '商品', value: stats.value.products, sub: '商城 SKU', to: '/admin/r/products', tone: '#7c3aed' },
  { label: '订单', value: stats.value.orders, sub: '交易单', to: '/admin/r/orders', tone: '#db2777' },
  { label: '企业', value: stats.value.companies, sub: `询盘 ${stats.value.inquiries ?? 0}`, to: '/admin/r/companies', tone: '#0891b2' },
])
</script>

<template>
  <div class="dash">
    <div class="hero">
      <div>
        <h2>运营总览</h2>
        <p>供需审核、会员交易与站点配置集中管理</p>
      </div>
      <NuxtLink class="hero-cta" to="/admin/settings">站点设置</NuxtLink>
    </div>

    <el-skeleton v-if="pending" :rows="4" animated />
    <el-alert v-else-if="error" title="加载失败，请确认管理员登录与 API 可用" type="error" show-icon :closable="false" />

    <div v-else class="grid">
      <a
        v-for="c in cards"
        :key="c.label"
        class="stat"
        :style="{ '--tone': c.tone }"
        href="#"
        @click.prevent="navigateTo(c.to)"
      >
        <span class="label">{{ c.label }}</span>
        <b>{{ c.value ?? 0 }}</b>
        <small>{{ c.sub }}</small>
      </a>
    </div>

    <div v-if="!pending && !error" class="mods">
      <div v-for="g in groups" :key="g.group" class="mod">
        <div class="mod-hd">{{ g.group }}</div>
        <div class="links">
          <button
            v-for="item in g.items"
            :key="item.key"
            type="button"
            class="chip"
            @click="navigateTo(item.to)"
          >{{ item.label }}</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dash { width: 100%; }
.hero {
  margin-bottom: 18px;
  padding: 20px 22px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  background: linear-gradient(120deg, #0e2a5c, #1443b8 55%, #2468f2);
  color: #fff;
}
.hero h2 { margin: 0 0 6px; font-size: 20px; font-weight: 700; }
.hero p { margin: 0; opacity: .7; font-size: 13px; }
.hero-cta {
  flex-shrink: 0;
  padding: 9px 16px;
  border-radius: 8px;
  background: #2468f2;
  color: #fff !important;
  font-weight: 700;
  font-size: 13px;
  text-decoration: none;
}
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}
.stat {
  background: #fff;
  border: 1px solid #e8ecf0;
  border-radius: 12px;
  padding: 16px 18px;
  display: grid;
  gap: 6px;
  color: inherit;
  text-decoration: none;
  border-left: 3px solid var(--tone);
  cursor: pointer;
}
.stat:hover { border-color: var(--tone); }
.stat .label { font-size: 13px; color: #64748b; }
.stat b { font-size: 28px; color: #1a2332; line-height: 1.1; }
.stat small { font-size: 12px; color: #94a3b8; }
.mods { display: grid; gap: 12px; }
.mod {
  background: #fff;
  border: 1px solid #e8ecf0;
  border-radius: 12px;
  padding: 14px 16px 16px;
}
.mod-hd {
  font-weight: 700;
  color: #1a2332;
  margin-bottom: 12px;
  font-size: 14px;
}
.links { display: flex; flex-wrap: wrap; gap: 8px; }
.chip {
  border: 1px solid #e8ecf0;
  background: #f8f9fb;
  color: #334155;
  padding: 7px 14px;
  border-radius: 999px;
  font: inherit;
  font-size: 13px;
  cursor: pointer;
}
.chip:hover {
  border-color: #d7e4fb;
  background: #eef4ff;
  color: #1443b8;
}
@media (max-width: 900px) {
  .grid { grid-template-columns: 1fr 1fr; }
  .hero { flex-direction: column; align-items: flex-start; }
}
</style>

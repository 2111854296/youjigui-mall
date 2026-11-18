<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '供应对比' })
const route = useRoute()
const config = useRuntimeConfig()
const ids = computed(() => String(route.query.ids || ''))

const { data, error } = await useAsyncData(
  () => `compare-${ids.value}`,
  () => $fetch(`${config.public.apiBase}/sell/compare`, { query: { ids: ids.value } }),
  { watch: [ids] },
)
const items = computed(() => (data.value as any)?.data || [])
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <NuxtLink to="/sell">供应信息</NuxtLink>
          <span class="on">对比</span>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>对比说明：</b>
        最多同时对比 5 条供应，请从供应列表勾选后进入。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div v-if="error" class="blk empty">请从供应列表勾选后对比</div>
        <div v-else class="dt-card">
          <div class="dt-h">供应对比（最多 5 条）</div>
          <div class="table-wrap">
            <table v-if="items.length" class="cmp">
              <thead>
                <tr>
                  <th>项目</th>
                  <th v-for="item in items" :key="item.id">
                    <NuxtLink :to="`/sell/${item.id}`">{{ item.title }}</NuxtLink>
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>价格</td>
                  <td v-for="item in items" :key="'p'+item.id" class="price">
                    {{ item.price ? `¥${item.price}/${item.unit || ''}` : '面议' }}
                  </td>
                </tr>
                <tr>
                  <td>品牌</td>
                  <td v-for="item in items" :key="'b'+item.id">{{ item.brand || '-' }}</td>
                </tr>
                <tr>
                  <td>地区</td>
                  <td v-for="item in items" :key="'a'+item.id">{{ item.area || '-' }}</td>
                </tr>
                <tr>
                  <td>起订量</td>
                  <td v-for="item in items" :key="'m'+item.id">{{ item.min_amount || '-' }}</td>
                </tr>
                <tr>
                  <td>公司</td>
                  <td v-for="item in items" :key="'c'+item.id">{{ item.user?.company_name || '-' }}</td>
                </tr>
              </tbody>
            </table>
            <p v-else class="empty" style="padding:20px 0">暂无可对比的供应，请返回供应列表勾选后再次对比。</p>
          </div>
          <NuxtLink class="btn-soft-link" to="/sell" style="margin-top:14px">← 返回供应列表</NuxtLink>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.table-wrap { overflow-x: auto; }
.cmp { width: 100%; border-collapse: collapse; font-size: 13.5px; }
.cmp th, .cmp td {
  border-bottom: 1px solid var(--line);
  padding: 12px 10px;
  text-align: left;
  vertical-align: top;
}
.cmp th { color: var(--ink-3); font-weight: 600; background: #F8FAFD; }
.cmp th a { color: var(--ink); text-decoration: none; font-weight: 700; }
.cmp th a:hover { color: var(--blue); }
.cmp td:first-child { color: var(--ink-3); font-weight: 600; white-space: nowrap; width: 88px; }
.cmp .price { color: var(--up); font-weight: 700; }
</style>

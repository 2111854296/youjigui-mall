<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '我的收藏' })
const { api } = useApi()
const { confirm: appConfirm } = useAppDialog()
const route = useRoute()
const filter = computed(() => String(route.query.type || 'all'))
const { data, refresh } = useMemberData(
  'my-favorites',
  () => api('/member/favorites', { query: filter.value === 'all' ? {} : { type: filter.value } }),
  { watch: [filter] },
)
const items = computed(() => (data.value as any)?.data || [])

function linkOf(item: any) {
  if (item.type === 'purchase') return `/buy/${item.target_id}`
  if (item.type === 'product') return `/mall/${item.target_id}`
  if (item.type === 'quote') return `/quotes/${item.target_id}`
  if (item.type === 'resume') return `/talent/${item.target_id}`
  if (item.type === 'job') return `/job/${item.target_id}`
  return `/sell/${item.target_id}`
}

function typeLabel(item: any) {
  if (item.type === 'purchase') return '求购'
  if (item.type === 'product') return '商品'
  if (item.type === 'quote') return '行情'
  if (item.type === 'resume') return '人才'
  if (item.type === 'job') return '职位'
  return '供应'
}

async function remove(id: number) {
  if (!(await appConfirm({
    title: '取消收藏',
    message: '确认取消这条收藏？',
    tone: 'danger',
    confirmText: '取消收藏',
  }))) return
  await api(`/member/favorites/${id}`, { method: 'DELETE' })
  await appOk('已取消收藏。', '操作成功')
  await refresh()
}

const tabs = [
  { key: 'all', label: '全部' },
  { key: 'quote', label: '行情' },
  { key: 'supply', label: '供应' },
  { key: 'purchase', label: '求购' },
  { key: 'product', label: '商品' },
  { key: 'resume', label: '人才' },
  { key: 'job', label: '职位' },
]
</script>

<template>
  <div class="panel">
    <h3>
      我的收藏
      <NuxtLink class="back-list" to="/jobs">招聘专区</NuxtLink>
    </h3>
    <div class="body">
      <div class="fav-tabs">
        <NuxtLink
          v-for="t in tabs"
          :key="t.key"
          :to="t.key === 'all' ? '/member/favorites' : `/member/favorites?type=${t.key}`"
          class="fav-tab"
          :class="{ on: filter === t.key }"
        >{{ t.label }}</NuxtLink>
      </div>
      <p v-if="filter === 'quote'" class="muted tip">
        这里是已收藏的行情品种。浏览全部行情请到
        <NuxtLink to="/quotes">价格行情</NuxtLink>。
      </p>
      <p v-else-if="filter === 'resume'" class="muted tip">
        这里是已收藏的人才。浏览全部人才请到
        <NuxtLink to="/jobs">招聘专区 → 企业招人</NuxtLink>。
      </p>
      <p v-else-if="filter === 'job'" class="muted tip">
        这里是已收藏的职位。浏览职位请到
        <NuxtLink to="/jobs">招聘专区 → 找工作</NuxtLink>。
      </p>
      <table class="table">
        <thead><tr><th>类型</th><th>标题</th><th>时间</th><th>操作</th></tr></thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ typeLabel(item) }}</td>
            <td><NuxtLink :to="linkOf(item)">{{ item.title }}</NuxtLink></td>
            <td class="muted">{{ item.created_at?.slice(0, 16) }}</td>
            <td><a href="#" @click.prevent="remove(item.id)">取消</a></td>
          </tr>
          <tr v-if="!items.length"><td colspan="4" class="muted">暂无收藏</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.fav-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
.fav-tab {
  padding: 6px 12px; border-radius: 999px; border: 1px solid var(--line, #e5e7eb);
  font-size: 13px; color: #475569; text-decoration: none;
}
.fav-tab.on { background: #2769dc; border-color: #2769dc; color: #fff; }
.tip { margin: 0 0 10px; font-size: 13px; }
</style>

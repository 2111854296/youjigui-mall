<script setup lang="ts">
const { confirm: appConfirm } = useAppDialog()
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '采购管理' })
const { api } = useApi()

const { data, refresh, pending } = useMemberData('my-purchases', () => api('/member/purchases'))
const items = computed(() => (data.value as any)?.data || [])

async function remove(id: number) {
  if (!(await appConfirm({ title: '确认删除', message: '确认删除？', tone: 'danger', confirmText: '删除' }))) return
  await api(`/member/purchases/${id}`, { method: 'DELETE' })
  await appOk('已删除。', '操作成功')
  await refresh()
}

async function offline(id: number) {
  if (!(await appConfirm({ title: '撤回求购', message: '撤回后不再计入首页待处理，确认撤回？', confirmText: '撤回' }))) return
  await api(`/member/purchases/${id}`, { method: 'PUT', body: { status: 'offline' } })
  await appOk('已撤回，首页待处理数量会减少。', '操作成功')
  await refresh()
}
</script>

<style scoped>
.acts { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
.link { background: none; border: 0; padding: 0; color: var(--blue, #2468f2); font: inherit; cursor: pointer; }
.link.danger { color: #c2410c; }
</style>

<template>
  <div class="panel">
    <h3>
      采购管理
      <NuxtLink class="back-list" to="/member/purchases/create">发布采购</NuxtLink>
    </h3>
    <div class="body">
      <p v-if="pending" class="muted">加载中…</p>
      <table v-else class="table">
        <thead>
          <tr><th>标题</th><th>状态</th><th>预算</th><th>操作</th></tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>
              <NuxtLink v-if="item.status === 'approved'" :to="`/buy/${item.id}`">{{ item.title }}</NuxtLink>
              <NuxtLink v-else :to="`/member/purchases/${item.id}`">{{ item.title }}</NuxtLink>
            </td>
            <td>{{ auditStatusLabel(item.status) }}</td>
            <td>{{ item.budget || '-' }}</td>
            <td class="acts">
              <NuxtLink :to="`/member/purchases/${item.id}`">编辑</NuxtLink>
              <button v-if="item.status === 'pending'" type="button" class="link" @click="offline(item.id)">撤回</button>
              <button type="button" class="link danger" @click="remove(item.id)">删除</button>
            </td>
          </tr>
          <tr v-if="!items.length"><td colspan="4" class="muted">暂无采购。待审核的求购可点「撤回」，首页数字会随之减少。</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

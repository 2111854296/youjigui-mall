<script setup lang="ts">
const { confirm: appConfirm } = useAppDialog()
definePageMeta({ layout: 'member', middleware: ['auth', 'enterprise'] })
useHead({ title: '供应管理' })
const { api } = useApi()

const { data, refresh } = useMemberData('my-supplies', () => api('/member/supplies'))
const items = computed(() => (data.value as any)?.data || [])

async function remove(id: number) {
  if (!(await appConfirm({ title: '确认删除', message: '确认删除？', tone: 'danger', confirmText: '删除' }))) return
  await api(`/member/supplies/${id}`, { method: 'DELETE' })
  await appOk('已删除。', '操作成功')
  await refresh()
}

async function refreshItem(id: number) {
  await api(`/member/supplies/${id}/refresh`, { method: 'POST' })
  await appOk('已刷新。', '操作成功')
  await refresh()
}
</script>

<template>
  <div class="panel">
    <h3>
      供应管理
      <NuxtLink class="back-list" to="/member/supplies/create">发布供应</NuxtLink>
    </h3>
    <div class="body">
      <table class="table">
        <thead>
          <tr><th>标题</th><th>状态</th><th>刷新</th><th>操作</th></tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.title }}</td>
            <td>{{ auditStatusLabel(item.status) }}</td>
            <td class="muted">{{ item.refreshed_at?.slice(0, 16) }}</td>
            <td>
              <NuxtLink :to="`/member/supplies/${item.id}`">编辑</NuxtLink>
              ·
              <a href="#" @click.prevent="refreshItem(item.id)">刷新</a>
              ·
              <a href="#" @click.prevent="remove(item.id)">删除</a>
            </td>
          </tr>
          <tr v-if="!items.length"><td colspan="4" class="muted">暂无供应</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

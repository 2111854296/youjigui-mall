<script setup lang="ts">
const { confirm: appConfirm } = useAppDialog()
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '商圈管理' })
const { api } = useApi()
const { data, refresh } = useMemberData('member-club', () => api('/member/club'))
const items = computed(() => (data.value as any)?.data || [])

async function remove(id: number) {
  if (!(await appConfirm({ title: '删除帖子', message: '确认删除该帖子？', tone: 'danger', confirmText: '删除' }))) return
  await api(`/member/club/${id}`, { method: 'DELETE' })
  await appOk('已删除。', '操作成功')
  await refresh()
}

function statusLabel(s: string) {
  return ({ pending: '待审', approved: '已发布', offline: '下架', rejected: '拒绝' } as Record<string, string>)[s] || s
}
</script>

<template>
  <div class="panel">
    <h3>
      商圈管理
      <NuxtLink class="back-list" to="/member/club/create">发布帖子</NuxtLink>
    </h3>
    <div class="body">
      <table class="table">
        <thead>
          <tr><th>标题</th><th>圈子</th><th>状态</th><th>浏览</th><th>操作</th></tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.title }}</td>
            <td>{{ item.circle || '—' }}</td>
            <td>{{ statusLabel(item.status) }}</td>
            <td>{{ item.views }}</td>
            <td>
              <NuxtLink :to="`/member/club/${item.id}`">编辑</NuxtLink>
              ·
              <NuxtLink v-if="item.status === 'approved'" :to="`/club/${item.id}`" target="_blank">查看</NuxtLink>
              <template v-if="item.status === 'approved'"> · </template>
              <a href="#" @click.prevent="remove(item.id)">删除</a>
            </td>
          </tr>
          <tr v-if="!items.length"><td colspan="5" class="muted">暂无帖子</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

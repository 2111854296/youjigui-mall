<script setup lang="ts">
const { confirm: appConfirm } = useAppDialog()
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '品牌管理' })
const { api } = useApi()
const { data, refresh } = useMemberData('member-brand', () => api('/member/brand'))
const items = computed(() => (data.value as any)?.data || [])
async function remove(id: number) {
  if (!(await appConfirm({ title: '确认删除', message: '确认删除？', tone: 'danger', confirmText: '删除' }))) return
  await api('/member/brand/' + id, { method: 'DELETE' })
  await appOk('已删除。', '操作成功')
  await refresh()
}
function statusLabel(s: string) {
  return ({ pending: '待审', approved: '已发布', published: '已发布', offline: '下架', rejected: '拒绝' } as Record<string, string>)[s] || s
}
</script>
<template>
  <div class="panel">
    <h3>品牌管理 <NuxtLink class="back-list" to="/member/brand/create">发布品牌</NuxtLink></h3>
    <div class="body">
      <table class="table">
        <thead><tr><th>名称</th><th>状态</th><th>操作</th></tr></thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.name }}</td>
            <td>{{ statusLabel(item.status) }}</td>
            <td>
              <NuxtLink :to="'/member/brand/' + item.id">编辑</NuxtLink>
              · <a href="#" @click.prevent="remove(item.id)">删除</a>
            </td>
          </tr>
          <tr v-if="!items.length"><td colspan="3" class="muted">暂无记录</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

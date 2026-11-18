<script setup lang="ts">
const { confirm: appConfirm } = useAppDialog()
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '知道管理' })
const { api } = useApi()
const { data, refresh } = useMemberData('member-know', () => api('/member/know'))
const items = computed(() => (data.value as any)?.data || [])

async function remove(id: number) {
  if (!(await appConfirm({ title: '删除提问', message: '确认删除该提问？', tone: 'danger', confirmText: '删除' }))) return
  await api(`/member/know/${id}`, { method: 'DELETE' })
  await appOk('已删除。', '操作成功')
  await refresh()
}
</script>

<template>
  <div class="panel">
    <h3>
      知道管理
      <NuxtLink class="back-list" to="/member/know/create">我要提问</NuxtLink>
    </h3>
    <div class="body">
      <table class="table">
        <thead>
          <tr><th>标题</th><th>悬赏</th><th>回答</th><th>状态</th><th>操作</th></tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.title }}</td>
            <td>{{ item.reward }}</td>
            <td>{{ item.answers_count }}</td>
            <td>{{ auditStatusLabel(item.status) }}</td>
            <td>
              <NuxtLink :to="`/member/know/${item.id}`">编辑</NuxtLink>
              ·
              <NuxtLink :to="`/know/${item.id}`" target="_blank">查看</NuxtLink>
              ·
              <a href="#" @click.prevent="remove(item.id)">删除</a>
            </td>
          </tr>
          <tr v-if="!items.length"><td colspan="5" class="muted">暂无提问</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

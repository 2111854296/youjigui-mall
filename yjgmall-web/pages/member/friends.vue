<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '我的好友' })
const { confirm: appConfirm } = useAppDialog()
const { api } = useApi()
const { user } = useAuth()
const friendId = ref('')
const q = ref('')
const msg = ref('')
const err = ref('')
const { data, refresh } = useMemberData('friends', () => api('/member/friends'))
const items = computed(() => (data.value as any)?.data || [])
const shown = computed(() => {
  const key = q.value.trim().toLowerCase()
  if (!key) return items.value
  return items.value.filter((item: any) => {
    const text = [item.id, item.name, item.username, item.company_name].join(' ').toLowerCase()
    return text.includes(key)
  })
})

async function add() {
  msg.value = ''
  err.value = ''
  try {
    await api('/member/friends', { method: 'POST', body: { friend_id: Number(friendId.value) } })
    await appOk('已添加。', '操作成功')
    friendId.value = ''
    await refresh()
  } catch (e: any) {
    await appFail(apiErrorText(e, '添加失败'), '保存失败')
  }
}

async function remove(item: any) {
  msg.value = ''
  err.value = ''
  const who = item.name || item.username || item.id
  if (!(await appConfirm({ title: '删除商友', message: `确认删除「${who}」？`, tone: 'danger', confirmText: '删除' }))) return
  try {
    await api(`/member/friends/${item.id}`, { method: 'DELETE' })
    await appOk('已删除。', '操作成功')
    await refresh()
  } catch (e: any) {
    await appFail(apiErrorText(e, '删除失败'), '保存失败')
  }
}
</script>

<template>
  <div class="panel">
    <h3>商友 / 好友</h3>
    <div class="body">
      <p class="mine-id">我的用户ID：<b>{{ user?.id || '-' }}</b>。注册后系统自动分配，加好友时把这个号发给对方。</p>
      <div v-if="msg" class="alert alert-success">{{ msg }}</div>
      <div v-if="err" class="alert alert-error">{{ err }}</div>
      <form class="add-row" @submit.prevent="add">
        <input v-model="friendId" placeholder="对方用户ID" required>
        <button class="btn" type="submit">添加好友</button>
        <input v-model="q" class="find" placeholder="搜索姓名、用户名、公司或ID">
      </form>
      <table class="table">
        <thead><tr><th>ID</th><th>姓名</th><th>用户名</th><th>公司</th><th>操作</th></tr></thead>
        <tbody>
          <tr v-for="item in shown" :key="item.id">
            <td>{{ item.id }}</td>
            <td>{{ item.name }}</td>
            <td>{{ item.username }}</td>
            <td>{{ item.company_name || '-' }}</td>
            <td><a class="del" href="#" @click.prevent="remove(item)">删除</a></td>
          </tr>
          <tr v-if="!shown.length"><td colspan="5" class="muted">{{ items.length ? '没有匹配的商友' : '暂无好友' }}</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.mine-id { margin: 0 0 12px; font-size: 13px; color: #5a6b85; }
.mine-id b { color: #123c8f; font-size: 16px; }
.add-row { display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; }
.add-row input {
  padding: 8px;
  border: 1px solid var(--line);
  border-radius: 4px;
}
.add-row .find { margin-left: auto; min-width: 220px; }
.del { color: #e10600; text-decoration: none; }
.del:hover { text-decoration: underline; }
</style>

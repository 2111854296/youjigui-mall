<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '我的询盘' })
const { api } = useApi()
const { data, refresh } = useMemberData('my-inquiries', () => api('/member/inquiries'))
const items = computed(() => (data.value as any)?.data || [])
const active = ref<number | null>(null)
const replyText = ref('')
const tip = ref('')
const loading = ref(false)

const statusLabel: Record<string, string> = {
  new: '平台审核中',
  processing: '已转达企业',
  reply_pending: '回复审核中',
  replied: '已回复',
  done: '已完成',
  closed: '已关闭',
}

async function open(item: any) {
  active.value = active.value === item.id ? null : item.id
  replyText.value = ''
  tip.value = ''
  if (active.value === item.id && item.direction === 'inbox' && !Number(item.is_read)) {
    try {
      await api(`/member/inquiries/${item.id}/read`, { method: 'POST' })
      item.is_read = 1
    } catch { /* 已查看失败不打断正文 */ }
  }
}

async function reply(item: any) {
  if (!replyText.value.trim()) return
  loading.value = true
  tip.value = ''
  try {
    const res: any = await api(`/member/inquiries/${item.id}/reply`, { method: 'POST', body: { reply: replyText.value } })
    await appOk(res?.message || '已提交，平台审核后将转达询盘方', '操作成功')
    replyText.value = ''
    await refresh()
  } catch (e: any) {
    await appFail(apiErrorText(e, '回复失败'), '操作失败')
  } finally {
    loading.value = false
  }
}

async function setStatus(item: any, status: string) {
  await api(`/member/inquiries/${item.id}/status`, { method: 'POST', body: { status } })
  await refresh()
}
</script>

<template>
  <div class="panel">
    <h3>询盘管理</h3>
    <div class="body">
      <div v-if="tip" class="alert" :class="tip.includes('失败') ? 'alert-error' : 'alert-success'">{{ tip }}</div>
      <table class="table">
        <thead>
          <tr><th>方向</th><th>标题</th><th>联系人</th><th>电话</th><th>状态</th><th>时间</th><th>操作</th></tr>
        </thead>
        <tbody>
          <template v-for="item in items" :key="item.id">
            <tr>
              <td>{{ item.direction === 'inbox' ? '收到' : '发出' }}</td>
              <td><button type="button" class="link" @click="open(item)">{{ item.title }}</button></td>
              <td>{{ item.contact_name }}</td>
              <td>{{ item.contact_phone }}</td>
              <td>{{ statusLabel[item.status] || item.status }}</td>
              <td class="muted">{{ item.created_at?.slice?.(0, 16) || item.created_at }}</td>
              <td><button type="button" class="link" @click="open(item)">{{ active === item.id ? '收起' : '处理' }}</button></td>
            </tr>
            <tr v-if="active === item.id">
              <td colspan="7" class="detail">
                <p><b>内容：</b>{{ item.content }}</p>
                <p v-if="item.reply"><b>回复：</b>{{ item.reply }} <span class="muted">{{ item.replied_at?.slice?.(0, 16) || (item.status === 'reply_pending' ? '（平台审核中）' : '') }}</span></p>
                <form v-if="item.direction === 'inbox' && !['closed', 'done'].includes(String(item.status))" class="reply" @submit.prevent="reply(item)">
                  <textarea v-model="replyText" rows="3" :placeholder="item.status === 'reply_pending' ? '可修改后重新提交（仍需平台审核）' : '输入回复内容（提交后需平台审核）'" required />
                  <div class="acts">
                    <button class="btn" type="submit" :disabled="loading">{{ item.status === 'reply_pending' ? '重新提交' : '提交回复' }}</button>
                    <button class="btn btn-outline" type="button" @click="setStatus(item, 'closed')">关闭</button>
                  </div>
                </form>
                <div v-else class="acts">
                  <button v-if="item.status !== 'closed'" class="btn btn-outline" type="button" @click="setStatus(item, 'closed')">关闭询盘</button>
                </div>
              </td>
            </tr>
          </template>
          <tr v-if="!items.length"><td colspan="7" class="muted">暂无询盘</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.link { background: none; border: 0; padding: 0; color: var(--blue, #2468f2); font: inherit; font-weight: 700; cursor: pointer; text-align: left; }
.detail { background: #fafafa; text-align: left; }
.reply { margin-top: 8px; }
.reply textarea { width: 100%; padding: 8px; border: 1px solid var(--line); font: inherit; }
.acts { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 8px; }
</style>

<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '收到的报价' })
const { api } = useApi()
const { data, refresh } = useMemberData('my-quotes', () => api('/member/quotes'))
const items = computed(() => (data.value as any)?.data || [])
const tip = ref('')

const statusLabel: Record<string, string> = {
  new: '新报价',
  accepted: '已采纳',
  rejected: '已婉拒',
  done: '已完成',
}

async function markRead(item: any) {
  if (Number(item.is_read)) return
  try {
    await api(`/member/quotes/${item.id}/read`, { method: 'POST' })
    item.is_read = 1
  } catch (e: any) {
    await appFail(apiErrorText(e, '操作失败'), '操作失败')
  }
}

async function setStatus(id: number, status: string) {
  tip.value = ''
  try {
    await api(`/member/quotes/${id}/status`, { method: 'POST', body: { status } })
    await appOk('状态已更新。', '操作成功')
    await refresh()
  } catch (e: any) {
    await appFail(apiErrorText(e, '操作失败'), '操作失败')
  }
}
</script>

<template>
  <div class="panel">
    <h3>收到的报价</h3>
    <div class="body">
      <div v-if="tip" class="alert alert-success">{{ tip }}</div>
      <table class="table">
        <thead>
          <tr><th>采购单</th><th>报价方</th><th>价格</th><th>数量</th><th>说明</th><th>状态</th><th>时间</th><th>操作</th></tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>
              <NuxtLink v-if="item.purchase" :to="`/buy/${item.purchase.id}`">{{ item.purchase.title }}</NuxtLink>
              <span v-else>-</span>
            </td>
            <td>
              {{ item.contact_name || item.user?.company_name || item.user?.name || '-' }}
              <div class="muted">{{ item.contact_phone }}</div>
            </td>
            <td>{{ item.price ? `¥${item.price}/${item.unit || ''}` : '面议' }}</td>
            <td>{{ item.quantity || '-' }}</td>
            <td class="content">{{ item.content }}</td>
            <td>{{ statusLabel[item.status] || item.status }}</td>
            <td class="muted">{{ item.created_at?.slice(0, 16) }}</td>
            <td class="acts">
              <button v-if="!Number(item.is_read)" type="button" class="link" @click="markRead(item)">查看</button>
              <template v-if="item.status === 'new'">
                <button type="button" class="link" @click="setStatus(item.id, 'accepted')">采纳</button>
                <button type="button" class="link" @click="setStatus(item.id, 'rejected')">婉拒</button>
              </template>
              <button v-else-if="item.status === 'accepted'" type="button" class="link" @click="setStatus(item.id, 'done')">完成</button>
              <span v-else class="muted">已处理</span>
            </td>
          </tr>
          <tr v-if="!items.length"><td colspan="8" class="muted">暂无报价（他人对您的采购报价会出现在这里）</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.content { max-width: 220px; font-size: 12px; color: #555; }
.acts { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.link { background: none; border: 0; padding: 0; color: var(--blue, #2468f2); font: inherit; font-weight: 700; cursor: pointer; }
</style>

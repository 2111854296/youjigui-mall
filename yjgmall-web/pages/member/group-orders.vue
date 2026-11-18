<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '团购订单' })
const { prompt: appPrompt } = useAppDialog()
const { api } = useApi()
const { data, refresh } = useMemberData('group-orders', () => api('/member/group-orders'))
const items = computed(() => (data.value as any)?.data || [])
const statusMap: Record<string, string> = {
  pending_pay: '待付款', paid: '已付款', shipped: '已发货', completed: '已完成', cancelled: '已取消',
  refund_requested: '退款申请中', refunded: '已退款',
}

async function act(id: number, status: string) {
  const body: any = { status }
  if (status === 'refund_requested') {
    const reason = await appPrompt({ title: '申请退款', message: '请填写退款原因（可选）', placeholder: '退款原因', allowEmpty: true })
    if (reason === null) return
    body.refund_reason = reason
  }
  await api(`/member/group-orders/${id}/status`, { method: 'POST', body })
  await appOk('操作成功。', '操作成功')
  await refresh()
}
</script>

<template>
  <div class="panel">
    <h3>
      团购订单
      <NuxtLink to="/member/group-seller-orders">售出团购</NuxtLink>
    </h3>
    <div class="body">
      <table class="table">
        <thead><tr><th>商品</th><th>金额</th><th>收货</th><th>状态</th><th>操作</th></tr></thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>
              <NuxtLink :to="`/group/${item.group_deal_id}`">{{ item.title }}</NuxtLink>
              <div class="muted">×{{ item.quantity }}</div>
            </td>
            <td>¥{{ item.amount }}</td>
            <td class="muted" style="font-size:12px">{{ item.receiver_name }} {{ item.receiver_phone }}<br>{{ item.receiver_address }}</td>
            <td>{{ statusMap[item.status] || item.status }}</td>
            <td>
              <template v-if="item.status === 'shipped'">
                <a href="#" @click.prevent="act(item.id, 'completed')">确认收货</a>
                · <a href="#" @click.prevent="act(item.id, 'refund_requested')">申请退款</a>
              </template>
              <template v-else-if="item.status === 'paid'">
                <a href="#" @click.prevent="act(item.id, 'cancelled')">取消</a>
                · <a href="#" @click.prevent="act(item.id, 'refund_requested')">申请退款</a>
              </template>
              <span v-else class="muted">—</span>
            </td>
          </tr>
          <tr v-if="!items.length"><td colspan="5" class="muted">暂无团购订单</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

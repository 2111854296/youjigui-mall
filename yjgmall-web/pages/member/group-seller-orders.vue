<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '售出团购' })
const { prompt: appPrompt } = useAppDialog()
const { api } = useApi()
const { data, refresh } = useMemberData('group-seller-orders', () => api('/member/group-seller-orders'))
const items = computed(() => (data.value as any)?.data || [])
const statusMap: Record<string, string> = {
  pending_pay: '待付款', paid: '已付款', shipped: '已发货', completed: '已完成', cancelled: '已取消',
  refund_requested: '退款申请中', refunded: '已退款',
}

async function ship(id: number) {
  const tracking_no = await appPrompt({ title: '发货', message: '请填写物流单号（可留空）', placeholder: '物流单号', allowEmpty: true })
  if (tracking_no === null) return
  await api(`/member/group-orders/${id}/status`, { method: 'POST', body: { status: 'shipped', tracking_no } })
  await appOk('已发货。', '操作成功')
  await refresh()
}
async function refundAct(id: number, status: string) {
  await api(`/member/group-orders/${id}/status`, { method: 'POST', body: { status, back_to: 'paid' } })
  await appOk('已处理。', '操作成功')
  await refresh()
}
</script>

<template>
  <div class="panel">
    <h3>售出团购 <NuxtLink to="/member/group-orders">我的团购订单</NuxtLink></h3>
    <div class="body">
      <table class="table">
        <thead><tr><th>商品</th><th>金额</th><th>买家收货</th><th>状态</th><th>操作</th></tr></thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.title }} ×{{ item.quantity }}</td>
            <td>¥{{ item.amount }}</td>
            <td class="muted" style="font-size:12px">
              {{ item.receiver_name }} {{ item.receiver_phone }}<br>{{ item.receiver_address }}
              <div v-if="item.refund_reason">退款原因：{{ item.refund_reason }}</div>
            </td>
            <td>{{ statusMap[item.status] || item.status }}</td>
            <td>
              <a v-if="item.status === 'paid'" href="#" @click.prevent="ship(item.id)">发货</a>
              <template v-if="item.status === 'refund_requested'">
                <a href="#" @click.prevent="refundAct(item.id, 'refunded')">同意退款</a>
                ·
                <a href="#" @click.prevent="refundAct(item.id, 'reject_refund')">拒绝</a>
              </template>
              <span v-if="!['paid','refund_requested'].includes(item.status)" class="muted">{{ item.tracking_no || '—' }}</span>
            </td>
          </tr>
          <tr v-if="!items.length"><td colspan="5" class="muted">暂无售出订单</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

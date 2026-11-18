<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '我的订单' })
const { prompt: appPrompt } = useAppDialog()
const { api } = useApi()
const { data, refresh } = useMemberData('my-orders', () => api('/member/orders'))
const items = computed(() => (data.value as any)?.data || [])
const detail = ref<any>(null)
const tip = ref('')

const statusLabel: Record<string, string> = {
  pending: '待支付',
  paid: '已支付',
  shipped: '已发货',
  completed: '已完成',
  cancelled: '已取消',
  refund_requested: '退款申请中',
  refunded: '已退款',
}

async function open(id: number) {
  tip.value = ''
  const res: any = await api(`/member/orders/${id}`)
  detail.value = res.data
}

async function act(action: string) {
  if (!detail.value) return
  tip.value = ''
  try {
    const body: any = { action }
    if (action === 'request_refund') {
      const reason = await appPrompt({ title: '申请退款', message: '请填写退款原因（可选）', placeholder: '退款原因', allowEmpty: true })
      if (reason === null) return
      body.refund_reason = reason
    }
    const res: any = await api(`/member/orders/${detail.value.id}/status`, { method: 'POST', body })
    detail.value = res.data
    await appOk('操作成功。', '操作成功')
    await refresh()
  } catch (e: any) {
    await appFail(apiErrorText(e, '操作失败'), '操作失败')
  }
}
</script>

<template>
  <div class="panel">
    <h3>我的订单</h3>
    <div class="body">
      <div v-if="tip" class="alert" :class="tip.includes('失败') ? 'alert-error' : 'alert-success'">{{ tip }}</div>
      <table class="table">
        <thead>
          <tr><th>订单号</th><th>金额</th><th>状态</th><th>时间</th><th>操作</th></tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.order_no || item.id }}</td>
            <td>¥{{ item.amount }}</td>
            <td>{{ statusLabel[item.status] || item.status }}</td>
            <td class="muted">{{ item.created_at?.slice(0, 16) }}</td>
            <td><a href="#" @click.prevent="open(item.id)">详情</a></td>
          </tr>
          <tr v-if="!items.length"><td colspan="5" class="muted">暂无订单</td></tr>
        </tbody>
      </table>

      <div v-if="detail" class="detail">
        <h4>订单详情 · {{ detail.order_no }}</h4>
        <p>状态：{{ statusLabel[detail.status] || detail.status }} · 金额 ¥{{ detail.amount }}</p>
        <p v-if="detail.refund_reason">退款原因：{{ detail.refund_reason }}</p>
        <p>收货：{{ detail.receiver_name }} / {{ detail.receiver_phone }} / {{ detail.receiver_address }}</p>
        <p v-if="detail.remark">备注：{{ detail.remark }}</p>
        <table class="table">
          <thead><tr><th>商品</th><th>单价</th><th>数量</th></tr></thead>
          <tbody>
            <tr v-for="it in detail.items || []" :key="it.id">
              <td>{{ it.title }}</td>
              <td>¥{{ it.price }}</td>
              <td>{{ it.quantity }}</td>
            </tr>
          </tbody>
        </table>
        <div class="acts">
          <button
            v-if="['paid','shipped'].includes(detail.status)"
            class="btn"
            type="button"
            @click="act('confirm')"
          >确认收货</button>
          <button
            v-if="['pending','paid'].includes(detail.status)"
            class="btn btn-outline"
            type="button"
            @click="act('cancel')"
          >取消订单</button>
          <button
            v-if="['paid','shipped'].includes(detail.status)"
            class="btn btn-outline"
            type="button"
            @click="act('request_refund')"
          >申请退款</button>
          <button class="btn btn-outline" type="button" @click="detail = null">关闭</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.detail { margin-top: 16px; padding: 12px; border: 1px solid #eee; background: #fafafa; }
.acts { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
</style>

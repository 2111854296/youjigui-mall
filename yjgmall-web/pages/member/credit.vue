<script setup lang="ts">
const { confirm: appConfirm } = useAppDialog()
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '购买积分' })
const { api } = useApi()
const points = ref(100)
const msg = ref('')
const error = ref('')
const { data, refresh } = useMemberData('finance-credit', () => api('/member/finance'))
const wallet = computed(() => (data.value as any)?.wallet || {})
const cost = computed(() => (points.value * 0.1).toFixed(2))

async function buy() {
  msg.value = ''
  error.value = ''
  if (!(await appConfirm({ title: '购买积分', message: `将创建对公付款订单 ¥${cost.value}，购买 ${points.value} 积分。\n转账并上传凭证，审核通过后到账。`, confirmText: '去付款' }))) return
  try {
    const res: any = await api('/member/points/buy', { method: 'POST', body: { points: points.value } })
    const oid = res?.data?.id
    if (oid) return navigateTo(`/member/fund-pay/${oid}`)
    await appOk(res.message || '请上传付款凭证。', '提交成功')
  } catch (e: any) {
    await appFail(apiErrorText(e, '购买失败'), '提交失败')
  }
}
</script>

<template>
  <div class="panel">
    <h3>购买积分</h3>
    <div class="body">
      <div class="stats">
        <div class="stat"><b>{{ wallet.balance ?? '0.00' }}</b>余额</div>
        <div class="stat"><b>{{ wallet.points ?? 0 }}</b>积分</div>
      </div>
      <p class="muted" style="margin:12px 0">兑换比例：1 积分 = 0.1 元</p>
      <div v-if="msg" class="alert alert-success">{{ msg }}</div>
      <div v-if="error" class="alert alert-error">{{ error }}</div>
      <form style="display:flex;gap:8px;align-items:center" @submit.prevent="buy">
        <input v-model.number="points" type="number" min="10" style="width:120px;padding:8px;border:1px solid var(--line)">
        <span>需支付 {{ cost }} 元</span>
        <button class="btn" type="submit">对公付款</button>
      </form>
    </div>
  </div>
</template>

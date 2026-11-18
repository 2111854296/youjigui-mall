<script setup lang="ts">
const { confirm: appConfirm } = useAppDialog()
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '资金管理' })
const { api } = useApi()
const amount = ref('100')
const msg = ref('')
const err = ref('')
const loading = ref(false)
const { data, refresh } = useMemberData('finance', () => api('/member/finance'))
const records = computed(() => (data.value as any)?.data || [])
const wallet = computed(() => (data.value as any)?.wallet || {})

const openOrders = ref<any[]>([])

async function loadOpenOrders() {
  try {
    const res = await api<{ data: any[] }>('/member/fund/orders')
    openOrders.value = (res.data || []).filter((o) =>
      ['pending_pay', 'pending_review'].includes(String(o.status)),
    )
  } catch {
    openOrders.value = []
  }
}

async function charge() {
  const n = Number(amount.value)
  if (!n || n < 1) {
    await appFail('请输入有效金额（至少 1 元）', '提交失败')
    return
  }
  if (!(await appConfirm({ title: '对公充值', message: `将创建对公充值订单 ¥${n}。\n请转账并上传凭证，财务审核通过后余额入账。`, confirmText: '创建订单' }))) return
  err.value = ''
  msg.value = ''
  loading.value = true
  try {
    const res = await api<{ data: { id: number }; message?: string; reused?: boolean }>(
      '/member/finance/charge',
      { method: 'POST', body: { amount: n } },
    )
    const oid = res.data?.id
    if (oid) {
      return navigateTo(`/member/fund-pay/${oid}`)
    }
    await appOk(res.message || '请按收款账户转账并上传凭证。', '提交成功')
    await refresh()
    await loadOpenOrders()
  } catch (e: any) {
    await appFail(apiErrorText(e, '下单失败'), '提交失败')
  } finally {
    loading.value = false
  }
}

onMounted(() => { void loadOpenOrders() })
</script>

<template>
  <div class="panel">
    <h3>资金 / 积分</h3>
    <div class="body">
      <div class="stats">
        <div class="stat"><b>{{ wallet.balance ?? '0.00' }}</b>余额</div>
        <div class="stat"><b>{{ wallet.deposit ?? '0.00' }}</b>保证金</div>
        <div class="stat"><b>{{ wallet.points ?? 0 }}</b>积分</div>
        <div class="stat"><b>{{ wallet.credit ?? 0 }}</b>信用</div>
      </div>
      <div v-if="msg" class="alert alert-success">{{ msg }}</div>
      <div v-if="err" class="alert alert-error">{{ err }}</div>

      <div v-if="openOrders.length" class="open-box">
        <div class="open-hd">
          <b>进行中的充值订单</b>
          <NuxtLink to="/member/fund-pay">全部订单</NuxtLink>
        </div>
        <ul>
          <li v-for="o in openOrders" :key="o.id">
            <span class="mono">{{ o.order_no }}</span>
            <span class="price">¥{{ o.amount_yuan || o.amount }}</span>
            <span class="st">{{ o.status_label || o.status }}</span>
            <NuxtLink :to="`/member/fund-pay/${o.id}`">继续</NuxtLink>
          </li>
        </ul>
      </div>

      <form class="charge-row" @submit.prevent="charge">
        <input v-model="amount" type="number" min="1" step="0.01" placeholder="充值金额">
        <button class="btn" type="submit" :disabled="loading">
          {{ loading ? '处理中…' : '对公充值' }}
        </button>
        <NuxtLink class="btn btn-outline" to="/member/fund-pay">充值订单</NuxtLink>
        <NuxtLink class="btn btn-outline" to="/member/deposit">保证金</NuxtLink>
        <NuxtLink class="btn btn-outline" to="/member/cash">提现</NuxtLink>
        <NuxtLink class="btn btn-outline" to="/member/credit">购积分</NuxtLink>
      </form>
      <p class="hint">充值、购买报告、次数包、商城、团购和积分，都是对公转账并上传凭证，财务审核通过后再生效。保证金从余额划转，提现从余额扣出。</p>

      <table class="table">
        <thead><tr><th>时间</th><th>类型</th><th>金额</th><th>余额</th><th>说明</th></tr></thead>
        <tbody>
          <tr v-for="r in records" :key="r.id">
            <td class="muted">{{ r.created_at?.slice?.(0, 16) || r.created_at }}</td>
            <td>{{ r.type }}</td>
            <td>{{ r.amount }}</td>
            <td>{{ r.balance_after }}</td>
            <td>{{ r.title }}</td>
          </tr>
          <tr v-if="!records.length"><td colspan="5" class="muted">暂无流水</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.charge-row { display: flex; gap: 8px; margin: 16px 0 8px; align-items: center; flex-wrap: wrap; }
.charge-row input { width: 120px; padding: 8px; border: 1px solid var(--line); }
.hint { margin: 0 0 14px; font-size: 12.5px; color: var(--ink-3, #64748b); line-height: 1.5; }
.open-box {
  margin: 12px 0 0; padding: 12px 14px; border-radius: 10px;
  background: #f8fbff; border: 1px solid #dbe7f8;
}
.open-hd { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
.open-hd a { font-size: 13px; font-weight: 700; color: var(--blue); text-decoration: none; }
.open-box ul { list-style: none; margin: 0; padding: 0; display: grid; gap: 6px; }
.open-box li { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; font-size: 13px; }
.mono { font-family: ui-monospace, Menlo, Consolas, monospace; }
.price { color: #c2410c; font-weight: 700; }
.st { color: #1d4fd7; font-weight: 600; }
</style>

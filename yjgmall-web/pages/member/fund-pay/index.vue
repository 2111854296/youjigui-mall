<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '对公付款订单' })

const { api } = useApi()

type PayOrder = {
  id: number
  order_no: string
  title?: string
  amount_yuan: string
  status: string
  status_label: string
  expire_at?: string
  created_at?: string
  voucher_url?: string | null
  admin_remark?: string | null
}

const items = ref<PayOrder[]>([])
const loading = ref(true)
const err = ref('')

async function load() {
  loading.value = true
  err.value = ''
  try {
    const res = await api<{ data: PayOrder[] }>('/member/fund/orders')
    items.value = res.data || []
  } catch (e: any) {
    await appFail(apiErrorText(e, '加载失败'), '加载失败')
    items.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => { void load() })

function openStatus(s: string) {
  return s === 'pending_pay' || s === 'pending_review'
}
</script>

<template>
  <div class="panel vip-pay-list">
    <div class="hd">
      <h3>对公付款订单</h3>
      <NuxtLink class="link" to="/member/finance">去充值</NuxtLink>
    </div>
    <p class="lead">充值、报告、次数包、商城、团购、积分的对公转账都在这里。上传凭证后由财务审核，通过后再生效。</p>
    <div v-if="loading" class="muted">加载中…</div>
    <div v-else-if="err" class="alert alert-error">{{ err }}</div>
    <table v-else class="table">
      <thead>
        <tr>
          <th>订单号</th>
          <th>类型</th>
          <th>金额</th>
          <th>状态</th>
          <th>时间</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in items" :key="item.id" :class="{ open: openStatus(item.status) }">
          <td class="mono">{{ item.order_no }}</td>
          <td>{{ item.title || '账户充值' }}</td>
          <td class="price">¥{{ item.amount_yuan }}</td>
          <td>
            <span class="st" :data-s="item.status">{{ item.status_label }}</span>
            <span v-if="item.status === 'rejected' && item.admin_remark" class="rej" :title="item.admin_remark">有驳回说明</span>
          </td>
          <td class="muted">{{ String(item.created_at || item.expire_at || '').replace('T', ' ').slice(0, 16) }}</td>
          <td>
            <NuxtLink :to="`/member/fund-pay/${item.id}`">
              {{ openStatus(item.status) ? '继续付款/查看' : '详情' }}
            </NuxtLink>
          </td>
        </tr>
        <tr v-if="!items.length">
          <td colspan="6" class="muted">暂无充值订单。请先到「资金管理」发起对公充值。</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.hd { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 6px; }
.hd h3 { margin: 0; }
.lead { margin: 0 0 14px; font-size: 13px; color: var(--ink-3, #64748b); }
.link { color: var(--blue, #2468f2); font-weight: 700; font-size: 13px; text-decoration: none; }
.mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 12.5px; }
.price { color: #c2410c; font-weight: 700; }
.muted { color: #94a3b8; font-size: 12.5px; }
.st {
  display: inline-block; font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 999px;
  background: #f1f5f9; color: #475569;
}
.st[data-s='pending_pay'] { background: #fff7ed; color: #c2410c; }
.st[data-s='pending_review'] { background: #eff6ff; color: #1d4fd7; }
.st[data-s='approved'] { background: #ecfdf5; color: #047857; }
.st[data-s='rejected'] { background: #fef2f2; color: #b91c1c; }
.rej { margin-left: 6px; font-size: 11px; color: #b91c1c; }
tr.open td { background: #f8fbff; }
.alert-error { padding: 10px 12px; border-radius: 8px; background: #fef2f2; color: #b91c1c; font-size: 13px; }
</style>

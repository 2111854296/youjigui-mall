<script setup lang="ts">
const { confirm: appConfirm } = useAppDialog()
definePageMeta({ layout: 'member', middleware: 'auth' })

const route = useRoute()
const { api } = useApi()
const id = computed(() => String(route.params.id || ''))

type PayOrder = {
  id: number
  order_no: string
  title?: string
  pay_type_label?: string
  amount: string | number
  amount_yuan: string
  status: string
  status_label: string
  remain_seconds: number
  expire_at: string
  bank_name?: string
  bank_account?: string
  bank_account_name?: string
  transfer_note?: string
  voucher_url?: string | null
  admin_remark?: string | null
  can_upload_voucher?: boolean
  can_cancel?: boolean
  remark?: string | null
  effect_hint?: string
  done_label?: string
  return_path?: string
  pay_type?: string
}

const order = ref<PayOrder | null>(null)
const err = ref('')
const msg = ref('')
const loading = ref(true)
const busy = ref(false)
const voucher = ref('')
const remain = ref(0)
let tickTimer: ReturnType<typeof setInterval> | null = null
let pollTimer: ReturnType<typeof setInterval> | null = null

const countdown = computed(() => {
  const s = Math.max(0, remain.value)
  const m = Math.floor(s / 60)
  const sec = s % 60
  return `${String(m).padStart(2, '0')}:${String(sec).padStart(2, '0')}`
})

const isUrgent = computed(() => remain.value > 0 && remain.value <= 300)
const isPendingPay = computed(() => order.value?.status === 'pending_pay')
const isPendingReview = computed(() => order.value?.status === 'pending_review')
const isApproved = computed(() => order.value?.status === 'approved')
const isRejected = computed(() => order.value?.status === 'rejected')
const isDead = computed(() => ['expired', 'cancelled'].includes(String(order.value?.status || '')))

useHead(() => ({
  title: order.value?.order_no ? `充值 ${order.value.order_no}` : '对公充值',
}))

async function load() {
  loading.value = true
  err.value = ''
  try {
    const res = await api<{ data: PayOrder }>(`/member/fund/orders/${id.value}`)
    order.value = res.data
    voucher.value = res.data.voucher_url || ''
    remain.value = Number(res.data.remain_seconds || 0)
    startTick()
    startPoll()
  } catch (e: any) {
    await appFail(apiErrorText(e, '订单加载失败'), '加载失败')
    order.value = null
  } finally {
    loading.value = false
  }
}

function startTick() {
  if (tickTimer) clearInterval(tickTimer)
  tickTimer = setInterval(() => {
    if (!isPendingPay.value) return
    if (remain.value <= 0) {
      void load()
      return
    }
    remain.value -= 1
    if (remain.value <= 0) void load()
  }, 1000)
}

function startPoll() {
  if (pollTimer) clearInterval(pollTimer)
  pollTimer = setInterval(() => {
    if (isPendingReview.value) void loadQuiet()
  }, 20000)
}

async function loadQuiet() {
  try {
    const res = await api<{ data: PayOrder }>(`/member/fund/orders/${id.value}`)
    order.value = res.data
    voucher.value = res.data.voucher_url || voucher.value
    remain.value = Number(res.data.remain_seconds || 0)
    if (!isPendingReview.value && pollTimer) {
      clearInterval(pollTimer)
      pollTimer = null
    }
  } catch {
    // ignore
  }
}

async function copyText(text: string, label: string) {
  const t = String(text || '').trim()
  if (!t) return
  try {
    await navigator.clipboard.writeText(t)
    msg.value = `已复制${label}`
    setTimeout(() => { if (msg.value.startsWith('已复制')) msg.value = '' }, 1600)
  } catch {
    await appFail('复制失败，请手动选择复制', '复制失败')
  }
}

async function submitVoucher() {
  if (!order.value) return
  if (!voucher.value) {
    await appFail('请先上传付款凭证截图', '提交失败')
    return
  }
  busy.value = true
  err.value = ''
  msg.value = ''
  try {
    const res = await api<{ data: PayOrder; message?: string }>(`/member/fund/orders/${order.value.id}/voucher`, {
      method: 'POST',
      body: { voucher_url: voucher.value },
    })
    order.value = res.data
    remain.value = Number(res.data.remain_seconds || 0)
    await appOk(res.message || '凭证已提交，等待财务审核入账。', '提交成功')
  } catch (e: any) {
    await appFail(apiErrorText(e, '提交失败'), '提交失败')
  } finally {
    busy.value = false
  }
}

async function cancelOrder() {
  if (!order.value) return
  if (!(await appConfirm({ title: '取消订单', message: '确认取消该充值订单？', tone: 'danger', confirmText: '确认取消' }))) return
  busy.value = true
  try {
    const res = await api<{ data: PayOrder }>(`/member/fund/orders/${order.value.id}/cancel`, { method: 'POST' })
    order.value = res.data
    await appOk('订单已取消。', '操作成功')
  } catch (e: any) {
    await appFail(apiErrorText(e, '取消失败'), '操作失败')
  } finally {
    busy.value = false
  }
}

onMounted(() => { void load() })
onBeforeUnmount(() => {
  if (tickTimer) clearInterval(tickTimer)
  if (pollTimer) clearInterval(pollTimer)
})
watch(id, () => { void load() })
</script>

<template>
  <div class="vip-pay">
    <div class="pay-crumb">
      <NuxtLink to="/member/finance">资金管理</NuxtLink>
      <span>/</span>
      <NuxtLink to="/member/fund-pay">充值订单</NuxtLink>
      <span>/</span>
      <span>订单详情</span>
    </div>

    <div v-if="loading" class="pay-skel">订单加载中…</div>
    <div v-else-if="err && !order" class="pay-err">{{ err }}</div>

    <template v-else-if="order">
      <header class="pay-hero" :class="{ urgent: isUrgent && isPendingPay, dead: isDead }">
        <div class="hero-l">
          <div class="status-pill">{{ order.status_label }}</div>
          <h1>应付金额</h1>
          <div class="amount"><small>¥</small>{{ order.amount_yuan }}</div>
          <p class="grade">{{ order.pay_type_label || order.title || '账户充值' }}</p>
        </div>
        <div v-if="isPendingPay" class="hero-r">
          <div class="cd-label">支付剩余时间</div>
          <div class="cd" :class="{ blink: isUrgent }">{{ countdown }}</div>
          <p class="cd-hint">超时未上传凭证订单将自动关闭</p>
        </div>
        <div v-else-if="isPendingReview" class="hero-r review">
          <div class="cd-label">审核中</div>
          <p class="cd-hint">{{ order.effect_hint || '财务核对到账后生效' }}</p>
        </div>
        <div v-else-if="isApproved" class="hero-r ok">
          <div class="cd-label">{{ order.done_label || '已通过' }}</div>
          <NuxtLink class="link-btn" :to="order.return_path || '/member/finance'">返回查看</NuxtLink>
        </div>
      </header>

      <div v-if="msg" class="alert ok">{{ msg }}</div>
      <div v-if="err" class="alert bad">{{ err }}</div>
      <div v-if="isRejected && order.admin_remark" class="alert bad">驳回原因：{{ order.admin_remark }}</div>

      <section class="card">
        <div class="card-hd">
          <b>订单信息</b>
          <button type="button" class="mini" @click="copyText(order.order_no, '订单号')">复制订单号</button>
        </div>
        <dl class="kv">
          <div><dt>订单号</dt><dd class="mono">{{ order.order_no }}</dd></div>
          <div><dt>类型</dt><dd>{{ order.pay_type_label || order.title || '账户充值' }}</dd></div>
          <div><dt>应付金额</dt><dd class="price">¥{{ order.amount_yuan }}</dd></div>
          <div><dt>支付截止</dt><dd>{{ String(order.expire_at || '').replace('T', ' ').slice(0, 19) }}</dd></div>
        </dl>
      </section>

      <section v-if="!isDead && !isApproved" class="card bank">
        <div class="card-hd"><b>收款账户（对公转账）</b></div>
        <p class="tip">请务必按下列信息转账，金额须与应付金额一致；备注请填写订单号以便核对。</p>
        <p v-if="order.pay_hint" class="pay-hint">{{ order.pay_hint }}</p>
        <div class="bank-rows">
          <div class="bank-row">
            <span class="k">户名</span>
            <span class="v">{{ order.bank_account_name || '—' }}</span>
            <button type="button" class="mini" @click="copyText(order.bank_account_name || '', '户名')">复制</button>
          </div>
          <div class="bank-row">
            <span class="k">开户行</span>
            <span class="v">{{ order.bank_name || '—' }}</span>
            <button type="button" class="mini" @click="copyText(order.bank_name || '', '开户行')">复制</button>
          </div>
          <div class="bank-row highlight">
            <span class="k">账号</span>
            <span class="v mono">{{ order.bank_account || '—' }}</span>
            <button type="button" class="mini primary" @click="copyText(order.bank_account || '', '账号')">复制账号</button>
          </div>
          <div class="bank-row">
            <span class="k">转账备注</span>
            <span class="v mono">{{ order.transfer_note || order.order_no }}</span>
            <button type="button" class="mini" @click="copyText(order.transfer_note || order.order_no, '转账备注')">复制</button>
          </div>
          <div class="bank-row">
            <span class="k">转账金额</span>
            <span class="v price">¥{{ order.amount_yuan }}</span>
            <button type="button" class="mini" @click="copyText(order.amount_yuan, '金额')">复制</button>
          </div>
        </div>
      </section>

      <section v-if="isPendingReview" class="card review-card">
        <div class="card-hd"><b>审核进度</b></div>
        <p class="tip">凭证已提交，财务核对到账后自动入账。本页会定时刷新；亦可稍后在「充值订单」列表查看。</p>
        <a v-if="order.voucher_url" class="voucher-link" :href="order.voucher_url" target="_blank" rel="noopener">查看已提交凭证</a>
        <div class="actions">
          <button type="button" class="btn ghost" :disabled="busy" @click="load">刷新状态</button>
          <button v-if="order.can_cancel" type="button" class="btn cancel" :disabled="busy" @click="cancelOrder">取消订单</button>
        </div>
      </section>

      <section v-if="order.can_upload_voucher" class="card">
        <div class="card-hd"><b>{{ isPendingReview ? '更换付款凭证' : '上传付款凭证' }}</b></div>
        <p class="tip">
          <template v-if="isPendingReview">如截图不清晰或金额有误，可重新上传替换后继续等待审核。</template>
          <template v-else-if="isRejected">请按驳回原因修正后重新上传银行回单。</template>
          <template v-else>请上传银行回单 / 网银转账成功截图（含金额、对方账户、时间）。支持 jpg / png，最大 5MB。</template>
        </p>
        <ImageUpload v-model="voucher" label="付款凭证" hint="建议上传清晰完整的转账回单" />
        <div class="actions">
          <button type="button" class="btn primary" :disabled="busy" @click="submitVoucher">
            {{ busy ? '提交中…' : (isRejected ? '重新提交凭证' : isPendingReview ? '更换凭证并提交' : '确认已转账并提交') }}
          </button>
          <button v-if="order.can_cancel && !isPendingReview" type="button" class="btn cancel" :disabled="busy" @click="cancelOrder">取消订单</button>
        </div>
      </section>

      <section v-else-if="order.voucher_url" class="card">
        <div class="card-hd"><b>已提交凭证</b></div>
        <a class="voucher-link" :href="order.voucher_url" target="_blank" rel="noopener">查看凭证图片</a>
      </section>

      <section v-if="isDead" class="card">
        <p class="tip">该订单已结束。如需继续，请重新发起付款。</p>
        <NuxtLink class="btn primary" :to="order.pay_type === 'charge' ? '/member/finance' : '/member/fund-pay'">返回</NuxtLink>
      </section>

      <ol class="steps">
        <li :class="{ on: true }"><i>1</i>创建对公付款订单</li>
        <li :class="{ on: true }"><i>2</i>在时限内对公转账（备注订单号）</li>
        <li :class="{ on: isPendingReview || isApproved || !!order.voucher_url }"><i>3</i>上传付款凭证</li>
        <li :class="{ on: isApproved }"><i>4</i>{{ order.effect_hint || '财务审核通过后生效' }}</li>
      </ol>
    </template>
  </div>
</template>

<style scoped>
.vip-pay { max-width: 720px; margin: 0 auto; padding: 8px 4px 40px; }
.pay-crumb { display: flex; gap: 8px; align-items: center; font-size: 13px; color: var(--ink-3); margin-bottom: 14px; }
.pay-crumb a { color: var(--blue); text-decoration: none; font-weight: 600; }
.pay-skel, .pay-err { padding: 40px 16px; text-align: center; color: var(--ink-3); }
.pay-err { color: #c9372b; }

.pay-hero {
  display: flex; justify-content: space-between; gap: 20px; flex-wrap: wrap;
  background: linear-gradient(135deg, #0f766e, #14b8a6);
  color: #fff; border-radius: 16px; padding: 22px 24px; margin-bottom: 14px;
  box-shadow: 0 12px 28px rgba(15, 118, 110, 0.28);
}
.pay-hero.urgent { background: linear-gradient(135deg, #c2410c, #ea580c); }
.pay-hero.dead { background: linear-gradient(135deg, #64748b, #94a3b8); box-shadow: none; }
.status-pill {
  display: inline-flex; font-size: 11px; font-weight: 700; padding: 3px 10px;
  border-radius: 999px; background: rgba(255,255,255,.2); margin-bottom: 8px;
}
.pay-hero h1 { margin: 0; font-size: 13px; font-weight: 600; opacity: .9; }
.amount { font-size: 40px; font-weight: 800; letter-spacing: .02em; line-height: 1.15; margin-top: 4px; }
.amount small { font-size: 20px; margin-right: 2px; font-weight: 700; }
.grade { margin: 6px 0 0; font-size: 13px; opacity: .88; }
.hero-r { text-align: right; min-width: 140px; }
.cd-label { font-size: 12px; opacity: .85; }
.cd { font-size: 36px; font-weight: 800; font-variant-numeric: tabular-nums; letter-spacing: .06em; margin-top: 4px; }
.cd.blink { animation: pulse 1s ease-in-out infinite; }
.cd-hint { margin: 6px 0 0; font-size: 11.5px; opacity: .8; max-width: 200px; margin-left: auto; line-height: 1.4; }
.link-btn {
  display: inline-flex; margin-top: 10px; padding: 7px 14px; border-radius: 999px;
  background: #fff; color: #0f766e; font-weight: 700; font-size: 13px; text-decoration: none;
}
@keyframes pulse { 50% { opacity: .55; } }

.alert { padding: 10px 14px; border-radius: 10px; font-size: 13px; margin-bottom: 12px; }
.alert.ok { background: #ecfdf5; color: #047857; }
.alert.bad { background: #fef2f2; color: #b91c1c; }

.card {
  background: #fff; border: 1px solid var(--line, #e9eef6); border-radius: 14px;
  padding: 16px 18px; margin-bottom: 12px; box-shadow: 0 1px 2px rgba(15,39,68,.04);
}
.card-hd { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
.card-hd b { font-size: 15px; color: var(--ink); }
.tip { margin: 0 0 12px; font-size: 12.5px; color: var(--ink-3); line-height: 1.5; }
.pay-hint {
  margin: 0 0 12px; padding: 10px 12px; border-radius: 10px;
  background: #fffbeb; border: 1px solid #fde68a; color: #92400e;
  font-size: 12.5px; line-height: 1.5;
}
.review-card { border-color: #99f6e4; background: #f0fdfa; }

.kv { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 16px; margin: 0; }
.kv > div { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.kv dt { font-size: 11.5px; color: var(--ink-3); }
.kv dd { margin: 0; font-size: 13.5px; font-weight: 600; color: var(--ink); word-break: break-all; }
.mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; letter-spacing: .02em; }
.price { color: #c2410c; font-weight: 800; }

.bank-rows { display: flex; flex-direction: column; gap: 8px; }
.bank-row {
  display: grid; grid-template-columns: 72px 1fr auto; gap: 8px; align-items: center;
  padding: 10px 12px; border-radius: 10px; background: #f6f9fe; border: 1px solid #e9eef6;
}
.bank-row.highlight { background: #ecfdf5; border-color: #a7f3d0; }
.bank-row .k { font-size: 12px; color: var(--ink-3); font-weight: 600; }
.bank-row .v { font-size: 14px; font-weight: 700; color: var(--ink); min-width: 0; word-break: break-all; }

.mini {
  border: 1px solid var(--line); background: #fff; color: var(--blue); font: inherit;
  font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 999px; cursor: pointer;
}
.mini.primary { background: #0f766e; color: #fff; border-color: #0f766e; }
.mini:hover { filter: brightness(1.03); }

.actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
.btn {
  display: inline-flex; align-items: center; justify-content: center; height: 40px; padding: 0 18px;
  border-radius: 10px; font: inherit; font-size: 14px; font-weight: 700; cursor: pointer;
  text-decoration: none; border: 0;
}
.btn.primary { background: linear-gradient(135deg, #0f766e, #14b8a6); color: #fff; box-shadow: 0 6px 14px rgba(15,118,110,.25); }
.btn.ghost { background: #f1f5f9; color: #5a6b85; }
/* !important 压过 tokens.css .btn{color:#fff!important}，避免白底白字 */
.btn.cancel {
  background: #fff7ed !important;
  color: #c2410c !important;
  border: 1.5px solid #ea580c;
  box-shadow: none;
}
.btn.cancel:hover {
  background: #ffedd5 !important;
  color: #9a3412 !important;
}
.btn:disabled { opacity: .65; cursor: wait; }

.voucher-link { color: var(--blue); font-weight: 700; font-size: 13.5px; }

.steps { list-style: none; margin: 18px 0 0; padding: 0; display: grid; gap: 8px; }
.steps li {
  display: flex; align-items: center; gap: 10px; font-size: 13px; color: var(--ink-3); font-weight: 500;
}
.steps li.on { color: var(--ink); font-weight: 600; }
.steps i {
  width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center;
  font-style: normal; font-size: 11px; font-weight: 800; background: #e8eef6; color: #64748b;
}
.steps li.on i { background: #0f766e; color: #fff; }

@media (max-width: 640px) {
  .kv { grid-template-columns: 1fr; }
  .amount { font-size: 32px; }
  .cd { font-size: 28px; }
  .hero-r { text-align: left; }
  .cd-hint { margin-left: 0; }
}
</style>

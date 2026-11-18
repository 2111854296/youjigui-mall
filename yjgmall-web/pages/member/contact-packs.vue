<script setup lang="ts">
const { confirm: appConfirm } = useAppDialog()
definePageMeta({ layout: 'member' })
useHead({ title: '联系方式次数包' })
const { api } = useApi()
const packs = ref<any[]>([])
const ent = ref<any>(null)
const msg = ref('')
const err = ref('')
const buying = ref<number | null>(null)

async function load() {
  const [p, m] = await Promise.all([api('/vip/contact-packs'), api('/vip/me')])
  packs.value = ((p as any)?.data || p) as any[]
  ent.value = (m as any)?.data || m
}

async function buy(id: number) {
  const pack = packs.value.find((p) => p.id === id)
  if (!pack) return
  if (!(await appConfirm({ title: '购买次数包', message: `将创建对公付款订单「${pack.name}」¥${pack.pay_price}。\n转账并上传凭证，审核通过后次数到账。`, confirmText: '去付款' }))) return
  err.value = ''
  msg.value = ''
  buying.value = id
  try {
    const res = await api<{ data?: { id?: number }; message?: string }>('/vip/contact-packs/buy', { method: 'POST', body: { pack_id: id } })
    const oid = res?.data?.id
    if (oid) return navigateTo(`/member/fund-pay/${oid}`)
    await appOk(res?.message || '请上传付款凭证。', '提交成功')
  } catch (e: any) {
    await appFail(apiErrorText(e, '购买失败'), '提交失败')
  } finally {
    buying.value = null
  }
}

onMounted(() => load().catch(async (e) => { await appFail(apiErrorText(e, '加载失败'), '加载失败') }))
</script>

<template>
  <div class="panel">
    <h3>联系方式次数包</h3>
    <p class="muted">
      全价 ¥100/2次、¥500/10次。会员折扣：白银 9 折 / 黄金 8 折 / 星钻 7 折。对公转账并上传凭证，审核通过后次数到账。
    </p>
    <p v-if="ent" class="meta">
      当前剩余可查看次数：{{ ent.contact_remain_total ?? 0 }}
      （月额度剩余 {{ ent.contact_remain_month ?? 0 }} + 赠送/次数包 {{ ent.contact_bonus_left ?? 0 }}）
    </p>
    <div v-if="msg" class="alert alert-success">{{ msg }}</div>
    <div v-if="err" class="alert alert-error">{{ err }}</div>

    <div class="grid">
      <div v-for="p in packs" :key="p.id" class="card">
        <b>{{ p.name }}</b>
        <p>{{ p.times }} 次联系方式查看</p>
        <p class="price">
          <span v-if="p.discount < 1" class="old">¥{{ p.list_price }}</span>
          ¥{{ p.pay_price }}
          <small v-if="p.discount < 1">（{{ Math.round(p.discount * 10) }}折）</small>
        </p>
        <button class="btn" type="button" :disabled="buying === p.id" @click="buy(p.id)">
          {{ buying === p.id ? '提交中…' : '对公付款' }}
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.muted { font-size: 13px; color: var(--ink-3); }
.meta { font-size: 13.5px; }
.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; margin-top: 14px; }
.card { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 16px; }
.card b { font-size: 16px; }
.price { font-size: 22px; font-weight: 800; color: var(--up); margin: 10px 0; }
.price .old { font-size: 13px; color: var(--ink-3); text-decoration: line-through; margin-right: 6px; font-weight: 500; }
.price small { font-size: 12px; color: var(--blue); font-weight: 600; }
</style>

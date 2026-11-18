<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '保证金' })
const { api } = useApi()
const amount = ref('100')
const msg = ref('')
const error = ref('')
const { data, refresh } = useMemberData('finance-dep', () => api('/member/finance'))
const wallet = computed(() => (data.value as any)?.wallet || {})

async function submit() {
  msg.value = ''
  error.value = ''
  try {
    const res: any = await api('/member/deposit', { method: 'POST', body: { amount: Number(amount.value) } })
    await appOk(res.message || '已转入保证金。', '操作成功')
    await refresh()
  } catch (e: any) {
    await appFail(apiErrorText(e, '操作失败'), '保存失败')
  }
}
</script>

<template>
  <div class="panel">
    <h3>保证金</h3>
    <div class="body">
      <div class="stats">
        <div class="stat"><b>{{ wallet.balance ?? '0.00' }}</b>可用余额</div>
        <div class="stat"><b>{{ wallet.deposit ?? '0.00' }}</b>保证金</div>
      </div>
      <div v-if="msg" class="alert alert-success">{{ msg }}</div>
      <div v-if="error" class="alert alert-error">{{ error }}</div>
      <form style="display:flex;gap:8px;margin-top:16px;align-items:center" @submit.prevent="submit">
        <input v-model="amount" type="number" min="1" style="width:120px;padding:8px;border:1px solid var(--line)">
        <button class="btn" type="submit">从余额转入保证金</button>
      </form>
    </div>
  </div>
</template>

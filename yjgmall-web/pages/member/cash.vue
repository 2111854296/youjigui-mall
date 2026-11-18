<script setup lang="ts">
const { confirm: appConfirm } = useAppDialog()
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '申请提现' })
import { isBankAccount, isRealName } from '~/utils/formCheck'
const { api } = useApi()
const msg = ref('')
const error = ref('')
const form = reactive({
  amount: '100',
  bank_name: '',
  bank_account: '',
  account_name: '',
  pay_password: '',
})
const { data, refresh } = useMemberData('withdraws', () => api('/member/withdraws'))
const list = computed(() => (data.value as any)?.data || [])
const wallet = computed(() => (data.value as any)?.wallet || {})

async function submit() {
  msg.value = ''
  error.value = ''
  if (form.account_name.trim() && !isRealName(form.account_name)) {
    await appFail('请用真实姓名', '提交失败')
    return
  }
  if (form.bank_account.trim() && !isBankAccount(form.bank_account)) {
    await appFail('请填写正确的银行账号', '提交失败')
    return
  }
  if (!(await appConfirm({ title: '申请提现', message: `确认申请提现 ¥${form.amount} 到 ${form.bank_name || '银行账户'}？\n提交后金额将冻结，待财务打款。`, confirmText: '提交申请' }))) return
  try {
    await api('/member/withdraws', {
      method: 'POST',
      body: {
        amount: Number(form.amount),
        bank_name: form.bank_name,
        bank_account: form.bank_account,
        account_name: form.account_name,
        pay_password: form.pay_password || null,
      },
    })
    await appOk('提现申请已提交。', '提交成功')
    await refresh()
  } catch (e: any) {
    await appFail(apiErrorText(e, '提交失败'), '提交失败')
  }
}
</script>

<template>
  <div class="panel">
    <h3>申请提现</h3>
    <div class="body">
      <p class="muted">可用余额：{{ wallet.balance ?? '0.00' }}</p>
      <div v-if="msg" class="alert alert-success">{{ msg }}</div>
      <div v-if="error" class="alert alert-error">{{ error }}</div>
      <form style="max-width:480px;margin:16px 0" @submit.prevent="submit">
        <div class="form-row"><label>金额</label><input v-model="form.amount" type="number" min="1" required></div>
        <div class="form-row"><label>开户行</label><input v-model="form.bank_name" required></div>
        <div class="form-row"><label>账号</label><input v-model="form.bank_account" required></div>
        <div class="form-row"><label>户名</label><input v-model="form.account_name" required></div>
        <div class="form-row"><label>支付密码</label><input v-model="form.pay_password" type="password" placeholder="已设置则必填"></div>
        <button class="btn" type="submit">提交申请</button>
      </form>
      <table class="table">
        <thead><tr><th>时间</th><th>金额</th><th>银行</th><th>状态</th></tr></thead>
        <tbody>
          <tr v-for="r in list" :key="r.id">
            <td class="muted">{{ r.created_at?.slice?.(0, 16) }}</td>
            <td>{{ r.amount }}</td>
            <td>{{ r.bank_name }} / {{ r.bank_account }}</td>
            <td>{{ r.status }}</td>
          </tr>
          <tr v-if="!list.length"><td colspan="4" class="muted">暂无记录</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

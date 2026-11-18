<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '修改密码' })
const { api } = useApi()
const saving = ref(false)
const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
  current_pay_password: '',
  pay_password: '',
  pay_password_confirmation: '',
})

async function onSubmit() {
  if (!form.password && !form.pay_password) {
    await appFail('请填写要修改的密码', '修改失败')
    return
  }
  saving.value = true
  try {
    const body: Record<string, string> = {}
    if (form.password) {
      body.current_password = form.current_password
      body.password = form.password
      body.password_confirmation = form.password_confirmation
    }
    if (form.pay_password) {
      body.current_pay_password = form.current_pay_password
      body.pay_password = form.pay_password
      body.pay_password_confirmation = form.pay_password_confirmation
    }
    const res: any = await api('/member/passwords', { method: 'PUT', body })
    await appOk(res?.message || '密码已更新', '修改成功')
    form.current_password = ''
    form.password = ''
    form.password_confirmation = ''
    form.current_pay_password = ''
    form.pay_password = ''
    form.pay_password_confirmation = ''
  } catch (e: any) {
    await appFail(apiErrorText(e, '修改失败'), '修改失败')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="panel">
    <h3>修改密码</h3>
    <div class="body">
      <form class="mf-form" @submit.prevent="onSubmit">
        <section class="mf-sec">
          <div class="mf-sec-hd mf-sec-hd-row">
            <span>登录密码</span>
            <a class="mf-forgot" href="/forgot-password">忘记登录密码</a>
          </div>
          <div class="mf-sec-bd">
            <div class="mf-grid-2">
              <div class="form-row"><label>现有密码</label><input v-model="form.current_password" type="password" autocomplete="current-password"></div>
              <div class="form-row"><label>新密码</label><input v-model="form.password" type="password" autocomplete="new-password"></div>
              <div class="form-row"><label>确认新密码</label><input v-model="form.password_confirmation" type="password" autocomplete="new-password"></div>
            </div>
          </div>
        </section>
        <section class="mf-sec">
          <div class="mf-sec-hd mf-sec-hd-row">
            <span>支付密码</span>
            <a class="mf-forgot" href="/forgot-password?target=pay">忘记支付密码</a>
          </div>
          <div class="mf-sec-bd">
            <p class="mf-empty" style="padding-top:0;margin-top:0">提现等资金操作使用；首次设置可不填现有支付密码</p>
            <div class="mf-grid-2">
              <div class="form-row"><label>现有支付密码</label><input v-model="form.current_pay_password" type="password"></div>
              <div class="form-row"><label>新支付密码</label><input v-model="form.pay_password" type="password"></div>
              <div class="form-row"><label>确认支付密码</label><input v-model="form.pay_password_confirmation" type="password"></div>
            </div>
          </div>
        </section>
        <div class="mf-actions">
          <button class="btn" type="submit" :disabled="saving">{{ saving ? '提交中…' : '确认修改' }}</button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.mf-sec-hd-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.mf-forgot {
  color: #2468f2;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
}
</style>

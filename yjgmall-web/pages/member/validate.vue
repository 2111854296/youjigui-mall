<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '会员认证' })
import { isBankAccount, isIdCard, isMobile, isRealName } from '~/utils/formCheck'

const { api } = useApi()
const { fetchMe } = useAuth()
const msg = ref('')
const error = ref('')
const { data, refresh } = useMemberData('validations', () => api('/member/validations'))
const info = computed(() => (data.value as any)?.data || {})

const mobile = ref('')
const smsCode = ref('')
const sending = ref(false)
const truename = ref('')
const idCard = ref('')
const bank = reactive({ bank_name: '', bank_account: '', truename: '' })

function stateText(verified: boolean, status?: string) {
  if (verified) return '已认证'
  if (status === 'pending') return '审核中'
  if (status === 'rejected') return '未通过'
  return '未认证'
}

async function sendCode() {
  msg.value = ''
  error.value = ''
  if (!isMobile(mobile.value)) {
    await appFail('请填写11位手机号', '无法发送')
    return
  }
  sending.value = true
  try {
    const res = await api<{ message?: string }>('/member/validations/sms', { method: 'POST', body: { mobile: mobile.value } })
    await appOk(res?.message || '请输入短信验证码。', '发送成功')
  } catch (e: any) {
    await appFail(apiErrorText(e, '获取失败'), '发送失败')
  } finally {
    sending.value = false
  }
}

watch(info, (v) => {
  mobile.value = v.mobile || ''
  truename.value = v.truename || ''
  idCard.value = v.id_card || ''
  bank.bank_name = v.bank_name || ''
  bank.bank_account = v.bank_account || ''
  bank.truename = v.truename || ''
}, { immediate: true })

async function submit(type: string, body: Record<string, string>) {
  msg.value = ''
  error.value = ''
  if (type === 'mobile' && !isMobile(body.mobile || '')) {
    await appFail('请填写11位手机号', '提交失败')
    return
  }
  if (type === 'truename') {
    if (!isRealName(body.truename || '')) {
      await appFail('请用真实姓名', '提交失败')
      return
    }
    if (!isIdCard(body.id_card || '')) {
      await appFail('请填写正确的18位身份证号码', '提交失败')
      return
    }
  }
  if (type === 'bank') {
    if (!isBankAccount(body.bank_account || '')) {
      await appFail('请填写正确的银行账号', '提交失败')
      return
    }
    if ((body.truename || '').trim() && !isRealName(body.truename)) {
      await appFail('请用真实姓名', '提交失败')
      return
    }
  }
  try {
    await api('/member/validations', { method: 'POST', body: { type, ...body } })
    await refresh()
    await fetchMe(true)
    await appOk(type === 'mobile' ? '短信验证成功，已提交审核。' : '已提交，等待后台审核。', '提交成功')
  } catch (e: any) {
    await appFail(apiErrorText(e, '提交失败'), '提交失败')
  }
}
</script>

<template>
  <div class="panel">
    <h3>会员认证</h3>
    <div class="body">
      <div v-if="msg" class="alert alert-success">{{ msg }}</div>
      <div v-if="error" class="alert alert-error">{{ error }}</div>
      <div class="stats" style="margin-bottom:16px">
        <div class="stat"><b>{{ stateText(!!info.mobile_verified, info.mobile_status) }}</b>手机</div>
        <div class="stat"><b>{{ stateText(!!info.truename_verified, info.truename_status) }}</b>实名</div>
        <div class="stat"><b>{{ stateText(!!info.bank_verified, info.bank_status) }}</b>银行</div>
      </div>

      <form class="mf-form" @submit.prevent="submit('mobile', { mobile, sms_code: smsCode })">
        <section class="mf-sec">
          <div class="mf-sec-hd">手机认证</div>
          <div class="mf-sec-bd">
            <p v-if="info.mobile_remark && info.mobile_status === 'rejected'" class="alert alert-error">{{ info.mobile_remark }}</p>
            <div class="mf-grid-2">
              <div class="form-row"><label>手机号</label><input v-model="mobile" required maxlength="11" placeholder="11位手机号"></div>
              <div class="form-row">
                <label>验证码</label>
                <div class="code-row">
                  <input v-model="smsCode" required placeholder="短信验证码">
                  <button class="btn btn-outline" type="button" :disabled="sending" @click="sendCode">{{ sending ? '发送中…' : '获取验证码' }}</button>
                </div>
              </div>
            </div>
            <div class="mf-actions">
              <button class="btn" type="submit">提交手机认证</button>
            </div>
          </div>
        </section>
      </form>

      <form class="mf-form" @submit.prevent="submit('truename', { truename, id_card: idCard })">
        <section class="mf-sec">
          <div class="mf-sec-hd">实名认证</div>
          <div class="mf-sec-bd">
            <p v-if="info.truename_remark && info.truename_status === 'rejected'" class="alert alert-error">{{ info.truename_remark }}</p>
            <div class="mf-grid-2">
              <div class="form-row"><label>真实姓名</label><input v-model="truename" required placeholder="请填写真实姓名"></div>
              <div class="form-row"><label>身份证号码</label><input v-model="idCard" required maxlength="18" placeholder="18位身份证号码"></div>
            </div>
            <div class="mf-actions">
              <button class="btn" type="submit">提交实名认证</button>
            </div>
          </div>
        </section>
      </form>

      <form class="mf-form" @submit.prevent="submit('bank', { ...bank })">
        <section class="mf-sec">
          <div class="mf-sec-hd">银行认证</div>
          <div class="mf-sec-bd">
            <p v-if="info.bank_remark && info.bank_status === 'rejected'" class="alert alert-error">{{ info.bank_remark }}</p>
            <div class="mf-grid-2">
              <div class="form-row"><label>开户行</label><input v-model="bank.bank_name" required placeholder="如：中国银行某某支行"></div>
              <div class="form-row"><label>账号</label><input v-model="bank.bank_account" required placeholder="银行卡号"></div>
              <div class="form-row"><label>户名</label><input v-model="bank.truename" placeholder="与银行卡一致的真实姓名"></div>
            </div>
            <div class="mf-actions">
              <button class="btn" type="submit">提交银行认证</button>
            </div>
          </div>
        </section>
      </form>
    </div>
  </div>
</template>

<style scoped>
.code-row { display: flex; gap: 8px; align-items: center; }
.code-row input { flex: 1; }
.code-row .btn { white-space: nowrap; }
</style>

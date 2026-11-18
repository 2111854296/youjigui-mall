<script setup lang="ts">
definePageMeta({ layout: 'home' })

const route = useRoute()
const config = useRuntimeConfig()
const isPay = computed(() => String(route.query.target || '') === 'pay')
const label = computed(() => (isPay.value ? '支付密码' : '登录密码'))
useHead({ title: computed(() => `忘记${label.value}`) })

const step = ref<1 | 2>(1)
const form = reactive({
  account: '',
  code: '',
  password: '',
  password_confirmation: '',
})
const demoCode = ref('')
const loading = ref(false)
const bound = ref(false)
const phone = ref('')
const logged = ref(false)
const maskedPhone = computed(() => (phone.value ? `${phone.value.slice(0, 3)}****${phone.value.slice(7)}` : ''))

function readToken() {
  if (!import.meta.client) return ''
  const matched = document.cookie.match(/(?:^|; )yjgmall_token=([^;]*)/)
  if (!matched) return ''
  let value = decodeURIComponent(matched[1])
  if (value.startsWith('"')) {
    try { value = JSON.parse(value) } catch { /* keep raw */ }
  }
  return value
}

onMounted(async () => {
  const token = readToken()
  logged.value = Boolean(token)
  if (!token) return
  try {
    const res: any = await $fetch(`${config.public.apiBase}/auth/me`, {
      headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' },
    })
    const mobile = String(res?.data?.mobile || '').trim()
    bound.value = true
    if (/^1\d{10}$/.test(mobile)) {
      phone.value = mobile
      form.account = mobile
    }
  } catch {
    bound.value = false
  }
})

async function sendCode() {
  demoCode.value = ''
  if (!form.account.trim()) {
    await appFail('请填写用户名 / 手机号 / 邮箱', '发送失败')
    return
  }
  loading.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/auth/forgot-password`, {
      method: 'POST',
      body: { account: form.account.trim(), target: isPay.value ? 'pay' : 'login' },
    })
    await appOk(res?.message || '验证码已发送。', '发送成功')
    if (res?.data?.code) {
      demoCode.value = String(res.data.code)
      form.code = String(res.data.code)
    }
    step.value = 2
  } catch (e: any) {
    await appFail(apiErrorText(e, '发送失败'), '发送失败')
  } finally {
    loading.value = false
  }
}

async function resetPwd() {
  if (form.password !== form.password_confirmation) {
    await appFail('两次密码不一致', '保存失败')
    return
  }
  loading.value = true
  try {
    const res: any = await $fetch(`${config.public.apiBase}/auth/reset-password`, {
      method: 'POST',
      body: {
        account: form.account.trim(),
        target: isPay.value ? 'pay' : 'login',
        code: form.code.trim(),
        password: form.password,
        password_confirmation: form.password_confirmation,
      },
    })
    await appOk(res?.message || `${label.value}已重置。`, '保存成功')
    await navigateTo(logged.value ? '/member/password' : '/login')
  } catch (e: any) {
    await appFail(apiErrorText(e, '重置失败'), '保存失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-page">
    <div class="auth-shell">
      <aside class="auth-brand">
        <div class="brand-kicker">有机硅商城</div>
        <h1>忘记{{ label }}</h1>
        <p>给绑定手机发短信验证码，验证通过后设置新的{{ label }}。</p>
        <ul class="brand-points">
          <li>验证码发到已绑定的手机号</li>
          <li>验证码有效期内完成重置</li>
          <li>这次重置的是{{ label }}</li>
        </ul>
        <div class="brand-links">
          <NuxtLink to="/login">返回登录</NuxtLink>
          <NuxtLink to="/">回首页</NuxtLink>
        </div>
      </aside>

      <section class="auth-card">
        <div class="auth-head">
          <h2>忘记{{ label }}</h2>
          <p v-if="isPay && !logged">请先登录，再重置支付密码</p>
          <p v-else-if="bound && phone">验证码发到绑定手机 {{ maskedPhone }}</p>
          <p v-else-if="bound">当前账号还没有绑定手机号，请先到个人资料绑定</p>
          <p v-else>输入注册账号，获取验证码后设置新{{ label }}</p>
        </div>

        <div v-if="demoCode" class="auth-ok">当前未接通短信网关，验证码 <b>{{ demoCode }}</b> 已填入。</div>

        <form v-if="isPay && !logged" class="auth-form" @submit.prevent>
          <div class="auth-actions">
            <NuxtLink class="auth-submit" to="/login?redirect=/forgot-password?target=pay">去登录</NuxtLink>
            <NuxtLink class="auth-ghost" to="/member/password">返回修改密码</NuxtLink>
          </div>
        </form>

        <form v-else-if="bound && !phone" class="auth-form" @submit.prevent>
          <p>请先在个人资料绑定 11 位手机号，再用短信验证码重置{{ label }}。</p>
          <div class="auth-actions">
            <NuxtLink class="auth-submit" to="/member/profile">去绑定手机号</NuxtLink>
            <NuxtLink class="auth-ghost" to="/member/password">返回修改密码</NuxtLink>
          </div>
        </form>

        <form v-else-if="step === 1" class="auth-form" @submit.prevent="sendCode">
          <label v-if="!phone" class="field">
            <span>账号</span>
            <input v-model="form.account" type="text" required autocomplete="username" placeholder="用户名 / 手机 / 邮箱">
          </label>
          <p v-else>验证码将发送到绑定手机 {{ maskedPhone }}。</p>
          <div class="auth-actions">
            <button class="auth-submit" type="submit" :disabled="loading">{{ loading ? '发送中…' : '获取验证码' }}</button>
            <NuxtLink v-if="logged" class="auth-ghost" to="/member/password">返回修改密码</NuxtLink>
            <NuxtLink v-else class="auth-ghost" to="/login">返回登录</NuxtLink>
          </div>
        </form>

        <form v-else class="auth-form" @submit.prevent="resetPwd">
          <p v-if="phone">绑定手机 {{ maskedPhone }}</p>
          <label v-else class="field">
            <span>账号</span>
            <input v-model="form.account" type="text" required autocomplete="username">
          </label>
          <label class="field">
            <span>验证码</span>
            <input v-model="form.code" required maxlength="8" autocomplete="one-time-code" placeholder="短信验证码">
          </label>
          <label class="field">
            <span>新{{ label }}</span>
            <input v-model="form.password" type="password" required minlength="6" autocomplete="new-password">
          </label>
          <label class="field">
            <span>确认新{{ label }}</span>
            <input v-model="form.password_confirmation" type="password" required minlength="6" autocomplete="new-password">
          </label>
          <div class="auth-actions">
            <button class="auth-submit" type="submit" :disabled="loading">{{ loading ? '提交中…' : '确认修改' }}</button>
            <button class="auth-ghost" type="button" @click="step = 1">上一步</button>
          </div>
        </form>
      </section>
    </div>
  </div>
</template>

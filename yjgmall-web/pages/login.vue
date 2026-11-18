<script setup lang="ts">
definePageMeta({ layout: 'home' })
useHead({ title: '会员登录' })

const { login, user, token, fetchMe } = useAuth()
const route = useRoute()
const form = reactive({ login: '', password: '' })
const error = ref('')
const loading = ref(false)
const showCaptcha = ref(false)

const redirectTo = computed(() => (route.query.redirect as string) || '/member')
const needLoginTip = computed(() => Boolean(route.query.redirect))

if (token.value && !user.value) {
  await fetchMe()
}
if (user.value) {
  await navigateTo(redirectTo.value)
}

function onSubmit() {
  error.value = ''
  if (!String(form.login).trim() || !form.password) {
    error.value = '请输入账号和密码'
    return
  }
  showCaptcha.value = true
}

async function onCaptchaVerified(captchaToken: string) {
  error.value = ''
  loading.value = true
  try {
    await login({ ...form, captcha_token: captchaToken })
    await navigateTo(redirectTo.value)
  } catch (e: any) {
    error.value = e?.data?.message || e?.data?.errors?.login?.[0] || e?.data?.errors?.captcha?.[0] || '登录失败'
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
        <h1>欢迎回来</h1>
        <p>登录后可收藏行情、发布供求、进入会员中心管理生意。</p>
        <ul class="brand-points">
          <li>浏览商城、资讯、行情无需登录</li>
          <li>收藏与发布需会员账号</li>
          <li>企业会员可查看更长周期行情</li>
        </ul>
        <div class="brand-links">
          <NuxtLink to="/">回首页</NuxtLink>
          <NuxtLink to="/quotes">看行情</NuxtLink>
          <NuxtLink to="/mall">逛商城</NuxtLink>
        </div>
      </aside>

      <section class="auth-card">
        <div class="auth-head">
          <h2>会员登录</h2>
          <p>使用用户名、邮箱或手机号登录</p>
        </div>

        <div v-if="needLoginTip" class="auth-hint">该功能需要登录后使用，登录成功将自动返回原页面。</div>
        <div v-if="error" class="auth-error">{{ error }}</div>

        <form class="auth-form" @submit.prevent="onSubmit">
          <label class="field">
            <span>账号</span>
            <input
              v-model="form.login"
              type="text"
              name="username"
              required
              autocomplete="username"
              placeholder="用户名 / 邮箱 / 手机号"
            >
          </label>
          <label class="field">
            <span>密码</span>
            <input
              v-model="form.password"
              type="password"
              name="password"
              required
              autocomplete="current-password"
              placeholder="请输入密码"
            >
          </label>

          <div class="auth-actions">
            <button class="auth-submit" type="submit" :disabled="loading">
              {{ loading ? '登录中…' : '登录' }}
            </button>
          </div>
        </form>

        <div class="auth-foot">
          <NuxtLink to="/register">免费注册</NuxtLink>
          <NuxtLink to="/forgot-password">忘记密码</NuxtLink>
        </div>
      </section>
    </div>

    <SlideCaptchaModal v-model="showCaptcha" @verified="onCaptchaVerified" />
  </div>
</template>

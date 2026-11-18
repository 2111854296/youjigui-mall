<script setup lang="ts">
definePageMeta({ layout: false })
useHead({
  title: '运营后台登录',
  link: [
    { rel: 'stylesheet', href: 'https://fonts.googleapis.com/css2?family=Outfit:wght@500;600;700&family=Noto+Sans+SC:wght@400;500;700&display=swap' },
  ],
})

const { login, user, token, fetchMe, logout } = useAuth('admin')
const route = useRoute()
const form = reactive({ login: '', password: '' })
const error = ref('')
const loading = ref(false)
const showCaptcha = ref(false)

const redirectTo = computed(() => (route.query.redirect as string) || '/admin')

if (token.value && !user.value) {
  await fetchMe(true)
}
if (user.value?.is_admin) {
  await navigateTo(redirectTo.value)
}

onMounted(() => {
  if (route.query.error === 'forbidden') {
    error.value = '当前账号不是管理员，请使用管理员账号登录'
  }
})

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
    const u = await login({ ...form, captcha_token: captchaToken })
    if (!u?.is_admin) {
      await logout()
      error.value = '该账号无管理员权限'
      return
    }
    await navigateTo(redirectTo.value)
  } catch (e: any) {
    error.value = e?.data?.message || e?.data?.errors?.captcha?.[0] || '登录失败'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login">
    <div class="visual" aria-hidden="true">
      <div class="visual-inner">
        <div class="mark">硅</div>
        <h1>有机硅商城</h1>
        <p>供需 · 企业库 · 商城 · 资讯<br>一站式 B2B 运营管理</p>
      </div>
    </div>

    <div class="form-side">
      <div class="card">
        <header class="hd">
          <h2>后台登录</h2>
          <p>请使用管理员账号登录</p>
        </header>

        <el-alert v-if="error" :title="error" type="error" show-icon :closable="false" class="err" />

        <el-form class="form" label-position="top" @submit.prevent="onSubmit">
          <el-form-item label="账号">
            <el-input
              v-model="form.login"
              size="large"
              autocomplete="username"
              placeholder="邮箱 / 用户名"
            />
          </el-form-item>
          <el-form-item label="密码">
            <el-input
              v-model="form.password"
              type="password"
              size="large"
              show-password
              autocomplete="current-password"
              placeholder="请输入密码"
              @keyup.enter="onSubmit"
            />
          </el-form-item>
          <el-button
            class="submit"
            size="large"
            type="primary"
            :loading="loading"
            native-type="submit"
          >
            {{ loading ? '登录中…' : '登录' }}
          </el-button>
        </el-form>

        <footer class="ft">
          <NuxtLink to="/">← 返回商城前台</NuxtLink>
        </footer>
      </div>
    </div>

    <SlideCaptchaModal v-model="showCaptcha" @verified="onCaptchaVerified" />
  </div>
</template>

<style scoped>
.login {
  --brand: #2468f2;
  --brand-deep: #1443b8;
  --ink: #1a2332;
  --muted: #6b7785;
  --line: #e6ebf0;
  min-height: 100vh;
  display: grid;
  grid-template-columns: minmax(280px, 1.05fr) minmax(320px, 0.95fr);
  font-family: "Outfit", "Noto Sans SC", "PingFang SC", "Microsoft YaHei", sans-serif;
  color: var(--ink);
  background: #f5f7fa;
}

.visual {
  position: relative;
  overflow: hidden;
  background:
    linear-gradient(165deg, rgba(26, 35, 50, 0.88), rgba(26, 35, 50, 0.72)),
    radial-gradient(ellipse at 30% 20%, rgba(232, 93, 4, 0.35), transparent 50%),
    linear-gradient(145deg, #1e3a5f 0%, #0f2744 45%, #1a2f45 100%);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 48px 40px;
}
.visual::after {
  content: "";
  position: absolute;
  inset: 0;
  background-image: radial-gradient(rgba(255, 255, 255, 0.07) 1px, transparent 1px);
  background-size: 22px 22px;
  opacity: 0.5;
  pointer-events: none;
}
.visual-inner {
  position: relative;
  z-index: 1;
  max-width: 420px;
  animation: in 0.55s ease-out both;
}
.mark {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: grid;
  place-items: center;
  font-size: 24px;
  font-weight: 800;
  background: var(--brand);
  box-shadow: 0 12px 28px rgba(232, 93, 4, 0.35);
  margin-bottom: 28px;
}
.visual h1 {
  margin: 0;
  font-size: clamp(32px, 4.2vw, 44px);
  font-weight: 700;
  letter-spacing: 0.04em;
  line-height: 1.15;
}
.visual p {
  margin: 18px 0 0;
  font-size: 15px;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.72);
  font-weight: 400;
}

.form-side {
  display: grid;
  place-items: center;
  padding: 40px 28px;
  background:
    radial-gradient(600px 280px at 100% 0%, rgba(232, 93, 4, 0.06), transparent 55%),
    #f5f7fa;
}
.card {
  width: min(400px, 100%);
  background: #fff;
  border: 1px solid var(--line);
  border-radius: 16px;
  padding: 36px 32px 28px;
  box-shadow: 0 16px 40px rgba(26, 35, 50, 0.06);
  animation: in 0.55s ease-out 0.08s both;
}
.hd h2 {
  margin: 0;
  font-size: 26px;
  font-weight: 700;
  letter-spacing: 0.02em;
}
.hd p {
  margin: 8px 0 0;
  color: var(--muted);
  font-size: 14px;
}
.err { margin-top: 18px; }
.form { margin-top: 24px; }
.form :deep(.el-form-item__label) {
  color: #3d4a5c !important;
  font-weight: 500;
}
.form :deep(.el-input__wrapper) {
  border-radius: 10px;
  box-shadow: 0 0 0 1px var(--line) inset;
  background: #fafbfc;
}
.form :deep(.el-input__wrapper:hover),
.form :deep(.el-input__wrapper.is-focus) {
  box-shadow: 0 0 0 1px var(--brand) inset;
}
.submit {
  width: 100%;
  height: 46px;
  margin-top: 8px;
  border: none !important;
  border-radius: 10px !important;
  font-weight: 700 !important;
  font-size: 15px !important;
  letter-spacing: 0.06em;
  background: var(--brand) !important;
  box-shadow: 0 8px 20px rgba(232, 93, 4, 0.28);
}
.submit:hover {
  background: var(--brand-deep) !important;
}
.ft {
  margin-top: 22px;
  text-align: center;
  font-size: 13px;
}
.ft a { color: var(--muted) !important; }
.ft a:hover { color: var(--brand) !important; }

@keyframes in {
  from { opacity: 0; transform: translateY(12px); }
  to { opacity: 1; transform: none; }
}

@media (max-width: 860px) {
  .login { grid-template-columns: 1fr; }
  .visual { min-height: 220px; padding: 36px 24px; }
  .visual p { display: none; }
  .card { padding: 28px 22px 22px; }
}
</style>

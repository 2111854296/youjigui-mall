<script setup lang="ts">
definePageMeta({ layout: 'home' })
useHead({ title: '免费注册' })

const { register, user } = useAuth()
const route = useRoute()
const form = reactive({
  member_type: 'company',
  username: '',
  name: '',
  gender: '先生',
  email: '',
  mobile: '',
  qq: '',
  area: '',
  company_name: '',
  company_type: '企业单位',
  password: '',
  password_confirmation: '',
  invite_code: String(route.query.invite || route.query.invite_code || ''),
})
const error = ref('')
const loading = ref(false)

if (user.value) {
  await navigateTo('/member')
}

async function onSubmit() {
  error.value = ''
  loading.value = true
  try {
    await register({ ...form })
    await navigateTo('/member')
  } catch (e: any) {
    const errors = e?.data?.errors
    error.value = errors ? Object.values(errors).flat().join('；') : (e?.data?.message || '注册失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="auth-page">
    <div class="auth-shell auth-shell--wide">
      <aside class="auth-brand">
        <div class="brand-kicker">有机硅商城</div>
        <h1>开通会员账号</h1>
        <p>注册后可发布供求、收藏行情、管理企业旺铺与商务中心。</p>
        <ul class="brand-points">
          <li>企业 / 个人会员均可注册</li>
          <li>支持邀请码绑定推广关系</li>
          <li>浏览行情与商城仍可先逛再注册</li>
        </ul>
        <div class="brand-links">
          <NuxtLink to="/login">已有账号</NuxtLink>
          <NuxtLink to="/">回首页</NuxtLink>
        </div>
      </aside>

      <section class="auth-card auth-card--scroll">
        <div class="auth-head">
          <h2>会员注册</h2>
          <p>请如实填写信息，便于买家与平台联系</p>
        </div>
        <div v-if="error" class="auth-error">{{ error }}</div>

        <form class="auth-form" @submit.prevent="onSubmit">
          <div class="sec-title">帐户信息</div>
          <label class="field">
            <span>会员类型</span>
            <select v-model="form.member_type">
              <option value="company">企业</option>
              <option value="personal">个人</option>
            </select>
          </label>
          <label class="field">
            <span>会员名</span>
            <input v-model="form.username" name="username" required minlength="4" autocomplete="username" placeholder="至少 4 位">
          </label>
          <label class="field">
            <span>密码</span>
            <input v-model="form.password" type="password" name="password" required autocomplete="new-password" placeholder="设置登录密码">
          </label>
          <label class="field">
            <span>重复密码</span>
            <input v-model="form.password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="再次输入密码">
          </label>

          <div class="sec-title">联系方式</div>
          <label class="field">
            <span>真实姓名</span>
            <input v-model="form.name" name="name" required autocomplete="name">
          </label>
          <label class="field">
            <span>称谓</span>
            <select v-model="form.gender">
              <option>先生</option>
              <option>女士</option>
            </select>
          </label>
          <label class="field">
            <span>所在地区</span>
            <input v-model="form.area" placeholder="如：广东东莞" autocomplete="address-level1">
          </label>
          <label class="field">
            <span>邮箱</span>
            <input v-model="form.email" type="email" name="email" required autocomplete="email">
          </label>
          <label class="field">
            <span>手机</span>
            <input v-model="form.mobile" name="tel" autocomplete="tel" placeholder="选填">
          </label>
          <label class="field">
            <span>QQ</span>
            <input v-model="form.qq" placeholder="选填">
          </label>
          <label class="field">
            <span>邀请码</span>
            <input v-model="form.invite_code" placeholder="选填，好友邀请码">
          </label>

          <template v-if="form.member_type === 'company'">
            <div class="sec-title">公司信息</div>
            <label class="field">
              <span>公司名称</span>
              <input v-model="form.company_name" required autocomplete="organization">
            </label>
            <label class="field">
              <span>公司类型</span>
              <select v-model="form.company_type">
                <option>企业单位</option>
                <option>事业社团</option>
                <option>个体经营</option>
                <option>其他组织</option>
              </select>
            </label>
          </template>

          <div class="auth-actions">
            <button class="auth-submit" type="submit" :disabled="loading">
              {{ loading ? '提交中…' : '立即注册' }}
            </button>
          </div>
        </form>

        <div class="auth-foot">
          <span style="color:var(--ink-3);font-weight:500">已有账号？</span>
          <NuxtLink to="/login">去登录</NuxtLink>
        </div>
      </section>
    </div>
  </div>
</template>

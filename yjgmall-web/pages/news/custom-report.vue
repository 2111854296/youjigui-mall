<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '定制报告' })

const { api } = useApi()
const { user, fetchMe } = useAuth()
const msg = ref('')
const err = ref('')
const loading = ref(false)

const BASE_PRICE = 2000

const tiers = [
  { code: 'free', label: '普通会员', discount: 1, note: '全价' },
  { code: 'silver', label: '白银会员', discount: 0.9, note: '9 折' },
  { code: 'gold', label: '黄金会员', discount: 0.8, note: '8 折' },
  { code: 'diamond', label: '星钻会员', discount: 0.7, note: '7 折' },
] as const

function priceOf(discount: number) {
  return Math.round(BASE_PRICE * discount)
}

const gradeOrder = ['free', 'silver', 'gold', 'diamond'] as const

function rankOf(code: string) {
  const i = gradeOrder.indexOf(code as (typeof gradeOrder)[number])
  return i < 0 ? 0 : i
}

import { memberTier } from '~/utils/memberTier'

const gradeCode = computed(() => memberTier(user.value))

const myRank = computed(() => rankOf(gradeCode.value))
const myTier = computed(() => tiers.find((t) => t.code === gradeCode.value) || tiers[0])
const myPrice = computed(() => priceOf(myTier.value.discount))

function tierHigher(code: string) {
  return rankOf(code) > myRank.value
}

async function onTier(t: (typeof tiers)[number]) {
  if (!tierHigher(t.code)) return
  const ok = await useAppDialog().confirm({
    title: '升级提示',
    message: `您现在是${myTier.value.label}。升级到${t.label}后，这份报告大约 ¥${priceOf(t.discount)}（${t.note}）。`,
    confirmText: '去会员页面',
    cancelText: '取消',
  })
  if (ok) await navigateTo('/member/grade')
}

onMounted(() => {
  if (user.value || import.meta.server) return
  fetchMe().catch(() => {})
})

const form = reactive({
  title: '',
  content: '',
  contact_name: user.value?.name || '',
  contact_phone: user.value?.mobile || '',
  contact_email: user.value?.email || '',
})

watch(() => user.value, (u) => {
  if (!u) return
  form.contact_name ||= u.name || ''
  form.contact_phone ||= u.mobile || ''
  form.contact_email ||= u.email || ''
})

async function submit() {
  err.value = ''
  msg.value = ''
  if (!form.content.trim()) {
    await appFail('请填写报告需求说明', '提交失败')
    return
  }
  if (!form.contact_name.trim() || !form.contact_phone.trim()) {
    await appFail('请填写联系人与电话', '提交失败')
    return
  }
  loading.value = true
  try {
    const tier = myTier.value
    const priceLine = `【专属定制服务】标价 ¥${BASE_PRICE}/次；当前身份 ${tier.label}（${tier.note}），应付约 ¥${priceOf(tier.discount)}`
    await api('/inquiries', {
      method: 'POST',
      body: {
        type: 'custom_report',
        title: form.title.trim() || '定制报告需求',
        content: `${form.content.trim()}\n\n${priceLine}`,
        contact_name: form.contact_name.trim(),
        contact_phone: form.contact_phone.trim(),
        contact_email: form.contact_email.trim() || undefined,
      },
      auth: !!user.value,
    })
    await appOk('定制报告需求已提交，我们会尽快与您联系确认。', '提交成功')
    form.title = ''
    form.content = ''
  } catch (e: any) {
    await appFail(apiErrorText(e, '提交失败，请稍后重试'), '提交失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <NuxtLink to="/news">资讯中心</NuxtLink>
          <span class="on">定制报告</span>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="dt-card cr-card">
          <div class="dt-h">专属定制服务</div>
          <p class="cr-desc">
            定制行业报告 / 行业数据。提交需求后，顾问将与您确认范围、交付周期与最终报价。
          </p>

          <div class="cr-price">
            <div class="cr-price-main">
              <span class="cr-tag">标价</span>
              <strong>¥{{ BASE_PRICE }}</strong>
              <span class="cr-unit">/ 次</span>
            </div>
            <div class="cr-price-mine">
              您当前为 <b>{{ myTier.label }}</b>，
              <template v-if="myTier.discount < 1">享 {{ myTier.note }}，</template>
              应付约 <em>¥{{ myPrice }}</em>
            </div>
          </div>

          <ul class="cr-tiers">
            <li
              v-for="t in tiers"
              :key="t.code"
              :class="{ on: t.code === gradeCode, up: tierHigher(t.code) }"
              @click="onTier(t)"
            >
              <span class="n">{{ t.label }}</span>
              <span class="d">{{ t.note }}</span>
              <span class="p">¥{{ priceOf(t.discount) }}</span>
            </li>
          </ul>

          <div v-if="msg" class="alert alert-success">{{ msg }}</div>
          <div v-if="err" class="alert alert-error">{{ err }}</div>
          <form class="cr-form" @submit.prevent="submit">
            <div class="form-row">
              <label>报告主题</label>
              <input v-model="form.title" placeholder="如：2026年有机硅DMC专项分析（可选）">
            </div>
            <div class="form-row">
              <label>需求说明 <em>*</em></label>
              <textarea
                v-model="form.content"
                required
                rows="6"
                placeholder="请说明关注产品、研究范围、用途、期望交付时间等"
              />
            </div>
            <div class="form-row">
              <label>联系人 <em>*</em></label>
              <input v-model="form.contact_name" required placeholder="姓名">
            </div>
            <div class="form-row">
              <label>联系电话 <em>*</em></label>
              <input v-model="form.contact_phone" required placeholder="手机号">
            </div>
            <div class="form-row">
              <label>邮箱</label>
              <input v-model="form.contact_email" type="email" placeholder="可选">
            </div>
            <div class="cr-actions">
              <button class="btn" type="submit" :disabled="loading">
                {{ loading ? '提交中…' : '提交定制需求' }}
              </button>
              <NuxtLink class="btn-soft-link" to="/member/grade">了解会员权益</NuxtLink>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.cr-card {
  max-width: 680px;
}
.cr-desc {
  margin: 0 0 14px;
  font-size: 13.5px;
  line-height: 1.7;
  color: var(--ink-2, #5a6577);
}
.cr-price {
  background: linear-gradient(135deg, #f4f8ff 0%, #eef4fc 100%);
  border: 1px solid #d9e5f7;
  border-radius: 10px;
  padding: 14px 16px;
  margin-bottom: 12px;
}
.cr-price-main {
  display: flex;
  align-items: baseline;
  gap: 8px;
}
.cr-tag {
  font-size: 12px;
  color: var(--ink-3, #8a93a3);
}
.cr-price-main strong {
  font-size: 28px;
  font-weight: 800;
  color: #c2410c;
  letter-spacing: -0.02em;
}
.cr-unit {
  font-size: 13px;
  color: var(--ink-2, #5a6577);
}
.cr-price-mine {
  margin-top: 8px;
  font-size: 13px;
  color: var(--ink-2, #5a6577);
  line-height: 1.5;
}
.cr-price-mine b {
  color: var(--ink, #1a2332);
}
.cr-price-mine em {
  font-style: normal;
  font-weight: 800;
  color: #c2410c;
  font-size: 15px;
}
.cr-tiers {
  list-style: none;
  margin: 0 0 18px;
  padding: 0;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
}
.cr-tiers li {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 11px;
  border: 1px solid var(--line, #e5e9f0);
  border-radius: 8px;
  background: #fff;
  font-size: 12.5px;
}
.cr-tiers li.on {
  border-color: #7aa6e8;
  background: #f5f9ff;
  box-shadow: inset 0 0 0 1px rgba(37, 99, 200, 0.12);
}
.cr-tiers li.up {
  cursor: pointer;
}
.cr-tiers li.up:hover {
  border-color: #7aa6e8;
  background: #f8fbff;
}
.cr-tiers .n {
  font-weight: 700;
  color: var(--ink, #1a2332);
}
.cr-tiers .d {
  color: var(--ink-3, #8a93a3);
  margin-right: auto;
}
.cr-tiers .p {
  font-weight: 800;
  color: #c2410c;
}
.cr-form .form-row {
  margin-bottom: 14px;
}
.cr-form label {
  display: block;
  font-size: 13px;
  color: var(--ink-3, #8a93a3);
  margin-bottom: 5px;
}
.cr-form label em {
  color: #c2410c;
  font-style: normal;
}
.cr-form input,
.cr-form textarea {
  width: 100%;
  box-sizing: border-box;
  padding: 9px 11px;
  border: 1px solid var(--line, #e5e9f0);
  border-radius: 8px;
  font: inherit;
}
.cr-actions {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-top: 6px;
}
@media (max-width: 560px) {
  .cr-tiers {
    grid-template-columns: 1fr;
  }
}
</style>

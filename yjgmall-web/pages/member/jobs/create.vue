<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: ['auth', 'certified-enterprise'] })
useHead({ title: '发布招聘' })
const { api } = useApi()
const { user } = useAuth()
const router = useRouter()

function emptyForm() {
  return {
    title: '',
    company_name: String((user.value as any)?.company_name || ''),
    area: '东莞',
    salary: '',
    salary_min: '',
    salary_max: '',
    education: '不限',
    experience: '不限',
    headcount: '',
    summary: '',
    content: '',
    contact_name: '',
    contact_phone: '',
    job_category: '硅橡胶',
    job_role: '销售',
    job_nature: '全职',
    company_size: '',
  }
}

const form = reactive(emptyForm())
const loading = ref(false)
const vipGateOpen = ref(false)
const vipGateMsg = ref('当前会员权益不足，请升级企业会员。')

function buildPayload() {
  const min = form.salary_min !== '' && form.salary_min != null ? Number(form.salary_min) : null
  const max = form.salary_max !== '' && form.salary_max != null ? Number(form.salary_max) : null
  const salary =
    min != null && max != null && !Number.isNaN(min) && !Number.isNaN(max)
      ? `${min}-${max}K`
      : min != null && !Number.isNaN(min)
        ? `${min}K`
        : max != null && !Number.isNaN(max)
          ? `${max}K`
          : '面议'
  return {
    title: String(form.title || '').trim(),
    company_name: form.company_name || undefined,
    area: form.area,
    salary,
    salary_min: min != null && !Number.isNaN(min) ? min : undefined,
    salary_max: max != null && !Number.isNaN(max) ? max : undefined,
    education: form.education,
    experience: form.experience,
    headcount: form.headcount || undefined,
    summary: form.summary || undefined,
    content: form.content,
    contact_name: form.contact_name || undefined,
    contact_phone: form.contact_phone || undefined,
    job_category: form.job_category || undefined,
    job_role: form.job_role || undefined,
    job_nature: form.job_nature || '全职',
    company_size: form.company_size || undefined,
  }
}

async function submit() {
  if (!String(form.title || '').trim()) {
    await appFail('请填写职位名称', '提交失败')
    return
  }
  if (!String(form.job_role || '').trim()) {
    await appFail('请选择职位类别', '提交失败')
    return
  }
  if (!String(form.job_category || '').trim()) {
    await appFail('请选择领域分类', '提交失败')
    return
  }
  if (!String(form.content || '').replace(/<[^>]+>/g, '').trim()) {
    await appFail('请填写职位描述', '提交失败')
    return
  }
  loading.value = true
  try {
    await api('/member/jobs', { method: 'POST', body: buildPayload() })
    await appOk('已提交，审核通过后在招聘专区展示。可在本页查看浏览量与投递。', '提交成功')
    router.push('/member/jobs')
  } catch (e: any) {
    const msg = apiErrorText(e, '提交失败')
    if (/上限|升级/.test(msg)) {
      vipGateMsg.value = msg
      vipGateOpen.value = true
    } else {
      await appFail(msg, '提交失败')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="panel">
    <h3>
      发布招聘
      <NuxtLink class="back-list" to="/member/jobs">返回列表</NuxtLink>
    </h3>
    <div class="body">
      <p class="muted tip">提交后进入平台后台审核；通过前不会在招聘专区展示。商户后台可管理职位与收到的投递，不能代替平台审核上站。</p>
      <JobPostForm v-model="form" :loading="loading" submit-text="提交审核" @submit="submit" />
    </div>
    <VipGateModal v-model="vipGateOpen" title="会员权益不足" :message="vipGateMsg" @join="router.push('/member/grade')" />
  </div>
</template>

<style scoped>
.tip { margin: 0 0 14px; font-size: 13px; }
</style>

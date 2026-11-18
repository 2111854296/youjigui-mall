<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: ['auth', 'certified-enterprise'] })
useHead({ title: '编辑招聘' })
const { api } = useApi()
const route = useRoute()
const router = useRouter()
const id = computed(() => Number(route.params.id))

const form = reactive({
  title: '',
  company_name: '',
  area: '东莞',
  salary: '',
  salary_min: '' as string | number,
  salary_max: '' as string | number,
  education: '不限',
  experience: '不限',
  headcount: '' as string | number,
  summary: '',
  content: '',
  contact_name: '',
  contact_phone: '',
  job_category: '',
  job_role: '',
  job_nature: '全职',
  company_size: '',
})
const meta = reactive({ views: 0, received_count: 0, status: '' })
const loading = ref(false)
const ready = ref(false)
const vipGateOpen = ref(false)
const vipGateMsg = ref('当前会员权益不足，请升级企业会员。')

onMounted(async () => {
  try {
    const res = await api<{ data: any }>(`/member/jobs/${id.value}`)
    const d = res.data || {}
    Object.assign(form, {
      title: d.title || '',
      company_name: d.company_name || '',
      area: d.area || '东莞',
      salary: d.salary || '',
      salary_min: d.salary_min ?? '',
      salary_max: d.salary_max ?? '',
      education: d.education || '不限',
      experience: d.experience || '不限',
      headcount: d.headcount ?? '',
      summary: d.summary || '',
      content: d.content || '',
      contact_name: d.contact_name || '',
      contact_phone: d.contact_phone || '',
      job_category: d.job_category || '',
      job_role: d.job_role || '',
      job_nature: d.job_nature || '全职',
      company_size: d.company_size || '',
    })
    meta.views = Number(d.views || 0)
    meta.received_count = Number(d.received_count || 0)
    meta.status = String(d.status || '')
    ready.value = true
  } catch (e: any) {
    await appFail(apiErrorText(e, '加载失败'), '加载失败')
  }
})

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
          : form.salary || '面议'
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
    await appFail('请填写职位名称', '保存失败')
    return
  }
  if (!String(form.job_role || '').trim()) {
    await appFail('请选择职位类别', '保存失败')
    return
  }
  if (!String(form.job_category || '').trim()) {
    await appFail('请选择领域分类', '保存失败')
    return
  }
  if (!String(form.content || '').replace(/<[^>]+>/g, '').trim()) {
    await appFail('请填写职位描述', '保存失败')
    return
  }
  loading.value = true
  try {
    await api(`/member/jobs/${id.value}`, { method: 'PUT', body: buildPayload() })
    await appOk('已保存，将重新进入审核。', '保存成功')
    router.push('/member/jobs')
  } catch (e: any) {
    const msg = apiErrorText(e, '保存失败')
    if (/上限|升级/.test(msg)) {
      vipGateMsg.value = msg
      vipGateOpen.value = true
    } else {
      await appFail(msg, '保存失败')
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="panel">
    <h3>
      编辑招聘
      <NuxtLink class="back-list" to="/member/jobs">返回列表</NuxtLink>
    </h3>
    <div class="body">
      <p v-if="ready" class="stats muted">
        浏览 <b>{{ meta.views }}</b>
        · 投递 <NuxtLink to="/member/job-received"><b>{{ meta.received_count }}</b> 份</NuxtLink>
        · 状态 {{ ({ pending: '待审', approved: '已发布', offline: '下架', rejected: '拒绝' } as Record<string, string>)[meta.status] || meta.status || '—' }}
      </p>
      <JobPostForm
        v-if="ready"
        v-model="form"
        :loading="loading"
        submit-text="保存并重新审核"
        @submit="submit"
      />
    </div>
    <VipGateModal v-model="vipGateOpen" title="会员权益不足" :message="vipGateMsg" @join="router.push('/member/grade')" />
  </div>
</template>

<style scoped>
.stats { margin: 0 0 14px; font-size: 13px; }
.stats b { color: var(--ink, #0f172a); }
</style>

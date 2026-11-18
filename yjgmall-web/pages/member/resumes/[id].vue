<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '编辑简历' })
import { isEmail, isMobile, isRealName } from '~/utils/formCheck'
import { FIELD_CATS, JOB_ROLE_CATS } from '~/utils/jobsDemo'
const route = useRoute()
const router = useRouter()
const { api } = useApi()
const id = computed(() => String(route.params.id))
const cats = FIELD_CATS.filter((c) => c !== '全部')
const roles = JOB_ROLE_CATS.filter((c) => c !== '全部')
const form = reactive({
  title: '',
  truename: '',
  gender: '',
  education: '',
  experience: '',
  minsalary: '' as string | number,
  maxsalary: '' as string | number,
  mobile: '',
  email: '',
  area: '',
  job_role: '',
  job_category: '',
  summary: '',
  content: '',
  open: 'public',
  is_default: false,
})
const err = ref('')
const loading = ref(false)
const auditStatus = ref('')

onMounted(async () => {
  const res = await api<{ data: any }>(`/member/resumes/${id.value}`)
  const d = res.data || {}
  auditStatus.value = String(d.status || '')
  Object.assign(form, {
    ...d,
    open: d.open === 'private' || d.open === '0' || d.open === 0 ? 'private' : 'public',
    is_default: Boolean(Number(d.is_default)),
    minsalary: d.minsalary ?? '',
    maxsalary: d.maxsalary ?? '',
    job_role: d.job_role || '',
    job_category: d.job_category || '',
  })
})

async function submit() {
  err.value = ''
  if (!form.job_role) {
    await appFail('请选择职位类别', '保存失败')
    return
  }
  if (!form.job_category) {
    await appFail('请选择所属行业', '保存失败')
    return
  }
  if (form.truename.trim() && !isRealName(form.truename)) {
    await appFail('请用真实姓名', '保存失败')
    return
  }
  if (form.mobile.trim() && !isMobile(form.mobile)) {
    await appFail('请填写11位手机号', '保存失败')
    return
  }
  if (form.email.trim() && !isEmail(form.email)) {
    await appFail('请填写正确的邮箱', '保存失败')
    return
  }
  loading.value = true
  try {
    await api(`/member/resumes/${id.value}`, {
      method: 'PUT',
      body: {
        ...form,
        minsalary: form.minsalary !== '' ? Number(form.minsalary) : null,
        maxsalary: form.maxsalary !== '' ? Number(form.maxsalary) : null,
        is_default: form.is_default ? 1 : 0,
      },
    })
    await appOk('已保存，将重新进入平台审核。', '保存成功')
    router.push('/member/resumes')
  } catch (e: any) {
    await appFail(apiErrorText(e, '保存失败'), '保存失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="panel">
    <h3>编辑简历 <NuxtLink to="/member/resumes">返回</NuxtLink></h3>
    <div class="body">
      <div v-if="err" class="alert alert-error">{{ err }}</div>
      <p class="muted tip">
        当前审核状态：{{ ({ pending: '待平台审核', approved: '已通过', published: '已通过', rejected: '已拒绝', offline: '已下架' } as Record<string, string>)[auditStatus] || auditStatus || '—' }}。
        修改后需重新审核，通过前不上人才库、不可投递。
      </p>
      <form class="mf-form" @submit.prevent="submit">
        <div class="form-row"><label>简历标题 <em>*</em></label><input v-model="form.title" required></div>
        <div class="mf-grid-2">
          <div class="form-row"><label>姓名</label><input v-model="form.truename"></div>
          <div class="form-row"><label>性别</label><input v-model="form.gender"></div>
          <div class="form-row"><label>学历</label><input v-model="form.education"></div>
          <div class="form-row"><label>经验</label><input v-model="form.experience"></div>
          <div class="form-row"><label>期望月薪下限(元)</label><input v-model="form.minsalary" type="number"></div>
          <div class="form-row"><label>期望月薪上限(元)</label><input v-model="form.maxsalary" type="number"></div>
          <div class="form-row"><label>手机</label><input v-model="form.mobile"></div>
          <div class="form-row"><label>邮箱</label><input v-model="form.email"></div>
          <div class="form-row"><label>期望地区</label><input v-model="form.area"></div>
          <div class="form-row">
            <label>职位类别 <em>*</em></label>
            <select v-model="form.job_role" required>
              <option value="">请选择</option>
              <option v-for="r in roles" :key="r" :value="r">{{ r }}</option>
            </select>
          </div>
          <div class="form-row">
            <label>所属行业 <em>*</em></label>
            <select v-model="form.job_category" required>
              <option value="">请选择</option>
              <option v-for="c in cats" :key="c" :value="c">{{ c }}</option>
            </select>
          </div>
          <div class="form-row">
            <label>公开状态</label>
            <select v-model="form.open">
              <option value="public">公开（进入人才库）</option>
              <option value="private">仅自己可见</option>
            </select>
          </div>
        </div>
        <div class="form-row"><label>摘要 / 技能标签</label><textarea v-model="form.summary" rows="2" /></div>
        <div class="form-row"><label>详细经历</label><RichTextEditor v-model="form.content" min-height="180px" /></div>
        <div class="form-row">
          <label><input v-model="form.is_default" type="checkbox"> 设为默认简历（投递时优先选用）</label>
        </div>
        <div class="mf-actions">
          <button class="btn" type="submit" :disabled="loading">保存并重新审核</button>
          <NuxtLink class="btn btn-outline" to="/member/resumes">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

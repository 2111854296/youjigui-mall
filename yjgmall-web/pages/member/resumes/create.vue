<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '新建简历' })
import { isEmail, isMobile, isRealName } from '~/utils/formCheck'
import { FIELD_CATS, JOB_ROLE_CATS } from '~/utils/jobsDemo'
const route = useRoute()
const { api } = useApi()
const router = useRouter()
const cats = FIELD_CATS.filter((c) => c !== '全部')
const roles = JOB_ROLE_CATS.filter((c) => c !== '全部')
const form = reactive({
  title: '',
  truename: '',
  gender: '',
  education: '',
  experience: '',
  minsalary: '',
  maxsalary: '',
  mobile: '',
  email: '',
  area: '',
  job_role: roles.includes(String(route.query.role || '') as any) ? String(route.query.role) : '',
  job_category: cats.includes(String(route.query.category || '') as any) ? String(route.query.category) : '',
  summary: '',
  content: '',
  open: 'public',
  is_default: false,
})
const err = ref('')
const loading = ref(false)

async function submit() {
  err.value = ''
  if (!form.job_role) {
    await appFail('请选择职位类别', '提交失败')
    return
  }
  if (!form.job_category) {
    await appFail('请选择所属行业', '提交失败')
    return
  }
  if (form.truename.trim() && !isRealName(form.truename)) {
    await appFail('请用真实姓名', '提交失败')
    return
  }
  if (form.mobile.trim() && !isMobile(form.mobile)) {
    await appFail('请填写11位手机号', '提交失败')
    return
  }
  if (form.email.trim() && !isEmail(form.email)) {
    await appFail('请填写正确的邮箱', '提交失败')
    return
  }
  loading.value = true
  try {
    await api('/member/resumes', {
      method: 'POST',
      body: {
        ...form,
        minsalary: form.minsalary ? Number(form.minsalary) : null,
        maxsalary: form.maxsalary ? Number(form.maxsalary) : null,
        is_default: form.is_default ? 1 : 0,
      },
    })
    await appOk('已提交平台审核。通过后方可投递；公开简历审核通过后进入人才库。', '提交成功')
    router.push('/member/resumes')
  } catch (e: any) {
    await appFail(apiErrorText(e, '提交失败'), '提交失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="panel">
    <h3>新建简历 <NuxtLink to="/member/resumes">返回</NuxtLink></h3>
    <div class="body">
      <div v-if="err" class="alert alert-error">{{ err }}</div>
      <p class="muted tip">提交后由平台后台审核，通过前不会出现在人才库，也不能用于投递职位。</p>
      <form class="mf-form" @submit.prevent="submit">
        <div class="form-row"><label>简历标题 <em>*</em></label><input v-model="form.title" required placeholder="如：硅橡胶配方研发工程师"></div>
        <div class="mf-grid-2">
          <div class="form-row"><label>姓名</label><input v-model="form.truename"></div>
          <div class="form-row"><label>性别</label><input v-model="form.gender" placeholder="男/女"></div>
          <div class="form-row"><label>学历</label><input v-model="form.education" placeholder="本科"></div>
          <div class="form-row"><label>经验</label><input v-model="form.experience" placeholder="3-5年"></div>
          <div class="form-row"><label>期望月薪下限(元)</label><input v-model="form.minsalary" type="number" placeholder="8000"></div>
          <div class="form-row"><label>期望月薪上限(元)</label><input v-model="form.maxsalary" type="number" placeholder="15000"></div>
          <div class="form-row"><label>手机</label><input v-model="form.mobile"></div>
          <div class="form-row"><label>邮箱</label><input v-model="form.email"></div>
          <div class="form-row"><label>期望地区</label><input v-model="form.area" placeholder="东莞"></div>
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
        <div class="form-row"><label>摘要 / 技能标签</label><textarea v-model="form.summary" rows="2" placeholder="用顿号分隔技能，如：HTV、外贸、偶联剂" /></div>
        <div class="form-row"><label>详细经历</label><RichTextEditor v-model="form.content" min-height="180px" /></div>
        <div class="form-row">
          <label><input v-model="form.is_default" type="checkbox"> 设为默认简历（投递时优先选用）</label>
        </div>
        <div class="mf-actions">
          <button class="btn" type="submit" :disabled="loading">提交审核</button>
          <NuxtLink class="btn btn-outline" to="/member/resumes">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

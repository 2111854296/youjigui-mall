<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '发布展会' })
const { api } = useApi()
const router = useRouter()
const form = reactive({
  title: '',
  area: '',
  venue: '',
  starts_on: '',
  ends_on: '',
  organizer: '',
  summary: '',
  content: '',
})
const err = ref('')
const loading = ref(false)
async function submit() {
  err.value = ''
  loading.value = true
  try {
    await api('/member/exhibit', { method: 'POST', body: { ...form } })
    await appOk('已提交。', '提交成功')
    router.push('/member/exhibit')
  } catch (e: any) {
    await appFail(apiErrorText(e, '提交失败'), '提交失败')
  } finally {
    loading.value = false
  }
}
</script>
<template>
  <div class="panel">
    <h3>发布展会 <NuxtLink to="/member/exhibit">返回</NuxtLink></h3>
    <div class="body">
      <div v-if="err" class="alert alert-error">{{ err }}</div>
      <form class="mf-form" @submit.prevent="submit">
        <div class="form-row"><label>标题 <em>*</em></label><input v-model="form.title" required></div>
        <div class="form-row"><label>地区</label><input v-model="form.area"></div>
        <div class="form-row"><label>场馆</label><input v-model="form.venue"></div>
        <div class="form-row"><label>开始日期</label><input v-model="form.starts_on"></div>
        <div class="form-row"><label>结束日期</label><input v-model="form.ends_on"></div>
        <div class="form-row"><label>主办方</label><input v-model="form.organizer"></div>
        <div class="form-row"><label>摘要</label><textarea v-model="form.summary" rows="2" /></div>
        <div class="form-row"><label>详情</label><RichTextEditor v-model="form.content" min-height="160px" /></div>
        <div class="mf-actions">
          <button class="btn" type="submit" :disabled="loading">提交审核</button>
          <NuxtLink class="btn btn-outline" to="/member/exhibit">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

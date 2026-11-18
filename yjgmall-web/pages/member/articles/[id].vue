<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: ['auth', 'enterprise'] })
useHead({ title: '编辑技术分享' })
const route = useRoute()
const router = useRouter()
const { api } = useApi()
const id = route.params.id as string
const error = ref('')
const form = reactive({
  title: '',
  thumb: '',
  summary: '',
  content: '',
})

const { data, pending } = useMemberData(`edit-article-${id}`, () => api(`/member/articles/${id}`))
watch(() => (data.value as any)?.data, (a) => {
  if (!a) return
  Object.assign(form, {
    title: a.title || '',
    thumb: a.thumb || '',
    summary: a.summary || '',
    content: a.content || '',
  })
}, { immediate: true })

async function onSubmit() {
  error.value = ''
  try {
    await api(`/member/articles/${id}`, { method: 'PUT', body: { ...form } })
    await appOk('已保存，重新进入审核。', '保存成功')
    router.push('/member/articles')
  } catch (e: any) {
    await appFail(apiErrorText(e, '保存失败'), '保存失败')
  }
}
</script>

<template>
  <div class="panel">
    <h3>
      编辑技术分享
      <NuxtLink to="/member/articles">返回列表</NuxtLink>
    </h3>
    <div class="body">
      <div v-if="pending" class="muted">加载中…</div>
      <div v-else-if="error" class="alert alert-error">{{ error }}</div>
      <form v-else class="mf-form" @submit.prevent="onSubmit">
        <section class="mf-sec">
          <div class="mf-sec-hd">基本信息</div>
          <div class="mf-sec-bd">
            <div class="form-row"><label>标题 <em>*</em></label><input v-model="form.title" required></div>
            <div class="form-row"><label>摘要</label><input v-model="form.summary"></div>
          </div>
        </section>
        <section class="mf-sec">
          <div class="mf-sec-hd">封面图</div>
          <div class="mf-sec-bd"><div class="mf-pics one"><ImageUpload v-model="form.thumb" label="标题图片" /></div></div>
        </section>
        <section class="mf-sec">
          <div class="mf-sec-hd">正文</div>
          <div class="mf-sec-bd">
            <div class="form-row"><label>内容</label><RichTextEditor v-model="form.content" min-height="220px" /></div>
          </div>
        </section>
        <div class="mf-actions">
          <button class="btn" type="submit">保存并重新审核</button>
          <NuxtLink class="btn btn-outline" to="/member/articles">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

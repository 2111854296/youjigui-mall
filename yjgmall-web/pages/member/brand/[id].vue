<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '编辑品牌' })
const route = useRoute()
const router = useRouter()
const { api } = useApi()
const id = computed(() => String(route.params.id))
const form = reactive({
  name: '',
  logo: '',
  country: '',
  website: '',
  intro: '',
  content: '',
})
const err = ref('')
const loading = ref(false)
onMounted(async () => {
  const res = await api<{ data: any }>('/member/brand/' + id.value)
  Object.assign(form, res.data || {})
})
async function submit() {
  err.value = ''
  loading.value = true
  try {
    await api('/member/brand/' + id.value, { method: 'PUT', body: { ...form } })
    router.push('/member/brand')
  } catch (e: any) {
    await appFail(apiErrorText(e, '保存失败'), '保存失败')
  } finally {
    loading.value = false
  }
}
</script>
<template>
  <div class="panel">
    <h3>编辑品牌 <NuxtLink to="/member/brand">返回</NuxtLink></h3>
    <div class="body">
      <div v-if="err" class="alert alert-error">{{ err }}</div>
      <form class="mf-form" @submit.prevent="submit">
        <div class="form-row"><label>品牌名称 <em>*</em></label><input v-model="form.name" required></div>
        <div class="form-row"><label>Logo URL</label><input v-model="form.logo"></div>
        <div class="form-row"><label>国家/地区</label><input v-model="form.country"></div>
        <div class="form-row"><label>官网</label><input v-model="form.website"></div>
        <div class="form-row"><label>简介</label><textarea v-model="form.intro" rows="2" /></div>
        <div class="form-row"><label>详情</label><RichTextEditor v-model="form.content" min-height="160px" /></div>
        <div class="mf-actions">
          <button class="btn" type="submit" :disabled="loading">保存</button>
          <NuxtLink class="btn btn-outline" to="/member/brand">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

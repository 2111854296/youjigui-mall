<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '编辑团购' })
const route = useRoute()
const router = useRouter()
const { api } = useApi()
const id = computed(() => String(route.params.id))
const form = reactive({
  title: '',
  thumb: '',
  price: 0,
  market_price: 0,
  stock: 0,
  ends_at: '',
  summary: '',
  content: '',
})
const err = ref('')
const loading = ref(false)
onMounted(async () => {
  const res = await api<{ data: any }>('/member/group/' + id.value)
  Object.assign(form, res.data || {})
})
async function submit() {
  err.value = ''
  loading.value = true
  try {
    await api('/member/group/' + id.value, { method: 'PUT', body: { ...form } })
    router.push('/member/group')
  } catch (e: any) {
    await appFail(apiErrorText(e, '保存失败'), '保存失败')
  } finally {
    loading.value = false
  }
}
</script>
<template>
  <div class="panel">
    <h3>编辑团购 <NuxtLink to="/member/group">返回</NuxtLink></h3>
    <div class="body">
      <div v-if="err" class="alert alert-error">{{ err }}</div>
      <form class="mf-form" @submit.prevent="submit">
        <div class="form-row"><label>标题 <em>*</em></label><input v-model="form.title" required></div>
        <div class="form-row"><label>封面图URL</label><input v-model="form.thumb"></div>
        <div class="form-row"><label>团购价</label><input v-model.number="form.price" type="number" step="0.01"></div>
        <div class="form-row"><label>市场价</label><input v-model.number="form.market_price" type="number" step="0.01"></div>
        <div class="form-row"><label>库存</label><input v-model.number="form.stock" type="number" step="0.01"></div>
        <div class="form-row"><label>截止时间</label><input v-model="form.ends_at"></div>
        <div class="form-row"><label>摘要</label><textarea v-model="form.summary" rows="2" /></div>
        <div class="form-row"><label>详情</label><RichTextEditor v-model="form.content" min-height="160px" /></div>
        <div class="mf-actions">
          <button class="btn" type="submit" :disabled="loading">保存</button>
          <NuxtLink class="btn btn-outline" to="/member/group">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

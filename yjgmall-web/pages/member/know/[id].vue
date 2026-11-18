<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '编辑提问' })
const { api } = useApi()
const route = useRoute()
const router = useRouter()
const id = computed(() => Number(route.params.id))
const form = reactive({ title: '', content: '', reward: 0 })
const err = ref('')
const loading = ref(false)
const ready = ref(false)

onMounted(async () => {
  try {
    const res = await api<{ data: any }>(`/member/know/${id.value}`)
    form.title = res.data.title || ''
    form.content = res.data.content || ''
    form.reward = res.data.reward || 0
    ready.value = true
  } catch (e: any) {
    await appFail(apiErrorText(e, '加载失败'), '加载失败')
  }
})

async function submit() {
  err.value = ''
  loading.value = true
  try {
    await api(`/member/know/${id.value}`, { method: 'PUT', body: { ...form } })
    router.push('/member/know')
  } catch (e: any) {
    await appFail(apiErrorText(e, '保存失败'), '保存失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="panel">
    <h3>
      编辑提问
      <NuxtLink to="/member/know">返回列表</NuxtLink>
    </h3>
    <div class="body">
      <div v-if="err" class="alert alert-error">{{ err }}</div>
      <form v-if="ready" class="mf-form" @submit.prevent="submit">
        <section class="mf-sec">
          <div class="mf-sec-hd">问题</div>
          <div class="mf-sec-bd">
            <div class="form-row"><label>标题 <em>*</em></label><input v-model="form.title" required maxlength="120"></div>
            <div class="form-row"><label>悬赏积分</label><input v-model.number="form.reward" type="number" min="0"></div>
            <div class="form-row"><label>详情</label><RichTextEditor v-model="form.content" min-height="200px" /></div>
          </div>
        </section>
        <div class="mf-actions">
          <button class="btn" type="submit" :disabled="loading">{{ loading ? '保存中…' : '保存' }}</button>
          <NuxtLink class="btn btn-outline" to="/member/know">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

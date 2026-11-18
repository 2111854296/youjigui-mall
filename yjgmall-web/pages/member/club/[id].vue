<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '编辑帖子' })
const { api } = useApi()
const route = useRoute()
const router = useRouter()
const id = computed(() => Number(route.params.id))
const form = reactive({ title: '', circle: '行情圈', content: '' })
const err = ref('')
const loading = ref(false)
const ready = ref(false)

onMounted(async () => {
  try {
    const res = await api<{ data: any }>(`/member/club/${id.value}`)
    form.title = res.data.title || ''
    form.circle = res.data.circle || '行情圈'
    form.content = res.data.content || ''
    ready.value = true
  } catch (e: any) {
    await appFail(apiErrorText(e, '加载失败'), '加载失败')
  }
})

async function submit() {
  err.value = ''
  loading.value = true
  try {
    await api(`/member/club/${id.value}`, { method: 'PUT', body: { ...form } })
    router.push('/member/club')
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
      编辑帖子
      <NuxtLink to="/member/club">返回列表</NuxtLink>
    </h3>
    <div class="body">
      <div v-if="err" class="alert alert-error">{{ err }}</div>
      <form v-if="ready" class="mf-form" @submit.prevent="submit">
        <section class="mf-sec">
          <div class="mf-sec-hd">帖子</div>
          <div class="mf-sec-bd">
            <div class="form-row"><label>标题 <em>*</em></label><input v-model="form.title" required maxlength="120"></div>
            <div class="form-row">
              <label>圈子</label>
              <select v-model="form.circle">
                <option>行情圈</option>
                <option>技术圈</option>
                <option>综合</option>
              </select>
            </div>
            <div class="form-row"><label>内容</label><RichTextEditor v-model="form.content" min-height="220px" /></div>
          </div>
        </section>
        <div class="mf-actions">
          <button class="btn" type="submit" :disabled="loading">{{ loading ? '保存中…' : '保存并重新审核' }}</button>
          <NuxtLink class="btn btn-outline" to="/member/club">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

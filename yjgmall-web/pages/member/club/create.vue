<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '商圈发帖' })
const { api } = useApi()
const router = useRouter()
const form = reactive({ title: '', circle: '行情圈', content: '' })
const err = ref('')
const loading = ref(false)

async function submit() {
  err.value = ''
  loading.value = true
  try {
    await api('/member/club', { method: 'POST', body: { ...form } })
    await appOk('已提交。', '提交成功')
    router.push('/member/club')
  } catch (e: any) {
    await appFail(apiErrorText(e, '提交失败'), '提交失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="panel">
    <h3>
      商圈发帖
      <NuxtLink to="/member/club">返回列表</NuxtLink>
    </h3>
    <div class="body">
      <div v-if="err" class="alert alert-error">{{ err }}</div>
      <form class="mf-form" @submit.prevent="submit">
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
            <div class="form-row"><label>内容</label><RichTextEditor v-model="form.content" placeholder="分享行情、技术或行业见闻…" min-height="220px" /></div>
          </div>
        </section>
        <div class="mf-actions">
          <button class="btn" type="submit" :disabled="loading">{{ loading ? '提交中…' : '提交审核' }}</button>
          <NuxtLink class="btn btn-outline" to="/member/club">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

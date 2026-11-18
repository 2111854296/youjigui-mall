<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '我要提问' })
const { api } = useApi()
const router = useRouter()
const form = reactive({ title: '', content: '', reward: 0 })
const err = ref('')
const loading = ref(false)

async function submit() {
  err.value = ''
  loading.value = true
  try {
    await api('/member/know', { method: 'POST', body: { ...form } })
    await appOk('已提交。', '提交成功')
    router.push('/member/know')
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
      我要提问
      <NuxtLink to="/member/know">返回列表</NuxtLink>
    </h3>
    <div class="body">
      <div v-if="err" class="alert alert-error">{{ err }}</div>
      <form class="mf-form" @submit.prevent="submit">
        <section class="mf-sec">
          <div class="mf-sec-hd">问题</div>
          <div class="mf-sec-bd">
            <div class="form-row"><label>标题 <em>*</em></label><input v-model="form.title" required maxlength="120"></div>
            <div class="form-row"><label>悬赏积分</label><input v-model.number="form.reward" type="number" min="0"></div>
            <div class="form-row"><label>详情</label><RichTextEditor v-model="form.content" placeholder="补充问题背景、已尝试方法…" min-height="200px" /></div>
          </div>
        </section>
        <div class="mf-actions">
          <button class="btn" type="submit" :disabled="loading">{{ loading ? '提交中…' : '发布问题' }}</button>
          <NuxtLink class="btn btn-outline" to="/member/know">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

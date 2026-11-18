<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: ['auth', 'enterprise'] })
useHead({ title: '发布技术分享' })
const { api } = useApi()
const router = useRouter()
const error = ref('')
const form = reactive({ title: '', thumb: '', summary: '', content: '' })

async function submit() {
  error.value = ''
  try {
    await api('/member/articles', { method: 'POST', body: { ...form } })
    await appOk('已提交，审核通过后展示在信息资讯·技术分享。', '提交成功')
    router.push('/member/articles')
  } catch (e: any) {
    await appFail(apiErrorText(e, '发布失败'), '发布失败')
  }
}
</script>

<template>
  <div class="panel">
    <h3>
      发布技术分享
      <NuxtLink to="/member/articles">返回列表</NuxtLink>
    </h3>
    <div class="body">
      <div v-if="error" class="alert alert-error">{{ error }}</div>
      <form class="mf-form" @submit.prevent="submit">
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
          <button class="btn" type="submit">提交审核</button>
          <NuxtLink class="btn btn-outline" to="/member/articles">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '留言反馈' })
const { api } = useApi()
const { user } = useAuth()
const msg = ref('')
const err = ref('')
const loading = ref(false)
const form = reactive({
  title: '',
  content: '',
  contact_name: user.value?.name || '',
  contact_phone: user.value?.mobile || '',
})

async function submit() {
  err.value = ''
  msg.value = ''
  loading.value = true
  try {
    await api('/inquiries', {
      method: 'POST',
      body: {
        type: 'guestbook',
        title: form.title || '网站留言',
        content: form.content,
        contact_name: form.contact_name,
        contact_phone: form.contact_phone,
      },
      auth: !!user.value,
    })
    await appOk('留言已提交，我们会尽快回复。', '提交成功')
    form.title = ''
    form.content = ''
  } catch (e: any) {
    await appFail(apiErrorText(e, '提交失败'), '提交失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <span class="on">留言反馈</span>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="dt-card" style="max-width:640px">
          <div class="dt-h">留言反馈</div>
          <div v-if="msg" class="alert alert-success">{{ msg }}</div>
          <div v-if="err" class="alert alert-error">{{ err }}</div>
          <form @submit.prevent="submit">
            <div class="form-row"><label>主题</label><input v-model="form.title" placeholder="可选"></div>
            <div class="form-row"><label>内容</label><textarea v-model="form.content" required /></div>
            <div class="form-row"><label>联系人</label><input v-model="form.contact_name" required></div>
            <div class="form-row"><label>电话</label><input v-model="form.contact_phone" required></div>
            <button class="btn" type="submit" :disabled="loading">提交留言</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '邮件订阅' })
const { api } = useApi()
const smtpReady = ref(false)
const notice = ref('邮件订阅暂未开放。')

onMounted(async () => {
  try {
    const res: any = await api('/member/mail')
    smtpReady.value = res?.data?.smtp_ready === true
    notice.value = res?.data?.mail_notice || notice.value
  } catch {
    /* keep closed tip */
  }
})
</script>

<template>
  <div class="panel">
    <h3>邮件订阅</h3>
    <div class="body">
      <p class="muted tip">{{ notice }}</p>
      <p class="muted">站点发信邮箱配置完成后再开放此功能。贸易提醒仍可通过站内信查看。</p>
      <p style="margin-top:16px"><NuxtLink class="btn-soft-link" to="/member">返回会员中心</NuxtLink></p>
    </div>
  </div>
</template>

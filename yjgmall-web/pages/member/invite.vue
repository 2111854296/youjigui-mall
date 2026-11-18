<script setup lang="ts">
const { alert: appAlert } = useAppDialog()
definePageMeta({ layout: 'member' })
useHead({ title: '拉新邀请' })
const { api } = useApi()
const config = useRuntimeConfig()
const data = ref<any>(null)

onMounted(async () => {
  try {
    const res = await api('/vip/invite')
    data.value = (res as any)?.data || res
  } catch (e: any) {
    await appFail(apiErrorText(e, '加载失败'), '加载失败')
  }
})

const fullLink = computed(() => {
  if (!data.value?.invite_code) return ''
  if (import.meta.client) return `${location.origin}/register?invite=${data.value.invite_code}`
  return `/register?invite=${data.value.invite_code}`
})

async function copy() {
  if (!fullLink.value) return
  await navigator.clipboard.writeText(fullLink.value)
  await appAlert({ title: '复制成功', message: '邀请链接已复制到剪贴板' })
}
</script>

<template>
  <div class="panel">
    <h3>拉新激励机制</h3>
    <p class="muted">通过邀请码拉新：注册奖励 7 天同档（免费企业奖励 3 天白银）；对方升级会员再奖 1 个月（免费企业奖 15 天白银）。</p>
    <div v-if="data" class="box">
      <p>我的邀请码：<b>{{ data.invite_code }}</b></p>
      <p class="link">{{ fullLink }}</p>
      <button type="button" class="btn" @click="copy">复制链接</button>
    </div>

    <h4>已邀请</h4>
    <ul>
      <li v-for="u in (data?.invitees || [])" :key="u.id">
        {{ u.company_name || u.name || u.username }} · {{ String(u.created_at || '').slice(0, 10) }}
        <span v-if="u.vip_grade_code"> · {{ u.vip_grade_code }}</span>
      </li>
      <li v-if="!(data?.invitees || []).length" class="muted">暂无</li>
    </ul>

    <h4>奖励记录</h4>
    <ul>
      <li v-for="r in (data?.rewards || [])" :key="r.id">
        {{ r.reward_type }} · +{{ r.days_granted }}天 · {{ r.note }}
      </li>
      <li v-if="!(data?.rewards || []).length" class="muted">暂无</li>
    </ul>
  </div>
</template>

<style scoped>
.muted { color: var(--ink-3); font-size: 13px; }
.box { background: #F8FAFD; padding: 14px; border-radius: 12px; margin: 12px 0; }
.link { word-break: break-all; font-size: 13px; color: var(--blue); }
h4 { margin: 18px 0 8px; }
ul { margin: 0; padding-left: 18px; font-size: 13.5px; }
</style>

<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '收到的投递' })
const { api } = useApi()
const router = useRouter()
const msg = ref('')
const { data, refresh } = useMemberData('job-recv', () => api('/member/job-received-applications'))
const items = computed(() => (data.value as any)?.data || [])
const statusMap: Record<string, string> = { pending: '待处理', viewed: '已查看', accepted: '已通过', rejected: '未通过' }
const vipGateOpen = ref(false)
const vipGateMsg = ref('本月简历联系次数已用完，请升级会员或下月再试。')
const unlockBusy = ref(0)

const err = ref('')
async function markSeen(item: any) {
  if (Number(item.is_read) || item.status !== 'pending') return
  try {
    await api(`/member/job-applications/${item.id}/read`, { method: 'POST' })
    item.is_read = 1
  } catch (e: any) {
    await appFail(apiErrorText(e, '操作失败'), '保存失败')
  }
}

async function setStatus(id: number, status: string) {
  err.value = ''
  msg.value = ''
  try {
    await api(`/member/job-applications/${id}/status`, { method: 'POST', body: { status } })
    await appOk(status === 'pending' ? '已恢复待处理。' : '已处理，首页待办会减少。', '操作成功')
    await refresh()
  } catch (e: any) {
    await appFail(apiErrorText(e, '操作失败'), '保存失败')
  }
}

async function unlockResume(item: any) {
  const resumeId = Number(item.resume?.id || item.resume_id || 0)
  if (!resumeId) {
    await appFail('无简历信息', '解锁失败')
    return
  }
  unlockBusy.value = item.id
  try {
    const res = await api('/vip/unlock-contact', {
      method: 'POST',
      body: { target_type: 'resume', target_id: resumeId },
    })
    const contact = (res as any)?.data?.contact || {}
    if (item.resume) {
      item.resume.mobile = contact.mobile || contact.phone || item.resume.mobile
      item.resume.email = contact.email || item.resume.email
      item.resume.contact_masked = false
      item.resume.contact_unlocked = true
    }
    await appOk(contact.mobile ? `联系方式：${contact.mobile}` : '已解锁', '解锁成功')
  } catch (e: any) {
    const text = apiErrorText(e, '解锁失败')
    if (/次数|用完|升级|quota/i.test(text)) {
      vipGateMsg.value = text
      vipGateOpen.value = true
    } else {
      await appFail(text, '解锁失败')
    }
  } finally {
    unlockBusy.value = 0
  }
}
</script>

<template>
  <div class="panel">
    <h3>收到的投递 <NuxtLink to="/member/jobs">招聘管理</NuxtLink></h3>
    <div class="body">
      <div v-if="msg" class="alert alert-success">{{ msg }}</div>
      <div v-if="err" class="alert alert-error">{{ err }}</div>
      <table class="table">
        <thead><tr><th>职位</th><th>求职者</th><th>简历</th><th>状态</th><th>操作</th></tr></thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>
              <NuxtLink v-if="item.job_post_id" :to="`/job/${item.job_post_id}`">{{ item.job?.title || '查看职位' }}</NuxtLink>
              <span v-else>{{ item.job?.title || '-' }}</span>
            </td>
            <td>{{ item.resume?.truename || item.applicant?.name || item.applicant?.username || item.applicant_user_id }}</td>
            <td>
              <NuxtLink
                v-if="item.resume?.id || item.resume_id"
                class="link"
                :to="`/talent/${item.resume?.id || item.resume_id}`"
                @click="markSeen(item)"
              >{{ item.resume?.title || '查看简历' }}</NuxtLink>
              <button v-else type="button" class="link" @click="markSeen(item)">{{ item.resume?.title || '查看简历' }}</button>
              <div class="muted" style="font-size:12px;margin-top:4px">
                <template v-if="item.resume?.contact_unlocked">
                  {{ item.resume?.mobile }} {{ item.resume?.email }}
                </template>
                <template v-else>
                  {{ item.resume?.mobile || '联系方式已隐藏' }}
                  <button type="button" class="link" :disabled="unlockBusy === item.id" @click="unlockResume(item)">
                    {{ unlockBusy === item.id ? '解锁中…' : '解锁联系方式' }}
                  </button>
                </template>
              </div>
              <div v-if="item.resume?.mask_notice" class="muted" style="font-size:12px">{{ item.resume.mask_notice }}</div>
            </td>
            <td>{{ statusMap[item.status] || item.status }}</td>
            <td class="acts">
              <button v-if="item.status === 'pending'" type="button" class="link" @click="setStatus(item.id, 'viewed')">标为已查看</button>
              <button type="button" class="link" @click="setStatus(item.id, 'accepted')">通过</button>
              <button type="button" class="link" @click="setStatus(item.id, 'rejected')">拒绝</button>
            </td>
          </tr>
          <tr v-if="!items.length"><td colspan="5" class="muted">暂无收到的投递</td></tr>
        </tbody>
      </table>
    </div>
    <VipGateModal v-model="vipGateOpen" title="会员权益不足" :message="vipGateMsg" @join="router.push('/member/grade')" />
  </div>
</template>

<style scoped>
.acts { display: flex; flex-wrap: wrap; gap: 10px; }
.link { background: none; border: 0; padding: 0; color: var(--blue, #2468f2); font: inherit; font-weight: 700; cursor: pointer; }
.link:disabled { opacity: .6; cursor: wait; }
</style>

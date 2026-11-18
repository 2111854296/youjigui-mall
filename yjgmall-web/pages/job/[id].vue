<script setup lang="ts">
definePageMeta({ layout: 'channel' })
const route = useRoute()
const config = useRuntimeConfig()
const { api, token } = useApi()
const { user } = useAuth()
const id = route.params.id as string
const { data, error, refresh } = await useAsyncData(`job-${id}`, () =>
  $fetch(`${config.public.apiBase}/jobs/${id}`, {
    headers: token.value ? { Authorization: `Bearer ${token.value}` } : {},
  }),
)
const item = computed(() => (data.value as any)?.data)
useHead(() => ({ title: item.value?.title || '招聘详情' }))

const showApply = ref(false)
const resumes = ref<any[]>([])
const resumeId = ref(0)
const applyHint = ref('')
const err = ref('')
const msg = ref('')
const loading = ref(false)
const unlockLoading = ref(false)
const unlockedPhone = ref('')
const vipGateOpen = ref(false)
const vipGateMsg = ref('当前会员权益不足，请升级企业会员。')
const router = useRouter()

async function unlockJobContact() {
  if (!user.value) {
    navigateTo(`/login?redirect=/job/${id}`)
    return
  }
  unlockLoading.value = true
  err.value = ''
  try {
    const res = await api('/vip/unlock-contact', {
      method: 'POST',
      body: { target_type: 'job', target_id: Number(id) },
    })
    unlockedPhone.value = (res as any)?.data?.contact?.phone || (res as any)?.data?.contact?.mobile || ''
    await refresh()
  } catch (e: any) {
    const text = apiErrorText(e, '解锁失败')
    if (/次数|用完|升级|quota/i.test(text)) {
      vipGateMsg.value = text
      vipGateOpen.value = true
    } else {
      await appFail(text, '操作失败')
    }
  } finally {
    unlockLoading.value = false
  }
}

async function openApply() {
  if (!user.value) {
    navigateTo(`/login?redirect=/job/${id}`)
    return
  }
  err.value = ''
  msg.value = ''
  try {
    const res = await api<any>(`/jobs/${id}/apply`)
    resumes.value = res?.data?.resumes || []
    resumeId.value = resumes.value[0]?.id || 0
    applyHint.value = String(res?.data?.hint || '')
    showApply.value = true
  } catch (e: any) {
    await appFail(apiErrorText(e, '无法投递'), '操作失败')
  }
}

async function submitApply() {
  if (!resumeId.value) {
    await appFail('请先创建简历', '投递失败')
    return
  }
  loading.value = true
  err.value = ''
  try {
    await api(`/jobs/${id}/apply`, { method: 'POST', body: { resume_id: resumeId.value } })
    await appOk('投递成功。', '提交成功')
    showApply.value = false
  } catch (e: any) {
    await appFail(apiErrorText(e, '投递失败'), '投递失败')
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
          <NuxtLink to="/jobs">招聘专区</NuxtLink>
          <NuxtLink to="/job">职位列表</NuxtLink>
          <span class="on">{{ item?.title || '详情' }}</span>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>投递提示：</b>
        请确认岗位与企业信息后再投递简历；站内交谈已关闭，请通过投递或解锁电话沟通。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div v-if="error" class="blk empty">信息不存在</div>
        <template v-else-if="item">
          <div class="dt-layout">
            <div class="dt-card">
              <div class="dt-sum">
                <h1>{{ item.title }}</h1>
                <p class="meta">{{ item.company_name }} · {{ item.area }} · {{ item.salary }} · 浏览 {{ item.views }}</p>
                <table class="dt-kv">
                  <tr><th>学历</th><td>{{ item.education || '-' }}</td></tr>
                  <tr><th>经验</th><td>{{ item.experience || '-' }}</td></tr>
                  <tr><th>人数</th><td>{{ item.headcount || '-' }}</td></tr>
                  <tr v-if="item.contact_name || item.contact_phone || unlockedPhone"><th>联系</th><td>
                    {{ item.contact_name }}
                    {{ unlockedPhone || item.contact_phone || '' }}
                    <button
                      v-if="item.contact_masked && !unlockedPhone"
                      type="button"
                      class="btn-soft"
                      style="margin-left:8px"
                      :disabled="unlockLoading"
                      @click="unlockJobContact"
                    >{{ unlockLoading ? '…' : '解锁电话' }}</button>
                  </td></tr>
                </table>
                <p v-if="item.summary" style="margin:12px 0 0;font-size:14px;color:var(--ink-2);line-height:1.6">{{ item.summary }}</p>
                <div class="dt-acts">
                  <button class="btn" type="button" @click="openApply">投递简历</button>
                  <FavoriteButton type="job" :id="item.id" />
                  <NuxtLink class="btn-soft-link" to="/member/resumes">我的简历</NuxtLink>
                  <NuxtLink class="btn-soft-link" to="/jobs">返回专区</NuxtLink>
                </div>
              </div>
              <div class="dt-body" style="margin-top:16px" v-html="item.content" />
              <div v-if="msg" class="alert alert-success" style="margin-top:12px">{{ msg }}</div>
              <div v-if="err" class="alert alert-error" style="margin-top:12px">{{ err }}</div>
            </div>
            <aside class="dt-side">
              <div class="side-blk">
                <h3>企业信息</h3>
                <p style="margin:0 0 8px;font-weight:700">{{ item.company_name || '—' }}</p>
                <p class="muted" style="font-size:13px;margin:0">工作地点：{{ item.area || '—' }}</p>
                <p class="muted" style="font-size:13px;margin:6px 0 0">薪资：{{ item.salary || '面议' }}</p>
              </div>
            </aside>
          </div>

          <div v-if="showApply" class="blk" style="margin-top:14px">
            <div class="b-head"><b>选择简历投递</b></div>
            <p v-if="!resumes.length" class="empty" style="padding:12px 0;text-align:left">
              {{ applyHint || '暂无已通过审核的简历' }}，请先
              <NuxtLink to="/member/resumes">管理简历</NuxtLink>
              或
              <NuxtLink to="/member/resumes/create">创建简历</NuxtLink>
              （提交后由平台审核通过再投递）
            </p>
            <template v-else>
              <select v-model.number="resumeId" style="width:100%;max-width:420px;padding:8px;margin-bottom:10px">
                <option v-for="r in resumes" :key="r.id" :value="r.id">{{ r.title }} · {{ r.truename || '' }}</option>
              </select>
              <div class="dt-acts" style="margin-top:0">
                <button class="btn" type="button" :disabled="loading" @click="submitApply">{{ loading ? '提交中…' : '确认投递' }}</button>
                <button class="btn-soft" type="button" @click="showApply = false">取消</button>
              </div>
            </template>
          </div>
        </template>
      </div>
    </div>

    <VipGateModal v-model="vipGateOpen" title="会员权益不足" :message="vipGateMsg" @join="router.push('/member/grade')" />
  </div>
</template>

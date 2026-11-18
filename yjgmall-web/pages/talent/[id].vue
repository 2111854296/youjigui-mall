<script setup lang="ts">
definePageMeta({ layout: 'channel' })
const route = useRoute()
const config = useRuntimeConfig()
const { api, token } = useApi()
const { user } = useAuth()
const id = route.params.id as string

const { data, error, refresh } = await useAsyncData(`talent-${id}`, () =>
  $fetch(`${config.public.apiBase}/talents/${id}`, {
    headers: token.value ? { Authorization: `Bearer ${token.value}` } : {},
  }),
)
const item = computed(() => (data.value as any)?.data)
useHead(() => ({ title: item.value?.title ? `${item.value.title} - 人才详情` : '人才详情' }))

const unlockLoading = ref(false)
const vipGateOpen = ref(false)
const vipGateMsg = ref('本月简历联系次数已用完，请升级会员。')
const router = useRouter()

const salaryText = computed(() => {
  const min = Number(item.value?.minsalary || 0)
  const max = Number(item.value?.maxsalary || 0)
  let a = min
  let b = max
  if (a > 1000) a = Math.round(a / 1000)
  if (b > 1000) b = Math.round(b / 1000)
  if (a || b) return `${a || b}-${b || a}K`
  return '面议'
})

async function unlockContact() {
  if (!user.value) {
    navigateTo(`/login?redirect=/talent/${id}`)
    return
  }
  unlockLoading.value = true
  try {
    await api('/vip/unlock-contact', {
      method: 'POST',
      body: { target_type: 'resume', target_id: Number(id) },
    })
    await refresh()
    await appOk('已解锁联系方式', '操作成功')
  } catch (e: any) {
    const msg = apiErrorText(e, '解锁失败')
    if (/次数|用完|升级|quota/i.test(msg)) {
      vipGateMsg.value = msg
      vipGateOpen.value = true
    } else {
      await appFail(msg, '解锁失败')
    }
  } finally {
    unlockLoading.value = false
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
          <span class="on">{{ item?.truename || item?.title || '人才详情' }}</span>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>招聘提示：</b>
        站内交谈已关闭；查看手机号需消耗本月简历联系次数，请通过解锁电话沟通。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div v-if="error" class="blk empty">人才信息不存在或未公开</div>
        <template v-else-if="item">
          <div class="dt-layout">
            <div class="dt-card">
              <div class="dt-sum">
                <h1>{{ item.truename || '求职者' }}</h1>
                <p class="meta">
                  期望：{{ item.title || '—' }}
                  · {{ item.area || '—' }}
                  · {{ item.experience || '经验不限' }}
                  · {{ item.education || '学历不限' }}
                  · 期望 {{ salaryText }}
                </p>
                <table class="dt-kv">
                  <tr><th>求职意向</th><td>{{ item.title || '—' }}</td></tr>
                  <tr><th>工作地区</th><td>{{ item.area || '—' }}</td></tr>
                  <tr><th>工作经验</th><td>{{ item.experience || '—' }}</td></tr>
                  <tr><th>学历</th><td>{{ item.education || '—' }}</td></tr>
                  <tr><th>期望薪资</th><td>{{ salaryText }}</td></tr>
                  <tr>
                    <th>联系电话</th>
                    <td>
                      <template v-if="item.contact_unlocked">{{ item.mobile || '—' }}</template>
                      <template v-else>
                        {{ item.mobile || '已隐藏' }}
                        <button type="button" class="btn-soft" style="margin-left:8px" :disabled="unlockLoading" @click="unlockContact">
                          {{ unlockLoading ? '…' : '解锁电话' }}
                        </button>
                      </template>
                    </td>
                  </tr>
                  <tr v-if="item.contact_unlocked && item.email"><th>邮箱</th><td>{{ item.email }}</td></tr>
                </table>
                <p v-if="item.mask_notice" class="muted" style="margin:10px 0 0;font-size:13px">{{ item.mask_notice }}</p>
                <p v-if="item.summary" style="margin:12px 0 0;font-size:14px;color:var(--ink-2);line-height:1.6">{{ item.summary }}</p>
                <div class="dt-acts">
                  <FavoriteButton type="resume" :id="item.id" label="收藏人才" saved-label="取消收藏" />
                  <NuxtLink class="btn-soft-link" to="/jobs">返回招聘专区</NuxtLink>
                  <NuxtLink class="btn-soft-link" to="/member/jobs">招聘管理</NuxtLink>
                  <NuxtLink class="btn-soft-link" to="/member/favorites?type=resume">我的收藏</NuxtLink>
                </div>
              </div>
              <div v-if="item.content" class="dt-body" style="margin-top:16px" v-html="item.content" />
              <div v-else-if="!item.resume_full_visible" class="blk" style="margin-top:14px">
                <p class="empty" style="padding:16px 0;text-align:left">完整经历仅对白银及以上会员开放，请升级会员后查看。</p>
                <NuxtLink class="btn" to="/member/grade">查看会员权益</NuxtLink>
              </div>
            </div>
            <aside class="dt-side">
              <div class="side-blk">
                <h3>沟通建议</h3>
                <p class="muted" style="font-size:13px;line-height:1.6;margin:0">
                  站内交谈已关闭；确需联系请解锁电话（计入本月额度）。
                </p>
              </div>
            </aside>
          </div>
        </template>
      </div>
    </div>

    <VipGateModal v-model="vipGateOpen" title="会员权益不足" :message="vipGateMsg" @join="router.push('/member/grade')" />
  </div>
</template>

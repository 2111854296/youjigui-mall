<script setup lang="ts">
const { confirm: appConfirm } = useAppDialog()
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '招聘管理' })
const { api } = useApi()
const router = useRouter()
const { data, refresh } = useMemberData('member-jobs', () => api('/member/jobs'))
const items = computed(() => (data.value as any)?.data || [])
const ent = computed(() => (data.value as any)?.entitlement || {})
const vipGateOpen = ref(false)
const vipGateMsg = ref('当前会员权益不足，请升级企业会员。')
const busyId = ref(0)

function statusLabel(s: string) {
  return ({ pending: '待审', approved: '已发布', offline: '下架', rejected: '拒绝' } as Record<string, string>)[s] || s
}

const quotaLine = computed(() => {
  const lim = Number(ent.value.jobs_online_limit ?? 0)
  const used = Number(ent.value.jobs_online_used ?? 0)
  if (lim <= 0) return `在线职位 ${used} / 不限`
  return `在线职位 ${used} / ${lim}`
})

const stickyHint = computed(() => {
  const gift = Number(ent.value.job_sticky_remain_month ?? 0)
  const price = Number(ent.value.top_price ?? 0)
  if (gift > 0) return `本月赠送置顶剩余 ${gift} 次`
  if (price > 0) return `置顶 ${price} 元/条（扣余额）`
  return '置顶需升级会员'
})

async function remove(id: number) {
  if (!(await appConfirm({ title: '删除招聘', message: '确认删除该招聘？', tone: 'danger', confirmText: '删除' }))) return
  await api(`/member/jobs/${id}`, { method: 'DELETE' })
  await appOk('已删除。', '操作成功')
  await refresh()
}

async function setStatus(id: number, status: 'approved' | 'offline') {
  busyId.value = id
  try {
    await api(`/member/jobs/${id}/status`, { method: 'POST', body: { status } })
    await appOk(status === 'approved' ? '已上架' : '已下架', '操作成功')
    await refresh()
  } catch (e: any) {
    const msg = apiErrorText(e, '操作失败')
    if (/上限|升级/.test(msg)) {
      vipGateMsg.value = msg
      vipGateOpen.value = true
    } else {
      await appFail(msg, '操作失败')
    }
  } finally {
    busyId.value = 0
  }
}

async function sticky(id: number) {
  const gift = Number(ent.value.job_sticky_remain_month ?? 0)
  const price = Number(ent.value.top_price ?? 0)
  const tip = gift > 0 ? '将消耗 1 次本月赠送置顶（3 天）' : `将扣余额 ${price} 元置顶 3 天`
  if (!(await appConfirm({ title: '职位置顶', message: tip, confirmText: '确认置顶' }))) return
  busyId.value = id
  try {
    const res = await api('/vip/jobs/sticky', { method: 'POST', body: { job_id: id } })
    await appOk((res as any)?.data?.message || '已置顶', '操作成功')
    await refresh()
  } catch (e: any) {
    const msg = apiErrorText(e, '置顶失败')
    if (/余额|升级|权益|次数/.test(msg)) {
      vipGateMsg.value = msg
      vipGateOpen.value = true
    } else {
      await appFail(msg, '置顶失败')
    }
  } finally {
    busyId.value = 0
  }
}
</script>

<template>
  <div class="panel">
    <h3>
      招聘管理
      <NuxtLink class="back-list" to="/member/jobs/create">发布招聘</NuxtLink>
    </h3>
    <div class="body">
      <p class="muted" style="margin:0 0 12px">
        {{ quotaLine }} · {{ stickyHint }}
        · <NuxtLink to="/member/jobs/create">发布招聘</NuxtLink>
        · <NuxtLink to="/member/job-received">收到的投递</NuxtLink>
        · <NuxtLink to="/jobs">招聘专区</NuxtLink>
      </p>
      <p class="muted" style="margin:0 0 12px;font-size:12px">职位由平台后台审核通过后才会在招聘专区展示；商户后台可下架/重新上架已通过职位，不能跳过审核。浏览量在详情打开时累计；投递份数可点「简历」列查看。</p>
      <table class="table">
        <thead>
          <tr><th>职位</th><th>地区</th><th>薪资</th><th>状态</th><th>浏览</th><th>简历</th><th>操作</th></tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>
              {{ item.title }}
              <span v-if="item.is_sticky" class="muted"> · 置顶</span>
            </td>
            <td>{{ item.area || '—' }}</td>
            <td>{{ item.salary || '—' }}</td>
            <td>{{ statusLabel(item.status) }}</td>
            <td>{{ item.views }}</td>
            <td>
              <NuxtLink to="/member/job-received">{{ item.received_count || 0 }} 份</NuxtLink>
            </td>
            <td>
              <NuxtLink :to="`/member/jobs/${item.id}`">编辑</NuxtLink>
              <template v-if="item.status === 'approved'">
                · <NuxtLink :to="`/job/${item.id}`" target="_blank">查看</NuxtLink>
                · <a href="#" @click.prevent="busyId !== item.id && sticky(item.id)">置顶</a>
                · <a href="#" @click.prevent="busyId !== item.id && setStatus(item.id, 'offline')">下架</a>
              </template>
              <template v-else-if="item.status === 'offline'">
                · <a href="#" @click.prevent="busyId !== item.id && setStatus(item.id, 'approved')">上架</a>
              </template>
              <template v-else-if="item.status === 'pending' || item.status === 'rejected'">
                · <span class="muted">{{ item.status === 'pending' ? '等待平台审核' : '已拒绝，请修改后重新提交' }}</span>
              </template>
              ·
              <a href="#" @click.prevent="remove(item.id)">删除</a>
            </td>
          </tr>
          <tr v-if="!items.length"><td colspan="7" class="muted">暂无招聘，点击右上角发布</td></tr>
        </tbody>
      </table>
    </div>
    <VipGateModal v-model="vipGateOpen" title="会员权益不足" :message="vipGateMsg" @join="router.push('/member/grade')" />
  </div>
</template>

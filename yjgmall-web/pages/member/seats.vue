<script setup lang="ts">
const { confirm: appConfirm } = useAppDialog()
definePageMeta({ layout: 'member' })
useHead({ title: '企业席位管理' })
const { api } = useApi()
const route = useRoute()
const msg = ref('')
const err = ref('')
const loading = ref(false)
const seats = ref<any[]>([])
const seatLimit = ref(1)
const form = reactive({ mobile: '', email: '' })

const statusLabel: Record<string, string> = {
  pending: '待接受',
  active: '已加入',
  revoked: '已回收',
}

function inviteLink(token: string) {
  if (!token) return ''
  const origin = import.meta.client ? window.location.origin : ''
  return `${origin}/member/seats?token=${encodeURIComponent(token)}`
}

async function copyLink(token: string) {
  const link = inviteLink(token)
  try {
    await navigator.clipboard.writeText(link)
    await appOk('邀请链接已复制。', '复制成功')
  } catch {
    msg.value = `请手动复制：${link}`
  }
}

async function load() {
  const res = await api('/vip/seats')
  const d = (res as any)?.data || res
  seats.value = d.items || []
  seatLimit.value = d.seat_limit || 1
}

async function invite() {
  err.value = ''
  msg.value = ''
  loading.value = true
  try {
    const res = await api('/vip/seats/invite', { method: 'POST', body: { ...form } })
    const path = (res as any)?.accept_path
    const token = (res as any)?.data?.invite_token || (res as any)?.invite_token
    const created = String((res as any)?.message || '邀请已创建')
    if (token) {
      await appOk(created.endsWith('。') ? created : `${created}。`, '操作成功')
      await copyLink(token)
    } else if (path) {
      await appOk(`${created}，请将链接发给成员：${path}`, '操作成功')
    } else {
      await appOk(created.endsWith('。') ? created : `${created}。`, '操作成功')
    }
    form.mobile = ''
    form.email = ''
    await load()
  } catch (e: any) {
    await appFail(apiErrorText(e, '邀请失败'), '操作失败')
  } finally {
    loading.value = false
  }
}

async function revoke(id: number) {
  if (!(await appConfirm({ title: '回收席位', message: '确认回收该席位？', tone: 'danger', confirmText: '确认回收' }))) return
  await api(`/vip/seats/${id}/revoke`, { method: 'POST' })
  await load()
}

async function acceptToken(token: string) {
  err.value = ''
  try {
    const res = await api('/vip/seats/accept', { method: 'POST', body: { token } })
    await appOk((res as any)?.data?.message || (res as any)?.message || '已加入席位。', '操作成功')
  } catch (e: any) {
    await appFail(apiErrorText(e, '接受失败'), '操作失败')
  }
}

onMounted(async () => {
  try {
    await load()
  } catch (e: any) {
    await appFail(apiErrorText(e, '加载失败（席位成员无需管理）'), '加载失败')
  }
  const token = String(route.query.token || '')
  if (token) await acceptToken(token)
})
</script>

<template>
  <div class="panel">
    <h3>企业授权席位</h3>
    <p class="muted">席位上限 {{ seatLimit }}（含企业主账号）。黄金 2 / 星钻 3。额度全企业共享，不重复扣次。</p>
    <p class="muted">「生成邀请链接」不会发短信/邮件，请把链接复制发给对方，对方打开并登录后即可加入。</p>
    <div v-if="msg" class="alert alert-success">{{ msg }}</div>
    <div v-if="err" class="alert alert-error">{{ err }}</div>

    <form class="row" @submit.prevent="invite">
      <input v-model="form.mobile" placeholder="成员手机（选填，仅备注）">
      <input v-model="form.email" placeholder="或邮箱（选填，仅备注）">
      <button class="btn" type="submit" :disabled="loading">生成邀请链接</button>
    </form>

    <table class="table" style="margin-top:16px">
      <thead>
        <tr><th>状态</th><th>成员</th><th>手机/邮箱</th><th>操作</th></tr>
      </thead>
      <tbody>
        <tr v-for="s in seats" :key="s.id">
          <td>{{ statusLabel[s.status] || s.status }}</td>
          <td>{{ s.member_name || s.member_user_id || '—' }}</td>
          <td>{{ s.mobile || s.email || '-' }}</td>
          <td class="ops">
            <button
              v-if="s.status === 'pending' && s.invite_token"
              type="button"
              class="btn btn--outline"
              @click="copyLink(s.invite_token)"
            >复制链接</button>
            <button type="button" class="btn btn--outline" @click="revoke(s.id)">回收</button>
          </td>
        </tr>
        <tr v-if="!seats.length"><td colspan="4" class="muted">暂无额外席位邀请</td></tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.muted { color: var(--ink-3); font-size: 13px; }
.row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
.row input { flex: 1; min-width: 140px; padding: 8px 10px; border: 1px solid var(--line); border-radius: 8px; }
.ops { display: flex; gap: 6px; flex-wrap: wrap; }
</style>

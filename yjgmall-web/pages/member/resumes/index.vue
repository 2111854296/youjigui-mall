<script setup lang="ts">
const { confirm: appConfirm } = useAppDialog()
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '我的简历' })
const { api } = useApi()
const { data, refresh } = useMemberData('member-resumes', () => api('/member/resumes'))
onMounted(() => {
  api('/member/notice-seen', { method: 'POST', body: { type: 'favored' } }).catch(() => {})
})
const items = computed(() => (data.value as any)?.data || [])
const busyId = ref(0)

function openLabel(open: any) {
  const v = String(open ?? 'public').toLowerCase()
  return v === 'private' || v === '0' ? '未公开' : '已公开'
}

function statusLabel(s: string) {
  return ({ pending: '待平台审核', approved: '已通过', published: '已通过', rejected: '已拒绝', offline: '已下架' } as Record<string, string>)[s] || s || '—'
}

async function remove(id: number) {
  if (!(await appConfirm({ title: '删除简历', message: '确认删除该简历？', tone: 'danger', confirmText: '删除' }))) return
  await api(`/member/resumes/${id}`, { method: 'DELETE' })
  await appOk('已删除。', '操作成功')
  await refresh()
}

async function setDefault(id: number) {
  busyId.value = id
  try {
    await api(`/member/resumes/${id}/default`, { method: 'POST' })
    await appOk('已设为默认简历', '操作成功')
    await refresh()
  } catch (e: any) {
    await appFail(apiErrorText(e, '设置失败'), '操作失败')
  } finally {
    busyId.value = 0
  }
}
</script>

<template>
  <div class="panel">
    <h3>
      <span class="h-left">
        <span>我的简历</span>
        <NuxtLink to="/member/job-applications">我投递的</NuxtLink>
        <NuxtLink to="/member/job-received">收到的投递</NuxtLink>
        <NuxtLink to="/member/favorites?type=resume">收藏的人才</NuxtLink>
      </span>
      <NuxtLink class="back-list" to="/member/resumes/create">新建简历</NuxtLink>
    </h3>
    <div class="body">
      <p class="muted tip">简历由平台后台审核；通过后方可投递职位，公开简历才会进入人才库。商户后台仅处理收到的投递，不能代替平台上站。</p>
      <table class="table">
        <thead>
          <tr><th>标题</th><th>姓名</th><th>地区</th><th>公开</th><th>审核</th><th>操作</th></tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>
              <NuxtLink class="ttl" :to="`/member/resumes/${item.id}`">{{ item.title }}</NuxtLink>
              <span v-if="Number(item.is_default)" class="def">默认</span>
            </td>
            <td>{{ item.truename || '—' }}</td>
            <td>{{ item.area || '—' }}</td>
            <td>{{ openLabel(item.open) }}</td>
            <td>
              <span :class="['st', item.status]">{{ statusLabel(item.status) }}</span>
            </td>
            <td>
              <NuxtLink :to="`/member/resumes/${item.id}`">编辑</NuxtLink>
              <template v-if="item.status === 'approved' || item.status === 'published'">
                · <NuxtLink :to="`/talent/${item.id}`" target="_blank">查看</NuxtLink>
              </template>
              ·
              <a v-if="!Number(item.is_default)" href="#" @click.prevent="busyId !== item.id && setDefault(item.id)">设默认</a>
              <template v-if="!Number(item.is_default)"> · </template>
              <a href="#" @click.prevent="remove(item.id)">删除</a>
            </td>
          </tr>
          <tr v-if="!items.length"><td colspan="6" class="muted">暂无简历，请新建并提交平台审核；通过后可投递，公开后进入人才库</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.h-left { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; }
.ttl { color: #1443b8; font-weight: 700; text-decoration: none; }
.ttl:hover { text-decoration: underline; }
.def {
  display: inline-block; margin-left: 8px; padding: 1px 6px; border-radius: 4px;
  background: #eef4ff; color: #2769dc; font-size: 12px; font-weight: 600;
}
.tip { margin: 0 0 12px; font-size: 12px; line-height: 1.5; }
.st { font-weight: 600; }
.st.pending { color: #c27803; }
.st.approved, .st.published { color: #047857; }
.st.rejected { color: #b91c1c; }
.st.offline { color: #64748b; }
</style>

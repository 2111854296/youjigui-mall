<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '站内通知' })

const { api } = useApi()
const page = ref(1)
const keyword = ref('')
const onlyUnread = ref(false)
const openId = ref<number | null>(null)
const replyText = ref('')
const replyLoading = ref(false)

const { data, refresh, pending } = useMemberData(
  'my-messages',
  () => api('/member/messages', { query: { box: 'inbox', page: page.value } }),
  { watch: [page] },
)

const items = computed(() => (data.value as any)?.data || [])
const meta = computed(() => (data.value as any)?.meta || { total: items.value.length, current_page: 1, last_page: 1 })
const unreadOnPage = computed(() => items.value.filter((i: any) => !Number(i.is_read)).length)
const shown = computed(() => {
  const q = keyword.value.trim()
  return items.value.filter((item: any) => {
    if (onlyUnread.value && Number(item.is_read)) return false
    if (!q) return true
    const blob = `${item.title || ''} ${item.content || ''} ${peerName(item)}`
    return blob.includes(q)
  })
})
const opened = computed(() => shown.value.find((i: any) => i.id === openId.value) || items.value.find((i: any) => i.id === openId.value) || null)

function peerName(item: any) {
  return item.from?.name || item.from?.username || item.from?.company_name || '平台通知'
}

function peerId(item: any) {
  return item.from?.username || item.from?.id || ''
}

/** 询盘类通知可回复（提交后进平台审核） */
function canReplyInquiry(item: any) {
  const title = String(item?.title || '')
  return title.includes('收到询盘') || (String(item?.action_to || '').includes('inquiries') && title.includes('询盘'))
}

async function openItem(item: any) {
  openId.value = item.id
  replyText.value = ''
  if (!Number(item.is_read)) {
    try {
      await api(`/member/messages/${item.id}/read`, { method: 'POST' })
      item.is_read = 1
      await refresh()
    } catch { /* 阅读状态失败不打断正文 */ }
  }
}

async function submitInquiryReply(item: any) {
  const content = replyText.value.trim()
  if (!content) {
    await appFail('请填写回复内容', '提示')
    return
  }
  replyLoading.value = true
  try {
    const title = String(item.title || '收到询盘')
    const res: any = await api('/member/messages', {
      method: 'POST',
      body: {
        to: String(peerId(item) || ''),
        title: title.startsWith('回复：') ? title : `回复：${title}`,
        content,
      },
    })
    await appOk(res?.message || '回复已提交，平台审核通过后将转达询盘方', '已提交审核')
    replyText.value = ''
    openId.value = null
    await refresh()
  } catch (e: any) {
    await appFail(apiErrorText(e, '提交失败'), '提交失败')
  } finally {
    replyLoading.value = false
  }
}
</script>

<template>
  <div class="panel mail">
    <h3>站内通知</h3>
    <div class="body">
      <p class="lead muted">会员自由互发已关闭。询盘通知可以回复，提交后需平台审核，通过后才会转达对方。也可到「询盘管理」处理。</p>

      <div class="toolbar">
        <label class="search">
          <input v-model="keyword" placeholder="搜索标题或正文">
        </label>
        <button type="button" class="filter" :class="{ on: onlyUnread }" @click="onlyUnread = !onlyUnread">
          {{ onlyUnread ? '只看未读' : '全部通知' }}
          <em v-if="unreadOnPage">{{ unreadOnPage }}</em>
        </button>
      </div>

      <p v-if="pending" class="muted">正在读取通知…</p>

      <article v-else-if="opened" class="sheet">
        <div class="sheet-bar">
          <button type="button" class="back" @click="openId = null">返回列表</button>
          <span>{{ Number(opened.is_read) ? '已读' : '未读' }}</span>
        </div>
        <h4>{{ opened.title || '站内通知' }}</h4>
        <dl>
          <div><dt>来源</dt><dd>{{ peerName(opened) }}</dd></div>
          <div><dt>时间</dt><dd>{{ opened.created_at?.slice(0, 16) || '-' }}</dd></div>
        </dl>
        <div class="sheet-body">{{ opened.content || opened.body || '（无正文）' }}</div>
        <div class="sheet-acts">
          <NuxtLink v-if="opened.action_to" class="btn ghost" :to="opened.action_to">去询盘管理</NuxtLink>
        </div>
        <form v-if="canReplyInquiry(opened)" class="reply-box" @submit.prevent="submitInquiryReply(opened)">
          <div class="reply-hd">回复此询盘（提交后需平台审核）</div>
          <p class="reply-to">回复给：<b>{{ peerName(opened) }}</b><span v-if="opened.from?.username" class="muted">（账号 {{ opened.from.username }}）</span></p>
          <textarea v-model="replyText" rows="4" required placeholder="输入回复内容" />
          <div class="reply-acts">
            <button class="btn" type="submit" :disabled="replyLoading">{{ replyLoading ? '提交中…' : '提交回复' }}</button>
          </div>
        </form>
      </article>

      <table v-else class="table mail-table">
        <thead>
          <tr>
            <th>标题</th>
            <th>来源</th>
            <th>状态</th>
            <th>时间</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in shown" :key="item.id" :class="{ unread: !Number(item.is_read) }">
            <td>
              <button type="button" class="subj" @click="openItem(item)">{{ item.title || '站内通知' }}</button>
              <div class="snip">{{ String(item.content || item.body || '').replace(/\s+/g, ' ').slice(0, 42) }}</div>
            </td>
            <td>{{ peerName(item) }}</td>
            <td>
              <span v-if="!Number(item.is_read)" class="st new">未读</span>
              <span v-else class="st">已读</span>
            </td>
            <td class="muted">{{ item.created_at?.slice(0, 16) }}</td>
            <td class="ops">
              <button type="button" @click="openItem(item)">查看</button>
              <button v-if="canReplyInquiry(item)" type="button" @click="openItem(item)">回复</button>
              <NuxtLink v-if="item.action_to" :to="item.action_to">处理</NuxtLink>
            </td>
          </tr>
          <tr v-if="!shown.length">
            <td colspan="5" class="muted empty">{{ keyword || onlyUnread ? '没有符合条件的通知' : '暂无通知' }}</td>
          </tr>
        </tbody>
      </table>

      <div v-if="!opened && meta.last_page > 1" class="pager">
        <button type="button" :disabled="page <= 1" @click="page--">上一页</button>
        <span>{{ meta.current_page }} / {{ meta.last_page }}</span>
        <button type="button" :disabled="page >= meta.last_page" @click="page++">下一页</button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.lead { margin: 0 0 14px; line-height: 1.7; }
.toolbar { display: flex; gap: 10px; align-items: center; margin-bottom: 12px; }
.search { flex: 1; }
.search input, .reply-box textarea {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #cfd8e3;
  padding: 8px 10px;
  font: inherit;
  background: #fff;
}
.filter, .pager button, .ops button, .back, .btn.ghost {
  border: 1px solid #cfd8e3;
  background: #fff;
  color: #2468f2;
  font: inherit;
  padding: 7px 12px;
  cursor: pointer;
}
.filter.on { background: #eef4ff; border-color: #2468f2; }
.filter em { font-style: normal; margin-left: 6px; color: #c2410c; }
.mail-table .subj {
  border: 0;
  background: none;
  padding: 0;
  color: #1d3e66;
  font: inherit;
  font-weight: 700;
  cursor: pointer;
  text-align: left;
}
.unread .subj { color: #2468f2; }
.snip { margin-top: 4px; color: #7b8ba0; font-size: 12px; max-width: 420px; }
.st { color: #7b8ba0; }
.st.new { color: #c2410c; font-weight: 700; }
.ops { white-space: nowrap; }
.ops button, .ops a { margin-right: 8px; color: #2468f2; background: none; border: 0; padding: 0; font: inherit; cursor: pointer; }
.pager { display: flex; justify-content: flex-end; align-items: center; gap: 10px; margin-top: 12px; }
.pager button:disabled { opacity: .45; cursor: default; }
.empty { text-align: center; padding: 28px 10px !important; }
.sheet { border: 1px solid #cfd8e3; background: #fff; }
.sheet-bar, .sheet-acts, .reply-acts { display: flex; gap: 10px; align-items: center; }
.sheet-bar { justify-content: space-between; padding: 10px 14px; background: #f5f8ff; border-bottom: 1px solid #cfd8e3; color: #7b8ba0; }
.sheet h4 { margin: 16px 16px 8px; font-size: 18px; color: #1d3e66; }
.sheet dl { display: flex; gap: 28px; margin: 0 16px 12px; }
.sheet dt { color: #7b8ba0; font-size: 12px; }
.sheet dd { margin: 2px 0 0; font-weight: 700; color: #24384e; }
.sheet-body { margin: 0 16px 16px; padding: 14px; background: #f7fafc; border: 1px solid #e4ebf2; white-space: pre-wrap; line-height: 1.75; min-height: 120px; }
.sheet-acts { padding: 0 16px 12px; }
.reply-box { margin: 0 16px 16px; padding: 12px; border: 1px solid #cfd8e3; background: #fff; }
.reply-hd { font-weight: 700; color: #2468f2; margin-bottom: 8px; }
.reply-to { margin: 0 0 10px; color: #35516f; }
.reply-to .muted { margin-left: 6px; color: #7b8ba0; font-size: 13px; }
.reply-acts { margin-top: 10px; }
.btn {
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  border: 1px solid #2468f2;
  background: #2468f2;
  color: #fff;
  padding: 8px 14px;
  font: inherit;
  cursor: pointer;
}
.btn.ghost { background: #fff; color: #2468f2; }
.btn:disabled { opacity: .55; cursor: default; }
</style>

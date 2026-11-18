<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: ['auth', 'enterprise'] })
useHead({ title: '荣誉资质' })
const { api } = useApi()
const { data, refresh } = useMemberData('my-shop', () => api('/member/shop'))
const shop = computed(() => (data.value as any)?.data || {})
const honors = computed(() => shop.value?.honors || [])
const tab = ref<'list' | 'add'>('list')
const honorForm = reactive({ title: '', image: '', authority: '', issued_at: '', expires_at: '' })
const msg = ref('')
const err = ref('')

const STATUS_MAP: Record<string, string> = {
  pending: '待审',
  approved: '已通过',
  rejected: '未通过',
  expired: '已过期',
  offline: '已下架',
}

function statusText(s?: string) {
  return STATUS_MAP[String(s || '')] || (s || '—')
}

async function addHonor() {
  err.value = ''
  msg.value = ''
  if (!honorForm.title.trim()) {
    await appFail('请填写证书名称', '提交失败')
    return
  }
  try {
    const body: Record<string, any> = {
      title: honorForm.title,
      image: honorForm.image || null,
      authority: honorForm.authority || null,
      issued_at: honorForm.issued_at || null,
      expires_at: honorForm.expires_at || null,
    }
    await api('/member/shop/honors', { method: 'POST', body })
    honorForm.title = ''
    honorForm.image = ''
    honorForm.authority = ''
    honorForm.issued_at = ''
    honorForm.expires_at = ''
    await appOk('已提交，等待后台审核。', '提交成功')
    tab.value = 'list'
    await refresh()
  } catch (e: any) {
    await appFail(apiErrorText(e, '提交失败'), '提交失败')
  }
}
async function delHonor(id: number) {
  await api(`/member/shop/honors/${id}`, { method: 'DELETE' })
  await refresh()
}
</script>

<template>
  <div class="panel">
    <h3>
      <span class="tabs">
        <button type="button" :class="{ on: tab === 'list' }" @click="tab = 'list'">荣誉资质</button>
        <button type="button" :class="{ on: tab === 'add' }" @click="tab = 'add'">添加荣誉</button>
      </span>
    </h3>
    <div class="body">
      <div v-if="msg" class="alert alert-success">{{ msg }}</div>
      <div v-if="err && tab === 'add'" class="alert alert-error">{{ err }}</div>
      <template v-if="tab === 'list'">
        <div v-if="honors.length" class="mf-list">
          <div v-for="h in honors" :key="h.id" class="mf-item">
            <div class="left">
              <div v-if="h.image" class="thumb-box"><img class="thumb" :src="h.image" alt=""></div>
              <div>
                <div class="ttl">
                  {{ h.title }}
                  <em class="st" :data-s="h.status">{{ statusText(h.status) }}</em>
                </div>
                <div class="sub">
                  {{ h.authority || '-' }}
                  · 发证 {{ h.issued_at || '—' }}
                  · 到期 {{ h.expires_at || '永久' }}
                </div>
              </div>
            </div>
            <a class="del" href="#" @click.prevent="delHonor(h.id)">删除</a>
          </div>
        </div>
        <div v-else class="empty-guide">
          <p>还没有添加过荣誉</p>
          <button class="btn" type="button" @click="tab = 'add'">去添加</button>
        </div>
      </template>
      <form v-else class="mf-form" @submit.prevent="addHonor">
        <section class="mf-sec">
          <div class="mf-sec-hd">添加荣誉</div>
          <div class="mf-sec-bd">
            <div class="form-row"><label>证书名称 <em>*</em></label><input v-model="honorForm.title" required></div>
            <div class="mf-pics one"><ImageUpload v-model="honorForm.image" label="证书图片" /></div>
            <div class="mf-grid-2" style="margin-top:12px">
              <div class="form-row"><label>发证机构</label><input v-model="honorForm.authority"></div>
              <div class="form-row"><label>发证日期</label><input v-model="honorForm.issued_at" type="date"></div>
            </div>
            <div class="form-row">
              <label>到期日期 <span class="hint-inline">留空表示永久有效；提交后需后台审核</span></label>
              <input v-model="honorForm.expires_at" type="date">
            </div>
          </div>
        </section>
        <div class="mf-actions"><button class="btn" type="submit">添加荣誉</button></div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.hint-inline { font-weight: 400; color: #94a3b8; font-size: 12px; margin-left: 6px; }
.mf-list { margin-bottom: 16px; }
.mf-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 0;
  border-bottom: 1px solid var(--line);
}
.left { display: flex; align-items: center; gap: 12px; min-width: 0; }
.tabs { display: flex; align-items: center; gap: 22px; }
.tabs button {
  border: 0;
  background: transparent;
  padding: 0;
  font: inherit;
  font-size: 15px;
  font-weight: 700;
  color: #5a6b85;
  cursor: pointer;
}
.tabs button.on { color: #1443b8; }
.empty-guide { padding: 28px 0 12px; color: #5a6b85; }
.empty-guide p { margin: 0 0 12px; }
.thumb-box {
  width: 88px;
  height: 88px;
  flex: 0 0 88px;
  overflow: hidden;
  background: #f4f7fb;
  border: 1px solid var(--line);
  border-radius: 6px;
}
.thumb {
  width: 100%;
  height: 100%;
  object-fit: contain;
}
.sub { margin-top: 4px; font-size: 12px; color: #5a6b85; }
.del { flex: 0 0 auto; font-size: 13px; }
.ttl { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.st {
  font-style: normal;
  font-size: 12px;
  padding: 1px 8px;
  border-radius: 999px;
  background: #e2e8f0;
  color: #475569;
}
.st[data-s="pending"] { background: #fef3c7; color: #92400e; }
.st[data-s="approved"] { background: #dcfce7; color: #166534; }
.st[data-s="rejected"] { background: #fee2e2; color: #991b1b; }
.st[data-s="expired"] { background: #f1f5f9; color: #64748b; }
</style>

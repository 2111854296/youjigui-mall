const fs = require('fs')
const path = require('path')
const base = path.join(__dirname, '../yjgmall-web/pages/member')

const mods = [
  { key: 'photo', title: '图库', api: 'photo', fields: [
    ['title', '标题', 'text', true], ['thumb', '封面图URL', 'text'], ['summary', '摘要', 'textarea'], ['content', '详情', 'rich'],
  ], cols: [['title', '标题'], ['status', '状态']] },
  { key: 'video', title: '视频', api: 'video', fields: [
    ['title', '标题', 'text', true], ['thumb', '封面图URL', 'text'], ['video_url', '视频地址', 'text'], ['summary', '摘要', 'textarea'], ['content', '详情', 'rich'],
  ], cols: [['title', '标题'], ['status', '状态']] },
  { key: 'group', title: '团购', api: 'group', fields: [
    ['title', '标题', 'text', true], ['thumb', '封面图URL', 'text'], ['price', '团购价', 'number'], ['market_price', '市场价', 'number'],
    ['stock', '库存', 'number'], ['ends_at', '截止时间', 'text'], ['summary', '摘要', 'textarea'], ['content', '详情', 'rich'],
  ], cols: [['title', '标题'], ['price', '价格'], ['status', '状态']] },
  { key: 'exhibit', title: '展会', api: 'exhibit', fields: [
    ['title', '标题', 'text', true], ['area', '地区', 'text'], ['venue', '场馆', 'text'], ['starts_on', '开始日期', 'text'],
    ['ends_on', '结束日期', 'text'], ['organizer', '主办方', 'text'], ['summary', '摘要', 'textarea'], ['content', '详情', 'rich'],
  ], cols: [['title', '标题'], ['area', '地区'], ['status', '状态']] },
  { key: 'brand', title: '品牌', api: 'brand', fields: [
    ['name', '品牌名称', 'text', true], ['logo', 'Logo URL', 'text'], ['country', '国家/地区', 'text'], ['website', '官网', 'text'],
    ['intro', '简介', 'textarea'], ['content', '详情', 'rich'],
  ], cols: [['name', '名称'], ['status', '状态']] },
]

function init(fields) {
  return fields.map(([n, , t]) => `  ${n}: ${t === 'number' ? 0 : "''"},`).join('\n')
}
function inputs(fields) {
  return fields.map(([n, l, t, req]) => {
    const em = req ? ' <em>*</em>' : ''
    const r = req ? ' required' : ''
    if (t === 'rich') return `        <div class="form-row"><label>${l}${em}</label><RichTextEditor v-model="form.${n}" min-height="160px" /></div>`
    if (t === 'textarea') return `        <div class="form-row"><label>${l}${em}</label><textarea v-model="form.${n}" rows="2" /></div>`
    if (t === 'number') return `        <div class="form-row"><label>${l}${em}</label><input v-model.number="form.${n}" type="number" step="0.01"${r}></div>`
    return `        <div class="form-row"><label>${l}${em}</label><input v-model="form.${n}"${r}></div>`
  }).join('\n')
}

for (const m of mods) {
  const dir = path.join(base, m.key)
  fs.mkdirSync(dir, { recursive: true })
  const th = m.cols.map(([, l]) => `<th>${l}</th>`).join('') + '<th>操作</th>'
  const td = m.cols.map(([n]) => (n === 'status' ? '<td>{{ statusLabel(item.status) }}</td>' : `<td>{{ item.${n} }}</td>`)).join('\n            ')
  const colspan = m.cols.length + 1
  const formInit = init(m.fields)
  const formInputs = inputs(m.fields)

  const index = `<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '${m.title}管理' })
const { api } = useApi()
const msg = ref('')
const { data, refresh } = useMemberData('member-${m.api}', () => api('/member/${m.api}'))
const items = computed(() => (data.value as any)?.data || [])
async function remove(id: number) {
  if (!confirm('确认删除？')) return
  await api('/member/${m.api}/' + id, { method: 'DELETE' })
  msg.value = '已删除'
  await refresh()
}
function statusLabel(s: string) {
  return ({ pending: '待审', approved: '已发布', published: '已发布', offline: '下架', rejected: '拒绝' } as Record<string, string>)[s] || s
}
</script>
<template>
  <div class="panel">
    <h3>${m.title}管理 <NuxtLink to="/member/${m.api}/create">发布${m.title}</NuxtLink></h3>
    <div class="body">
      <div v-if="msg" class="alert alert-success">{{ msg }}</div>
      <table class="table">
        <thead><tr>${th}</tr></thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            ${td}
            <td>
              <NuxtLink :to="'/member/${m.api}/' + item.id">编辑</NuxtLink>
              · <a href="#" @click.prevent="remove(item.id)">删除</a>
            </td>
          </tr>
          <tr v-if="!items.length"><td colspan="${colspan}" class="muted">暂无记录</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
`

  const create = `<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '发布${m.title}' })
const { api } = useApi()
const router = useRouter()
const form = reactive({
${formInit}
})
const err = ref('')
const loading = ref(false)
async function submit() {
  err.value = ''
  loading.value = true
  try {
    await api('/member/${m.api}', { method: 'POST', body: { ...form } })
    router.push('/member/${m.api}')
  } catch (e: any) {
    err.value = e?.data?.message || '提交失败'
  } finally {
    loading.value = false
  }
}
</script>
<template>
  <div class="panel">
    <h3>发布${m.title} <NuxtLink to="/member/${m.api}">返回</NuxtLink></h3>
    <div class="body">
      <div v-if="err" class="alert alert-error">{{ err }}</div>
      <form class="mf-form" @submit.prevent="submit">
${formInputs}
        <div class="mf-actions">
          <button class="btn" type="submit" :disabled="loading">提交审核</button>
          <NuxtLink class="btn btn-outline" to="/member/${m.api}">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>
`

  const edit = `<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '编辑${m.title}' })
const route = useRoute()
const router = useRouter()
const { api } = useApi()
const id = computed(() => String(route.params.id))
const form = reactive({
${formInit}
})
const err = ref('')
const loading = ref(false)
onMounted(async () => {
  const res = await api<{ data: any }>('/member/${m.api}/' + id.value)
  Object.assign(form, res.data || {})
})
async function submit() {
  err.value = ''
  loading.value = true
  try {
    await api('/member/${m.api}/' + id.value, { method: 'PUT', body: { ...form } })
    router.push('/member/${m.api}')
  } catch (e: any) {
    err.value = e?.data?.message || '保存失败'
  } finally {
    loading.value = false
  }
}
</script>
<template>
  <div class="panel">
    <h3>编辑${m.title} <NuxtLink to="/member/${m.api}">返回</NuxtLink></h3>
    <div class="body">
      <div v-if="err" class="alert alert-error">{{ err }}</div>
      <form class="mf-form" @submit.prevent="submit">
${formInputs}
        <div class="mf-actions">
          <button class="btn" type="submit" :disabled="loading">保存</button>
          <NuxtLink class="btn btn-outline" to="/member/${m.api}">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>
`

  fs.writeFileSync(path.join(dir, 'index.vue'), index, 'utf8')
  fs.writeFileSync(path.join(dir, 'create.vue'), create, 'utf8')
  fs.writeFileSync(path.join(dir, '[id].vue'), edit, 'utf8')
  console.log('ok', m.key)
}

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { normalizeToggle } from '~/utils/adminFormValues'

definePageMeta({ layout: 'admin', middleware: 'admin' })

const route = useRoute()
const router = useRouter()
const { api } = useApi()

const editId = computed(() => {
  const raw = route.query.id
  const n = Number(Array.isArray(raw) ? raw[0] : raw)
  return Number.isFinite(n) && n > 0 ? n : null
})

const busy = ref(false)
const loading = ref(true)
const fields = ref<any[]>([])
const form = reactive<Record<string, any>>({})

const pageTitle = computed(() => (editId.value ? '修改资讯' : '添加资讯'))

watch(
  pageTitle,
  (t) => useHead({ title: `${t} - 运营后台` }),
  { immediate: true },
)

function blankForm(list: any[]) {
  Object.keys(form).forEach((k) => delete form[k])
  for (const f of list) {
    if (f.type === 'toggle') form[f.name] = false
    else if (f.type === 'number') form[f.name] = f.name === 'level' || f.name === 'sort' || f.name === 'views' || f.name === 'view_boost' ? 0 : null
    else if (f.name === 'type') form[f.name] = 'news'
    else if (f.name === 'status') form[f.name] = 'pending'
    else if (f.name === 'source') form[f.name] = '有机硅商城'
    else form[f.name] = ''
  }
}

function applyRow(list: any[], dataRow: Record<string, any>) {
  blankForm(list)
  for (const f of list) {
    let v = dataRow[f.name]
    if (f.type === 'datetime' && v) v = String(v).replace(' ', 'T').slice(0, 16)
    else if (f.type === 'date' && v) v = String(v).slice(0, 10)
    else if (f.type === 'toggle') v = normalizeToggle(v)
    else if (f.type === 'select' || f.type === 'relation' || f.type === 'category') {
      v = v == null || v === '' ? '' : String(v)
    }
    else if (f.type === 'multiselect') {
      v = Array.isArray(v) ? v.map(String).join(',') : (v == null ? '' : String(v))
    }
    else if (f.type === 'number') v = v == null || v === '' ? 0 : Number(v)
    form[f.name] = v ?? (f.type === 'toggle' ? false : f.type === 'number' ? 0 : '')
  }
}

async function boot() {
  loading.value = true
  try {
    const meta = await api('/admin/resources/articles/meta')
    const list = ((meta as any)?.data?.fields || []) as any[]
    fields.value = list
    if (editId.value) {
      const res = await api(`/admin/resources/articles/${editId.value}`)
      applyRow(list, (res as any)?.data || {})
    } else {
      blankForm(list)
      if (!form.published_at) {
        const d = new Date()
        const pad = (n: number) => String(n).padStart(2, '0')
        form.published_at = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
      }
    }
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '加载失败')
  } finally {
    loading.value = false
  }
}

onMounted(boot)
watch(editId, () => { void boot() })

function payloadFromForm() {
  const body: Record<string, any> = {}
  for (const f of fields.value) {
    let v = form[f.name]
    if (f.type === 'datetime' && v) {
      v = String(v).replace('T', ' ') + (String(v).length === 16 ? ':00' : '')
    }
    if (f.type === 'number' && (v === '' || v === undefined)) v = 0
    if (f.type === 'category' && (v === '' || v === undefined)) v = null
    body[f.name] = v
  }
  return body
}

async function submit() {
  if (!String(form.title || '').trim()) {
    ElMessage.warning('请填写资讯标题')
    return
  }
  busy.value = true
  try {
    const body = payloadFromForm()
    if (editId.value) {
      await api(`/admin/resources/articles/${editId.value}`, { method: 'PUT', body })
      ElMessage.success('已保存')
    } else {
      const res = await api('/admin/resources/articles', { method: 'POST', body })
      const id = (res as any)?.data?.id
      ElMessage.success('已创建')
      if (id) await router.replace(`/admin/articles/edit?id=${id}`)
      else await router.push('/admin/r/articles')
    }
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '保存失败')
  } finally {
    busy.value = false
  }
}

function goBack() {
  navigateTo('/admin/r/articles')
}
</script>

<template>
  <div class="article-edit">
    <div class="hd">
      <div>
        <h2>{{ pageTitle }}</h2>
        <p>日常发资讯：信息齐全、宽屏编辑</p>
      </div>
      <div class="hd-acts">
        <el-button @click="goBack">返回列表</el-button>
        <el-button type="warning" :loading="busy" @click="submit">确定</el-button>
      </div>
    </div>

    <div v-if="loading" class="loading">加载中…</div>
    <div v-else class="panel">
      <AdminResourceForm v-model="form" :fields="fields" :mode="editId ? 'edit' : 'create'" resource="articles" />
      <div class="foot">
        <el-button type="warning" size="large" :loading="busy" @click="submit">确定</el-button>
        <el-button size="large" @click="goBack">返回</el-button>
      </div>
    </div>
  </div>
</template>

<style scoped>
.article-edit {
  width: 100%;
  max-width: none;
}
.hd {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}
.hd h2 {
  margin: 0 0 4px;
  font-size: 20px;
  color: #1a2332;
}
.hd p {
  margin: 0;
  color: #8b93a0;
  font-size: 13px;
}
.hd-acts { display: flex; gap: 8px; flex-shrink: 0; }
.panel {
  background: #fff;
  border: 1px solid #e8ecf0;
  border-radius: 12px;
  padding: 20px 22px 24px;
  width: 100%;
  box-sizing: border-box;
}
.loading { padding: 48px; text-align: center; color: #94a3b8; }
.foot {
  display: flex;
  gap: 12px;
  margin-top: 24px;
  padding-top: 18px;
  border-top: 1px solid #f0f2f5;
}
.panel :deep(.admin-form) {
  grid-template-columns: 1fr;
  max-width: none;
}
.panel :deep(.field.rich .rte),
.panel :deep(.field.rich) {
  --rte-min: 420px;
}
.panel :deep(.rte-body .tiptap),
.panel :deep(.rte-body .ProseMirror) {
  min-height: 420px;
}
@media (max-width: 760px) {
  .hd { flex-direction: column; }
}
</style>

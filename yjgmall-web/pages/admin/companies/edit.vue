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
const linkedUser = ref<Record<string, any> | null>(null)

const pageTitle = computed(() => (editId.value ? '修改企业' : '添加企业'))

const sections = [
  { key: 'basic', title: '公司资料' },
  { key: 'env', title: '厂房环境图（4～8 张）' },
  { key: 'cert', title: '资料认证' },
  { key: 'advanced', title: '模板与状态' },
]

watch(
  pageTitle,
  (t) => useHead({ title: `${t} - 运营后台` }),
  { immediate: true },
)

function fieldsOf(section: string) {
  return fields.value.filter(f => (f.section || 'basic') === section)
}

function blankForm(list: any[]) {
  Object.keys(form).forEach((k) => delete form[k])
  for (const f of list) {
    if (f.type === 'toggle') form[f.name] = false
    else if (f.type === 'number') form[f.name] = f.name === 'vip_level' || f.name === 'vip_theory' || f.name === 'vip_correct' || f.name === 'views' ? 0 : null
    else if (f.name === 'status') form[f.name] = 'pending'
    else if (f.name === 'template') form[f.name] = 'atelier'
    else if (f.name === 'company_type') form[f.name] = '企业单位'
    else if (f.name === 'business_mode') form[f.name] = '制造商'
    else if (f.name === 'capital_unit') form[f.name] = '人民币'
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
  linkedUser.value = dataRow.user || null
  form.user_mobile_verified = normalizeToggle(dataRow.user_mobile_verified)
  form.user_bank_verified = normalizeToggle(dataRow.user_bank_verified)
  form.user_truename_verified = normalizeToggle(dataRow.user_truename_verified)
}

async function boot() {
  loading.value = true
  linkedUser.value = null
  try {
    const meta = await api('/admin/resources/companies/meta')
    const list = ((meta as any)?.data?.fields || []) as any[]
    fields.value = list
    if (editId.value) {
      const res = await api(`/admin/resources/companies/${editId.value}`)
      applyRow(list, (res as any)?.data || {})
    } else {
      blankForm(list)
    }
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '加载失败')
  } finally {
    loading.value = false
  }
}

onMounted(boot)
watch(editId, () => { void boot() })

watch(() => [form.vip_theory, form.vip_correct], () => {
  const t = Number(form.vip_theory || 0)
  const c = Number(form.vip_correct || 0)
  form.vip_level = Math.max(0, Math.min(10, t + c))
})

function payloadFromForm() {
  const body: Record<string, any> = {}
  for (const f of fields.value) {
    if (f.virtual) continue
    let v = form[f.name]
    if (f.type === 'datetime' && v) {
      v = String(v).replace('T', ' ') + (String(v).length === 16 ? ':00' : '')
    }
    if (f.type === 'number' && (v === '' || v === undefined)) v = 0
    if ((f.type === 'category' || f.type === 'relation') && (v === '' || v === undefined)) v = null
    body[f.name] = v
  }
  return body
}

async function submit() {
  if (!String(form.name || '').trim()) {
    ElMessage.warning('请填写企业名称')
    return
  }
  if (!editId.value && !form.user_id) {
    ElMessage.warning('请选择关联用户')
    return
  }
  if (!String(form.slug || '').trim()) {
    form.slug = 'c' + Date.now()
  }
  busy.value = true
  try {
    const body = payloadFromForm()
    if (editId.value) {
      await api(`/admin/resources/companies/${editId.value}`, { method: 'PUT', body })
      ElMessage.success('已保存')
      await boot()
    } else {
      const res = await api('/admin/resources/companies', { method: 'POST', body })
      const id = (res as any)?.data?.id
      ElMessage.success('已创建')
      if (id) await router.replace(`/admin/companies/edit?id=${id}`)
      else await router.push('/admin/r/companies')
    }
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '保存失败')
  } finally {
    busy.value = false
  }
}

function goBack() {
  navigateTo('/admin/r/companies')
}

function previewFront() {
  const slug = String(form.slug || '').trim()
  if (!slug) {
    ElMessage.warning('请先填写公司主页 slug')
    return
  }
  window.open(`/companies/${encodeURIComponent(slug)}`, '_blank')
}
</script>

<template>
  <div class="company-edit">
    <div class="hd">
      <div>
        <h2>{{ pageTitle }}</h2>
        <p>公司资料 / 厂房环境 / 资料认证 / 模板与状态</p>
      </div>
      <div class="hd-acts">
        <el-button @click="goBack">返回</el-button>
        <el-button v-if="editId" @click="previewFront">前台预览</el-button>
        <el-button type="warning" :loading="busy" @click="submit">确定</el-button>
      </div>
    </div>

    <div v-if="loading" class="loading">加载中…</div>
    <template v-else>
      <section v-if="linkedUser || editId" class="panel member-panel">
        <h3>关联会员</h3>
        <div v-if="linkedUser" class="member-grid">
          <div><span>用户名</span><b>{{ linkedUser.username || '-' }}</b></div>
          <div><span>昵称</span><b>{{ linkedUser.name || '-' }}</b></div>
          <div><span>会员组</span><b>{{ linkedUser.group_code || linkedUser.member_type || '-' }}</b></div>
          <div><span>手机</span><b>{{ linkedUser.mobile || '-' }}</b></div>
          <div><span>邮箱</span><b>{{ linkedUser.email || '-' }}</b></div>
          <div>
            <el-button link type="primary" @click="navigateTo(`/admin/r/users`)">去会员列表</el-button>
          </div>
        </div>
        <p v-else class="hint">新建时请在下方选择关联用户。</p>
      </section>

      <section v-for="sec in sections" :key="sec.key" class="panel">
        <h3>{{ sec.title }}</h3>
        <AdminResourceForm
          v-model="form"
          :fields="fieldsOf(sec.key)"
          :mode="editId ? 'edit' : 'create'"
          resource="companies"
        />
      </section>

      <div class="foot">
        <el-button type="warning" size="large" :loading="busy" @click="submit">确定</el-button>
        <el-button size="large" @click="previewFront">前台预览</el-button>
        <el-button size="large" @click="goBack">返回</el-button>
      </div>
    </template>
  </div>
</template>

<style scoped>
.company-edit { width: 100%; max-width: none; }
.hd {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}
.hd h2 { margin: 0 0 4px; font-size: 20px; color: #1a2332; }
.hd p { margin: 0; color: #8b93a0; font-size: 13px; }
.hd-acts { display: flex; gap: 8px; flex-shrink: 0; }
.panel {
  background: #fff;
  border: 1px solid #e8ecf0;
  border-radius: 12px;
  padding: 18px 22px 22px;
  width: 100%;
  box-sizing: border-box;
  margin-bottom: 14px;
}
.panel h3 {
  margin: 0 0 14px;
  font-size: 15px;
  color: #1e40af;
  padding-bottom: 10px;
  border-bottom: 1px solid #e8ecf0;
}
.member-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 12px 18px;
}
.member-grid span { display: block; font-size: 12px; color: #94a3b8; margin-bottom: 2px; }
.member-grid b { font-size: 14px; color: #1a2332; font-weight: 600; }
.hint { margin: 0; color: #94a3b8; font-size: 13px; }
.loading { padding: 48px; text-align: center; color: #94a3b8; }
.foot {
  display: flex;
  gap: 12px;
  margin: 8px 0 24px;
  padding: 18px 0;
}
.panel :deep(.admin-form) {
  grid-template-columns: 1fr 1fr;
  max-width: none;
  gap: 14px 20px;
}
.panel :deep(.field.rich),
.panel :deep(.field.textarea) {
  grid-column: 1 / -1;
}
.panel :deep(.field.rich .rte),
.panel :deep(.field.rich) {
  --rte-min: 320px;
}
@media (max-width: 900px) {
  .panel :deep(.admin-form) { grid-template-columns: 1fr; }
  .hd { flex-direction: column; }
}
</style>

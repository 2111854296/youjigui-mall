<script setup lang="ts">
import { ElMessage } from 'element-plus'

definePageMeta({ layout: 'admin', middleware: 'admin' })
useHead({ title: '公司模板 - 运营后台' })

const { api } = useApi()
const busy = ref(false)
const templates = ref<{ code: string; name: string; count: number }[]>([])
const other = ref(0)
const selected = ref<number[]>([])
const assignCode = ref('atelier')
const companyQ = ref('')
const companies = ref<any[]>([])

async function loadTemplates() {
  const res = await api('/admin/modules/company/templates')
  const d = (res as any)?.data || {}
  templates.value = d.templates || []
  other.value = d.other || 0
}

async function loadCompanies() {
  const res = await api('/admin/resources/companies', {
    query: { q: companyQ.value || undefined, page: 1 },
  })
  companies.value = (res as any)?.data || []
}

onMounted(async () => {
  try {
    await Promise.all([loadTemplates(), loadCompanies()])
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '加载失败')
  }
})

async function searchCompanies() {
  try {
    await loadCompanies()
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '查询失败')
  }
}

async function assign() {
  if (!selected.value.length) {
    ElMessage.warning('请先勾选企业')
    return
  }
  busy.value = true
  try {
    await api('/admin/modules/company/templates/assign', {
      method: 'POST',
      body: { template: assignCode.value, ids: selected.value },
    })
    ElMessage.success('已更新模板')
    selected.value = []
    await Promise.all([loadTemplates(), loadCompanies()])
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '更新失败')
  } finally {
    busy.value = false
  }
}

function filterByTemplate(code: string) {
  companyQ.value = ''
  assignCode.value = code
  void (async () => {
    const res = await api('/admin/resources/companies', { query: { page: 1 } })
    const all = (res as any)?.data || []
    companies.value = all.filter((c: any) => (c.template || 'atelier') === code)
  })()
}
</script>

<template>
  <div class="mod-page">
    <div class="hd">
      <div>
        <h2>公司模板</h2>
        <p>旺铺皮肤：展厅·清雅 / 工场·沉稳 / 名录·商务</p>
      </div>
    </div>

    <div class="tpl-grid">
      <button
        v-for="t in templates"
        :key="t.code"
        type="button"
        class="tpl-card"
        :class="{ on: assignCode === t.code }"
        @click="filterByTemplate(t.code)"
      >
        <b>{{ t.name }}</b>
        <span>{{ t.code }}</span>
        <em>{{ t.count }} 家企业</em>
      </button>
      <div class="tpl-card muted">
        <b>其他/空</b>
        <span>—</span>
        <em>{{ other }} 家</em>
      </div>
    </div>

    <section class="panel">
      <div class="toolbar">
        <el-input v-model="companyQ" clearable placeholder="搜索企业" style="width: 220px" @keyup.enter="searchCompanies" />
        <el-button @click="searchCompanies">查询</el-button>
        <el-select v-model="assignCode" style="width: 160px">
          <el-option v-for="t in templates" :key="t.code" :label="t.name" :value="t.code" />
        </el-select>
        <el-button type="warning" :loading="busy" @click="assign">批量应用模板</el-button>
      </div>
      <el-table :data="companies" border stripe @selection-change="(rows: any[]) => { selected = rows.map(r => r.id) }">
        <el-table-column type="selection" width="48" />
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="企业名" min-width="180" />
        <el-table-column prop="template" label="当前模板" width="120" />
        <el-table-column prop="status" label="状态" width="100" />
      </el-table>
    </section>
  </div>
</template>

<style scoped>
.mod-page { width: 100%; }
.hd { margin-bottom: 16px; }
.hd h2 { margin: 0 0 4px; font-size: 20px; }
.hd p { margin: 0; color: #8b93a0; font-size: 13px; }
.tpl-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}
.tpl-card {
  text-align: left;
  background: #fff;
  border: 1px solid #e8ecf0;
  border-radius: 12px;
  padding: 14px 16px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 4px;
}
.tpl-card.on { border-color: #2468f2; background: #eef4ff; }
.tpl-card.muted { cursor: default; color: #94a3b8; }
.tpl-card b { font-size: 15px; color: #1a2332; }
.tpl-card span { font-size: 12px; color: #94a3b8; }
.tpl-card em { font-style: normal; font-size: 13px; color: #1443b8; font-weight: 600; }
.panel {
  background: #fff; border: 1px solid #e8ecf0; border-radius: 12px;
  padding: 16px;
}
.toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; align-items: center; }
</style>

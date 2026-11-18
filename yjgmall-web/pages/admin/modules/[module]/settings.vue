<script setup lang="ts">
import { ElMessage } from 'element-plus'

definePageMeta({ layout: 'admin', middleware: 'admin' })

const route = useRoute()
const { api } = useApi()
const moduleKey = computed(() => String(route.params.module || ''))

const busy = ref(false)
const pending = ref(true)
const form = reactive<Record<string, string>>({})

const title = computed(() => (moduleKey.value === 'company' ? '企业库 · 模块设置' : '资讯 · 模块设置'))
watch(title, (t) => useHead({ title: `${t} - 运营后台` }), { immediate: true })

const labels: Record<string, string> = {
  news_page_size: '列表每页条数',
  news_need_check: '发布需审核（1/0）',
  news_list_order: '列表排序',
  company_need_check: '入驻需审核（1/0）',
  company_vip_min: 'VIP 最低指数',
  company_page_size: '列表每页条数',
  company_default_template: '默认旺铺模板',
}

async function load() {
  pending.value = true
  try {
    const res = await api(`/admin/modules/${moduleKey.value}/settings`)
    const d = (res as any)?.data || {}
    Object.keys(form).forEach((k) => delete form[k])
    Object.keys(d).forEach((k) => { form[k] = String(d[k] ?? '') })
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '加载失败')
  } finally {
    pending.value = false
  }
}

onMounted(load)
watch(moduleKey, () => { void load() })

async function save() {
  busy.value = true
  try {
    await api(`/admin/modules/${moduleKey.value}/settings`, { method: 'PUT', body: { ...form } })
    ElMessage.success('已保存')
    await load()
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '保存失败')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="mod-page">
    <div class="hd">
      <div>
        <h2>{{ title }}</h2>
        <p>模块级配置写入站点设置表，可随时调整</p>
      </div>
      <el-button type="warning" :loading="busy" @click="save">保存设置</el-button>
    </div>
    <section class="panel">
      <div v-if="pending" class="loading">加载中…</div>
      <el-form v-else label-width="160px" class="form" @submit.prevent="save">
        <el-form-item v-for="(val, key) in form" :key="key" :label="labels[key] || key">
          <el-input v-model="form[key]" />
        </el-form-item>
      </el-form>
    </section>
  </div>
</template>

<style scoped>
.mod-page { width: 100%; }
.hd {
  display: flex; justify-content: space-between; align-items: flex-start;
  gap: 12px; margin-bottom: 16px;
}
.hd h2 { margin: 0 0 4px; font-size: 20px; }
.hd p { margin: 0; color: #8b93a0; font-size: 13px; }
.panel {
  background: #fff; border: 1px solid #e8ecf0; border-radius: 12px;
  padding: 18px 22px; max-width: 720px;
}
.loading { padding: 24px; color: #94a3b8; text-align: center; }
.form { max-width: 560px; }
</style>

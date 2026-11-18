<script setup lang="ts">
import { ElMessage, ElMessageBox } from 'element-plus'

definePageMeta({ layout: 'admin', middleware: 'admin' })
useHead({ title: '回收站 - 运营后台' })

const { api } = useApi('admin')
const { runWithSecondPassword } = useAdminSecondPassword()

const q = ref('')
const page = ref(1)
const busy = ref(false)

const { data, pending, refresh } = await useAsyncData(
  'admin-recycle',
  async () => {
    const res = await api<any>('/admin/recycle', { query: { page: page.value, q: q.value || undefined } })
    return res
  },
  { server: false, watch: [page] },
)

const rows = computed(() => (data.value as any)?.data || [])
const meta = computed(() => (data.value as any)?.meta || { current_page: 1, last_page: 1, total: 0 })

const resourceLabels: Record<string, string> = {
  banners: '首页轮播',
  ads: '广告位',
  announcements: '网站公告',
  hotwords: '搜索热词',
  'friend-links': '友情链接',
  pages: '帮助单页',
  'quote-products': '行情品种',
  'quote-product-prices': '行情日价',
  supplies: '供应信息',
  purchases: '采购信息',
  products: '商品',
  articles: '资讯',
  companies: '企业',
}

function labelOf(resource: string) {
  return resourceLabels[resource] || resource
}

async function search() {
  page.value = 1
  await refresh()
}

async function restore(row: any) {
  try {
    await ElMessageBox.confirm(`确认还原「${row.title || '#' + row.id}」？`, '还原确认', { type: 'warning' })
  } catch {
    return
  }
  busy.value = true
  try {
    await api(`/admin/recycle/${row.id}/restore`, { method: 'POST' })
    ElMessage.success('已还原')
    await refresh()
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '还原失败')
  } finally {
    busy.value = false
  }
}

async function purge(row: any) {
  try {
    await ElMessageBox.confirm(`确认彻底删除「${row.title || '#' + row.id}」？此操作不可恢复。`, '彻底删除', {
      type: 'warning',
      confirmButtonText: '彻底删除',
    })
  } catch {
    return
  }
  busy.value = true
  try {
    const res = await runWithSecondPassword(() =>
      api(`/admin/recycle/${row.id}`, { method: 'DELETE' }),
    )
    if (res === undefined) return
    ElMessage.success('已彻底删除')
    await refresh()
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '删除失败')
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div class="recycle-page">
    <div class="page-hd">
      <div>
        <h2>回收站</h2>
        <p>后台删除的内容会先进入这里，可还原或彻底删除</p>
      </div>
    </div>

    <div class="toolbar">
      <el-input v-model="q" clearable placeholder="关键词 / 资源名" style="max-width:260px" @keyup.enter="search" />
      <el-button type="primary" @click="search">查询</el-button>
    </div>

    <el-skeleton v-if="pending" :rows="6" animated />
    <template v-else>
      <el-table :data="rows" stripe border empty-text="回收站是空的">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="类型" min-width="120">
          <template #default="{ row }">{{ labelOf(row.resource) }}</template>
        </el-table-column>
        <el-table-column prop="title" label="标题" min-width="180" show-overflow-tooltip />
        <el-table-column prop="record_id" label="原ID" width="90" />
        <el-table-column prop="deleted_at" label="删除时间" min-width="160" />
        <el-table-column label="操作" fixed="right" width="180">
          <template #default="{ row }">
            <el-button link type="primary" :disabled="busy" @click="restore(row)">还原</el-button>
            <el-button link type="danger" :disabled="busy" @click="purge(row)">彻底删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      <div v-if="meta.last_page > 1" class="pager">
        <el-pagination
          background
          layout="prev, pager, next, total"
          :total="meta.total"
          :page-size="20"
          :current-page="meta.current_page"
          @current-change="(p: number) => { page = p }"
        />
      </div>
    </template>
  </div>
</template>

<style scoped>
.recycle-page { width: 100%; }
.page-hd { margin-bottom: 16px; }
.page-hd h2 { margin: 0 0 4px; font-size: 20px; color: #1a2332; }
.page-hd p { margin: 0; color: #8b93a0; font-size: 13px; }
.toolbar { display: flex; gap: 10px; margin-bottom: 14px; }
.pager { margin-top: 14px; display: flex; justify-content: flex-end; }
</style>

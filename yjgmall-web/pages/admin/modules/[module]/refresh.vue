<script setup lang="ts">
import { ElMessage } from 'element-plus'

definePageMeta({ layout: 'admin', middleware: 'admin' })

const route = useRoute()
const { api } = useApi()
const moduleKey = computed(() => String(route.params.module || ''))

const busy = ref(false)
const result = ref<Record<string, any> | null>(null)

const title = computed(() => (moduleKey.value === 'company' ? '企业库 · 更新数据' : '资讯 · 更新数据'))
watch(title, (t) => useHead({ title: `${t} - 运营后台` }), { immediate: true })

const hints = computed(() => {
  if (moduleKey.value === 'company') {
    return [
      '为空的企业 Slug 自动生成（c{id}）',
      '空 VIP 指数按认证/推荐兜底',
      '清理无所属企业的新闻/荣誉/单页/链接',
    ]
  }
  return [
    '已发布但缺少发布时间的资讯补齐 published_at',
    '非法状态重置为待审',
    '统计资讯总数 / 待审 / 已发布',
  ]
})

async function run() {
  if (!['news', 'company'].includes(moduleKey.value)) {
    ElMessage.error('未知模块')
    return
  }
  busy.value = true
  result.value = null
  try {
    const res = await api(`/admin/modules/${moduleKey.value}/refresh`, { method: 'POST' })
    result.value = (res as any)?.data || {}
    ElMessage.success((res as any)?.message || '更新完成')
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '更新失败')
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
        <p>对齐 Destoon「更新数据」：批处理修复字段与统计，可重复执行</p>
      </div>
      <el-button type="warning" :loading="busy" @click="run">立即更新</el-button>
    </div>
    <section class="panel">
      <h3>将执行</h3>
      <ul>
        <li v-for="(h, i) in hints" :key="i">{{ h }}</li>
      </ul>
      <div v-if="result" class="result">
        <h3>结果</h3>
        <el-descriptions :column="2" border>
          <el-descriptions-item v-for="(v, k) in result" :key="k" :label="String(k)">{{ v }}</el-descriptions-item>
        </el-descriptions>
      </div>
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
  padding: 18px 22px;
}
.panel h3 { margin: 0 0 10px; font-size: 15px; color: #1a2332; }
ul { margin: 0; padding-left: 18px; color: #475569; line-height: 1.8; }
.result { margin-top: 18px; }
</style>

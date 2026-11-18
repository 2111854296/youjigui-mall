<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: ['auth', 'enterprise'] })
useHead({ title: '公司新闻' })
const { api } = useApi()
const { data, refresh } = useMemberData('my-shop', () => api('/member/shop'))
const shop = computed(() => (data.value as any)?.data || {})
const newsForm = reactive({ title: '', content: '' })
const imgCount = computed(() => (String(newsForm.content || '').match(/<img\b/gi) || []).length)

async function addNews() {
  await api('/member/shop/news', { method: 'POST', body: { ...newsForm } })
  newsForm.title = ''
  newsForm.content = ''
  await refresh()
}
async function delNews(id: number) {
  await api(`/member/shop/news/${id}`, { method: 'DELETE' })
  await refresh()
}
</script>

<template>
  <div class="panel">
    <h3>公司新闻</h3>
    <div class="body">
      <table class="table">
        <thead>
          <tr><th>标题</th><th style="width:160px">时间</th><th style="width:80px">操作</th></tr>
        </thead>
        <tbody>
          <tr v-for="n in shop.news || []" :key="n.id">
            <td>{{ n.title }}</td>
            <td>{{ String(n.created_at || '').slice(0, 16) || '—' }}</td>
            <td><span class="ops"><button type="button" class="danger" @click="delNews(n.id)">删除</button></span></td>
          </tr>
          <tr v-if="!(shop.news || []).length"><td colspan="3" class="muted">暂无新闻</td></tr>
        </tbody>
      </table>
      <form class="mf-form" @submit.prevent="addNews">
        <section class="mf-sec">
          <div class="mf-sec-hd">添加新闻</div>
          <div class="mf-sec-bd">
            <div class="form-row"><label>标题 <em>*</em></label><input v-model="newsForm.title" required></div>
            <div class="form-row">
              <label>内容 <span class="img-hint">（可拖入插入图片 {{ imgCount }}/3）</span></label>
              <RichTextEditor v-model="newsForm.content" :max-images="3" min-height="120px" />
            </div>
          </div>
        </section>
        <div class="mf-actions"><button class="btn" type="submit">添加新闻</button></div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.img-hint { font-weight: 400; color: #5a6b85; font-size: 12px; }
</style>

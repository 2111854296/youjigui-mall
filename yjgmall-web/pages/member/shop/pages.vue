<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: ['auth', 'enterprise'] })
useHead({ title: '公司单页' })
const { api } = useApi()
const { data, refresh } = useMemberData('my-shop', () => api('/member/shop'))
const shop = computed(() => (data.value as any)?.data || {})
const pageForm = reactive({ title: '', slug: '', content: '' })

async function addPage() {
  await api('/member/shop/pages', { method: 'POST', body: { ...pageForm } })
  pageForm.title = ''
  pageForm.slug = ''
  pageForm.content = ''
  await refresh()
}
async function delPage(id: number) {
  await api(`/member/shop/pages/${id}`, { method: 'DELETE' })
  await refresh()
}
</script>

<template>
  <div class="panel">
    <h3>公司单页</h3>
    <div class="body">
      <table class="table">
        <thead>
          <tr><th>标题</th><th>标识</th><th style="width:80px">操作</th></tr>
        </thead>
        <tbody>
          <tr v-for="p in shop.pages || []" :key="p.id">
            <td>{{ p.title }}</td>
            <td>{{ p.slug || '—' }}</td>
            <td><span class="ops"><button type="button" class="danger" @click="delPage(p.id)">删除</button></span></td>
          </tr>
          <tr v-if="!(shop.pages || []).length"><td colspan="3" class="muted">暂无单页</td></tr>
        </tbody>
      </table>
      <form class="mf-form" @submit.prevent="addPage">
        <section class="mf-sec">
          <div class="mf-sec-hd">添加单页</div>
          <div class="mf-sec-bd">
            <div class="mf-grid-2">
              <div class="form-row"><label>标题 <em>*</em></label><input v-model="pageForm.title" required></div>
              <div class="form-row"><label>标识</label><input v-model="pageForm.slug" placeholder="可选"></div>
            </div>
            <div class="form-row"><label>内容</label><RichTextEditor v-model="pageForm.content" min-height="140px" /></div>
          </div>
        </section>
        <div class="mf-actions"><button class="btn" type="submit">添加单页</button></div>
      </form>
    </div>
  </div>
</template>

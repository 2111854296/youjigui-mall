<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: ['auth', 'enterprise'] })
useHead({ title: '友情链接' })
const { api } = useApi()
const { data, refresh } = useMemberData('my-shop', () => api('/member/shop'))
const shop = computed(() => (data.value as any)?.data || {})
const linkForm = reactive({ name: '', url: '' })

async function addLink() {
  await api('/member/shop/links', { method: 'POST', body: { ...linkForm } })
  linkForm.name = ''
  linkForm.url = ''
  await refresh()
}
async function delLink(id: number) {
  await api(`/member/shop/links/${id}`, { method: 'DELETE' })
  await refresh()
}
</script>

<template>
  <div class="panel">
    <h3>友情链接</h3>
    <div class="body">
      <table class="table">
        <thead>
          <tr><th>名称</th><th>链接</th><th style="width:80px">操作</th></tr>
        </thead>
        <tbody>
          <tr v-for="l in shop.links || []" :key="l.id">
            <td>{{ l.name }}</td>
            <td><a :href="l.url" target="_blank" rel="noopener">{{ l.url }}</a></td>
            <td><span class="ops"><button type="button" class="danger" @click="delLink(l.id)">删除</button></span></td>
          </tr>
          <tr v-if="!(shop.links || []).length"><td colspan="3" class="muted">暂无链接</td></tr>
        </tbody>
      </table>
      <form class="mf-form" @submit.prevent="addLink">
        <section class="mf-sec">
          <div class="mf-sec-hd">添加链接</div>
          <div class="mf-sec-bd">
            <div class="mf-grid-2">
              <div class="form-row"><label>名称 <em>*</em></label><input v-model="linkForm.name" required></div>
              <div class="form-row"><label>链接 <em>*</em></label><input v-model="linkForm.url" required placeholder="https://"></div>
            </div>
          </div>
        </section>
        <div class="mf-actions"><button class="btn" type="submit">添加链接</button></div>
      </form>
    </div>
  </div>
</template>

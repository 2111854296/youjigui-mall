<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: ['auth', 'enterprise'] })
useHead({ title: '技术分享' })
const { api } = useApi()
const { data, refresh } = useMemberData('member-articles', () => api('/member/articles'))
const list = computed(() => (data.value as any)?.data || [])

async function remove(id: number) {
  await api(`/member/articles/${id}`, { method: 'DELETE' })
  await refresh()
}
</script>

<template>
  <div class="panel">
    <h3>
      技术分享
      <NuxtLink class="back-list" to="/member/articles/create">发布分享</NuxtLink>
    </h3>
    <p class="hint muted">发布后经审核，展示在前台「信息资讯 · 技术分享」栏目。</p>
    <div class="body">
      <table class="table">
        <thead><tr><th>标题</th><th>状态</th><th></th></tr></thead>
        <tbody>
          <tr v-for="a in list" :key="a.id">
            <td>{{ a.title }}</td>
            <td>{{ auditStatusLabel(a.status) }}</td>
            <td>
              <NuxtLink :to="`/member/articles/${a.id}`">编辑</NuxtLink>
              ·
              <button class="btn btn-outline" type="button" @click="remove(a.id)">删除</button>
            </td>
          </tr>
          <tr v-if="!list.length"><td colspan="3" class="muted">暂无技术分享</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.hint { margin: 0 0 10px; font-size: 13px; }
</style>

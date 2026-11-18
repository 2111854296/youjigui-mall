<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '问客服' })
const { api } = useApi()
const form = reactive({ title: '', content: '', contact_name: '', contact_phone: '' })
const { data, refresh } = useMemberData('asks', () => api('/member/asks'))
const list = computed(() => (data.value as any)?.data || [])

async function submit() {
  await api('/member/asks', { method: 'POST', body: { ...form } })
  await appOk('已提交。', '提交成功')
  form.title = ''
  form.content = ''
  await refresh()
}
</script>

<template>
  <div class="panel">
    <h3>问客服</h3>
    <div class="body">
      <form style="max-width:520px;margin-bottom:16px" @submit.prevent="submit">
        <div class="form-row"><label>标题</label><input v-model="form.title" required></div>
        <div class="form-row"><label>内容</label><textarea v-model="form.content" rows="5" required></textarea></div>
        <div class="form-row"><label>联系人</label><input v-model="form.contact_name"></div>
        <div class="form-row"><label>电话</label><input v-model="form.contact_phone"></div>
        <button class="btn" type="submit">提交</button>
      </form>
      <table class="table">
        <thead><tr><th>标题</th><th>状态</th><th>回复</th></tr></thead>
        <tbody>
          <tr v-for="a in list" :key="a.id">
            <td>{{ a.title }}</td>
            <td>{{ auditStatusLabel(a.status) }}</td>
            <td>{{ a.reply || '-' }}</td>
          </tr>
          <tr v-if="!list.length"><td colspan="3" class="muted">暂无工单</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '贸易提醒' })
const { api } = useApi()
const form = reactive({ module: 'sell', keyword: '', area: '', price_min: '', price_max: '' })
const { data, refresh } = useMemberData('alerts', () => api('/member/alerts'))
const list = computed(() => (data.value as any)?.data || [])

async function add() {
  await api('/member/alerts', {
    method: 'POST',
    body: {
      module: form.module,
      keyword: form.keyword,
      area: form.area || null,
      price_min: form.price_min ? Number(form.price_min) : null,
      price_max: form.price_max ? Number(form.price_max) : null,
    },
  })
  form.keyword = ''
  await refresh()
}

async function remove(id: number) {
  await api(`/member/alerts/${id}`, { method: 'DELETE' })
  await refresh()
}
</script>

<template>
  <div class="panel">
    <h3>贸易提醒</h3>
    <div class="body">
      <form style="max-width:520px;margin-bottom:16px" @submit.prevent="add">
        <div class="form-row">
          <label>模块</label>
          <select v-model="form.module">
            <option value="sell">供应</option>
            <option value="buy">采购</option>
            <option value="mall">商城</option>
            <option value="company">企业</option>
          </select>
        </div>
        <div class="form-row"><label>关键词</label><input v-model="form.keyword" required></div>
        <div class="form-row"><label>地区</label><input v-model="form.area"></div>
        <div class="form-row"><label>价格区间</label>
          <div style="display:flex;gap:8px">
            <input v-model="form.price_min" type="number" placeholder="最低" style="width:100px">
            <input v-model="form.price_max" type="number" placeholder="最高" style="width:100px">
          </div>
        </div>
        <button class="btn" type="submit">添加提醒</button>
      </form>
      <table class="table">
        <thead><tr><th>模块</th><th>关键词</th><th>地区</th><th>价格</th><th></th></tr></thead>
        <tbody>
          <tr v-for="a in list" :key="a.id">
            <td>{{ a.module }}</td>
            <td>{{ a.keyword }}</td>
            <td>{{ a.area || '-' }}</td>
            <td>{{ a.price_min || '-' }} ~ {{ a.price_max || '-' }}</td>
            <td><button class="btn btn-outline" type="button" @click="remove(a.id)">删除</button></td>
          </tr>
          <tr v-if="!list.length"><td colspan="5" class="muted">暂无提醒</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

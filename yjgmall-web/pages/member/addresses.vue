<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '收货地址' })
const { api } = useApi()
const err = ref('')
const form = reactive({
  name: '',
  phone: '',
  province: '',
  city: '',
  district: '',
  address: '',
  is_default: true,
})

const { data, refresh } = useMemberData('my-addresses', () => api('/member/addresses'))
const items = computed(() => (data.value as any)?.data || [])

async function submit() {
  err.value = ''
  try {
    await api('/member/addresses', { method: 'POST', body: { ...form } })
    await appOk('地址已保存。', '保存成功')
    form.address = ''
    await refresh()
  } catch (e: any) {
    await appFail(apiErrorText(e, '保存失败'), '保存失败')
  }
}

async function remove(id: number) {
  await api(`/member/addresses/${id}`, { method: 'DELETE' })
  await refresh()
}
</script>

<template>
  <div class="panel">
    <h3>收货地址</h3>
    <div class="body">
      <div v-if="err" class="alert alert-error">{{ err }}</div>

      <table class="table">
        <thead>
          <tr><th>收货人</th><th>手机</th><th>地址</th><th style="width:80px">操作</th></tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.name }}<span v-if="item.is_default"> · 默认</span></td>
            <td>{{ item.phone }}</td>
            <td>{{ item.full }}</td>
            <td><span class="ops"><button type="button" class="danger" @click="remove(item.id)">删除</button></span></td>
          </tr>
          <tr v-if="!items.length"><td colspan="4" class="muted">暂无地址</td></tr>
        </tbody>
      </table>

      <form class="mf-form" @submit.prevent="submit">
        <section class="mf-sec">
          <div class="mf-sec-hd">新增地址</div>
          <div class="mf-sec-bd">
            <div class="mf-grid-2">
              <div class="form-row"><label>收货人 <em>*</em></label><input v-model="form.name" required></div>
              <div class="form-row"><label>手机 <em>*</em></label><input v-model="form.phone" required></div>
            </div>
            <div class="mf-grid-3">
              <div class="form-row"><label>省</label><input v-model="form.province"></div>
              <div class="form-row"><label>市</label><input v-model="form.city"></div>
              <div class="form-row"><label>区</label><input v-model="form.district"></div>
            </div>
            <div class="form-row"><label>详细地址 <em>*</em></label><input v-model="form.address" required></div>
            <label style="font-size:13px;font-weight:400"><input v-model="form.is_default" type="checkbox"> 设为默认</label>
          </div>
        </section>
        <div class="mf-actions"><button class="btn" type="submit">保存地址</button></div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'channel' })
useHead({ title: '招商合作' })
const config = useRuntimeConfig()
const { api } = useApi()
const { user } = useAuth()
const msg = ref('')
const err = ref('')
const { data } = await useAsyncData('invest', () => $fetch(`${config.public.apiBase}/invest`))
const items = computed(() => (data.value as any)?.data || [])
const form = reactive({
  invest_project_id: null as number | null,
  company_name: user.value?.company_name || '',
  contact_name: user.value?.name || '',
  contact_phone: user.value?.mobile || '',
  content: '',
})

async function apply() {
  err.value = ''
  msg.value = ''
  try {
    await api('/invest/apply', { method: 'POST', body: { ...form }, auth: !!user.value })
    await appOk('招商意向已提交。', '提交成功')
    form.content = ''
  } catch (e: any) {
    await appFail(apiErrorText(e, '提交失败'), '提交失败')
  }
}
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <span class="on">招商合作</span>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>招商提示：</b>
        提交合作意向后，平台或项目方将尽快与您联系，请保持电话畅通。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="dt-layout">
          <div class="dt-main">
            <div class="list-panel">
              <div class="lp-hd"><h1>招商项目</h1></div>
              <NuxtLink v-for="item in items" :key="item.id" class="lp-item" :to="`/invest/${item.id}`">
                <div class="body">
                  <div class="tt">{{ item.title }}</div>
                  <div class="sum">{{ item.area || '地区不限' }} · {{ item.amount || '金额面议' }}</div>
                </div>
              </NuxtLink>
              <p v-if="!items.length" class="empty">暂无项目</p>
            </div>
          </div>
          <div class="dt-side">
            <div class="blk">
              <div class="b-head"><b>提交合作意向</b></div>
              <div v-if="msg" class="alert alert-success">{{ msg }}</div>
              <div v-if="err" class="alert alert-error">{{ err }}</div>
              <form @submit.prevent="apply">
                <div class="form-row">
                  <label>意向项目</label>
                  <select v-model="form.invest_project_id">
                    <option :value="null">综合咨询</option>
                    <option v-for="item in items" :key="item.id" :value="item.id">{{ item.title }}</option>
                  </select>
                </div>
                <div class="form-row"><label>公司</label><input v-model="form.company_name"></div>
                <div class="form-row"><label>联系人</label><input v-model="form.contact_name" required></div>
                <div class="form-row"><label>电话</label><input v-model="form.contact_phone" required></div>
                <div class="form-row"><label>说明</label><textarea v-model="form.content" /></div>
                <button class="btn" type="submit">提交意向</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'channel' })
const route = useRoute()
const config = useRuntimeConfig()
const { api } = useApi()
const { user } = useAuth()
const id = route.params.id as string
const msg = ref('')
const form = reactive({ receiver_name: '', receiver_phone: '', receiver_address: '' })
const { data, error, refresh } = await useAsyncData(`gift-${id}`, () => $fetch(`${config.public.apiBase}/gift/${id}`))
const item = computed(() => (data.value as any)?.data)
useHead(() => ({ title: item.value?.title || '礼品详情' }))

async function redeem() {
  if (!user.value) return navigateTo({ path: '/login', query: { redirect: route.fullPath } })
  msg.value = ''
  try {
    await api(`/gift/${id}/redeem`, { method: 'POST', body: { ...form } })
    await refresh()
    await appOk('兑换成功。', '操作成功')
  } catch (e: any) {
    await appFail(apiErrorText(e, '兑换失败'), '操作失败')
  }
}
</script>

<template>
  <div class="ch-portal">
    <div class="crumb-row">
      <div class="container">
        <div class="crumb-tabs">
          <NuxtLink to="/">首页</NuxtLink>
          <NuxtLink to="/gift">积分换礼</NuxtLink>
          <span class="on">{{ item?.title || '详情' }}</span>
        </div>
      </div>
    </div>

    <div class="tip-bar">
      <div class="container">
        <b>兑换提示：</b>
        兑换后积分即时扣除，请核对收货信息后再提交。
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div v-if="error" class="blk empty">不存在</div>
        <template v-else-if="item">
          <div class="dt-card">
            <div class="dt-sum">
              <h1>{{ item.title }}</h1>
              <p class="price">{{ item.points }} 积分</p>
              <p class="meta">库存 {{ item.stock }} · 我的积分 {{ user?.points ?? '-' }}</p>
              <p v-if="item.summary" style="margin:0 0 12px;font-size:14px;color:var(--ink-2);line-height:1.6">{{ item.summary }}</p>
            </div>
            <div class="dt-body" v-html="item.content" />
          </div>

          <div class="blk" style="margin-top:14px;max-width:520px">
            <div class="b-head"><b>填写收货信息</b></div>
            <form @submit.prevent="redeem">
              <div v-if="msg" class="alert" :class="msg.includes('成功') ? 'alert-success' : 'alert-error'">{{ msg }}</div>
              <div class="form-row"><label>收货人</label><input v-model="form.receiver_name" required></div>
              <div class="form-row"><label>电话</label><input v-model="form.receiver_phone" required></div>
              <div class="form-row"><label>地址</label><input v-model="form.receiver_address" required></div>
              <button class="btn" type="submit">立即兑换</button>
            </form>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>

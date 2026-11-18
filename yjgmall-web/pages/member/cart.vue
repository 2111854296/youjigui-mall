<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '购物车' })
const { api } = useApi()
const msg = ref('')
const loading = ref(false)
const addressId = ref<number | null>(null)
const form = reactive({
  receiver_name: '',
  receiver_phone: '',
  receiver_address: '',
  remark: '',
})

const { data, refresh } = useMemberData('my-cart', () => api('/member/cart'))
const { data: addrData } = useMemberData('cart-addrs', () => api('/member/addresses'))
const items = computed(() => (data.value as any)?.data || [])
const addresses = computed(() => (addrData.value as any)?.data || [])

function unitPrice(item: any) {
  const n = Number(item?.price ?? item?.product?.price)
  return Number.isFinite(n) ? n : 0
}
function lineAmount(item: any) {
  return unitPrice(item) * Math.max(0, Number(item?.quantity || 0))
}
function money(n: number) {
  return (Number.isFinite(n) ? n : 0).toFixed(2)
}
const total = computed(() => items.value.reduce((s: number, i: any) => s + lineAmount(i), 0))

watch(addresses, (list) => {
  if (!list.length || addressId.value) return
  const def = list.find((a: any) => a.is_default) || list[0]
  pickAddress(def)
}, { immediate: true })

function pickAddress(a: any) {
  if (!a) return
  addressId.value = a.id
  form.receiver_name = a.name || a.receiver_name || ''
  form.receiver_phone = a.phone || a.mobile || ''
  form.receiver_address = [a.area, a.address].filter(Boolean).join(' ') || a.full_address || a.address || ''
}

async function removeItem(id: number) {
  await api(`/member/cart/${id}`, { method: 'DELETE' })
  await refresh()
}

async function changeQty(item: any, delta: number) {
  const next = Math.max(1, Number(item.quantity || 1) + delta)
  await api(`/member/cart/${item.id}`, { method: 'PUT', body: { quantity: next } })
  await refresh()
}

async function setQty(item: any, ev: Event) {
  const v = Math.max(1, Math.min(9999, Number((ev.target as HTMLInputElement).value) || 1))
  await api(`/member/cart/${item.id}`, { method: 'PUT', body: { quantity: v } })
  await refresh()
}

async function checkout() {
  loading.value = true
  msg.value = ''
  try {
    const res = await api<{ data?: { id?: number }; message?: string }>('/member/checkout', { method: 'POST', body: { ...form } })
    const oid = res?.data?.id
    if (oid) return navigateTo(`/member/fund-pay/${oid}`)
    await appOk(res?.message || '请上传付款凭证。', '提交成功')
  } catch (e: any) {
    await appFail(apiErrorText(e, '结算失败'), '结算失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="panel">
    <h3>购物车</h3>
    <div class="body">
      <div v-if="msg" class="alert" :class="msg.includes('成功') ? 'alert-success' : 'alert-error'">{{ msg }}</div>
      <table class="table">
        <thead>
          <tr><th>商品</th><th>单价</th><th>数量</th><th>小计</th><th>操作</th></tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>
              <div class="goods">
                <img :src="item.product?.thumb || '/img/logo-yjg.png'" alt="">
                <NuxtLink v-if="item.product?.id" :to="`/mall/${item.product.id}`">{{ item.product.title }}</NuxtLink>
                <span v-else>商品已下架</span>
              </div>
            </td>
            <td class="num">¥{{ money(unitPrice(item)) }}<span v-if="item.product?.unit">/{{ item.product.unit }}</span></td>
            <td>
              <div class="qty">
                <button type="button" @click="changeQty(item, -1)">-</button>
                <input :value="item.quantity" type="number" min="1" @change="setQty(item, $event)">
                <button type="button" @click="changeQty(item, 1)">+</button>
              </div>
            </td>
            <td class="num">¥{{ money(lineAmount(item)) }}</td>
            <td><a href="#" @click.prevent="removeItem(item.id)">删除</a></td>
          </tr>
          <tr v-if="!items.length"><td colspan="5" class="muted">购物车为空</td></tr>
        </tbody>
      </table>
      <p v-if="items.length" class="total">合计：<b>¥{{ money(total) }}</b></p>

      <form v-if="items.length" class="settle" @submit.prevent="checkout">
        <h4>结算信息</h4>
        <div v-if="addresses.length" class="form-row">
          <label>收货地址</label>
          <select :value="addressId ?? ''" @change="pickAddress(addresses.find((a: any) => a.id === Number(($event.target as HTMLSelectElement).value)))">
            <option v-for="a in addresses" :key="a.id" :value="a.id">
              {{ a.name || a.receiver_name }} · {{ a.phone || a.mobile }} · {{ a.address }}
            </option>
          </select>
        </div>
        <div class="form-row"><label>收货人</label><input v-model="form.receiver_name" required></div>
        <div class="form-row"><label>手机</label><input v-model="form.receiver_phone" required></div>
        <div class="form-row"><label>地址</label><input v-model="form.receiver_address" required></div>
        <div class="form-row"><label>备注</label><input v-model="form.remark"></div>
        <button class="btn" type="submit" :disabled="loading">
          {{ loading ? '提交中…' : '对公付款' }}
        </button>
        <NuxtLink to="/member/addresses" class="btn btn-outline" style="margin-left:8px">管理地址</NuxtLink>
      </form>
    </div>
  </div>
</template>

<style scoped>
.goods { display: flex; align-items: center; gap: 10px; min-width: 0; }
.goods img { width: 56px; height: 56px; object-fit: cover; border-radius: 6px; border: 1px solid var(--line); flex: none; background: #f6f8fb; }
.goods a, .goods span { color: var(--ink); font-weight: 600; line-height: 1.4; }
.num { white-space: nowrap; font-variant-numeric: tabular-nums; }
.qty { display: inline-flex; align-items: center; }
.qty button {
  width: 28px; height: 28px; border: 1px solid var(--line); background: #fff; cursor: pointer;
}
.qty input { width: 48px; height: 28px; padding: 0; border: 1px solid var(--line); border-left: 0; border-right: 0; text-align: center; font: inherit; }
.total { text-align: right; margin: 14px 0 0; font-size: 15px; }
.total b { color: var(--up); font-size: 20px; }
.settle { max-width: 520px; margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--line); }
.settle h4 { margin: 0 0 12px; font-size: 15px; color: var(--ink); }
</style>

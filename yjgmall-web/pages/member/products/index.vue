<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: ['auth', 'enterprise'] })
useHead({ title: '商品管理' })
const { confirm: appConfirm } = useAppDialog()
const { api } = useApi()
const keyword = ref('')
const sort = ref('date_desc')
const list = ref<any[]>([])
const showcase = ref({ grade_name: '普通会员', limit: 0, used: 0 })
const picked = ref<number[]>([])
const msg = ref('')
const err = ref('')
const tip = ref<HTMLElement | null>(null)

const allOn = computed(() => list.value.length > 0 && picked.value.length === list.value.length)

function showTip() {
  nextTick(() => tip.value?.scrollIntoView({ block: 'nearest' }))
}

function onSite(p: any) {
  return p?.status === 'approved' && Number(p?.is_listed) === 1
}

function reviewLabel(status?: string) {
  if (status === 'offline') return '未在售'
  return auditStatusLabel(status)
}

async function load(notice = false) {
  if (!notice) msg.value = ''
  err.value = ''
  try {
    const res = await api('/member/products', { query: { q: keyword.value, sort: sort.value } })
    list.value = (res as any)?.data || []
    showcase.value = (res as any)?.showcase || showcase.value
    picked.value = picked.value.filter((id) => list.value.some((p) => p.id === id))
    if (notice) {
      msg.value = list.value.length ? `找到 ${list.value.length} 个商品` : '没有找到相关商品'
      showTip()
    }
  } catch (e: any) {
    list.value = []
    await appFail(apiErrorText(e, '加载失败，请稍后再试'), '加载失败')
    showTip()
  }
}

function toggleAll() {
  picked.value = allOn.value ? [] : list.value.map((p) => p.id)
}

function toggleOne(id: number) {
  picked.value = picked.value.includes(id) ? picked.value.filter((x) => x !== id) : [...picked.value, id]
}

async function shelf(listed: 0 | 1, ids = picked.value) {
  const act = listed ? '上架' : '下架'
  if (!ids.length) {
    await appFail(`请先勾选要${act}的商品`, '操作失败')
    return
  }
  const one = ids.length === 1 ? list.value.find((p) => p.id === ids[0]) : null
  try {
    const res = await api('/member/products/shelf', { method: 'POST', body: { ids, listed } })
    const n = Number((res as any)?.updated ?? ids.length)
    if (!n) {
      await appFail(`没有${act}成功，请刷新后再试`, '操作失败')
    } else if (one) {
      const extra = listed ? '审核通过后才会出现在网站上。' : '下架后只有这里能看到。'
      await appOk(`「${one.title}」已${act}。${extra}`, '操作成功')
    } else {
      const extra = listed ? '审核通过后才会出现在网站上。' : '下架后只有这里能看到。'
      await appOk(`已${act} ${n} 个商品。${extra}`, '操作成功')
    }
    await load()
  } catch (e: any) {
    await appFail(apiErrorText(e, `${act}失败，请稍后再试`), '操作失败')
  }
}

async function toggleSample(item: any) {
  const on = item.is_sample ? 0 : 1
  try {
    const res = await api('/member/products/sample', { method: 'POST', body: { id: item.id, on } })
    await appOk((res as any)?.message || (on ? '已放入样品专区。' : '已移出样品专区。'), '操作成功')
    await load()
  } catch (e: any) {
    await appFail(apiErrorText(e, '设置失败，请稍后再试'), '操作失败')
  }
}

async function toggleShowcase(item: any) {
  const on = item.is_showcase ? 0 : 1
  try {
    const res = await api('/member/products/showcase', { method: 'POST', body: { id: item.id, on } })
    await appOk((res as any)?.message || (on ? '已设为展示。' : '已取消展示。'), '操作成功')
    await load()
  } catch (e: any) {
    await appFail(apiErrorText(e, '设置失败，请稍后再试'), '操作失败')
  }
}

async function remove(item: any) {
  if (!(await appConfirm({ title: '删除商品', message: `确认删除「${item.title}」？删除后不能恢复。`, tone: 'danger', confirmText: '删除' }))) return
  try {
    await api(`/member/products/${item.id}`, { method: 'DELETE' })
    await appOk(`「${item.title}」已删除。`, '操作成功')
    await load()
  } catch (e: any) {
    await appFail(apiErrorText(e, '删除失败，请稍后再试'), '操作失败')
  }
}

onMounted(() => load())
</script>

<template>
  <div class="panel">
    <h3>商品管理 <NuxtLink class="back-list" to="/member/products/create">发布商品</NuxtLink></h3>
    <div class="body">
      <p class="hint">上架并且审核通过后，才会出现在网站上。下架后只有这里能看到。</p>
      <p class="hint">{{ showcase.limit ? `${showcase.grade_name}可在商品页右侧展示 ${showcase.limit} 款本店商品。点「展示」优先固定这些商品；没选满时会用其它已上架商品补齐。已选 ${showcase.used} 款。` : '普通会员不能在商品页展示本店商品。白银可展示 2 款，黄金 3 款，星钻 5 款。' }}</p>
      <p class="hint">样品专区和优质产品是两套商品。已上架并通过审核的商品，点「放入」才会出现在样品专区。</p>
      <form class="bar" @submit.prevent="load(true)">
        <input v-model="keyword" placeholder="搜索商品标题">
        <button class="btn sm" type="submit">搜索</button>
        <select v-model="sort" @change="load(false)">
          <option value="date_desc">日期新到旧</option>
          <option value="date_asc">日期旧到新</option>
        </select>
        <button class="btn btn-outline sm" type="button" @click="shelf(1)">批量上架</button>
        <button class="btn btn-outline sm" type="button" @click="shelf(0)">批量下架</button>
      </form>
      <div ref="tip">
        <div v-if="msg" class="alert alert-success">{{ msg }}</div>
        <div v-if="err" class="alert alert-error">{{ err }}</div>
      </div>
      <table class="table">
        <thead>
          <tr>
            <th style="width:42px"><input type="checkbox" :checked="allOn" @change="toggleAll"></th>
            <th>标题</th>
            <th>价格</th>
            <th>库存</th>
            <th>日期</th>
            <th>审核</th>
            <th>上架</th>
            <th>店铺展示</th>
            <th>样品专区</th>
            <th>操作</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in list" :key="p.id">
            <td><input type="checkbox" :checked="picked.includes(p.id)" @change="toggleOne(p.id)"></td>
            <td><NuxtLink class="ttl" :to="`/member/products/${p.id}`">{{ p.title }}</NuxtLink></td>
            <td>{{ p.price }}</td>
            <td>{{ p.stock }}</td>
            <td>{{ String(p.created_at || '').slice(0, 16) }}</td>
            <td>{{ reviewLabel(p.status) }}</td>
            <td>{{ onSite(p) ? '已上架' : '未上架' }}</td>
            <td>
              <span class="ops">
                <em v-if="p.showcase_on">展示中</em>
                <em v-else-if="p.is_showcase">超出档位</em>
                <button type="button" @click="toggleShowcase(p)">{{ p.is_showcase ? '取消' : '展示' }}</button>
              </span>
            </td>
            <td>
              <span class="ops">
                <em v-if="p.is_sample">样品中</em>
                <button type="button" @click="toggleSample(p)">{{ p.is_sample ? '移出' : '放入' }}</button>
              </span>
            </td>
            <td>
              <span class="ops">
                <button type="button" @click="shelf(onSite(p) ? 0 : 1, [p.id])">{{ onSite(p) ? '下架' : '上架' }}</button>
                <NuxtLink :to="`/member/products/${p.id}`">编辑</NuxtLink>
                <button type="button" class="danger" @click="remove(p)">删除</button>
              </span>
            </td>
          </tr>
          <tr v-if="!list.length"><td colspan="10" class="muted">暂无商品</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.hint { margin: 0 0 10px; font-size: 13px; color: #5a6b85; }
.bar { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 10px; }
.bar .sm { padding: 0 12px; }
.bar input,
.bar select,
.bar .sm {
  height: 32px;
  padding: 0 10px;
  font-size: 13px;
  line-height: 30px;
  box-sizing: border-box;
  border-radius: 4px;
}
.bar input,
.bar select { border: 1px solid var(--line); background: #fff; color: #13294b; }
.bar input { min-width: 180px; }
.bar .sm { padding: 0 12px; }
.ttl { color: #1443b8; font-weight: 700; text-decoration: none; }
.ttl:hover { text-decoration: underline; }
.ops { display: inline-flex; align-items: center; gap: 14px; white-space: nowrap; }
.ops button,
.ops a {
  background: none;
  border: 0;
  padding: 0;
  margin: 0;
  font-size: 13px;
  line-height: 1.4;
  color: #2468f2;
  cursor: pointer;
  text-decoration: none;
}
.ops button:hover,
.ops a:hover { text-decoration: underline; }
.ops .danger { color: #e10600; }
.ops em { font-style: normal; color: #0f7b3a; font-size: 12px; }
</style>

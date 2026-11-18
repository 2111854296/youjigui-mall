<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: ['auth', 'enterprise'] })
useHead({ title: '编辑商品' })
const route = useRoute()
const { api } = useApi()
const { alert: appAlert } = useAppDialog()
const id = route.params.id as string
const loading = ref(false)
const form = reactive({
  title: '',
  category_id: null as number | null,
  price: '0',
  stock: 0,
  summary: '',
  content: '',
  thumb: '',
  thumb1: '',
  thumb2: '',
})
const tiers = reactive([
  { min_qty: '', max_qty: '', price: '' },
  { min_qty: '', max_qty: '', price: '' },
  { min_qty: '', max_qty: '', price: '' },
])
const imageCount = ref(1)
const detailImages = reactive(['', '', '', '', ''])

function setImageCount(n: number) {
  imageCount.value = n
  if (n < 2) form.thumb1 = ''
  if (n < 3) form.thumb2 = ''
}

const { data, pending } = useMemberData(`edit-product-${id}`, () => api(`/member/products/${id}`))
watch(() => (data.value as any)?.data, (p) => {
  if (!p) return
  Object.assign(form, {
    title: p.title || '',
    category_id: p.category_id != null ? Number(p.category_id) : (p.category?.id != null ? Number(p.category.id) : null),
    price: String(p.price ?? '0'),
    stock: Number(p.stock || 0),
    summary: p.summary || '',
    content: p.content || '',
    thumb: p.thumb || '',
    thumb1: p.thumb1 || '',
    thumb2: p.thumb2 || '',
  })
  const saved = Array.isArray(p.price_tiers) ? p.price_tiers : []
  for (let i = 0; i < 3; i++) {
    const row = saved[i] || {}
    tiers[i].min_qty = row.min_qty != null && saved[i] ? String(row.min_qty) : ''
    tiers[i].max_qty = row.max_qty != null && saved[i] ? String(row.max_qty) : ''
    tiers[i].price = row.price != null && saved[i] ? String(row.price) : ''
  }
  imageCount.value = [p.thumb, p.thumb1, p.thumb2].reduce((n: number, src: string, i: number) => (String(src || '').trim() ? i + 1 : n), 1)
  const pics = Array.isArray(p.detail_images) ? p.detail_images : []
  for (let i = 0; i < 5; i++) detailImages[i] = String(pics[i] || '')
}, { immediate: true })

function saveBody() {
  return {
    ...form,
    category_id: form.category_id || null,
    price: Number(form.price),
    stock: Number(form.stock),
    price_tiers: tiers,
    detail_images: detailImages,
  }
}

function failText(e: any, fallback: string) {
  return e?.data?.message || e?.data?.msg || e?.message || fallback
}

async function onSubmit() {
  if (!form.category_id) {
    await appAlert({ title: '保存失败', message: '请选择商品分类', tone: 'danger' })
    return
  }
  if (form.title.trim().length > 30) {
    await appAlert({ title: '保存失败', message: '标题请控制在30字以内', tone: 'danger' })
    return
  }
  loading.value = true
  try {
    await api(`/member/products/${id}`, { method: 'PUT', body: saveBody() })
    await appAlert({ title: '保存成功', message: '修改已保存。', tone: 'success' })
  } catch (e: any) {
    await appAlert({ title: '保存失败', message: failText(e, '保存失败，请稍后再试'), tone: 'danger' })
  } finally {
    loading.value = false
  }
}

async function preview() {
  if (form.title.trim().length > 30) {
    await appAlert({ title: '无法预览', message: '标题请控制在30字以内', tone: 'danger' })
    return
  }
  loading.value = true
  try {
    await api(`/member/products/${id}`, { method: 'PUT', body: saveBody() })
    await navigateTo(`/mall/${id}`)
  } catch (e: any) {
    await appAlert({ title: '无法预览', message: failText(e, '保存失败，无法预览'), tone: 'danger' })
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="panel">
    <h3>
      编辑商品
      <NuxtLink class="back-list" to="/member/products">返回列表</NuxtLink>
    </h3>
    <div class="body">
      <p v-if="pending" class="muted">加载中…</p>
      <form v-if="!pending" class="mf-form" @submit.prevent="onSubmit">
        <section class="mf-sec">
          <div class="mf-sec-hd">基本信息</div>
          <div class="mf-sec-bd">
            <div class="form-row">
              <label>分类 <em>*</em></label>
              <CategorySelect v-model="form.category_id" module="sell" />
              <p class="field-hint">先选大类再选小类，例如：硅油 → 二甲基硅油</p>
            </div>
            <div class="form-row">
              <label>标题 <em>*</em></label>
              <input v-model="form.title" required maxlength="30" placeholder="30字以内">
              <p class="field-hint" :class="{ over: form.title.length > 30 }">标题 30 字以内，当前 {{ form.title.length }}/30</p>
            </div>
            <div class="mf-grid-2">
              <div class="form-row"><label>价格 <em>*</em></label><input v-model="form.price" type="number" min="0" step="0.01" required></div>
              <div class="form-row"><label>库存</label><input v-model.number="form.stock" type="number" min="0"></div>
            </div>
            <div class="tier-box">
              <div class="tier-hd">区间价 <span>最多三档，由商家自己填写。不填则全数量都用上面的价格。最后一档截止留空表示该数量及以上。</span></div>
              <div v-for="(tier, i) in tiers" :key="i" class="tier-row">
                <span>第{{ i + 1 }}档</span>
                <input v-model="tier.min_qty" type="number" min="1" placeholder="起始数量">
                <input v-model="tier.max_qty" type="number" min="1" placeholder="截止数量">
                <input v-model="tier.price" type="number" min="0" step="0.01" placeholder="单价">
              </div>
            </div>
          </div>
        </section>
        <section class="mf-sec">
          <div class="mf-sec-hd">商品图片</div>
          <div class="mf-sec-bd">
            <div class="pic-count">
              <span>主图张数</span>
              <button v-for="n in 3" :key="n" type="button" :class="{ on: imageCount === n }" @click="setImageCount(n)">{{ n }} 张</button>
            </div>
            <p class="pic-hint">前台商品页按这个张数切换主图，最多 3 张。</p>
            <div class="mf-pics">
              <ImageUpload v-model="form.thumb" label="第 1 张" hint="方图 1:1，建议 800×800。jpg / png / gif / webp，最大 5MB" />
              <ImageUpload v-if="imageCount >= 2" v-model="form.thumb1" label="第 2 张" hint="方图 1:1，建议 800×800。jpg / png / gif / webp，最大 5MB" />
              <ImageUpload v-if="imageCount >= 3" v-model="form.thumb2" label="第 3 张" hint="方图 1:1，建议 800×800。jpg / png / gif / webp，最大 5MB" />
            </div>
          </div>
        </section>
        <section class="mf-sec">
          <div class="mf-sec-hd">详情页图片</div>
          <div class="mf-sec-bd">
            <p class="pic-hint">显示在商品评价上面。可不传，最多 5 张。竖图 3:4，建议 750×1000。</p>
            <div class="mf-pics detail-pics">
              <ImageUpload v-for="n in 5" :key="n" v-model="detailImages[n - 1]" :label="`第 ${n} 张`" ratio="3 / 4" hint="竖图 3:4，jpg / png / gif / webp，最大 5MB" />
            </div>
          </div>
        </section>
        <section class="mf-sec">
          <div class="mf-sec-hd">详细描述</div>
          <div class="mf-sec-bd">
            <div class="form-row"><label>摘要</label><input v-model="form.summary"></div>
            <div class="form-row"><label>详情</label><RichTextEditor v-model="form.content" min-height="140px" /></div>
          </div>
        </section>
        <div class="mf-actions">
          <button class="btn" type="submit" :disabled="loading">{{ loading ? '保存中…' : '保存修改' }}</button>
          <button class="btn btn-outline" type="button" :disabled="loading" @click="preview">效果预览</button>
          <NuxtLink class="btn btn-outline" to="/member/products">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.pic-count { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; font-size: 13px; color: #1e4976; font-weight: 700; }
.pic-count button { border: 1px solid #d5e0ee; background: #fff; border-radius: 6px; padding: 4px 12px; cursor: pointer; color: #35577e; }
.pic-count button.on { border-color: #2468f2; background: #eef4ff; color: #1443b8; font-weight: 700; }
.pic-hint { margin: 0 0 12px; font-size: 12px; color: #7b8ba3; }
.detail-pics { grid-template-columns: repeat(5, minmax(0, 120px)); }
.tier-box { margin-top: 8px; }
.tier-hd { font-size: 13px; font-weight: 700; color: #1e4976; margin-bottom: 8px; }
.tier-hd span { display: block; margin-top: 4px; font-weight: 400; color: #7b8ba3; font-size: 12px; }
.tier-row { display: grid; grid-template-columns: 52px 1fr 1fr 1fr; gap: 8px; align-items: center; margin-bottom: 8px; }
.tier-row span { font-size: 12px; color: #35577e; }
.tier-row input { width: 100%; }
.field-hint { margin: 6px 0 0; font-size: 12px; color: #7b8ba3; }
.panel h3 :deep(a.back-list) {
  padding: 6px 14px;
  border-radius: 6px;
  background: #2468f2;
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  text-decoration: none;
}
.panel h3 :deep(a.back-list):hover { background: #1443b8; }
.field-hint.over { color: #f04b3e; }
</style>

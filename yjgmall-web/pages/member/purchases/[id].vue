<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '编辑采购' })
const route = useRoute()
const { api } = useApi()
const id = route.params.id as string
const error = ref('')
const loading = ref(false)
const form = reactive({
  title: '',
  thumb: '',
  budget: '',
  quantity: '',
  unit: 't',
  area: '',
  days: '',
  is_urgent: false,
  summary: '',
  content: '',
  category_id: null as number | null,
})

const { data, pending } = useMemberData(`edit-purchase-${id}`, () => api(`/member/purchases/${id}`))
watch(() => (data.value as any)?.data, (s) => {
  if (!s) return
  Object.assign(form, {
    title: s.title || '',
    thumb: s.thumb || '',
    budget: s.budget || '',
    quantity: s.quantity || '',
    unit: s.unit || 't',
    area: s.area || '',
    days: s.days || '',
    is_urgent: !!s.is_urgent,
    summary: s.summary || '',
    content: s.content || '',
    category_id: s.category_id || null,
  })
}, { immediate: true })

async function onSubmit() {
  error.value = ''
  loading.value = true
  try {
    await api(`/member/purchases/${id}`, {
      method: 'PUT',
      body: {
        ...form,
        budget: form.budget || null,
        days: form.days ? Number(form.days) : null,
        category_id: form.category_id || null,
      },
    })
    await appOk('修改已保存。', '保存成功')
    await navigateTo('/member/purchases')
  } catch (e: any) {
    await appFail(apiErrorText(e, '保存失败'), '保存失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="panel pub">
    <h3>
      编辑采购
      <NuxtLink to="/member/purchases">返回列表</NuxtLink>
    </h3>
    <div class="body">
      <p v-if="pending" class="muted">加载中…</p>
      <div v-if="error" class="alert alert-error">{{ error }}</div>
      <form v-if="!pending" class="pub-form" @submit.prevent="onSubmit">
        <section class="sec">
          <div class="sec-hd">基本信息</div>
          <div class="sec-bd">
            <div class="form-row"><label>分类 <em>*</em></label><CategorySelect v-model="form.category_id" /></div>
            <div class="form-row"><label>标题 <em>*</em></label><input v-model="form.title" required></div>
            <div class="form-row chk"><label class="inline"><input v-model="form.is_urgent" type="checkbox"> 紧急采购</label></div>
          </div>
        </section>
        <section class="sec">
          <div class="sec-hd">封面图</div>
          <div class="sec-bd"><div class="pics"><ImageUpload v-model="form.thumb" label="标题图片" /></div></div>
        </section>
        <section class="sec">
          <div class="sec-hd">需求与预算</div>
          <div class="sec-bd">
            <div class="grid-3">
              <div class="form-row"><label>预算</label><input v-model="form.budget" type="number" step="0.01"></div>
              <div class="form-row"><label>数量</label><input v-model="form.quantity"></div>
              <div class="form-row"><label>单位</label><input v-model="form.unit"></div>
              <div class="form-row"><label>地区</label><input v-model="form.area"></div>
              <div class="form-row"><label>交期(天)</label><input v-model="form.days" type="number" min="0"></div>
            </div>
          </div>
        </section>
        <section class="sec">
          <div class="sec-hd">详细说明</div>
          <div class="sec-bd">
            <div class="form-row"><label>摘要</label><textarea v-model="form.summary" rows="2" /></div>
            <div class="form-row"><label>详情</label><RichTextEditor v-model="form.content" /></div>
          </div>
        </section>
        <div class="actions">
          <button class="btn" type="submit" :disabled="loading">{{ loading ? '保存中…' : '保存修改' }}</button>
          <NuxtLink class="btn btn-outline" to="/member/purchases">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.pub-form { max-width: 860px; }
.sec { border: 1px solid #e8eef5; margin-bottom: 14px; }
.sec-hd {
  padding: 10px 14px; background: linear-gradient(90deg, #f3f7fc, #fff);
  border-bottom: 1px solid #e8eef5; font-weight: 700; color: #1e4976; font-size: 14px;
}
.sec-bd { padding: 14px; }
.form-row label em { color: #f04b3e; font-style: normal; }
.grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0 14px; }
.pics { display: grid; grid-template-columns: minmax(0, 160px); }
.chk .inline { display: inline-flex; align-items: center; gap: 8px; font-weight: 400; }
.actions { display: flex; gap: 10px; padding: 4px 0 8px; }
.actions .btn { min-width: 120px; padding: 10px 18px; }
@media (max-width: 720px) { .grid-3 { grid-template-columns: 1fr; } }
</style>


<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: 'auth' })
useHead({ title: '发布采购' })
const { api } = useApi()
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
const error = ref('')
const loading = ref(false)

async function onSubmit() {
  error.value = ''
  loading.value = true
  try {
    await api('/member/purchases', {
      method: 'POST',
      body: {
        ...form,
        budget: form.budget || null,
        days: form.days ? Number(form.days) : null,
        category_id: form.category_id || null,
      },
    })
    await appOk('已提交。', '提交成功')
    await navigateTo('/member/purchases')
  } catch (e: any) {
    await appFail(apiErrorText(e, '提交失败'), '提交失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="panel pub">
    <h3>
      发布采购
      <NuxtLink to="/member/purchases">返回列表</NuxtLink>
    </h3>
    <div class="body">
      <div v-if="error" class="alert alert-error">{{ error }}</div>
      <form class="pub-form" @submit.prevent="onSubmit">
        <section class="sec">
          <div class="sec-hd">基本信息</div>
          <div class="sec-bd">
            <div class="form-row">
              <label>分类 <em>*</em></label>
              <CategorySelect v-model="form.category_id" />
            </div>
            <div class="form-row">
              <label>标题 <em>*</em></label>
              <input v-model="form.title" required placeholder="如：急需 D4 现货">
            </div>
            <div class="form-row chk">
              <label class="inline"><input v-model="form.is_urgent" type="checkbox"> 标记为紧急采购</label>
            </div>
          </div>
        </section>

        <section class="sec">
          <div class="sec-hd">封面图</div>
          <div class="sec-bd">
            <div class="pics"><ImageUpload v-model="form.thumb" label="标题图片" /></div>
          </div>
        </section>

        <section class="sec">
          <div class="sec-hd">需求与预算</div>
          <div class="sec-bd">
            <div class="grid-3">
              <div class="form-row"><label>预算</label><input v-model="form.budget" type="number" step="0.01" placeholder="可留空面议"></div>
              <div class="form-row"><label>数量</label><input v-model="form.quantity" placeholder="如：20"></div>
              <div class="form-row"><label>单位</label><input v-model="form.unit"></div>
              <div class="form-row"><label>地区</label><input v-model="form.area" placeholder="如：江苏"></div>
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
          <button class="btn" type="submit" :disabled="loading">{{ loading ? '提交中…' : '提交审核' }}</button>
          <NuxtLink class="btn btn-outline" to="/member/purchases">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.pub-form { max-width: 860px; }
.sec { border: 1px solid #e8eef5; margin-bottom: 14px; background: #fff; }
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
@media (max-width: 720px) {
  .grid-3 { grid-template-columns: 1fr; }
}
</style>

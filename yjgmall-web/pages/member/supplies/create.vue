<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: ['auth', 'enterprise'] })
useHead({ title: '发布供应' })
const { api } = useApi()
const form = reactive({
  title: '',
  thumb: '',
  thumb1: '',
  thumb2: '',
  price: '',
  unit: 't',
  min_amount: '',
  amount: '',
  days: '',
  brand: '',
  area: '',
  attr1_name: '规格',
  attr1_value: '',
  attr2_name: '粘度',
  attr2_value: '',
  attr3_name: '含量',
  attr3_value: '',
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
    await api('/member/supplies', {
      method: 'POST',
      body: {
        ...form,
        price: form.price || null,
        min_amount: form.min_amount || null,
        days: form.days || null,
        category_id: form.category_id || null,
      },
    })
    await appOk('已提交。', '提交成功')
    await navigateTo('/member/supplies')
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
      发布供应
      <NuxtLink to="/member/supplies">返回列表</NuxtLink>
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
              <input v-model="form.title" required placeholder="如：气相白炭黑 疏水型 工业级">
            </div>
            <div class="grid-2">
              <div class="form-row">
                <label>品牌</label>
                <input v-model="form.brand" placeholder="选填">
              </div>
              <div class="form-row">
                <label>地区</label>
                <input v-model="form.area" placeholder="如：广东东莞">
              </div>
            </div>
          </div>
        </section>

        <section class="sec">
          <div class="sec-hd">产品图片</div>
          <div class="sec-bd">
            <div class="pics">
              <ImageUpload v-model="form.thumb" label="主图" hint="方图 1:1，建议 800×800。jpg / png / gif / webp，最大 5MB" />
              <ImageUpload v-model="form.thumb1" label="附图 1" hint="方图 1:1，建议 800×800。jpg / png / gif / webp，最大 5MB" />
              <ImageUpload v-model="form.thumb2" label="附图 2" hint="方图 1:1，建议 800×800。jpg / png / gif / webp，最大 5MB" />
            </div>
          </div>
        </section>

        <section class="sec">
          <div class="sec-hd">价格与供货</div>
          <div class="sec-bd">
            <div class="grid-3">
              <div class="form-row">
                <label>价格</label>
                <input v-model="form.price" type="number" step="0.01" placeholder="面议可留空">
              </div>
              <div class="form-row">
                <label>单位</label>
                <input v-model="form.unit" placeholder="吨 / kg">
              </div>
              <div class="form-row">
                <label>发货天数</label>
                <input v-model="form.days" type="number" min="0" placeholder="如：3">
              </div>
              <div class="form-row">
                <label>起订量</label>
                <input v-model="form.min_amount" placeholder="如：1">
              </div>
              <div class="form-row">
                <label>供货量</label>
                <input v-model="form.amount" placeholder="如：100 吨">
              </div>
            </div>
          </div>
        </section>

        <section class="sec">
          <div class="sec-hd">规格参数</div>
          <div class="sec-bd">
            <div class="grid-3 attrs">
              <div class="form-row">
                <label>参数1</label>
                <input v-model="form.attr1_name">
                <input v-model="form.attr1_value" placeholder="参数值" class="mt">
              </div>
              <div class="form-row">
                <label>参数2</label>
                <input v-model="form.attr2_name">
                <input v-model="form.attr2_value" placeholder="参数值" class="mt">
              </div>
              <div class="form-row">
                <label>参数3</label>
                <input v-model="form.attr3_name">
                <input v-model="form.attr3_value" placeholder="参数值" class="mt">
              </div>
            </div>
          </div>
        </section>

        <section class="sec">
          <div class="sec-hd">详细描述</div>
          <div class="sec-bd">
            <div class="form-row">
              <label>摘要</label>
              <textarea v-model="form.summary" rows="2" placeholder="一句话介绍，列表页展示" />
            </div>
            <div class="form-row">
              <label>详情</label>
              <RichTextEditor v-model="form.content" placeholder="产品说明、包装、交期等" />
            </div>
          </div>
        </section>

        <div class="actions">
          <button class="btn" type="submit" :disabled="loading">
            {{ loading ? '提交中…' : '提交审核' }}
          </button>
          <NuxtLink class="btn btn-outline" to="/member/supplies">取消</NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.pub-form { max-width: 860px; }
.sec {
  border: 1px solid #e8eef5;
  margin-bottom: 14px;
  background: #fff;
}
.sec-hd {
  padding: 10px 14px;
  background: linear-gradient(90deg, #f3f7fc, #fff);
  border-bottom: 1px solid #e8eef5;
  font-weight: 700;
  color: #1e4976;
  font-size: 14px;
}
.sec-bd { padding: 14px; }
.form-row label em { color: #f04b3e; font-style: normal; margin-left: 2px; }
.grid-2 {
  display: grid; grid-template-columns: 1fr 1fr; gap: 0 14px;
}
.grid-3 {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 0 14px;
}
.pics {
  display: grid; grid-template-columns: repeat(3, minmax(0, 160px)); gap: 16px;
}
.mt { margin-top: 8px; }
.actions {
  display: flex; gap: 10px; align-items: center; padding: 4px 0 8px;
}
.actions .btn { min-width: 120px; padding: 10px 18px; }
@media (max-width: 720px) {
  .grid-2, .grid-3, .pics { grid-template-columns: 1fr; }
}
</style>

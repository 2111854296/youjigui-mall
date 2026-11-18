<script setup lang="ts">
import { FIELD_CATS, JOB_ROLE_CATS } from '~/utils/jobsDemo'

const props = withDefaults(
  defineProps<{
    modelValue: Record<string, any>
    submitText?: string
    loading?: boolean
    cancelTo?: string
  }>(),
  {
    submitText: '提交审核',
    loading: false,
    cancelTo: '/member/jobs',
  },
)
const emit = defineEmits<{
  'update:modelValue': [v: Record<string, any>]
  submit: []
}>()

const WELFARE = ['五险一金', '包吃住', '双休', '年终奖', '带薪年假', '绩效奖金', '房补餐补', '股权激励']
const AREAS = ['东莞', '深圳', '广州', '上海', '苏州', '宁波', '宜昌', '淄博', '佛山', '杭州', '惠州']
const EXPS = ['不限', '应届生', '1-3年', '3-5年', '5-10年', '10年以上']
const EDUS = ['不限', '高中', '大专', '本科', '硕士', '博士']
const NATURES = ['全职', '兼职', '实习']
const SIZES = [
  { v: '', t: '不限' },
  { v: '20-', t: '20人以下' },
  { v: '20-99', t: '20-99人' },
  { v: '100-499', t: '100-499人' },
  { v: '500+', t: '500人以上' },
]
const CATS = FIELD_CATS.filter((c) => c !== '全部')
const ROLES = JOB_ROLE_CATS.filter((c) => c !== '全部')

const form = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const welfareOn = computed(() => {
  const raw = String(form.value.summary || '')
  const set = new Set(raw.split(/[,，、\s]+/).map((s) => s.trim()).filter(Boolean))
  return Object.fromEntries(WELFARE.map((w) => [w, set.has(w)]))
})

function toggleWelfare(w: string) {
  const cur = new Set(
    String(form.value.summary || '')
      .split(/[,，、\s]+/)
      .map((s) => s.trim())
      .filter(Boolean),
  )
  if (cur.has(w)) cur.delete(w)
  else cur.add(w)
  form.value.summary = WELFARE.filter((x) => cur.has(x)).join('、')
}

function onSubmit() {
  emit('submit')
}
</script>

<template>
  <form class="mf-form job-post-form" @submit.prevent="onSubmit">
    <section class="mf-sec">
      <div class="mf-sec-hd">职位信息</div>
      <div class="mf-sec-bd">
        <div class="form-row">
          <label>职位名称 <em>*</em></label>
          <input v-model="form.title" required maxlength="120" placeholder="如：硅橡胶配方研发工程师">
        </div>
        <div class="mf-grid-2">
          <div class="form-row">
            <label>职位类别 <em>*</em></label>
            <select v-model="form.job_role" required>
              <option disabled value="">请选择</option>
              <option v-for="r in ROLES" :key="r" :value="r">{{ r }}</option>
            </select>
          </div>
          <div class="form-row">
            <label>领域分类 <em>*</em></label>
            <select v-model="form.job_category" required>
              <option disabled value="">请选择</option>
              <option v-for="c in CATS" :key="c" :value="c">{{ c }}</option>
            </select>
          </div>
          <div class="form-row">
            <label>工作性质</label>
            <select v-model="form.job_nature">
              <option v-for="n in NATURES" :key="n" :value="n">{{ n }}</option>
            </select>
          </div>
          <div class="form-row">
            <label>工作地区 <em>*</em></label>
            <select v-model="form.area" required>
              <option v-for="a in AREAS" :key="a" :value="a">{{ a }}</option>
            </select>
          </div>
          <div class="form-row">
            <label>招聘人数</label>
            <input v-model="form.headcount" type="number" min="1" placeholder="如：2">
          </div>
          <div class="form-row">
            <label>经验要求</label>
            <select v-model="form.experience">
              <option v-for="e in EXPS" :key="e" :value="e">{{ e }}</option>
            </select>
          </div>
          <div class="form-row">
            <label>学历要求</label>
            <select v-model="form.education">
              <option v-for="e in EDUS" :key="e" :value="e">{{ e }}</option>
            </select>
          </div>
          <div class="form-row">
            <label>企业规模</label>
            <select v-model="form.company_size">
              <option v-for="s in SIZES" :key="s.v || 'all'" :value="s.v">{{ s.t }}</option>
            </select>
          </div>
          <div class="form-row">
            <label>公司名称</label>
            <input v-model="form.company_name" placeholder="企业全称">
          </div>
        </div>
        <div class="form-row">
          <label>薪资范围（K/月）</label>
          <div class="salary-row">
            <input v-model="form.salary_min" type="number" min="0" step="1" placeholder="最低，如 8">
            <span>—</span>
            <input v-model="form.salary_max" type="number" min="0" step="1" placeholder="最高，如 15">
            <span class="muted">K / 月（不填则显示面议）</span>
          </div>
        </div>
        <div class="form-row">
          <label>职位福利</label>
          <div class="welfare-picks">
            <label v-for="w in WELFARE" :key="w" class="welfare-pick" :class="{ on: welfareOn[w] }">
              <input type="checkbox" :checked="welfareOn[w]" @change="toggleWelfare(w)">
              <span>{{ w }}</span>
            </label>
          </div>
        </div>
      </div>
    </section>

    <section class="mf-sec">
      <div class="mf-sec-hd">岗位详情</div>
      <div class="mf-sec-bd">
        <div class="form-row">
          <label>职位描述 <em>*</em></label>
          <RichTextEditor v-model="form.content" placeholder="岗位职责、任职要求、加分项…" min-height="220px" />
        </div>
      </div>
    </section>

    <section class="mf-sec">
      <div class="mf-sec-hd">联系方式</div>
      <div class="mf-sec-bd mf-grid-2">
        <div class="form-row"><label>联系人</label><input v-model="form.contact_name" placeholder="HR 姓名"></div>
        <div class="form-row"><label>联系电话</label><input v-model="form.contact_phone" placeholder="手机号"></div>
      </div>
    </section>

    <div class="mf-actions">
      <button class="btn" type="submit" :disabled="loading">{{ loading ? '提交中…' : submitText }}</button>
      <NuxtLink class="btn btn-outline" :to="cancelTo">取消</NuxtLink>
    </div>
  </form>
</template>

<style scoped>
.salary-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
}
.salary-row input {
  width: 120px;
  max-width: 40%;
}
.welfare-picks {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.welfare-pick {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 6px 12px;
  border: 1px solid var(--line, #e5e7eb);
  border-radius: 999px;
  font-size: 13px;
  cursor: pointer;
  user-select: none;
}
.welfare-pick input {
  display: none;
}
.welfare-pick.on {
  border-color: var(--brand, #2563eb);
  background: color-mix(in srgb, var(--brand, #2563eb) 10%, white);
  color: var(--brand, #2563eb);
  font-weight: 600;
}
.muted {
  color: #94a3b8;
  font-size: 12px;
}
</style>

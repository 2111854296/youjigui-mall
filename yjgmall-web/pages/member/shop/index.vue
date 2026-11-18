<script setup lang="ts">
definePageMeta({ layout: 'member', middleware: ['auth', 'enterprise'] })
useHead({ title: '旺铺设置' })
const { api } = useApi()
const msg = ref('')
const err = ref('')
const busy = ref(false)
const { data, refresh } = useMemberData('my-shop', () => api('/member/shop'))
const shop = computed(() => (data.value as any)?.data || {})

const MODE_OPTIONS = ['制造商', '贸易商', '服务商', '其他组织']

const form = reactive({
  name: '',
  logo: '',
  banner: '',
  env_images: ['', '', '', ''] as string[],
  intro: '',
  content: '',
  area: '',
  address: '',
  zip: '',
  business: '',
  tags: '',
  company_type: '企业单位',
  business_mode: '制造商',
  scale: '',
  capital: '',
  capital_unit: '人民币',
  reg_year: '' as string | number,
  sell_products: '',
  buy_products: '',
  website: '',
  contact: '',
  phone: '',
  fax: '',
  email: '',
  template: 'atelier',
})

const ENV_MIN = 4
const ENV_MAX = 8

function padEnvSlots(list: string[]) {
  const cleaned = list.map(s => String(s || '').trim())
  while (cleaned.length < ENV_MIN) cleaned.push('')
  return cleaned.slice(0, ENV_MAX)
}

function addEnvSlot() {
  if (form.env_images.length >= ENV_MAX) return
  form.env_images.push('')
}

function removeEnvSlot(i: number) {
  if (form.env_images.length <= ENV_MIN) {
    form.env_images[i] = ''
    return
  }
  form.env_images.splice(i, 1)
  if (form.env_images.length < ENV_MIN) {
    while (form.env_images.length < ENV_MIN) form.env_images.push('')
  }
}

const tagSlots = ref(['', '', '', ''])
const sellSlots = ref(['', '', '', '', '', ''])
const buySlots = ref(['', '', '', '', '', ''])

function toSlots(raw: string, sep: RegExp, count: number) {
  const parts = String(raw || '').split(sep).map(s => s.trim()).filter(Boolean).slice(0, 12)
  const n = Math.max(count, parts.length)
  return Array.from({ length: n }, (_, i) => parts[i] || '')
}
function joinSlots(slots: string[], sep: string, max: number) {
  return slots.map(s => s.trim()).filter(Boolean).slice(0, max).join(sep)
}

const modeChecked = computed({
  get: () => String(form.business_mode || '').split(/[,|]/).map(s => s.trim()).filter(Boolean),
  set: (vals: string[]) => {
    form.business_mode = vals.slice(0, 2).join(',')
  },
})

function toggleMode(val: string, on: boolean) {
  let cur = modeChecked.value.slice()
  if (on) {
    if (!cur.includes(val)) cur.push(val)
    if (cur.length > 2) cur = cur.slice(-2)
  } else {
    cur = cur.filter(x => x !== val)
  }
  modeChecked.value = cur
}

const templateOptions = [
  { code: 'atelier', name: '展厅·清雅' },
  { code: 'forge', name: '工场·沉稳' },
  { code: 'ledger', name: '名录·商务' },
]
function normalizeTpl(raw?: string) {
  const t = String(raw || 'atelier')
  if (t === 'forge' || t === 'orange') return 'forge'
  if (t === 'ledger' || t === 'blue') return 'ledger'
  return 'atelier'
}
watch(shop, (s) => {
  Object.assign(form, {
    name: s.name || '',
    logo: s.logo || '',
    banner: s.banner || '',
    env_images: padEnvSlots(Array.isArray(s.env_images) ? s.env_images : []),
    intro: s.intro || '',
    content: s.content || '',
    area: s.area || '',
    address: s.address || '',
    zip: s.zip || '',
    business: s.business || '',
    tags: s.tags || '',
    company_type: s.company_type || '企业单位',
    business_mode: s.business_mode || '制造商',
    scale: s.scale || '',
    capital: s.capital || '',
    capital_unit: s.capital_unit || '人民币',
    reg_year: s.reg_year || '',
    sell_products: s.sell_products || '',
    buy_products: s.buy_products || '',
    website: s.website || '',
    contact: s.contact || '',
    phone: s.phone || '',
    fax: s.fax || '',
    email: s.email || '',
    template: normalizeTpl(s.template),
  })
  tagSlots.value = toSlots(s.tags || '', /[,，]/, 4)
  sellSlots.value = toSlots(s.sell_products || '', /[|｜]/, 6)
  buySlots.value = toSlots(s.buy_products || '', /[|｜]/, 6)
}, { immediate: true })

async function save() {
  if (busy.value) return false
  err.value = ''
  msg.value = ''
  busy.value = true
  try {
    const body: Record<string, any> = { ...form }
    body.reg_year = form.reg_year === '' || form.reg_year == null ? null : Number(form.reg_year)
    body.env_images = form.env_images.map(s => String(s || '').trim()).filter(Boolean).slice(0, ENV_MAX)
    body.tags = joinSlots(tagSlots.value, ',', 4)
    body.sell_products = joinSlots(sellSlots.value, '|', 12)
    body.buy_products = joinSlots(buySlots.value, '|', 12)
    await api('/member/shop', { method: 'PUT', body })
    await appOk('设置已保存。', '保存成功')
    await refresh()
    return true
  } catch (e: any) {
    await appFail(apiErrorText(e, '保存失败'), '保存失败')
    return false
  } finally {
    busy.value = false
  }
}

async function previewShop() {
  const ok = await save()
  if (!ok) return
  const key = String(shop.value?.slug || shop.value?.id || '')
  if (!key) {
    await appFail('保存成功，但还没有旺铺地址', '无法预览')
    return
  }
  await navigateTo(`/companies/${encodeURIComponent(key)}`)
}

function statusLabel(s?: string) {
  if (s === 'approved') return '正常'
  if (s === 'pending') return '待审核'
  if (s === 'rejected') return '未通过'
  if (s === 'offline') return '未开通'
  return auditStatusLabel(s)
}
</script>

<template>
  <div class="panel">
    <h3>
      旺铺设置
      <a href="#" @click.prevent="previewShop">预览旺铺</a>
    </h3>
    <div class="body">
      <div v-if="msg" class="alert alert-success">{{ msg }}</div>
      <div v-if="err" class="alert alert-error">{{ err }}</div>
      <div class="mf-meta">
        <span><em>状态</em><b>{{ statusLabel(shop.status) }}</b></span>
        <span>
          <em>旺铺地址</em>
          <b v-if="shop.slug"><NuxtLink :to="`/companies/${shop.slug}`">/companies/{{ shop.slug }}</NuxtLink></b>
          <b v-else>保存后生成</b>
        </span>
      </div>
      <form class="mf-form" @submit.prevent="save">
        <section class="mf-sec">
          <div class="mf-sec-hd">基本资料</div>
          <div class="mf-sec-bd">
            <div class="form-row"><label>公司名称 <em>*</em></label><input v-model="form.name" required></div>
            <div class="mf-grid-2">
              <div class="form-row">
                <label>旺铺模板</label>
                <select v-model="form.template">
                  <option
                    v-for="t in (shop.templates?.length ? shop.templates : templateOptions)"
                    :key="t.code"
                    :value="t.code"
                  >{{ t.name }}</option>
                </select>
              </div>
              <div class="form-row"><label>所在地区</label><input v-model="form.area" placeholder="如：广东东莞"></div>
            </div>
            <div class="mf-grid-2">
              <div class="form-row">
                <label>公司类型</label>
                <select v-model="form.company_type">
                  <option>企业单位</option>
                  <option>个体经营</option>
                  <option>事业单位</option>
                  <option>其他组织</option>
                </select>
              </div>
              <div class="form-row">
                <label>经营模式 <span class="hint-inline">最多选 2 项</span></label>
                <div class="mode-row">
                  <label v-for="m in MODE_OPTIONS" :key="m" class="mode-item">
                    <input
                      type="checkbox"
                      :checked="modeChecked.includes(m)"
                      @change="toggleMode(m, ($event.target as HTMLInputElement).checked)"
                    >
                    {{ m }}
                  </label>
                </div>
              </div>
            </div>
            <div class="mf-grid-2">
              <div class="form-row">
                <label>人员规模</label>
                <select v-model="form.scale">
                  <option value="">请选择</option>
                  <option>1-19人</option>
                  <option>20-49人</option>
                  <option>50-99人</option>
                  <option>100-499人</option>
                  <option>500人以上</option>
                </select>
              </div>
              <div class="form-row"><label>成立年份</label><input v-model="form.reg_year" type="number" min="1900" max="2100" placeholder="如：2010"></div>
            </div>
            <div class="mf-grid-2">
              <div class="form-row"><label>注册资本</label><input v-model="form.capital" placeholder="如：500万"></div>
              <div class="form-row">
                <label>资本单位</label>
                <select v-model="form.capital_unit">
                  <option>人民币</option>
                  <option>美元</option>
                  <option>欧元</option>
                  <option>港币</option>
                  <option>其他</option>
                </select>
              </div>
            </div>
            <div class="form-row"><label>经营范围</label><input v-model="form.business" placeholder="如：DMC、107胶等"></div>
            <div class="form-row">
              <label>名企标签 <span class="hint-inline">首页展示，最多4个</span></label>
              <div class="chip-inputs">
                <input v-for="(_, i) in tagSlots" :key="'tag'+i" v-model="tagSlots[i]" :placeholder="['如：广东', '如：硫化剂', '如：食品级', '如：生产销售'][i] || '标签'">
              </div>
            </div>
            <div class="form-row">
              <label>销售产品</label>
              <div class="chip-inputs">
                <input v-for="(_, i) in sellSlots" :key="'sell'+i" v-model="sellSlots[i]" placeholder="产品名称">
              </div>
            </div>
            <div class="form-row">
              <label>采购产品</label>
              <div class="chip-inputs">
                <input v-for="(_, i) in buySlots" :key="'buy'+i" v-model="buySlots[i]" placeholder="产品名称">
              </div>
            </div>
            <div class="form-row"><label>公司网址</label><input v-model="form.website" placeholder="https://"></div>
          </div>
        </section>

        <section class="mf-sec">
          <div class="mf-sec-hd">公司 Logo / 横幅</div>
          <div class="mf-sec-bd">
            <div class="mf-pics one">
              <ImageUpload
                v-model="form.logo"
                label="Logo"
                hint="方图 1:1，建议 500×500。jpg / png / gif / webp，最大 5MB"
              />
            </div>
            <div class="banner-upload">
              <ImageUpload
                v-model="form.banner"
                label="旺铺广告图（首页通栏大图，可选）"
                ratio="1280 / 200"
                wide
                hint="横图 6:1，建议 1280×200。jpg / png / gif / webp，最大 5MB"
              />
            </div>
          </div>
        </section>

        <section class="mf-sec">
          <div class="mf-sec-hd">
            厂房环境图
            <span class="hint-inline">首页展示 4 格可滑动，默认 4 张，最多加到 8 张</span>
          </div>
          <div class="mf-sec-bd">
            <div class="mf-pics env-pics">
              <div v-for="(_, i) in form.env_images" :key="'env'+i" class="env-slot">
                <ImageUpload
                  v-model="form.env_images[i]"
                  :label="`环境图 ${i + 1}`"
                  ratio="2 / 1"
                  hint="横图 2:1，建议 1000×500。jpg / png / gif / webp，最大 5MB"
                />
                <button
                  v-if="form.env_images.length > ENV_MIN || form.env_images[i]"
                  type="button"
                  class="env-rm"
                  @click="removeEnvSlot(i)"
                >清除</button>
              </div>
            </div>
            <button
              v-if="form.env_images.length < ENV_MAX"
              type="button"
              class="btn btn-outline env-add"
              @click="addEnvSlot"
            >添加环境图（{{ form.env_images.length }}/{{ ENV_MAX }}）</button>
          </div>
        </section>

        <section class="mf-sec">
          <div class="mf-sec-hd">联系方式</div>
          <div class="mf-sec-bd">
            <div class="mf-grid-2">
              <div class="form-row"><label>联系人</label><input v-model="form.contact"></div>
              <div class="form-row"><label>电话</label><input v-model="form.phone"></div>
            </div>
            <div class="mf-grid-2">
              <div class="form-row"><label>传真</label><input v-model="form.fax"></div>
              <div class="form-row"><label>邮箱</label><input v-model="form.email" type="email"></div>
            </div>
            <div class="form-row"><label>公司地址</label><input v-model="form.address"></div>
            <div class="form-row"><label>邮政编码</label><input v-model="form.zip"></div>
          </div>
        </section>

        <section class="mf-sec">
          <div class="mf-sec-hd">公司介绍</div>
          <div class="mf-sec-bd">
            <div class="form-row"><label>简介</label><textarea v-model="form.intro" rows="3" /></div>
            <div class="form-row"><label>详情</label><RichTextEditor v-model="form.content" min-height="200px" /></div>
          </div>
        </section>

        <div class="mf-actions">
          <button class="btn" type="submit" :disabled="busy">保存设置</button>
          <button class="btn btn-outline" type="button" :disabled="busy" @click="previewShop">预览旺铺</button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.hint-inline { font-weight: 400; color: #94a3b8; font-size: 12px; margin-left: 6px; }
.chip-inputs {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 8px;
}
.chip-inputs input { width: 100%; min-width: 0; }
@media (max-width: 720px) {
  .chip-inputs { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
.mode-row { display: flex; flex-wrap: wrap; gap: 10px 16px; padding-top: 6px; }
.mode-item { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #334155; font-weight: 400; }
.banner-upload { margin-top: 12px; }
.env-pics { grid-template-columns: repeat(4, minmax(0, 220px)); }
.env-slot { position: relative; }
.env-rm {
  margin-top: 6px;
  border: 0;
  background: transparent;
  color: #94a3b8;
  font-size: 12px;
  cursor: pointer;
  padding: 0;
}
.env-rm:hover { color: #c2410c; }
.env-add { margin-top: 14px; }
@media (max-width: 720px) {
  .env-pics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
</style>

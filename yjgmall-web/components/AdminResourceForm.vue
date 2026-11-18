<script setup lang="ts">
import { CATEGORY_ICON_OPTIONS, CATEGORY_ICONS } from '~/utils/categoryIcons'
import {
  multiToStorage,
  normalizeMulti,
  normalizeOptions,
  normalizeSelect,
} from '~/utils/adminFormValues'
import { QUOTE_CHIP_LABELS, QUOTE_CATALOG } from '~/utils/quoteCatalog'

export type AdminField = {
  name: string
  label: string
  type: string
  options?: Record<string, string> | string[]
  relation?: string
  relation_label?: string
  readonly?: boolean
  required?: boolean
  create_only?: boolean
  edit_only?: boolean
  hint?: string
  module?: string
  max?: number
  section?: string
  virtual?: boolean
}

const props = defineProps<{
  fields: AdminField[]
  mode: 'create' | 'edit'
  resource?: string
}>()

const model = defineModel<Record<string, any>>({ required: true })

const { api } = useApi()
const relationCache = reactive<Record<string, { id: number | string, label: string }[]>>({})
const relationLoading = reactive<Record<string, boolean>>({})
const relationReady = reactive<Record<string, boolean>>({})
const relationTimers = reactive<Record<string, ReturnType<typeof setTimeout> | undefined>>({})

const quoteChipPairs = QUOTE_CHIP_LABELS.map((chip, i) => ({
  chip,
  full: QUOTE_CATALOG[i] as string,
}))

const visibleFields = computed(() =>
  (props.fields || []).filter((f) => {
    if (props.mode === 'create' && f.edit_only) return false
    if (props.mode === 'create' && f.readonly) return false
    return true
  }),
)

function isDisabled(f: AdminField) {
  // virtual 字段不入库，编辑态禁止改，避免「能拨但保存无效」
  if (f.virtual) return true
  return !!f.readonly && props.mode === 'edit'
}

function isRequired(f: AdminField) {
  if (f.type === 'password' && props.mode === 'edit') return false
  return !!f.required && !isDisabled(f)
}

function fieldOptions(f: AdminField): Record<string, string> {
  return normalizeOptions(f.options)
}

function shortRelationLabel(label: string) {
  return String(label || '').replace(/\s*#\d+\s*$/, '').trim()
}

async function loadRelation(rel: string, q = '', ensureId?: string | number | null) {
  if (!rel) return
  const want = ensureId != null && ensureId !== '' ? String(ensureId) : ''
  const blankQuery = !q
  // 空关键词已拉取过且已选项已在列表中：不再重复请求，避免「无数据」闪烁
  if (blankQuery && relationReady[rel] && !relationLoading[rel]) {
    const rows = relationCache[rel] || []
    if (!want || rows.some((o) => String(o.id) === want)) return
  }
  relationLoading[rel] = true
  try {
    const res = await api(`/admin/options/${rel}`, { query: { q: q || undefined, limit: 50 } })
    let rows = ((res as any)?.data || []) as { id: number | string, label: string }[]
    if (want && !rows.some((o) => String(o.id) === want)) {
      // 当前已选不在本页结果里时，再按 id 搜一次补进列表
      try {
        const one = await api(`/admin/options/${rel}`, { query: { q: want, limit: 10 } })
        const extra = ((one as any)?.data || []) as { id: number | string, label: string }[]
        const hit = extra.find((o) => String(o.id) === want)
        if (hit) rows = [hit, ...rows]
        else rows = [{ id: want, label: `已选 #${want}` }, ...rows]
      } catch {
        rows = [{ id: want, label: `已选 #${want}` }, ...rows]
      }
    }
    relationCache[rel] = rows
    if (blankQuery) relationReady[rel] = true
  } catch {
    relationCache[rel] = relationCache[rel] || []
    if (blankQuery) relationReady[rel] = true
  } finally {
    relationLoading[rel] = false
  }
}

function onRelationRemote(rel: string, q: string, fieldName?: string) {
  if (!rel) return
  const ensure = fieldName ? model.value[fieldName] : undefined
  if (relationTimers[rel]) clearTimeout(relationTimers[rel])
  relationTimers[rel] = setTimeout(() => {
    void loadRelation(rel, q, ensure)
  }, q ? 220 : 0)
}

function pickQuoteChip(fieldName: string, fullName: string) {
  const rel = 'quoteProduct'
  const rows = relationCache[rel] || []
  const hit = rows.find((o) => shortRelationLabel(o.label) === fullName || shortRelationLabel(o.label).includes(fullName))
  if (hit) {
    model.value[fieldName] = String(hit.id)
    return
  }
  void loadRelation(rel, fullName).then(() => {
    const again = (relationCache[rel] || []).find(
      (o) => shortRelationLabel(o.label) === fullName || shortRelationLabel(o.label).includes(fullName),
    )
    if (again) model.value[fieldName] = String(again.id)
  })
}

function quoteChipActive(fieldName: string, fullName: string) {
  const id = model.value[fieldName]
  if (id == null || id === '') return false
  const row = (relationCache.quoteProduct || []).find((o) => String(o.id) === String(id))
  if (!row) return false
  const lab = shortRelationLabel(row.label)
  return lab === fullName || lab.includes(fullName)
}

watch(
  () => props.fields,
  (fields) => {
    const rels = new Set((fields || []).filter(f => f.type === 'relation' && f.relation).map(f => f.relation!))
    rels.forEach((r) => {
      const field = (fields || []).find((f) => f.relation === r)
      const ensure = field ? model.value[field.name] : undefined
      if (!relationCache[r]) void loadRelation(r, '', ensure)
    })
    // 保证 select / multiselect 与 option 值类型一致（避免 0 !== "0"）
    for (const f of fields || []) {
      if (f.type === 'select') {
        const cur = model.value[f.name]
        if (cur != null && cur !== '') model.value[f.name] = normalizeSelect(cur)
      } else if (f.type === 'multiselect') {
        model.value[f.name] = multiToStorage(normalizeMulti(model.value[f.name]))
      } else if (f.type === 'relation' || f.type === 'category') {
        const cur = model.value[f.name]
        if (cur != null && cur !== '') model.value[f.name] = String(cur)
      }
    }
  },
  { immediate: true },
)

function asMulti(name: string): string[] {
  return normalizeMulti(model.value[name])
}

function toggleMulti(f: AdminField, val: string, checked: boolean) {
  const max = Number(f.max || 0)
  let cur = asMulti(f.name)
  if (checked) {
    if (max > 0 && cur.length >= max && !cur.includes(val)) {
      // 达上限时拒绝再选，避免静默挤掉旧项造成「选不中/莫名取消」
      return
    }
    if (!cur.includes(val)) cur.push(val)
  } else {
    cur = cur.filter(x => x !== val)
  }
  model.value[f.name] = multiToStorage(cur)
}

function multiDisabled(f: AdminField, val: string) {
  if (isDisabled(f)) return true
  const max = Number(f.max || 0)
  if (max <= 0) return false
  const cur = asMulti(f.name)
  return cur.length >= max && !cur.includes(val)
}

function isTopCategory() {
  const v = model.value.parent_id
  return v == null || v === '' || Number(v) === 0
}

function categoryModule(f: AdminField) {
  if (f.module) return f.module
  const map: Record<string, string> = {
    supplies: 'sell',
    products: 'mall',
    purchases: 'buy',
    articles: 'news',
    downloads: 'down',
  }
  return map[props.resource || ''] || 'sell'
}

const moduleNames: Record<string, string> = {
  sell: '供应',
  buy: '采购',
  mall: '商城',
  company: '企业库',
  news: '资讯',
  article: '资讯',
}
const parentOptions = ref<{ id: number; label: string }[]>([])
const hasParentField = computed(() => (props.fields || []).some((f) => f.type === 'category_parent'))

async function loadParentCategories(module: string) {
  if (!hasParentField.value) return
  try {
    const res = await api('/admin/options/category', { query: { module: module || undefined, limit: 400 } })
    const rows = ((res as any)?.data || []) as { id: number; label: string; parent_id?: number | null; module?: string }[]
    const byId = new Map(rows.map((r) => [Number(r.id), r]))
    const selfId = model.value.id
    parentOptions.value = rows
      .filter((r) => String(r.id) !== String(selfId || ''))
      .map((r) => {
        const parent = r.parent_id ? byId.get(Number(r.parent_id)) : null
        const mod = module ? '' : `${moduleNames[String(r.module || '')] || r.module || ''} · `
        const label = parent ? `${mod}${parent.label} / ${r.label}` : `${mod}${r.label}`
        return { id: Number(r.id), label }
      })
    const current = model.value.parent_id
    if (current != null && current !== '') {
      const n = Number(current)
      model.value.parent_id = parentOptions.value.some((o) => o.id === n) ? n : null
    }
  } catch {
    parentOptions.value = []
  }
}

watch(
  () => [hasParentField.value, String(model.value.module || '')] as const,
  ([on, module]) => {
    if (on) void loadParentCategories(module)
  },
  { immediate: true },
)
</script>

<template>
  <div class="admin-form">
    <div
      v-for="f in visibleFields"
      v-show="f.type !== 'category_icon' || isTopCategory()"
      :key="f.name"
      class="field"
      :class="{ rich: f.type === 'richtext', 'icon-field': f.type === 'category_icon' }"
    >
      <label>
        {{ f.label }}
        <span v-if="isRequired(f)" class="req">*</span>
      </label>
      <p v-if="f.hint" class="hint">{{ f.hint }}</p>

      <div v-if="f.type === 'image' && isDisabled(f)" class="img-audit">
        <template v-if="model[f.name]">
          <el-image
            :src="model[f.name]"
            :preview-src-list="[String(model[f.name])]"
            fit="contain"
            class="audit-preview"
            preview-teleported
            hide-on-click-modal
          />
          <p class="hint">点击图片可放大核对金额 / 账号 / 时间</p>
        </template>
        <p v-else class="hint">用户尚未上传付款凭证</p>
      </div>
      <div v-else-if="f.type === 'image'" :class="{ disabled: isDisabled(f) }">
        <ImageUpload
          v-model="model[f.name]"
          :label="f.label"
        />
      </div>

      <RichTextEditor
        v-else-if="f.type === 'richtext'"
        v-model="model[f.name]"
        :placeholder="f.placeholder || `请输入${f.label}`"
      />

      <textarea
        v-else-if="f.type === 'textarea'"
        v-model="model[f.name]"
        rows="4"
        :disabled="isDisabled(f)"
        :required="isRequired(f)"
      />

      <label v-else-if="f.type === 'toggle'" class="toggle">
        <input v-model="model[f.name]" type="checkbox" :disabled="isDisabled(f)">
        <span>{{ model[f.name] ? '是' : '否' }}</span>
      </label>

      <el-select
        v-else-if="f.type === 'select'"
        v-model="model[f.name]"
        filterable
        :clearable="!isRequired(f)"
        :disabled="isDisabled(f)"
        placeholder="请选择"
        style="width: 100%"
      >
        <el-option
          v-for="(lab, val) in fieldOptions(f)"
          :key="val"
          :label="lab"
          :value="String(val)"
        />
      </el-select>

      <div v-else-if="f.type === 'category_icon'" class="icon-pick">
        <button
          v-for="opt in CATEGORY_ICON_OPTIONS"
          :key="opt.key"
          type="button"
          :class="{ on: model[f.name] === opt.key }"
          @click="model[f.name] = opt.key"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" v-html="CATEGORY_ICONS[opt.key]" />
          <span>{{ opt.label }}</span>
        </button>
      </div>

      <el-select
        v-else-if="f.type === 'category_parent'"
        v-model="model[f.name]"
        filterable
        clearable
        :disabled="isDisabled(f)"
        placeholder="不选则为顶级"
        style="width: 100%"
      >
        <el-option v-for="o in parentOptions" :key="o.id" :label="o.label" :value="o.id" />
      </el-select>

      <div v-else-if="f.type === 'multiselect'" class="multi">
        <label v-for="(lab, val) in fieldOptions(f)" :key="val" class="multi-item" :class="{ dim: multiDisabled(f, String(val)) }">
          <input
            type="checkbox"
            :checked="asMulti(f.name).includes(String(val))"
            :disabled="multiDisabled(f, String(val))"
            @change="toggleMulti(f, String(val), ($event.target as HTMLInputElement).checked)"
          >
          <span>{{ lab }}</span>
        </label>
      </div>

      <div v-else-if="f.type === 'relation'" class="rel" :class="{ 'rel-quote': f.relation === 'quoteProduct' }">
        <el-select
          v-model="model[f.name]"
          filterable
          remote
          clearable
          reserve-keyword
          :remote-method="(q: string) => onRelationRemote(f.relation || '', q, f.name)"
          :loading="!!relationLoading[f.relation || '']"
          :disabled="isDisabled(f)"
          :placeholder="f.relation === 'quoteProduct' ? '输入名称搜索，或点下方快捷标签' : (f.relation === 'toUser' ? '输入企业名搜索（仅已审核）' : '输入关键词搜索')"
          :no-data-text="f.relation === 'toUser' ? '暂无已审核企业' : '无数据'"
          class="rel-select"
          @visible-change="(open: boolean) => open && onRelationRemote(f.relation || '', '', f.name)"
        >
          <el-option
            v-for="o in (relationCache[f.relation || ''] || [])"
            :key="String(o.id)"
            :label="shortRelationLabel(o.label)"
            :value="String(o.id)"
          >
            <span class="rel-opt-name">{{ shortRelationLabel(o.label) }}</span>
            <span class="rel-opt-id">#{{ o.id }}</span>
          </el-option>
        </el-select>
        <div v-if="f.relation === 'quoteProduct' && !isDisabled(f)" class="rel-chips">
          <button
            v-for="c in quoteChipPairs"
            :key="c.chip"
            type="button"
            class="rel-chip"
            :class="{ on: quoteChipActive(f.name, c.full) }"
            :title="c.full"
            @click="pickQuoteChip(f.name, c.full)"
          >{{ c.chip }}</button>
        </div>
      </div>

      <CategorySelect
        v-else-if="f.type === 'category'"
        v-model="model[f.name]"
        :module="categoryModule(f)"
      />

      <input
        v-else-if="f.type === 'password'"
        v-model="model[f.name]"
        type="password"
        autocomplete="new-password"
        :placeholder="mode === 'edit' ? '留空则不修改' : ''"
        :required="isRequired(f)"
      >

      <input
        v-else-if="f.type === 'number'"
        v-model.number="model[f.name]"
        type="number"
        step="any"
        :disabled="isDisabled(f)"
        :required="isRequired(f)"
      >

      <input
        v-else-if="f.type === 'date'"
        v-model="model[f.name]"
        type="date"
        :disabled="isDisabled(f)"
        :required="isRequired(f)"
      >

      <input
        v-else-if="f.type === 'datetime'"
        v-model="model[f.name]"
        type="datetime-local"
        :disabled="isDisabled(f)"
        :required="isRequired(f)"
      >

      <input
        v-else
        v-model="model[f.name]"
        :type="f.type === 'email' ? 'email' : 'text'"
        :disabled="isDisabled(f)"
        :required="isRequired(f)"
      >
    </div>
  </div>
</template>

<style scoped>
.admin-form { display: grid; gap: 12px; }
.field label { display: block; font-size: 12px; color: #64748b; margin-bottom: 4px; }
.field .req { color: #c41d7f; }
.hint { margin: 0 0 6px; font-size: 12px; color: #94a3b8; }
.field input, .field textarea, .field select {
  border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 10px; font: inherit; width: 100%;
  box-sizing: border-box;
}
.field input:disabled, .field textarea:disabled, .field select:disabled {
  background: #f8fafc; color: #64748b;
}
.toggle { display: flex; align-items: center; gap: 8px; font-size: 14px; color: #334155; }
.rel { display: grid; gap: 10px; max-width: 520px; }
.rel-quote { max-width: none; }
.rel-select { width: 100%; max-width: 520px; }
.rel-select :deep(.el-select__wrapper) {
  min-height: 40px;
  border-radius: 10px;
  box-shadow: 0 0 0 1px #dbe3ef inset;
  padding: 4px 12px;
}
.rel-select :deep(.el-select__wrapper:hover) {
  box-shadow: 0 0 0 1px #94a3b8 inset;
}
.rel-select :deep(.el-select__wrapper.is-focused) {
  box-shadow: 0 0 0 1px #2468f2 inset, 0 0 0 3px rgba(36, 104, 242, 0.12);
}
.rel-opt-name { float: left; color: #1e293b; }
.rel-opt-id { float: right; color: #94a3b8; font-size: 12px; }
.rel-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.rel-chip {
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #475569;
  border-radius: 999px;
  padding: 6px 12px;
  font: inherit;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  line-height: 1.2;
  transition: border-color .15s, background .15s, color .15s;
}
.rel-chip:hover {
  border-color: #fdba74;
  background: #eef4ff;
  color: #1443b8;
}
.rel-chip.on {
  border-color: #2468f2;
  background: #eef4ff;
  color: #1443b8;
  box-shadow: 0 0 0 2px rgba(36, 104, 242, 0.12);
}
.field.rich, .field.icon-field { grid-column: 1 / -1; }
.icon-pick { display: flex; flex-wrap: wrap; gap: 8px; }
.icon-pick button {
  width: 72px; border: 1px solid #cbd5e1; background: #fff; border-radius: 8px;
  padding: 8px 4px 6px; cursor: pointer; color: #334155;
}
.icon-pick button.on { border-color: #2468f2; background: #eef4ff; color: #1443b8; }
.icon-pick svg { width: 28px; height: 28px; display: block; margin: 0 auto 4px; }
.icon-pick span { display: block; font-size: 12px; text-align: center; }
.disabled { pointer-events: none; opacity: .75; }
.multi { display: flex; flex-wrap: wrap; gap: 10px 16px; }
.multi-item { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #334155; margin: 0; cursor: pointer; }
.multi-item.dim { opacity: .45; cursor: not-allowed; }
.img-audit { display: grid; gap: 8px; }
.audit-preview {
  width: min(420px, 100%); max-height: 360px; border-radius: 8px;
  border: 1px solid #e2e8f0; background: #f8fafc; cursor: zoom-in; overflow: hidden;
}
.audit-preview :deep(img) { width: 100%; max-height: 360px; object-fit: contain; }
@media (min-width: 720px) {
  .admin-form { grid-template-columns: 1fr 1fr; }
  .field.rich, .field:has(textarea), .field:has(.img-audit) { grid-column: 1 / -1; }
}
</style>

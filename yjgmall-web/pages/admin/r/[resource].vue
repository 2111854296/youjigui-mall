<script setup lang="ts">
import { ElMessage, ElMessageBox } from 'element-plus'
import { buildAdminLabelMaps, fieldEnumLabel } from '~/utils/statusLabel'
import { normalizeToggle } from '~/utils/adminFormValues'

definePageMeta({ layout: 'admin', middleware: 'admin' })

const route = useRoute()
const { api } = useApi()
const { runWithSecondPassword } = useAdminSecondPassword()
const resource = computed(() => String(route.params.resource || ''))

// 旧「VIP申请」已停用：前台开通全部走对公付款审核
// 「仪表盘」入口误进 /admin/r/dashboard 时，转到真正的总览页
watch(resource, (r) => {
  if (r === 'vip-applications') {
    void navigateTo('/admin/r/vip-pay-orders', { replace: true })
  }
  if (r === 'dashboard') {
    void navigateTo('/admin', { replace: true })
  }
}, { immediate: true })

const q = ref('')
const status = ref('')
const vip = ref('')
const moduleFilter = ref('')
const positionFilter = ref('')
const groupFilter = ref('')
const page = ref(1)
const sortKey = ref('id')
const sortOrder = ref<'asc' | 'desc'>('desc')
const busy = ref(false)
const showForm = ref(false)
const formMode = ref<'create' | 'edit'>('create')
const editingId = ref<number | null>(null)
const form = reactive<Record<string, any>>({})
const selectedIds = ref<number[]>([])

const colFilters = reactive<Record<string, string>>({})

const companyFilters = reactive({
  member: '',
  area: '',
  is_featured: '',
  is_certified: '',
  vip_level: '',
  company_type: '',
  business_mode: '',
  capital_min: '',
  capital_max: '',
  category_id: '',
})

const honorTab = ref('all')
const batchVipLevel = ref(5)
const showVipEdit = ref(false)
const quoteFile = ref<File | null>(null)
const quoteImporting = ref(false)
const quoteTarget = ref<any>(null)
const showQuoteImport = ref(false)
const showQuoteHistory = ref(false)
const quoteHistoryLoading = ref(false)
const quoteHistorySaving = ref(false)
const quoteHistory = ref<any[]>([])
const quoteNew = reactive({ quoted_on: '', price_avg: '', price_min: '', price_max: '' })
const quoteResult = ref<{ imported: number; updated: number; skipped: number; errors: string[] } | null>(null)
const vipEditRow = ref<any>(null)
const vipEditForm = reactive({ vip_theory: 0, vip_correct: 0, vip_level: 0, vip_from: '' })
const showInquiryView = ref(false)
const inquiryViewLoading = ref(false)
const inquiryView = ref<any>(null)

async function openInquiryView(row: any) {
  showInquiryView.value = true
  inquiryViewLoading.value = true
  inquiryView.value = row
  try {
    const res = await api(`/admin/resources/inquiries/${row.id}`)
    inquiryView.value = (res as any)?.data || row
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '加载留言失败')
  } finally {
    inquiryViewLoading.value = false
  }
}

function inquiryStatusText(s: string) {
  const map: Record<string, string> = {
    new: '待审（未转发）',
    processing: '已转发企业',
    reply_pending: '企业回复待审',
    replied: '已通过回复',
    done: '不予转发',
    closed: '不予转发',
  }
  return map[s] || s || '—'
}

function inquiryActionVisible(row: any, act: string) {
  if (resource.value !== 'inquiries') return true
  const s = String(row?.status || '')
  if (act === 'approve') return s === 'new'
  if (act === 'approve_reply') return s === 'reply_pending'
  if (act === 'done') return s === 'new'
  return true
}

const VIP_COLUMNS = [
  { key: 'user_id', label: '会员ID' },
  { key: 'name', label: '公司名' },
  { key: 'user.username', label: '会员名' },
  { key: 'user_group_code', label: '会员级别' },
  { key: 'vip_from', label: '开通时间' },
  { key: 'user_vip_expire_at', label: '到期时间' },
  { key: 'vip_level', label: 'VIP指数' },
  { key: 'vip_theory', label: '理论值' },
  { key: 'vip_correct', label: '修正值' },
]

function queryOne(v: unknown): string {
  if (v == null) return ''
  return String(Array.isArray(v) ? v[0] : v)
}

function queryFromLocation(key: string): string {
  if (!import.meta.client) return ''
  try {
    return new URLSearchParams(window.location.search).get(key) || ''
  } catch {
    return ''
  }
}

/** 路由 query 优先，地址栏兜底（防止 navigateTo 尚未落地时读到空） */
function routeOrLocation(key: string): string {
  return queryOne(route.query[key]) || queryFromLocation(key)
}

function syncQueryFilters() {
  status.value = routeOrLocation('status')
  vip.value = routeOrLocation('vip')
  moduleFilter.value = routeOrLocation('module')
  positionFilter.value = routeOrLocation('position')
  groupFilter.value = routeOrLocation('group')
  honorTab.value = routeOrLocation('tab') || 'all'
  if (resource.value === 'company-honors' && honorTab.value !== 'all' && !status.value) {
    status.value = honorTab.value === 'rejected' ? 'rejected'
      : honorTab.value === 'expired' ? 'expired'
        : honorTab.value === 'pending' ? 'pending'
          : honorTab.value === 'offline' ? 'offline' : status.value
  }
  // URL 已锁定的维度，清掉同名列筛，避免 cf_module / cf_position 与 URL 参数互打
  if (moduleFilter.value && 'module' in colFilters) colFilters.module = ''
  if ((positionFilter.value || groupFilter.value) && 'position' in colFilters) colFilters.position = ''
  page.value = 1
  selectedIds.value = []
}

/** 状态下拉写入地址栏，保证后续 sync / 请求都以 URL 为准 */
async function onStatusFilterChange(v: string | null | undefined) {
  const next = v == null ? '' : String(v)
  status.value = next
  page.value = 1
  selectedIds.value = []
  const q = { ...route.query } as Record<string, any>
  if (next) q.status = next
  else delete q.status
  if (routeOrLocation('status') === next) {
    await refresh()
    return
  }
  await navigateTo({ path: route.path, query: q, replace: true })
}

syncQueryFilters()

const routeLockedVip = computed(() => routeOrLocation('vip') !== '')
const routeLockedModule = computed(() => routeOrLocation('module') !== '')
const routeLockedPosition = computed(() => routeOrLocation('position') !== '')
const routeLockedGroup = computed(() => routeOrLocation('group') !== '')

const isCompanies = computed(() => resource.value === 'companies')
const isVipMode = computed(() => isCompanies.value && vip.value === '1')
const isHonors = computed(() => resource.value === 'company-honors')
const isArticles = computed(() => resource.value === 'articles')
const isAds = computed(() => resource.value === 'ads' || resource.value === 'banners')
const articleMoveCat = ref<number | ''>('')
const articleCats = ref<{ id: number; label: string }[]>([])
const articleQuoteId = ref<number | ''>('')
const articleQuotes = ref<{ id: number; label: string }[]>([])

function nestCategoryLabels(rows: any[]) {
  const seen = new Set<number>()
  const out: { id: number; label: string }[] = []
  const childrenOf = (pid: number) => rows.filter((r) => Number(r.parent_id) === pid)
  function walk(row: any, prefix: string) {
    const id = Number(row.id)
    if (!id || seen.has(id)) return
    seen.add(id)
    const name = String(row.label || row.name || '')
    const label = prefix ? `${prefix} / ${name}` : name
    out.push({ id, label })
    for (const child of childrenOf(id)) walk(child, label)
  }
  for (const row of rows) {
    if (!row.parent_id) walk(row, '')
  }
  for (const row of rows) walk(row, '')
  return out
}

async function loadArticleCats() {
  if (!isArticles.value) return
  try {
    const [articleRes, newsRes] = await Promise.all([
      api<any>('/admin/options/category', { query: { module: 'article', limit: 400 } }),
      api<any>('/admin/options/category', { query: { module: 'news', limit: 400 } }),
    ])
    const rows = [...((articleRes as any)?.data || []), ...((newsRes as any)?.data || [])]
    const uniq = new Map<number, any>()
    for (const row of rows) uniq.set(Number(row.id), row)
    articleCats.value = nestCategoryLabels([...uniq.values()])
  } catch {
    articleCats.value = []
  }
}

async function loadArticleQuotes() {
  if (!isArticles.value) return
  try {
    const res = await api<any>('/admin/options/quoteProduct', { query: { limit: 100 } })
    articleQuotes.value = ((res as any)?.data || []).map((o: any) => ({
      id: Number(o.id),
      label: String(o.label || '').replace(/\s*#\d+\s*$/, '').trim() || `#${o.id}`,
    })).filter((o: { id: number }) => o.id > 0)
  } catch {
    articleQuotes.value = []
  }
}

const placeNames: Record<string, string> = {
  home: '首页轮播',
  home_banner: '首页轮播',
  home_right: '首页右侧',
  home_portal: '首页分类展位',
  company_top: '企业库轮播',
  mall_top: '商城轮播',
  news_top: '资讯轮播',
  sell_right: '供应右侧',
  buy_right: '求购右侧',
  sample_top: '样品专区轮播',
}
function placeOptionName(code: string) {
  const field = fields.value.find((f) => f?.name === 'position')
  return String(field?.options?.[code] || placeNames[code] || code)
}
function adAreaTitle(name: string, code: string) {
  const dotted = name.match(/^(.*)·第\d+[位张]$/)
  if (dotted) return dotted[1]
  const pageRank = name.match(/^(.*?)第\d+页/)
  if (pageRank?.[1] && code.startsWith('rank_')) return `${pageRank[1]}列表`
  return ''
}
const positionLabel = computed(() => {
  const code = positionFilter.value
  if (!code) return ''
  return placeOptionName(code)
})
const groupLabel = computed(() => {
  const code = groupFilter.value
  if (!code) return ''
  const name = placeOptionName(code)
  return adAreaTitle(name, code) || name
})

const pageTitle = computed(() => {
  if (resource.value === 'articles' && status.value === 'pending') return '审核资讯'
  if (isVipMode.value) return 'VIP管理'
  if (resource.value === 'categories' && moduleFilter.value === 'article') return '资讯分类'
  if (resource.value === 'categories' && moduleFilter.value === 'news') return '资讯分类'
  if (resource.value === 'categories' && moduleFilter.value === 'company') return '行业分类'
  if (isHonors.value) return '荣誉资质'
  if (groupLabel.value) return groupLabel.value
  if (positionLabel.value) return positionLabel.value
  return metaCfg.value?.label || '资源'
})

const { data: metaRes, refresh: refreshMeta } = await useAsyncData(
  () => `admin-meta-${resource.value}`,
  () => api(`/admin/resources/${resource.value}/meta`),
  { watch: [resource], server: false },
)
const metaCfg = computed(() => (metaRes.value as any)?.data || null)
const fields = computed(() => (metaCfg.value?.fields || []) as any[])
const resourceHint = computed(() => {
  const fromMeta = String((metaCfg.value as any)?.hint || '').trim()
  if (fromMeta) return fromMeta
  if (isAds.value) {
    return '标记为「上架」的广告会全部按「排序」进入对应广告位轮播，不会因数量多而被截掉；不想起作用请改「下架」。'
  }
  return ''
})

const displayColumns = computed(() => {
  if (isVipMode.value) return VIP_COLUMNS
  return metaCfg.value?.columns || []
})

watch([metaCfg, pageTitle], () => {
  useHead({ title: `${pageTitle.value} - 运营后台` })
}, { immediate: true })

const filterDefs = computed(() => {
  const raw = metaCfg.value?.filters
  if (!raw || typeof raw !== 'object') return [] as any[]
  return Object.entries(raw).map(([key, f]: [string, any]) => ({
    key: String(f?.field || key),
    label: String(f?.label || key),
    type: String(f?.type || 'select'),
    options: f?.options && typeof f.options === 'object' ? f.options : {},
  }))
})
/** 工具栏只放下拉；标题/ID 用关键词即可，避免表头再叠一层输入框 */
const toolbarFilters = computed(() =>
  filterDefs.value.filter((f) => {
    if (f.type !== 'select') return false
    if (!Object.keys(f.options || {}).length) return false
    if (['id', 'title', 'name', 'username'].includes(f.key)) return false
    if (routeLockedPosition.value && f.key === 'position') return false
    if (routeLockedGroup.value && f.key === 'position') return false
    if (routeLockedModule.value && f.key === 'module') return false
    return true
  }),
)
const hasActiveFilters = computed(() => {
  if (q.value || status.value) return true
  return Object.values(colFilters).some((v) => v !== '' && v != null)
    || Object.values(companyFilters).some((v) => v !== '' && v != null)
})
const sortableKeys = computed(() => {
  const list = metaCfg.value?.sortable
  return Array.isArray(list) ? list.map(String) : ['id']
})

function listQuery() {
  // 一律以 URL/地址栏为准，refs 仅作 UI；杜绝竞态时用空 status 拉全量
  const effectiveStatus = routeOrLocation('status') || status.value
  const effectiveVip = routeOrLocation('vip') || vip.value
  const effectiveModule = routeOrLocation('module') || moduleFilter.value
  const effectivePosition = routeOrLocation('position') || positionFilter.value
  const effectiveGroup = routeOrLocation('group') || groupFilter.value
  const query: Record<string, any> = {
    q: q.value || undefined,
    status: effectiveStatus || undefined,
    vip: effectiveVip || undefined,
    module: effectiveModule || undefined,
    position: effectiveGroup ? undefined : (effectivePosition || undefined),
    group: effectiveGroup || undefined,
    page: page.value,
    sort: sortKey.value || undefined,
    order: sortOrder.value || undefined,
  }
  if (isCompanies.value && !isVipMode.value) {
    Object.entries(companyFilters).forEach(([k, v]) => {
      if (v !== '' && v != null) query[k] = v
    })
  }
  Object.entries(colFilters).forEach(([k, v]) => {
    if (v !== '' && v != null) query['cf_' + k] = v
  })
  return query
}

const listReqSeq = ref(0)
const { data, pending, refresh } = await useAsyncData(
  () => `admin-res-${resource.value}-${page.value}-${routeOrLocation('status')}-${routeOrLocation('vip')}-${routeOrLocation('module')}-${routeOrLocation('position')}-${routeOrLocation('group')}-${q.value}-${sortKey.value}-${sortOrder.value}-${JSON.stringify(companyFilters)}-${JSON.stringify(colFilters)}-${honorTab.value}`,
  async () => {
    const mySeq = ++listReqSeq.value
    // 每次请求前再对齐一次 URL，避免拿着过期的空筛选出门
    if (import.meta.client) {
      const s = routeOrLocation('status')
      if (s) status.value = s
      const vv = routeOrLocation('vip')
      if (vv) vip.value = vv
      const mm = routeOrLocation('module')
      if (mm) moduleFilter.value = mm
      const pp = routeOrLocation('position')
      if (pp) positionFilter.value = pp
      const gg = routeOrLocation('group')
      if (gg) groupFilter.value = gg
    }
    const qobj = listQuery()
    const res = await api(`/admin/resources/${resource.value}`, { query: qobj })
    // 只接受最新一次请求；过期响应直接丢弃，防止全量列表盖住筛选结果
    if (mySeq !== listReqSeq.value) {
      return (data.value as any) ?? res
    }
    let out = res as any
    if (Array.isArray(out?.data)) {
      const filtered = guardRowsByQuery(out.data, qobj)
      if (filtered.length !== out.data.length) {
        out = { ...out, data: filtered }
      }
    }
    return out
  },
  {
    // 只盯 URL + 本地筛选，避免 status ref 与 fullPath 各打一遍造成竞态
    watch: [() => route.fullPath, page, q, sortKey, sortOrder, honorTab, colFilters, companyFilters],
    server: false,
  },
)

/** 展示/响应层兜底：URL 锁定的维度不允许脏行闪进表格 */
function guardRowsByQuery(rows: any[], qobj: Record<string, any>) {
  let out = rows
  if (qobj.status) {
    const want = String(qobj.status)
    out = out.filter((row) => String(row?.status ?? '') === want)
  }
  if (qobj.vip === '1' || qobj.vip === 1) {
    out = out.filter((row) => Number(row?.vip_level ?? 0) > 0)
  }
  if (qobj.module) {
    const want = String(qobj.module)
    out = out.filter((row) => String(row?.module ?? '') === want)
  }
  if (qobj.position) {
    const want = String(qobj.position)
    // banners 首页位后端会扩成 home/home_banner
    if (want === 'home' || want === 'home_banner') {
      out = out.filter((row) => ['home', 'home_banner'].includes(String(row?.position ?? '')))
    } else {
      out = out.filter((row) => String(row?.position ?? '') === want)
    }
  }
  return out
}

const items = computed(() => {
  const rows = ((data.value as any)?.data || []) as any[]
  return guardRowsByQuery(rows, {
    status: routeOrLocation('status') || status.value || undefined,
    vip: routeOrLocation('vip') || vip.value || undefined,
    module: routeOrLocation('module') || moduleFilter.value || undefined,
    position: (routeOrLocation('group') || groupFilter.value)
      ? undefined
      : (routeOrLocation('position') || positionFilter.value || undefined),
  })
})
const pageMeta = computed(() => (data.value as any)?.meta || {})

watch(
  () => route.fullPath,
  () => {
    syncQueryFilters()
    showForm.value = false
    editingId.value = null
    Object.keys(form).forEach(k => delete form[k])
    void refreshMeta()
    // 不在这里 refresh：useAsyncData 已 watch fullPath，再调会叠请求并可能用旧 status
  },
  { flush: 'sync' },
)

watch(resource, () => {
  syncQueryFilters()
  articleMoveCat.value = ''
  articleQuoteId.value = ''
  void loadArticleCats()
  void loadArticleQuotes()
  q.value = ''
  sortKey.value = 'id'
  sortOrder.value = 'desc'
  Object.keys(colFilters).forEach((k) => { colFilters[k] = '' })
  Object.keys(companyFilters).forEach((k) => { (companyFilters as any)[k] = '' })
  showForm.value = false
  editingId.value = null
  Object.keys(form).forEach(k => delete form[k])
}, { flush: 'sync' })
if (isArticles.value) {
  void loadArticleCats()
  void loadArticleQuotes()
}

watch(filterDefs, (defs) => {
  for (const f of defs) {
    if (!(f.key in colFilters)) colFilters[f.key] = ''
  }
}, { immediate: true })

async function search() {
  page.value = 1
  selectedIds.value = []
  articleSelectAll.value = false
  await refresh()
}

async function resetFilters() {
  q.value = ''
  Object.keys(colFilters).forEach((k) => { colFilters[k] = '' })
  Object.keys(companyFilters).forEach((k) => { (companyFilters as any)[k] = '' })
  sortKey.value = 'id'
  sortOrder.value = 'desc'
  page.value = 1
  selectedIds.value = []
  articleSelectAll.value = false
  // 状态在地址栏里：重置时一并清掉，避免 sync 又写回来
  if (routeOrLocation('status') || status.value) {
    status.value = ''
    const nextQuery = { ...route.query } as Record<string, any>
    delete nextQuery.status
    await navigateTo({ path: route.path, query: nextQuery, replace: true })
    return
  }
  await refresh()
}

function onSortChange(payload: { prop?: string; order?: string | null }) {
  const prop = String(payload?.prop || '')
  if (!prop || !sortableKeys.value.includes(prop)) {
    if (!payload?.order) {
      sortKey.value = 'id'
      sortOrder.value = 'desc'
      page.value = 1
      void refresh()
    }
    return
  }
  sortKey.value = prop
  sortOrder.value = payload?.order === 'ascending' ? 'asc' : 'desc'
  page.value = 1
  void refresh()
}

function setHonorTab(tab: string) {
  honorTab.value = tab
  status.value = tab === 'all' ? '' : tab
  page.value = 1
  selectedIds.value = []
  // 只改 URL；列表由 useAsyncData watch fullPath/honorTab 拉，禁止再 pe() 叠请求
  void navigateTo({
    path: route.path,
    query: { ...route.query, tab: tab === 'all' ? undefined : tab, status: undefined },
    replace: true,
  })
}

async function doAction(row: any, action: string) {
  const body: Record<string, any> = { action }
  if (resource.value === 'vip-pay-orders' || resource.value === 'fund-pay-orders') {
    if (action === 'approve') {
      if (!row.voucher_url) {
        ElMessage.warning('该订单尚未上传付款凭证，无法通过')
        return
      }
      if (row.status !== 'pending_review') {
        ElMessage.warning('仅「待审核」订单可执行通过')
        return
      }
      try {
        const okLabel = resource.value === 'fund-pay-orders' ? '确认通过' : '确认开通'
        await ElMessageBox.confirm(
          `确认通过订单 ${row.order_no || '#' + row.id}？请先核对照片中的金额与账户是否到账。`,
          '审核通过',
          { type: 'warning', confirmButtonText: okLabel, cancelButtonText: '再看看' },
        )
      } catch {
        return
      }
    }
    if (action === 'reject') {
      try {
        const { value } = await ElMessageBox.prompt('请填写驳回原因（用户可见）', '拒绝付款', {
          inputPattern: /\S+/,
          inputErrorMessage: '驳回原因必填',
          confirmButtonText: '确认驳回',
          cancelButtonText: '取消',
          type: 'warning',
        })
        body.admin_remark = String(value || '').trim()
      } catch {
        return
      }
    }
  } else if (action === 'approve' || action === 'offline') {
    const label = actionText(action)
    try {
      await ElMessageBox.confirm(`确认${label}「${row.title || row.name || '#' + row.id}」？`, `${label}确认`, {
        type: 'warning',
        confirmButtonText: `确认${label}`,
        cancelButtonText: '取消',
      })
    } catch {
      return
    }
  }
  busy.value = true
  try {
    await api(`/admin/resources/${resource.value}/${row.id}`, { method: 'PUT', body })
    ElMessage.success(`已${actionText(action)} #${row.id}`)
    await refresh()
    if (import.meta.client) window.dispatchEvent(new Event('admin-menu-refresh'))
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '操作失败')
  } finally {
    busy.value = false
  }
}

async function removeRow(row: any) {
  const isCategory = resource.value === 'categories'
  let categoryPassword = ''
  try {
    const tip = resource.value === 'quote-products'
      ? `确认删除「${row.name || row.id}」？这条行情和它的历史价格会一起去掉，并移入回收站。`
      : isCategory
        ? `确认删除「${row.name || row.id}」？父级分类会连同下面的子分类一起移入回收站，可以再恢复。`
        : `确认删除 #${row.id}？删除后可在回收站还原。`
    await ElMessageBox.confirm(tip, '删除确认', { type: 'warning', confirmButtonText: '确认删除' })
    if (isCategory) {
      const { value } = await ElMessageBox.prompt('删除分类每次都要输入二级密码', '二级密码', {
        inputType: 'password',
        inputPlaceholder: '请输入二级密码',
        confirmButtonText: '确认删除',
        cancelButtonText: '取消',
        inputPattern: /\S{6,}/,
        inputErrorMessage: '二级密码至少 6 位',
      })
      categoryPassword = String(value || '').trim()
    }
  } catch {
    return
  }
  busy.value = true
  try {
    const res = isCategory
      ? await api(`/admin/resources/${resource.value}/${row.id}`, { method: 'DELETE', body: { password: categoryPassword } })
      : await runWithSecondPassword(() =>
        api(`/admin/resources/${resource.value}/${row.id}`, { method: 'DELETE' }),
      )
    if (res === undefined) return
    ElMessage.success(`已移入回收站 #${row.id}`)
    await refresh()
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '删除失败')
  } finally {
    busy.value = false
  }
}

function blankForm() {
  Object.keys(form).forEach(k => delete form[k])
  for (const f of fields.value) {
    if (f.type === 'toggle') form[f.name] = false
    else if (f.type === 'number') form[f.name] = null
    else if (f.type === 'multiselect') form[f.name] = ''
    else if (f.type === 'select' || f.type === 'relation' || f.type === 'category') form[f.name] = ''
    else form[f.name] = ''
  }
}

function openCreate() {
  if (resource.value === 'articles') {
    navigateTo('/admin/articles/edit')
    return
  }
  if (resource.value === 'companies') {
    navigateTo('/admin/companies/edit')
    return
  }
  formMode.value = 'create'
  editingId.value = null
  blankForm()
  if (positionFilter.value) form.position = positionFilter.value
  else if (groupFilter.value) form.position = groupFilter.value
  else if (resource.value === 'banners') form.position = 'home'
  if (resource.value === 'categories' && moduleFilter.value) form.module = moduleFilter.value
  showForm.value = true
}

async function openEdit(row: any) {
  if (resource.value === 'articles') {
    navigateTo(`/admin/articles/edit?id=${row.id}`)
    return
  }
  if (resource.value === 'companies' && !isVipMode.value) {
    navigateTo(`/admin/companies/edit?id=${row.id}`)
    return
  }
  if (isVipMode.value) {
    openVipEdit(row)
    return
  }
  formMode.value = 'edit'
  editingId.value = row.id
  blankForm()
  busy.value = true
  try {
    const res = await api(`/admin/resources/${resource.value}/${row.id}`)
    const dataRow = (res as any)?.data || row
    for (const f of fields.value) {
      let v = dataRow[f.name]
      if (f.type === 'datetime' && v) v = String(v).replace(' ', 'T').slice(0, 16)
      else if (f.type === 'date' && v) v = String(v).slice(0, 10)
      else if (f.type === 'toggle') v = normalizeToggle(v)
      else if (f.type === 'select' || f.type === 'relation' || f.type === 'category') {
        v = v == null || v === '' ? '' : String(v)
      }
      else if (f.type === 'multiselect') {
        v = Array.isArray(v) ? v.map(String).join(',') : (v == null ? '' : String(v))
      }
      else if (f.type === 'password') v = ''
      form[f.name] = v ?? (f.type === 'toggle' ? false : '')
    }
    showForm.value = true
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '加载详情失败')
  } finally {
    busy.value = false
  }
}

function openVipEdit(row: any) {
  vipEditRow.value = row
  vipEditForm.vip_theory = Number(row.vip_theory || 0)
  vipEditForm.vip_correct = Number(row.vip_correct || 0)
  vipEditForm.vip_level = Number(row.vip_level || 0)
  vipEditForm.vip_from = row.vip_from ? String(row.vip_from).replace(' ', 'T').slice(0, 16) : ''
  showVipEdit.value = true
}

watch(() => [vipEditForm.vip_theory, vipEditForm.vip_correct], () => {
  vipEditForm.vip_level = Math.max(0, Math.min(10, Number(vipEditForm.vip_theory || 0) + Number(vipEditForm.vip_correct || 0)))
})

async function saveVipEdit() {
  if (!vipEditRow.value) return
  busy.value = true
  try {
    const body: Record<string, any> = {
      vip_theory: Number(vipEditForm.vip_theory || 0),
      vip_correct: Number(vipEditForm.vip_correct || 0),
    }
    if (vipEditForm.vip_from) {
      body.vip_from = String(vipEditForm.vip_from).replace('T', ' ') + (String(vipEditForm.vip_from).length === 16 ? ':00' : '')
    }
    await api(`/admin/resources/companies/${vipEditRow.value.id}`, { method: 'PUT', body })
    ElMessage.success('VIP 指数已更新')
    showVipEdit.value = false
    await refresh()
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '保存失败')
  } finally {
    busy.value = false
  }
}

function payloadFromForm() {
  const body: Record<string, any> = {}
  for (const f of fields.value) {
    if (f.virtual) continue
    if (f.readonly && formMode.value === 'edit') continue
    if (formMode.value === 'create' && f.readonly) continue
    if (formMode.value === 'edit' && f.create_only) continue
    let v = form[f.name]
    if (f.type === 'password' && (v === '' || v == null)) continue
    if (f.type === 'datetime' && v) v = String(v).replace('T', ' ') + (String(v).length === 16 ? ':00' : '')
    if (f.type === 'number' && (v === '' || v === undefined)) v = null
    if ((f.type === 'relation' || f.type === 'category') && (v === '' || v === undefined)) v = null
    body[f.name] = v
  }
  return body
}

async function submitForm() {
  if ((resource.value === 'vip-pay-orders' || resource.value === 'fund-pay-orders') && formMode.value === 'edit') {
    if (form.status === 'approved' && !form.voucher_url) {
      ElMessage.warning(resource.value === 'fund-pay-orders' ? '未上传付款凭证，不能设为已通过' : '未上传付款凭证，不能设为已开通')
      return
    }
    if (form.status === 'rejected' && !String(form.admin_remark || '').trim()) {
      ElMessage.warning('驳回时请填写审核备注（用户可见）')
      return
    }
  }
  if (resource.value === 'categories') {
    const parentEmpty = form.parent_id == null || form.parent_id === '' || form.parent_id === 0
    if (parentEmpty && !form.icon && (formMode.value === 'create' || !editingId.value)) {
      ElMessage.warning('请先选择侧栏图标。父级分类要显示在首页左侧，名称前面必须有一个图标')
      return
    }
    if (!String(form.name || '').trim()) {
      ElMessage.warning('请填写名称')
      return
    }
    if (!String(form.slug || '').trim()) {
      ElMessage.warning('请填写 Slug')
      return
    }
    if (!form.module) {
      ElMessage.warning('请选择模块')
      return
    }
  }
  busy.value = true
  try {
    const body = payloadFromForm()
    if (formMode.value === 'create') {
      await api(`/admin/resources/${resource.value}`, { method: 'POST', body })
      ElMessage.success('已创建')
    } else {
      await api(`/admin/resources/${resource.value}/${editingId.value}`, { method: 'PUT', body })
      ElMessage.success(`已保存 #${editingId.value}`)
    }
    showForm.value = false
    await refresh()
  } catch (e: any) {
    ElMessage.error(apiFailText(e, '保存失败'))
  } finally {
    busy.value = false
  }
}

function apiFailText(e: any, fallback: string) {
  const data = e?.data
  const raw = typeof data === 'string' ? data : (data?.message || e?.message || '')
  const msg = String(raw || '').replace(/<[^>]+>/g, '').trim()
  if (!msg || msg.includes('页面错误') || msg.includes('请稍后再试')) return fallback
  return msg
}

async function runBatch(action: string) {
  if (!selectedIds.value.length) {
    ElMessage.warning('请先勾选企业')
    return
  }
  if (action === 'offline') {
    try {
      await ElMessageBox.confirm(
        `确认下架已选的 ${selectedIds.value.length} 家企业？`,
        '下架确认',
        { type: 'warning', confirmButtonText: '确认下架', cancelButtonText: '取消' },
      )
    } catch {
      return
    }
  }
  busy.value = true
  try {
    const body: Record<string, any> = { ids: selectedIds.value, action }
    if (action === 'vip') body.vip_level = Number(batchVipLevel.value || 1)
    const res = await api('/admin/resources/companies/batch', { method: 'POST', body })
    ElMessage.success((res as any)?.message || '批量处理完成')
    selectedIds.value = []
    await refresh()
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '批量失败')
  } finally {
    busy.value = false
  }
}

function onSelectionChange(rows: any[]) {
  selectedIds.value = rows.map(r => Number(r.id)).filter(Boolean)
}

function tagTone(label: string) {
  if (label === '通过' || label === '上架') return 'ok'
  if (label === '待审' || label === '未上架') return 'wait'
  if (label === '拒绝') return 'no'
  if (label === '下架') return 'off'
  return 'muted'
}

const articleSelectAll = ref(false)
const articlePickCount = computed(() => articleSelectAll.value ? Number(pageMeta.value.total || 0) : selectedIds.value.length)

function toggleArticleAll() {
  articleSelectAll.value = !articleSelectAll.value
}

async function runArticleBatch(action: 'publish' | 'offline' | 'delete' | 'move' | 'set_quote') {
  const n = articlePickCount.value
  const who = articleSelectAll.value ? `当前筛选的 ${n} 篇` : `已选的 ${n} 篇`
  if (!n) {
    ElMessage.warning(articleSelectAll.value ? '当前筛选下没有资讯' : '请先勾选要处理的资讯')
    return
  }
  const cat = articleCats.value.find((c) => c.id === Number(articleMoveCat.value))
  const quote = articleQuotes.value.find((c) => c.id === Number(articleQuoteId.value))
  if (action === 'move' && !cat) {
    ElMessage.warning('请先选择要放入的资讯分类')
    return
  }
  if (action === 'set_quote' && !quote) {
    ElMessage.warning('请先选择行情品种')
    return
  }
  const copy: Record<string, { title: string; message: string; ok: string }> = {
    publish: {
      title: '批量上架',
      message: `确认上架${who}？上架后会出现在对应分类的资讯板块里。`,
      ok: '确认上架',
    },
    offline: {
      title: '批量下架',
      message: `确认下架${who}？下架后前台不再显示。`,
      ok: '确认下架',
    },
    delete: {
      title: '批量删除',
      message: `确认删除${who}？删除后可以在回收站还原。`,
      ok: '确认删除',
    },
    move: {
      title: '移动分类',
      message: `确认把${who}移动到「${cat?.label || ''}」？移动本身不会上架，需要再点一次批量上架，才会出现在这个分类的资讯板块。`,
      ok: '确认移动',
    },
    set_quote: {
      title: '绑定行情品种',
      message: `确认把${who}绑定到行情品种「${quote?.label || ''}」？价格行情资讯按品种挂文时会用到。`,
      ok: '确认绑定',
    },
  }
  try {
    await ElMessageBox.confirm(copy[action].message, copy[action].title, {
      type: 'warning',
      confirmButtonText: copy[action].ok,
      cancelButtonText: '取消',
    })
  } catch {
    return
  }
  const filters = listQuery()
  delete filters.page
  delete filters.sort
  delete filters.order
  const send = () => api('/admin/resources/articles/batch', {
    method: 'POST',
    body: articleSelectAll.value
      ? {
          ...filters,
          scope: 'filter',
          action,
          category_id: action === 'move' ? Number(articleMoveCat.value) : undefined,
          quote_product_id: action === 'set_quote' ? Number(articleQuoteId.value) : undefined,
        }
      : {
          ids: selectedIds.value,
          action,
          category_id: action === 'move' ? Number(articleMoveCat.value) : undefined,
          quote_product_id: action === 'set_quote' ? Number(articleQuoteId.value) : undefined,
        },
  })
  busy.value = true
  try {
    const res = action === 'delete' ? await runWithSecondPassword(send) : await send()
    if (res === undefined) return
    ElMessage.success((res as any)?.message || '批量处理完成')
    selectedIds.value = []
    articleSelectAll.value = false
    await refresh()
    if (import.meta.client) window.dispatchEvent(new Event('admin-menu-refresh'))
  } catch (e: any) {
    ElMessage.error(apiFailText(e, '批量失败'))
  } finally {
    busy.value = false
  }
}

const actionLabels: Record<string, string> = {
  approve: '通过',
  reject: '拒绝',
  offline: '下架',
  pending: '待审',
  ship: '发货',
  done: '完成',
  cancel: '取消',
}

function isShelfApprove() {
  const acts = metaCfg.value?.actions || {}
  return 'offline' in acts
    && resource.value !== 'vip-pay-orders'
    && resource.value !== 'fund-pay-orders'
}

const labelMaps = computed(() => buildAdminLabelMaps(metaCfg.value))
const canEdit = computed(() => (metaCfg.value as any)?.edit !== false && (fields.value?.length || 0) > 0)
const canDelete = computed(() => !!metaCfg.value?.create || !!metaCfg.value?.destroy)
/** 操作列单行展示：按按钮数量给够宽度，列表整体可横向滑动 */
const opsColMinWidth = computed(() => {
  if (resource.value === 'quote-products') return 360
  if (isArticles.value) return 320
  const acts = Object.keys(metaCfg.value?.actions || {}).length
  const extra = resource.value === 'inquiries' ? 72 : 0
  return Math.max(200, 72 + acts * 52 + (canDelete.value ? 48 : 0) + (canEdit.value ? 48 : 0) + extra)
})

function onPageChange(p: number) {
  page.value = p
}

function actionText(act: string) {
  const custom = (metaCfg.value as any)?.action_labels?.[act]
  if (custom) return String(custom)
  if (act === 'approve' && isShelfApprove()) return '上架'
  return actionLabels[act] || fieldEnumLabel('status', act) || act
}

function cellIsImage(key: string) {
  if (key === 'image' || key === 'logo' || key === 'thumb' || key === 'voucher_url') return true
  return key.endsWith('_url') && /image|logo|thumb|voucher|cover|avatar|photo/i.test(key)
}

function columnSortable(key: string) {
  return sortableKeys.value.includes(key) ? 'custom' : false
}

function openQuoteImport(row: any) {
  quoteTarget.value = row
  quoteFile.value = null
  quoteResult.value = null
  showQuoteImport.value = true
}

function onQuoteFile(e: Event) {
  const input = e.target as HTMLInputElement
  quoteFile.value = input.files?.[0] || null
  quoteResult.value = null
}

async function uploadQuotes() {
  if (!quoteTarget.value?.id) return
  if (!quoteFile.value) {
    ElMessage.warning('请先选择这个品种的表格')
    return
  }
  const body = new FormData()
  body.append('file', quoteFile.value)
  body.append('product_id', String(quoteTarget.value.id))
  quoteImporting.value = true
  try {
    const res = await api<any>('/admin/resources/quote-products/import', { method: 'POST', body })
    quoteResult.value = res?.data || null
    const d = quoteResult.value
    ElMessage.success(d ? `这个品种新增 ${d.imported} 天，覆盖 ${d.updated} 天` : '导入完成')
    await refresh()
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '导入失败')
  } finally {
    quoteImporting.value = false
  }
}

function openQuoteHistory(row: any) {
  if (!row?.id) return
  window.location.href = '/admin-quote-board.html?id=' + encodeURIComponent(String(row.id))
}

async function openQuoteHistoryEditor(row: any) {
  quoteTarget.value = row
  quoteHistory.value = []
  quoteNew.quoted_on = ''
  quoteNew.price_avg = ''
  quoteNew.price_min = ''
  quoteNew.price_max = ''
  showQuoteHistory.value = true
  quoteHistoryLoading.value = true
  try {
    const res = await api<any>(`/admin/resources/quote-products/${row.id}/prices`)
    quoteHistory.value = res?.data?.prices || []
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '历史加载失败')
  } finally {
    quoteHistoryLoading.value = false
  }
}

async function saveQuoteDay(row: { id?: number; quoted_on: string; price_avg: string; price_min: string; price_max: string }) {
  if (!quoteTarget.value?.id) return
  quoteHistorySaving.value = true
  try {
    await api(`/admin/resources/quote-products/${quoteTarget.value.id}/prices`, {
      method: 'POST',
      body: {
        id: row.id || undefined,
        quoted_on: row.quoted_on,
        price_avg: row.price_avg,
        price_min: row.price_min,
        price_max: row.price_max,
      },
    })
    ElMessage.success('已保存')
    await openQuoteHistoryEditor(quoteTarget.value)
    await refresh()
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '保存失败')
  } finally {
    quoteHistorySaving.value = false
  }
}

async function addQuoteDay() {
  await saveQuoteDay(quoteNew)
}

async function removeQuoteDay(row: { id: number }) {
  if (!quoteTarget.value?.id) return
  try {
    await ElMessageBox.confirm('确认删除这一天的价格？删除后可在回收站还原。', '删除确认', {
      type: 'warning',
      confirmButtonText: '确认删除',
    })
  } catch {
    return
  }
  quoteHistorySaving.value = true
  try {
    const res = await runWithSecondPassword(() =>
      api(`/admin/resources/quote-products/${quoteTarget.value!.id}/prices/${row.id}`, { method: 'DELETE' }),
    )
    if (res === undefined) return
    ElMessage.success('已移入回收站')
    await openQuoteHistoryEditor(quoteTarget.value)
    await refresh()
  } catch (e: any) {
    ElMessage.error(e?.data?.message || '删除失败')
  } finally {
    quoteHistorySaving.value = false
  }
}

function vipBar(level: any) {
  const n = Math.max(0, Math.min(10, Number(level || 0)))
  return '★'.repeat(n) + '☆'.repeat(10 - n)
}

/** 标题右侧的「示意图」：点击后在页面中间弹出，不把列表顶开 */
const showAdSchematic = computed(() =>
  resource.value === 'ads' || resource.value === 'banners',
)
const siteSchematics: Record<string, { place: string; name: string; where: string }> = {
  announcements: {
    place: 'announcements',
    name: '网站公告',
    where: '首页右侧，「您好，欢迎回来」下面的网站公告。点「更多」进入公告列表。',
  },
  hotwords: {
    place: 'hotwords',
    name: '搜索热词',
    where: '首页顶部搜索框下面一行「热门搜索」。',
  },
  'friend-links': {
    place: 'friend-links',
    name: '友情链接',
    where: '首页最底部的友情链接。',
  },
  pages: {
    place: 'pages',
    name: '帮助单页',
    where: '每页右上角的「帮助中心」，以及页脚「帮助中心」这一列。点进去就是这些单页。',
  },
  categories: {
    place: 'categories',
    name: '分类管理',
    where: '首页左侧「全部产品分类」，以及中间轮播下面的「子分类」。',
  },
}
const siteSchematic = computed(() => siteSchematics[resource.value] || null)
const showSchematicBtn = computed(() =>
  (showAdSchematic.value && !!pageSchematic.value) || !!siteSchematic.value,
)
const schematicOpen = ref(false)
const pageSchematic = computed(() => {
  if (resource.value === 'banners' && !positionFilter.value && !groupFilter.value) {
    return { code: 'home', name: '首页轮播', title: '首页轮播', size: '' }
  }
  const code = positionFilter.value || groupFilter.value
  if (!code) return null
  const name = groupLabel.value || positionLabel.value || placeNames[code] || code
  return { code, name, title: name, size: '' }
})
function openSchematic() {
  if (!showSchematicBtn.value) return
  schematicOpen.value = true
}
watch(() => route.fullPath, () => {
  schematicOpen.value = false
})
</script>

<template>
  <div class="panel">
    <div class="toolbar">
      <div class="toolbar-main">
        <div class="title-row">
          <strong class="ptitle">{{ pageTitle }}</strong>
          <el-button v-if="showSchematicBtn" plain type="primary" @click="openSchematic">示意图</el-button>
        </div>
        <p v-if="resourceHint" class="res-hint">{{ resourceHint }}</p>
        <div class="filter-bar">
          <el-input
            v-model="q"
            clearable
            placeholder="关键词"
            class="filter-q"
            @keyup.enter="search"
          />
          <el-select
            v-if="metaCfg?.status && !isHonors"
            v-model="status"
            clearable
            placeholder="全部状态"
            class="filter-sel"
            @change="onStatusFilterChange"
          >
            <el-option v-for="(lab, val) in metaCfg.status.options" :key="val" :label="lab" :value="String(val)" />
          </el-select>
          <el-select
            v-for="f in toolbarFilters"
            :key="'tf-' + f.key"
            v-model="colFilters[f.key]"
            clearable
            :placeholder="f.label"
            class="filter-sel"
            @change="search"
          >
            <el-option v-for="(lab, val) in f.options" :key="val" :label="lab" :value="String(val)" />
          </el-select>
          <el-tag v-if="routeLockedVip" type="danger" effect="plain" class="filter-tag">VIP 企业</el-tag>
          <el-tag v-if="routeLockedModule" type="info" effect="plain" class="filter-tag">模块：{{ moduleFilter }}</el-tag>
          <el-tag v-if="routeLockedGroup" type="info" effect="plain" class="filter-tag">区域：{{ groupLabel }}</el-tag>
          <el-tag v-else-if="routeLockedPosition" type="info" effect="plain" class="filter-tag">位置：{{ positionLabel }}</el-tag>
          <el-button type="primary" :loading="pending" @click="search">查询</el-button>
          <el-button v-if="hasActiveFilters" plain @click="resetFilters">重置</el-button>
        </div>
      </div>
      <el-button v-if="metaCfg?.create" type="warning" @click="openCreate">新建</el-button>
    </div>

    <el-dialog
      v-model="schematicOpen"
      :title="`${pageTitle}示意图`"
      :width="siteSchematic ? '980px' : '760px'"
      align-center
      append-to-body
      destroy-on-close
    >
      <AdPlacePreview
        v-if="showAdSchematic && pageSchematic"
        :code="pageSchematic.code"
        :name="pageSchematic.name"
        :title="pageSchematic.title"
        :size="pageSchematic.size"
      />
      <SitePlacePreview
        v-else-if="siteSchematic"
        :place="siteSchematic.place"
        :name="siteSchematic.name"
        :where="siteSchematic.where"
      />
    </el-dialog>

    <div v-if="isHonors" class="honor-tabs">
      <el-radio-group :model-value="honorTab" size="small" @change="(v: any) => setHonorTab(String(v))">
        <el-radio-button value="all">列表</el-radio-button>
        <el-radio-button value="pending">待审</el-radio-button>
        <el-radio-button value="expired">过期</el-radio-button>
        <el-radio-button value="rejected">未通过</el-radio-button>
        <el-radio-button value="offline">回收站</el-radio-button>
      </el-radio-group>
    </div>

    <div v-if="isCompanies && !isVipMode" class="company-filters">
      <el-input v-model="companyFilters.member" clearable placeholder="会员名/ID" style="width: 130px" />
      <el-input v-model="companyFilters.area" clearable placeholder="所在地区" style="width: 120px" />
      <el-select v-model="companyFilters.is_featured" clearable placeholder="推荐" style="width: 100px">
        <el-option label="推荐" value="1" />
        <el-option label="未推荐" value="0" />
      </el-select>
      <el-select v-model="companyFilters.is_certified" clearable placeholder="核实" style="width: 100px">
        <el-option label="已核实" value="1" />
        <el-option label="未核实" value="0" />
      </el-select>
      <el-input v-model="companyFilters.vip_level" clearable placeholder="VIP指数" style="width: 100px" />
      <el-select v-model="companyFilters.company_type" clearable placeholder="公司类型" style="width: 120px">
        <el-option label="企业单位" value="企业单位" />
        <el-option label="个体经营" value="个体经营" />
        <el-option label="事业单位" value="事业单位" />
        <el-option label="其他组织" value="其他组织" />
      </el-select>
      <el-select v-model="companyFilters.business_mode" clearable placeholder="经营模式" style="width: 120px">
        <el-option label="制造商" value="制造商" />
        <el-option label="贸易商" value="贸易商" />
        <el-option label="服务商" value="服务商" />
      </el-select>
      <el-input v-model="companyFilters.capital_min" clearable placeholder="资本≥" style="width: 90px" />
      <el-input v-model="companyFilters.capital_max" clearable placeholder="资本≤" style="width: 90px" />
      <el-button @click="search">筛选</el-button>
    </div>

    <div v-if="isArticles" class="article-batch" data-article-batch="native">
      <div class="ab-lead">
        <b>{{ articlePickCount }}</b>
        <span>篇已选</span>
      </div>
      <el-button class="ab-all" :class="{ on: articleSelectAll }" @click="toggleArticleAll">
        {{ articleSelectAll ? '取消全选' : '全选当前筛选的全部' }}
      </el-button>
      <div class="ab-group">
        <el-button type="primary" :disabled="busy || !articlePickCount" @click="runArticleBatch('publish')">批量上架</el-button>
        <el-button :disabled="busy || !articlePickCount" @click="runArticleBatch('offline')">批量下架</el-button>
        <el-button type="danger" plain :disabled="busy || !articlePickCount" @click="runArticleBatch('delete')">批量删除</el-button>
      </div>
      <div class="ab-move">
        <span>改到别的资讯分类</span>
        <el-select v-model="articleMoveCat" filterable clearable placeholder="选择要放入的分类" class="ab-cat">
          <el-option v-for="c in articleCats" :key="c.id" :label="c.label" :value="c.id" />
        </el-select>
        <el-button type="warning" :disabled="busy || !articlePickCount || !articleMoveCat" @click="runArticleBatch('move')">
          {{ articleMoveCat ? '移动过去' : '先选分类' }}
        </el-button>
      </div>
      <div class="ab-move ab-quote">
        <span>绑定行情品种</span>
        <el-select v-model="articleQuoteId" filterable clearable placeholder="选择行情品种（DMC/生胶等）" class="ab-cat">
          <el-option v-for="c in articleQuotes" :key="c.id" :label="c.label" :value="c.id" />
        </el-select>
        <el-button type="success" :disabled="busy || !articlePickCount || !articleQuoteId" @click="runArticleBatch('set_quote')">
          {{ articleQuoteId ? '绑定品种' : '先选品种' }}
        </el-button>
      </div>
      <p class="ab-note">{{ articleSelectAll ? '已选中当前筛选下的全部资讯，翻页也还在。移动到新分类后，再批量上架，才会出现在那个分类的资讯板块。价格行情资讯还可批量绑定行情品种。' : '表头勾选只选这一页。旁边的按钮选中当前筛选的全部页。移动到新分类后，再批量上架，才会出现在那个分类的资讯板块。价格行情资讯还可批量绑定行情品种。' }}</p>
    </div>

    <div v-if="isCompanies && !isVipMode" class="batch-bar">
      <span>已选 {{ selectedIds.length }}</span>
      <el-button size="small" :disabled="busy" @click="runBatch('feature')">批量推荐</el-button>
      <el-button size="small" :disabled="busy" @click="runBatch('certify')">批量核实</el-button>
      <el-input-number v-model="batchVipLevel" :min="0" :max="10" size="small" />
      <el-button size="small" type="warning" :disabled="busy" @click="runBatch('vip')">设置VIP指数</el-button>
      <el-button size="small" type="danger" plain :disabled="busy" @click="runBatch('offline')">批量下架</el-button>
    </div>

    <el-table
        v-loading="pending"
        :data="items"
        stripe
        border
        class="tbl"
        empty-text="暂无数据"
        :default-sort="{ prop: sortKey, order: sortOrder === 'asc' ? 'ascending' : 'descending' }"
        @selection-change="onSelectionChange"
        @sort-change="onSortChange"
      >
        <el-table-column v-if="(isCompanies && !isVipMode) || isArticles" type="selection" width="46" />
        <el-table-column
          v-for="c in displayColumns"
          :key="c.key"
          :prop="c.key"
          :label="c.label"
          :sortable="columnSortable(c.key)"
          :min-width="c.key === 'title' || c.key === 'name' ? 180 : 108"
          show-overflow-tooltip
        >
          <template #default="{ row }">
            <button
              v-if="resource === 'quote-products' && c.key === 'name'"
              type="button"
              class="quote-name"
              @click="openQuoteHistory(row)"
            >{{ row.name }}</button>
            <el-image
              v-else-if="cellIsImage(c.key) && row[c.key]"
              :src="row[c.key]"
              :preview-src-list="[String(row[c.key])]"
              fit="cover"
              class="thumb"
              preview-teleported
              hide-on-click-modal
            />
            <span v-else-if="cellIsImage(c.key)" class="muted">未上传</span>
            <span v-else-if="c.key === 'audit_label' || c.key === 'shelf_label'" class="ab-tag" :data-tone="tagTone(String(row[c.key] || ''))">{{ row[c.key] || '—' }}</span>
            <span v-else-if="c.key === 'vip_level' && isVipMode" class="vip-bar" :title="String(row.vip_level || 0)">{{ vipBar(row.vip_level) }}</span>
            <span v-else-if="c.key === 'expires_at'">{{ row.expires_at || '永久' }}</span>
            <span v-else-if="c.key === 'vip_from'">{{ row.vip_from || '—' }}</span>
            <AdminCell v-else :value="row" :path="c.key" :maps="labelMaps" />
          </template>
        </el-table-column>
        <el-table-column label="操作" fixed="right" :min-width="opsColMinWidth">
          <template #default="{ row }">
            <div class="ops">
              <el-button v-if="resource === 'quote-products'" link type="primary" :disabled="busy" @click="openQuoteHistory(row)">价格图表</el-button>
              <el-button v-if="resource === 'quote-products'" link type="success" :disabled="busy" @click="openQuoteImport(row)">导入历史</el-button>
              <el-button v-if="resource === 'inquiries'" link type="primary" :disabled="busy" @click="openInquiryView(row)">查看留言</el-button>
              <el-button v-if="canEdit" link type="primary" :disabled="busy" @click="openEdit(row)">{{ isVipMode ? '改指数' : '编辑' }}</el-button>
              <el-button
                v-for="(_val, act) in (metaCfg?.actions || {})"
                v-show="inquiryActionVisible(row, String(act))"
                :key="act"
                link
                type="warning"
                :disabled="busy"
                @click="doAction(row, String(act))"
              >{{ actionText(String(act)) }}</el-button>
              <el-button v-if="canDelete" link type="danger" :disabled="busy" @click="removeRow(row)">删除</el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>

    <el-dialog
      v-model="showInquiryView"
      :title="inquiryView?.title ? `留言：${inquiryView.title}` : '查看留言'"
      width="640px"
      class="inq-view-dialog"
      align-center
      destroy-on-close
    >
      <div v-if="inquiryViewLoading" class="inq-view-loading">加载中…</div>
      <div v-else-if="inquiryView" class="inq-view-scroll">
        <div class="inq-view">
          <div class="inq-meta">
            <span>状态：{{ inquiryStatusText(String(inquiryView.status || '')) }}</span>
            <span>提交：{{ String(inquiryView.created_at || '').slice(0, 19) || '—' }}</span>
            <span>意向企业：{{ inquiryView.to_user_label || inquiryView.to_user_id || '—' }}</span>
          </div>
          <div class="inq-meta">
            <span>联系人：{{ inquiryView.contact_name || '—' }}</span>
            <span>电话：{{ inquiryView.contact_phone || '—' }}</span>
            <span v-if="inquiryView.contact_email">邮箱：{{ inquiryView.contact_email }}</span>
          </div>
          <div class="inq-bubble user">
            <div class="inq-bubble-hd">用户留言</div>
            <pre class="inq-bubble-bd">{{ inquiryView.content || '（无内容）' }}</pre>
          </div>
          <div v-if="inquiryView.reply" class="inq-bubble ent">
            <div class="inq-bubble-hd">企业回复{{ String(inquiryView.status || '') === 'reply_pending' ? '（待审）' : '' }}</div>
            <pre class="inq-bubble-bd">{{ inquiryView.reply }}</pre>
          </div>
        </div>
      </div>
      <template #footer>
        <el-button @click="showInquiryView = false">返回</el-button>
        <el-button
          v-if="inquiryView && String(inquiryView.status || '') === 'new'"
          type="warning"
          :disabled="busy"
          @click="showInquiryView = false; doAction(inquiryView, 'approve')"
        >转发企业</el-button>
        <el-button
          v-if="inquiryView && String(inquiryView.status || '') === 'new'"
          type="danger"
          plain
          :disabled="busy"
          @click="showInquiryView = false; doAction(inquiryView, 'done')"
        >不予转发</el-button>
        <el-button
          v-if="inquiryView && String(inquiryView.status || '') === 'reply_pending'"
          type="warning"
          :disabled="busy"
          @click="showInquiryView = false; doAction(inquiryView, 'approve_reply')"
        >通过回复</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="showQuoteImport" :title="`导入「${quoteTarget?.name || ''}」的历史价格`" width="560px">
      <div class="quote-import-row">
        <input type="file" accept=".csv,.xlsx,text/csv" @change="onQuoteFile">
      </div>
      <div v-if="quoteResult" class="quote-import-result">
        <p>新增 {{ quoteResult.imported }} 天，覆盖 {{ quoteResult.updated }} 天，跳过 {{ quoteResult.skipped }} 天。</p>
        <p v-for="(err, i) in quoteResult.errors" :key="i">{{ err }}</p>
      </div>
      <template #footer>
        <el-button @click="showQuoteImport = false">关闭</el-button>
        <el-button type="primary" :loading="quoteImporting" @click="uploadQuotes">上传这个品种</el-button>
      </template>
    </el-dialog>

    <el-dialog v-model="showQuoteHistory" :title="`「${quoteTarget?.name || ''}」的历史价格`" width="760px">
      <p class="quote-dialog-lead">这里是这个品种的全部历史，共 {{ quoteHistory.length }} 天。上传的表格和后来每天改的价格都在里面，按日期从新到旧排列。改完点这一行的保存，最新一天会显示在行情页上。</p>
      <div class="quote-history-row quote-history-add">
        <input v-model="quoteNew.quoted_on" type="date">
        <input v-model="quoteNew.price_avg" type="number" step="any" placeholder="均价">
        <input v-model="quoteNew.price_min" type="number" step="any" placeholder="最低价">
        <input v-model="quoteNew.price_max" type="number" step="any" placeholder="最高价">
        <el-button type="primary" size="small" :loading="quoteHistorySaving" @click="addQuoteDay">添加</el-button>
      </div>
      <p v-if="quoteHistoryLoading" class="quote-note">加载中…</p>
      <div v-else class="quote-history-list">
        <div v-for="row in quoteHistory" :key="row.id" class="quote-history-row">
          <input v-model="row.quoted_on" type="date">
          <input v-model="row.price_avg" type="number" step="any" placeholder="均价">
          <input v-model="row.price_min" type="number" step="any" placeholder="最低价">
          <input v-model="row.price_max" type="number" step="any" placeholder="最高价">
          <el-button size="small" :loading="quoteHistorySaving" @click="saveQuoteDay(row)">保存</el-button>
          <el-button size="small" type="danger" link :disabled="quoteHistorySaving" @click="removeQuoteDay(row)">删除</el-button>
        </div>
        <p v-if="!quoteHistory.length" class="quote-note">还没有历史价格。可以在上面加一天，或用「导入历史」上传表格。</p>
      </div>
      <template #footer>
        <el-button @click="showQuoteHistory = false">关闭</el-button>
      </template>
    </el-dialog>

    <div v-if="isVipMode" class="vip-glossary">
      <p><b>名词解释</b></p>
      <p><b>VIP指数：</b>综合评分，范围 0–10，用于排序与推荐权重。</p>
      <p><b>理论值：</b>系统按会员级别/认证等给出的赠送指数。</p>
      <p><b>修正值：</b>人工加减调整；最终 VIP指数 = clamp(理论值 + 修正值, 0, 10)。</p>
    </div>

    <div v-if="pageMeta.last_page > 1" class="pager">
      <el-pagination
        background
        layout="total, prev, pager, next"
        :total="pageMeta.total || 0"
        :page-size="20"
        :current-page="page"
        @current-change="onPageChange"
      />
    </div>

    <el-drawer
      v-model="showForm"
      :title="`${formMode === 'create' ? '新建' : '编辑'} ${metaCfg?.label || ''}`"
      size="min(960px, 96vw)"
      destroy-on-close
    >
      <AdminResourceForm v-model="form" :fields="fields" :mode="formMode" :resource="resource" />
      <template #footer>
        <el-button @click="showForm = false">取消</el-button>
        <el-button type="primary" :loading="busy" @click="submitForm">保存</el-button>
      </template>
    </el-drawer>

    <el-dialog v-model="showVipEdit" title="修改 VIP 指数" width="420px" destroy-on-close>
      <div class="vip-edit">
        <label>理论值 <el-input-number v-model="vipEditForm.vip_theory" :min="-10" :max="10" /></label>
        <label>修正值 <el-input-number v-model="vipEditForm.vip_correct" :min="-10" :max="10" /></label>
        <label>VIP指数 <el-input-number v-model="vipEditForm.vip_level" :min="0" :max="10" disabled /></label>
        <label>开通时间 <el-input v-model="vipEditForm.vip_from" type="datetime-local" /></label>
      </div>
      <template #footer>
        <el-button @click="showVipEdit = false">取消</el-button>
        <el-button type="primary" :loading="busy" @click="saveVipEdit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.panel {
  background: #fff;
  border: 1px solid #e8eaed;
  border-radius: 14px;
  padding: 16px 16px 14px;
  box-shadow: 0 1px 2px rgba(20,24,32,.04);
  width: 100%;
  box-sizing: border-box;
}
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}
.toolbar-main {
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 0;
  flex: 1;
}
.filter-bar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  background: #f7f9fc;
  border: 1px solid #e8edf5;
  border-radius: 10px;
}
.filter-q { width: 200px; }
.filter-sel { width: 132px; }
.filter-tag { height: 32px; line-height: 30px; }
.title-row {
  display: flex;
  align-items: center;
  gap: 12px;
}
.res-hint {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 12.5px;
  line-height: 1.55;
  max-width: 920px;
}
.ptitle {
  font-size: 16px;
  font-weight: 700;
  color: #1a2332;
  line-height: 1.3;
}
.tbl :deep(thead th) {
  background: #fafbfc !important;
  color: #334155;
  font-weight: 600;
}
.tbl :deep(.caret-wrapper) {
  height: 18px;
}
.ops {
  display: inline-flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 2px;
  white-space: nowrap;
}
.pager { display: flex; justify-content: flex-end; margin-top: 14px; }
.tbl { width: 100%; }
/* 只让表体横滑，表头跟随同步，避免上下各出一条滚动条 */
.tbl :deep(.el-table__header-wrapper) {
  overflow-x: hidden !important;
}
.tbl :deep(.el-table__body-wrapper) {
  overflow-x: auto !important;
}
.tbl :deep(.el-table__fixed-right .ops),
.tbl :deep(.el-table__body .ops) {
  flex-wrap: nowrap;
  white-space: nowrap;
}
.company-filters, .batch-bar, .honor-tabs {
  display: flex; flex-wrap: wrap; gap: 8px; align-items: center; margin-bottom: 12px;
}
.batch-bar { padding: 8px 10px; background: #f8fafc; border-radius: 8px; font-size: 13px; color: #64748b; }
.article-batch {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px 16px;
  margin-bottom: 12px;
  padding: 14px 16px 12px;
  background: #fff;
  border: 1px solid #e6ebf2;
  border-left: 3px solid #1d4fd7;
  border-radius: 12px;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
}
.ab-lead { display: flex; align-items: baseline; gap: 6px; min-width: 88px; color: #334155; }
.ab-lead b { font-size: 22px; line-height: 1; color: #1d4fd7; }
.ab-all { color: #1d4fd7; border-color: #c9d7f5; background: #f5f8ff; }
.ab-all.on { background: #1d4fd7; border-color: #1d4fd7; color: #fff; }
.ab-group, .ab-move { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
.ab-move { padding-left: 14px; border-left: 1px solid #e8edf5; color: #475569; font-size: 13px; }
.ab-cat { width: 240px; }
.ab-note { flex: 1 1 100%; margin: 0; color: #64748b; font-size: 12px; line-height: 1.5; }
.ab-tag {
  display: inline-flex;
  align-items: center;
  min-width: 52px;
  justify-content: center;
  padding: 2px 8px;
  border-radius: 999px;
  font-size: 12px;
  line-height: 20px;
  background: #f1f5f9;
  color: #475569;
}
.ab-tag[data-tone="ok"] { background: #e8f7ee; color: #157a3a; }
.ab-tag[data-tone="wait"] { background: #fff6e8; color: #b45309; }
.ab-tag[data-tone="no"] { background: #fdecec; color: #b42318; }
.ab-tag[data-tone="off"] { background: #eef2f7; color: #475569; }
.thumb {
  width: 56px; height: 56px; border-radius: 4px; border: 1px solid #e2e8f0;
  cursor: zoom-in; display: block;
}
.thumb :deep(img) { width: 56px; height: 56px; object-fit: cover; }
.muted { color: #94a3b8; font-size: 12px; }
.vip-bar { letter-spacing: 1px; color: #1443b8; font-size: 12px; white-space: nowrap; }
.vip-glossary {
  margin-top: 16px; padding: 12px 14px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; font-size: 13px; color: #78350f;
}
.vip-glossary p { margin: 4px 0; }
.quote-note { margin-top: 8px !important; color: #5b6b7c; }
.quote-import-result { margin-top: 8px; color: #1443b8; }
.quote-import-result p { margin: 2px 0; }
.quote-dialog-lead { margin: 0 0 8px; }
.quote-import-row { display: flex; gap: 10px; align-items: center; margin-top: 10px; flex-wrap: wrap; }
.quote-name { border: 0; background: none; padding: 0; color: #1443b8; cursor: pointer; font: inherit; font-weight: 700; }
.quote-name:hover { text-decoration: underline; }
.quote-history-list { max-height: 420px; overflow: auto; margin-top: 10px; }
.quote-history-row { display: grid; grid-template-columns: 150px 1fr 1fr 1fr auto auto; gap: 8px; align-items: center; margin-bottom: 8px; }
.quote-history-row input { width: 100%; box-sizing: border-box; padding: 6px 8px; border: 1px solid #d7e0ea; border-radius: 4px; }
.quote-history-add { margin-top: 8px; }
.vip-edit { display: grid; gap: 12px; }
.vip-edit label { display: flex; align-items: center; justify-content: space-between; gap: 12px; font-size: 13px; color: #475569; }
.toolbar :deep(.el-button--warning) {
  background: #2468f2;
  border-color: #2468f2;
  color: #fff;
  font-weight: 700;
}
.inq-view-loading { color: #64748b; padding: 12px 0; }
.inq-view-scroll {
  max-height: min(58vh, 520px);
  overflow-y: auto;
  overscroll-behavior: contain;
  padding-right: 4px;
}
.inq-view { display: flex; flex-direction: column; gap: 12px; }
.inq-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 16px;
  font-size: 13px;
  color: #64748b;
}
.inq-bubble {
  border: 1px solid #e8edf5;
  border-radius: 10px;
  overflow: hidden;
  background: #f8fafc;
}
.inq-bubble.ent { background: #f0f7ff; border-color: #d6e6ff; }
.inq-bubble-hd {
  padding: 8px 12px;
  font-size: 12px;
  font-weight: 700;
  color: #334155;
  border-bottom: 1px solid #e8edf5;
}
.inq-bubble.ent .inq-bubble-hd { border-bottom-color: #d6e6ff; color: #1e40af; }
.inq-bubble-bd {
  margin: 0;
  padding: 12px;
  white-space: pre-wrap;
  word-break: break-word;
  font: inherit;
  font-size: 13.5px;
  line-height: 1.55;
  color: #0f172a;
  max-height: 240px;
  overflow-y: auto;
}
</style>

<style>
/* 弹窗本体限制在可视区内，长内容在内部滚动，不撑出屏幕 */
.inq-view-dialog.el-dialog {
  margin-top: 6vh !important;
  max-height: 88vh;
  display: flex;
  flex-direction: column;
}
.inq-view-dialog .el-dialog__body {
  flex: 1;
  min-height: 0;
  overflow: hidden;
  padding-top: 8px;
  padding-bottom: 8px;
}
</style>

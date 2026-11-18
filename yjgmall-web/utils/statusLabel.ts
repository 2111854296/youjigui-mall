/** 后台/前台通用：枚举码 → 中文（绝不把英文码直接给客户看） */

const GLOBAL: Record<string, string> = {
  // 通用状态
  pending: '待审核',
  active: '正常',
  banned: '已禁用',
  approved: '已通过',
  published: '已发布',
  rejected: '已拒绝',
  offline: '已下架',
  draft: '草稿',
  hidden: '已隐藏',
  open: '开放中',
  closed: '已关闭',
  done: '已完成',
  replied: '已回复',
  processing: '处理中',
  new: '新',
  expired: '已过期',
  cancelled: '已取消',
  canceled: '已取消',
  refunded: '已退款',
  paid: '已付款',
  shipped: '已发货',
  completed: '已完成',

  // 会员类型 / 会员组
  personal: '个人会员',
  company: '企业会员',
  enterprise: '企业会员',
  admin: '管理员',
  guest: '游客',
  vip: 'VIP会员',

  // 资讯/广告等类型
  news: '资讯',
  quote: '行情',
  image: '图片',
  text: '文字',
  html: 'HTML',
  code: '代码',

  // 广告位 / 模块
  home_banner: '首页横幅',
  home_right: '首页右侧',
  sell_right: '供应右侧',
  buy_right: '求购右侧',
  mall_top: '商城顶部',
  news_top: '资讯顶部',
  footer: '页脚',
  sell: '供应',
  buy: '采购',
  mall: '商城',
  company_mod: '企业库',
  article: '资讯',
  down: '下载',
  job: '招聘',
  know: '知道',
  club: '商圈',

  // 布尔 / 开关常见码
  '1': '是',
  '0': '否',
  true: '是',
  false: '否',

  // VIP 等级数字（无 field 上下文时再细化）
}

/** 按字段名的专用映射（覆盖 GLOBAL） */
const BY_FIELD: Record<string, Record<string, string>> = {
  member_type: {
    personal: '个人会员',
    company: '企业会员',
  },
  group_code: {
    admin: '管理员',
    banned: '禁止访问',
    guest: '游客',
    pending: '待审核会员',
    personal: '个人会员',
    enterprise: '企业会员',
    company: '企业会员',
    vip: 'VIP渠道会员',
  },
  status: {
    pending: '待审核',
    active: '正常',
    banned: '已禁用',
    approved: '已通过',
    published: '已发布',
    rejected: '已拒绝',
    offline: '已下架',
    draft: '草稿',
    hidden: '已隐藏',
    open: '开放中',
    closed: '已关闭',
    done: '已完成',
    replied: '已回复',
    processing: '处理中',
    new: '新',
    paid: '已付款',
    shipped: '已发货',
    completed: '已完成',
    cancelled: '已取消',
    refunded: '已退款',
    expired: '已过期',
  },
  level: {
    '0': '普通会员',
    '1': '白银会员',
    '2': '黄金会员',
    '3': '星钻会员',
  },
  type: {
    news: '资讯',
    quote: '行情',
    image: '图片',
    text: '文字',
    html: 'HTML',
    supply: '供应',
    purchase: '采购',
    product: '商品',
    company: '企业',
  },
  module: {
    sell: '供应',
    buy: '采购',
    mall: '商城',
    company: '企业库',
    news: '资讯',
    article: '资讯',
    down: '下载',
    job: '招聘',
    know: '知道',
    club: '商圈',
    home: '首页',
  },
  position: {
    home: '首页轮播',
    home_banner: '首页轮播',
    home_right: '首页右侧',
    sell_right: '供应右侧',
    buy_right: '求购右侧',
    mall_top: '商城轮播',
    company_top: '企业库轮播',
    news_top: '资讯轮播',
    footer: '页脚',
  },
  is_active: { '1': '启用', '0': '停用', true: '启用', false: '停用' },
  is_featured: { '1': '是', '0': '否', true: '是', false: '否' },
  is_certified: { '1': '已认证', '0': '未认证', true: '已认证', false: '未认证' },
  is_top: { '1': '是', '0': '否', true: '是', false: '否' },
  is_admin: { '1': '是', '0': '否', true: '是', false: '否' },
  is_best: { '1': '是', '0': '否', true: '是', false: '否' },
  is_vip: { '1': '是', '0': '否', true: '是', false: '否' },
}

export function auditStatusLabel(s?: string | null) {
  return enumLabel(s)
}

/** 任意枚举值 → 中文；未知则原样返回（避免空白） */
export function enumLabel(value: unknown): string {
  if (value === true) return '是'
  if (value === false) return '否'
  if (value == null || value === '') return '—'
  const key = String(value)
  return GLOBAL[key] ?? BY_FIELD.status[key] ?? key
}

/**
 * 按字段名翻译。优先用接口下发的 options 映射，其次字段专用表，最后全局表。
 * maps: { status: { pending: '待审' }, member_type: {...} }
 */
export function fieldEnumLabel(
  fieldPath: string,
  value: unknown,
  maps?: Record<string, Record<string, string>> | null,
): string {
  if (value === true) return '是'
  if (value === false) return '否'
  if (value == null || value === '') return '—'

  const leaf = fieldPath.includes('.') ? fieldPath.split('.').pop()! : fieldPath
  const key = String(value)

  const fromMeta = maps?.[leaf]?.[key] ?? maps?.[fieldPath]?.[key]
  if (fromMeta) return fromMeta

  // 数字键兼容
  const fromMetaNum = maps?.[leaf]?.[Number(key) as any]
  if (fromMetaNum) return fromMetaNum

  const byField = BY_FIELD[leaf]?.[key]
  if (byField) return byField

  return GLOBAL[key] ?? key
}

/** 从后台 meta.fields / status.options / filters 拼出翻译表 */
export function buildAdminLabelMaps(meta: any): Record<string, Record<string, string>> {
  const maps: Record<string, Record<string, string>> = {}
  const status = meta?.status
  if (status?.options && typeof status.options === 'object') {
    const field = String(status.field || 'status')
    maps[field] = normalizeOptions(status.options)
  }
  for (const f of meta?.fields || []) {
    if (f?.name && f.options && typeof f.options === 'object') {
      maps[String(f.name)] = normalizeOptions(f.options)
    }
  }
  const filters = meta?.filters
  if (filters && typeof filters === 'object') {
    for (const [key, f] of Object.entries(filters as Record<string, any>)) {
      if (f?.options && typeof f.options === 'object') {
        maps[String(f.field || key)] = normalizeOptions(f.options)
      }
    }
  }
  return maps
}

function normalizeOptions(opts: Record<string, string>): Record<string, string> {
  const out: Record<string, string> = {}
  for (const [k, v] of Object.entries(opts)) {
    out[String(k)] = String(v)
  }
  return out
}

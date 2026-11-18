/**
 * Restore Chinese labels in admin_resources.php from think-deploy UTF-8 copy,
 * then apply known post-deploy renames + vip/fund pay blocks.
 * Writes UTF-8 without BOM via fs.writeFileSync.
 */
const fs = require('fs')
const path = require('path')

const root = path.resolve(__dirname, '../..')
const badPath = path.join(root, 'yjgmall-think/config/admin_resources.php')
const goodPath = path.join(root, 'yjgmall-think-deploy/config/admin_resources.php')
const logPath = path.join(root, 'debug-b1a1b9.log')

function log(message, data) {
  const line = JSON.stringify({
    sessionId: 'b1a1b9',
    hypothesisId: 'A',
    location: 'restore_admin_resources.js',
    message,
    data,
    timestamp: Date.now(),
    runId: 'fix',
  })
  fs.appendFileSync(logPath, line + '\n')
  console.log(line)
}

function extractResourceBlocks(src) {
  const map = {}
  const re = /\n    '([a-z0-9-]+)' => \[([\s\S]*?)\n    \],/g
  let m
  while ((m = re.exec(src))) {
    map[m[1]] = m[2]
  }
  return map
}

function pick(str, re) {
  const m = str.match(re)
  return m ? m[1] : null
}

function extractMeta(body) {
  const label = pick(body, /'label' => '([^']*)'/)
  const group = pick(body, /'group' => '([^']*)'/)
  const columns = {}
  const colRe = /\['key' => '([^']+)', 'label' => '([^']*)'\]/g
  let m
  while ((m = colRe.exec(body))) columns[m[1]] = m[2]
  const options = {}
  const optBlock = body.match(/'options' => \[([\s\S]*?)\]/)
  if (optBlock) {
    const optRe = /'([^']+)' => '([^']*)'/g
    while ((m = optRe.exec(optBlock[1]))) options[m[1]] = m[2]
  }
  return { label, group, columns, options }
}

const bad = fs.readFileSync(badPath, 'utf8')
const good = fs.readFileSync(goodPath, 'utf8')
const goodBlocks = extractResourceBlocks(good)
const goodMeta = {}
for (const [k, body] of Object.entries(goodBlocks)) {
  goodMeta[k] = extractMeta(body)
}

// Post-menu-regroup overrides (from earlier Agent work)
const labelOverrides = {
  users: { label: '会员账号', group: '会员与资金' },
  supplies: { label: '供应信息', group: '信息审核' },
  purchases: { label: '采购信息', group: '信息审核' },
  products: { label: '商城商品', group: '信息审核' },
  orders: { label: '商城订单', group: '买卖与订单' },
  inquiries: { label: '询盘留言', group: '买卖与订单' },
  reviews: { label: '商品评价', group: '买卖与订单' },
  withdraws: { label: '提现审核', group: '会员与资金' },
  'ask-tickets': { label: '客服工单', group: '会员与资金' },
  'vip-applications': { label: 'VIP申请(旧)', group: '会员与资金' },
  banners: { label: '首页轮播', group: '广告推广' },
  ads: { label: '广告位', group: '广告推广' },
  'ad-bookings': { label: '广告预定', group: '广告推广' },
  spreads: { label: '关键词推广', group: '广告推广' },
  announcements: { label: '网站公告', group: '网站配置' },
  hotwords: { label: '搜索热词', group: '网站配置' },
  'friend-links': { label: '友情链接', group: '网站配置' },
  pages: { label: '帮助单页', group: '网站配置' },
  categories: { label: '分类管理', group: '网站配置' },
  articles: { group: '资讯' },
  companies: { group: '企业库' },
  'company-news': { label: '企业新闻', group: '企业库' },
  'company-honors': { label: '荣誉资质', group: '企业库' },
  'company-pages': { label: '企业单页', group: '企业库' },
  'company-links': { label: '企业友情链接', group: '企业库' },
  'special-items': { label: '专题条目', group: '更多功能' },
  jobs: { group: '更多功能' },
  know: { group: '更多功能' },
  club: { group: '更多功能' },
  brands: { group: '更多功能' },
  downloads: { group: '更多功能' },
  exhibits: { group: '更多功能' },
  gifts: { group: '更多功能' },
  'group-deals': { group: '更多功能' },
  photos: { group: '更多功能' },
  videos: { group: '更多功能' },
}

const vipPayBlock = `
    'vip-pay-orders' => [
        'label' => 'VIP开通审核',
        'model' => app\\model\\VipPayOrder::class,
        'group' => '会员与资金',
        'with' => ['user', 'grade'],
        'search' => ['order_no', 'company_name', 'contact_name', 'mobile', 'transfer_note'],
        'columns' => [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'order_no', 'label' => '订单号'],
            ['key' => 'user.username', 'label' => '会员账号'],
            ['key' => 'grade_name', 'label' => '档位'],
            ['key' => 'amount', 'label' => '金额'],
            ['key' => 'company_name', 'label' => '公司'],
            ['key' => 'contact_name', 'label' => '联系人'],
            ['key' => 'mobile', 'label' => '手机'],
            ['key' => 'voucher_url', 'label' => '付款凭证'],
            ['key' => 'status', 'label' => '状态'],
            ['key' => 'expire_at', 'label' => '支付截止'],
            ['key' => 'voucher_uploaded_at', 'label' => '传凭证时间'],
        ],
        'status' => [
            'field' => 'status',
            'options' => [
                'pending_pay' => '待付款',
                'pending_review' => '待审核',
                'approved' => '已开通',
                'rejected' => '已驳回',
                'expired' => '已超时',
                'cancelled' => '已取消',
            ],
        ],
        'actions' => ['approve' => 'approved', 'reject' => 'rejected'],
        'editable' => ['status', 'admin_remark'],
        'create' => false,
        'default_status' => 'pending_review',
        'hook' => 'vip_pay_approve',
    ],
`

const fundPayBlock = `
    'fund-pay-orders' => [
        'label' => '充值入账审核',
        'model' => app\\model\\FundPayOrder::class,
        'group' => '会员与资金',
        'with' => ['user'],
        'search' => ['order_no', 'transfer_note', 'title'],
        'columns' => [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'order_no', 'label' => '订单号'],
            ['key' => 'user.username', 'label' => '会员账号'],
            ['key' => 'title', 'label' => '类型'],
            ['key' => 'amount', 'label' => '金额'],
            ['key' => 'voucher_url', 'label' => '付款凭证'],
            ['key' => 'status', 'label' => '状态'],
            ['key' => 'expire_at', 'label' => '支付截止'],
            ['key' => 'voucher_uploaded_at', 'label' => '传凭证时间'],
        ],
        'status' => [
            'field' => 'status',
            'options' => [
                'pending_pay' => '待付款',
                'pending_review' => '待审核',
                'approved' => '已入账',
                'rejected' => '已驳回',
                'expired' => '已超时',
                'cancelled' => '已取消',
            ],
        ],
        'actions' => ['approve' => 'approved', 'reject' => 'rejected'],
        'editable' => ['status', 'admin_remark'],
        'create' => false,
        'default_status' => 'pending_review',
        'hook' => 'fund_pay_approve',
    ],
`

// Extra hard-coded blocks only for vip/fund (structure differs a lot from deploy backup)
const extraFullReplace = {
  'vip-pay-orders': vipPayBlock,
  'fund-pay-orders': fundPayBlock,
}


function restoreBody(key, body) {
  const meta = goodMeta[key]
  const ov = labelOverrides[key] || {}
  let out = body

  if (meta?.label || ov.label) {
    out = out.replace(/'label' => '[^']*'/, `'label' => '${ov.label || meta.label}'`)
  }
  if (meta?.group || ov.group) {
    out = out.replace(/'group' => '[^']*'/, `'group' => '${ov.group || meta.group}'`)
  }
  if (meta?.columns) {
    out = out.replace(/\['key' => '([^']+)', 'label' => '[^']*'\]/g, (full, colKey) => {
      const lab = meta.columns[colKey]
      return lab ? `['key' => '${colKey}', 'label' => '${lab}']` : full
    })
  }
  if (meta?.options && Object.keys(meta.options).length) {
    out = out.replace(/'options' => \[([\s\S]*?)\]/, (full, inner) => {
      const next = inner.replace(/'([^']+)' => '[^']*'/g, (f, optKey) => {
        const lab = meta.options[optKey]
        return lab != null ? `'${optKey}' => '${lab}'` : f
      })
      return `'options' => [${next}]`
    })
  }

  // Common column labels missing from older good file
  const commonCol = {
    is_hot: '热门',
    views: '浏览',
    view_boost: '浏览加权',
    published_at: '发布时间',
    voucher_url: '付款凭证',
    expire_at: '支付截止',
    voucher_uploaded_at: '传凭证时间',
    'user.username': '会员账号',
    grade_name: '档位',
    order_no: '订单号',
    transfer_note: '转账备注',
    capital_display: '注册资本',
    vip_level: 'VIP指数',
    reg_year: '成立年份',
  }
  out = out.replace(/\['key' => '([^']+)', 'label' => '(\?+)'\]/g, (full, colKey, qs) => {
    if (commonCol[colKey]) return `['key' => '${colKey}', 'label' => '${commonCol[colKey]}']`
    return full
  })

  // Remaining status options that are still ???
  const commonStatus = {
    pending: '待审',
    pending_pay: '待付款',
    pending_review: '待审核',
    active: '正常',
    banned: '禁用',
    approved: '通过',
    rejected: '拒绝',
    offline: '下架',
    published: '发布',
    draft: '草稿',
    hidden: '隐藏',
    new: '新',
    replied: '已回复',
    closed: '关闭',
    processing: '处理中',
    done: '完成',
    paid: '已付',
    shipped: '已发货',
    completed: '完成',
    cancelled: '取消',
    refunded: '退款',
    open: '开放',
    expired: '过期',
  }
  out = out.replace(/'([a-z_]+)' => '(\?+)'/g, (full, k, qs) => {
    if (commonStatus[k]) return `'${k}' => '${commonStatus[k]}'`
    return full
  })

  // Remaining simple Chinese labels still as ??
  const simpleLabels = {
    账号: '账号',
    姓名: '姓名',
    公司: '公司',
    类型: '类型',
    状态: '状态',
    标题: '标题',
    价格: '价格',
    库存: '库存',
    地区: '地区',
    银行: '银行',
    金额: '金额',
    用户: '用户',
    手机: '手机',
    联系人: '联系人',
  }
  // Fix leftover ? sequences in label fields using length heuristics is risky; do key-based pass below
  return out
}

// Rebuild file: keep header from good, iterate bad resource order
const badBlocks = extractResourceBlocks(bad)
const order = Object.keys(badBlocks)

let out = `<?php

/**
 * Vue3 运营后台资源注册表（前后端分离）
 * key = URL 段 /admin/r/{key} 与 API /admin/resources/{key}
 */
return [

`

for (const key of order) {
  if (extraFullReplace[key]) {
    // blocks already end with `],` — do not append another comma
    out += extraFullReplace[key].trimEnd() + '\n\n'
    continue
  }
  const restored = restoreBody(key, badBlocks[key])
  out += `    '${key}' => [${restored}\n    ],\n\n`
}

out = out.replace(/,\n\n$/, '\n') + '];\n'
// Guard against empty array elements from accidental `],,`
out = out.replace(/\],\s*,/g, '],')

// Final cleanup: any remaining ASCII-only ? labels for known column keys already handled;
// report leftover question marks in Chinese-ish contexts
const leftover = (out.match(/'[^']*\?+[^']*'/g) || []).slice(0, 40)
fs.writeFileSync(badPath, out, 'utf8')

const written = fs.readFileSync(badPath, 'utf8')
log('restored admin_resources.php', {
  hasCjk: /[\u4e00-\u9fff]/.test(written),
  qMarks: (written.match(/\?/g) || []).length,
  vipLabel: (written.match(/'vip-pay-orders'[\s\S]*?'label' => '([^']*)'/) || [])[1],
  fundLabel: (written.match(/'fund-pay-orders'[\s\S]*?'label' => '([^']*)'/) || [])[1],
  leftoverSample: leftover,
  bytes: Buffer.byteLength(written, 'utf8'),
})

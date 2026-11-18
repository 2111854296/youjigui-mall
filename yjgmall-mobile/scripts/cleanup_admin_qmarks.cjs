const fs = require('fs')
const path = require('path')
const p = path.resolve(__dirname, '../../yjgmall-think/config/admin_resources.php')
const logPath = path.resolve(__dirname, '../../debug-b1a1b9.log')
let s = fs.readFileSync(p, 'utf8')

const colMap = {
  area: '地区',
  vip_level: 'VIP指数',
  'category.name': '分类',
  level: '级别',
  'company.name': '企业',
  title: '标题',
  status: '状态',
  image: '证书图',
  authority: '颁发机构',
  issued_at: '颁发日',
  expires_at: '到期',
  created_at: '提交时间',
  slug: '别名',
  sort: '排序',
  name: '名称',
  url: '链接',
  points_cost: '积分',
  special_id: '专题ID',
  item_type: '类型',
}

for (const [k, v] of Object.entries(colMap)) {
  const esc = k.replace(/\./g, '\\.')
  const re = new RegExp(`\\['key' => '${esc}', 'label' => '[^']*'\\]`, 'g')
  s = s.replace(re, `['key' => '${k}', 'label' => '${v}']`)
}

s = s.replace(/\/\/ \?+/, '// 正式开通已改为对公付款审核，旧申请入口隐藏')

fs.writeFileSync(p, s, 'utf8')
const t = fs.readFileSync(p, 'utf8')
const leftover = t.match(/'[^']*\?+[^']*'/g) || []
const line = JSON.stringify({
  sessionId: 'b1a1b9',
  hypothesisId: 'A',
  location: 'cleanup_admin_qmarks.js',
  message: 'second-pass leftover cleanup',
  data: {
    hasCjk: /[\u4e00-\u9fff]/.test(t),
    qMarks: (t.match(/\?/g) || []).length,
    vipLabel: (t.match(/'vip-pay-orders'[\s\S]*?'label' => '([^']*)'/) || [])[1],
    leftover,
  },
  timestamp: Date.now(),
  runId: 'fix',
})
fs.appendFileSync(logPath, line + '\n')
console.log(line)

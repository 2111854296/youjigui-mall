const fs = require('fs')
const path = require('path')
const p = path.resolve(__dirname, '../../yjgmall-think/config/admin_resources.php')
let s = fs.readFileSync(p, 'utf8')
const map = {
  area: '地区',
  vip_level: 'VIP指数',
  'category.name': '分类',
  level: '等级',
  'company.name': '企业',
  title: '标题',
  status: '状态',
  image: '图片',
  authority: '发证机构',
  issued_at: '发证日期',
  expires_at: '到期日',
  created_at: '创建时间',
  slug: '别名',
  sort: '排序',
  name: '名称',
  url: '链接',
  points_cost: '积分',
  special_id: '专题ID',
  item_type: '类型',
}
s = s.replace(/\['key' => '([^']+)', 'label' => '\?+'\]/g, (full, k) =>
  map[k] ? `['key' => '${k}', 'label' => '${map[k]}']` : full,
)
s = s.replace(/\['key' => 'vip_level', 'label' => 'VIP\?+'\]/g, `['key' => 'vip_level', 'label' => 'VIP指数']`)
s = s.replace(/'menu' => false, \/\/ \?+/, `'menu' => false, // 隐藏菜单`)
s = s.replace(/\],\s*,/g, '],')
fs.writeFileSync(p, s, 'utf8')
console.log('Q', (s.match(/\?/g) || []).length)
console.log('leftover', (s.match(/'[^']*\?+[^']*'/g) || []).slice(0, 20))
console.log('empty', /,\s*,/.test(s))
console.log('CJK', /[\u4e00-\u9fff]/.test(s))

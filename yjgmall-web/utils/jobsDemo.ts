/** 招聘专区演示数据（自客户 HTML 原型，后续可换真实接口） */

export const PAGE_SIZE = 6

export const HOT_CITIES = [
  '全国', '东莞', '深圳', '广州', '上海', '苏州', '宁波', '杭州', '宜昌', '淄博', '佛山', '惠州',
] as const

/** 领域分类（与企业发职位「领域分类」同一套，写入 job_category） */
export const FIELD_CATS = [
  '全部',
  '有机硅单体/中间体',
  '硅油',
  '硅橡胶',
  '有机硅助剂/填料',
  '机械设备',
  '硅烷',
  '胶粘剂',
  '硅树脂',
  '其他',
] as const

/** 职位职能（与企业发职位「职位类别」同一套，写入 job_role；筛选页不可写死无联动） */
export const JOB_ROLE_CATS = [
  '全部',
  '管理',
  '研发',
  '销售',
  '文员',
  '生产',
  '品控',
  '外贸',
  '行政',
  '前台',
  '其他',
] as const

/** 经验要求：筛选芯片与企业表单取值一致 */
export const JOB_EXP_OPTS = [
  ['全部', '不限'],
  ['应届生', '应届生'],
  ['1-3年', '1-3年'],
  ['3-5年', '3-5年'],
  ['5-10年', '5-10年'],
  ['10年以上', '10年以上'],
] as const

export const JOB_EDU_OPTS = ['全部', '高中', '大专', '本科', '硕士', '博士'] as const
export const JOB_NATURE_OPTS = ['全部', '全职', '兼职', '实习'] as const
export const JOB_SIZE_OPTS = [
  ['全部', '不限'],
  ['20-', '20人以下'],
  ['20-99', '20-99人'],
  ['100-499', '100-499人'],
  ['500+', '500人以上'],
] as const
export const JOB_SALARY_OPTS = [
  ['全部', '不限'],
  ['5k', '5K 以下'],
  ['5-8', '5-8K'],
  ['8-12', '8-12K'],
  ['12-20', '12-20K'],
  ['20+', '20K 以上'],
] as const

export type DemoJob = {
  id: number
  t: string
  co: string
  companySlug?: string
  gold: number
  city: string
  salary: string
  sMin: number
  sMax: number
  exp: string
  edu: string
  size: string
  type: string
  /** 职位职能：管理/研发/销售… */
  role: string
  cat: string
  field: string
  tags: string[]
  hot: number
  date: string
  fav: boolean
  peerUserId?: number
}

export type DemoTalent = {
  id: number
  name: string
  av: string
  pos: string
  city: string
  expNum: number
  exp: string
  edu: string
  sMin: number
  sMax: number
  salary: string
  st: string
  stCls: string
  /** 职位职能：管理/研发/销售… */
  role: string
  cat: string
  field: string
  skills: string[]
  date: string
  fav: boolean
  peerUserId?: number
}

export type DemoCompany = { n: string; j: number; g: number; slug?: string }

export const DEMO_JOBS: DemoJob[] = [
  { id: 1, t: '硅橡胶配方研发工程师', co: '华硅新材料科技股份有限公司', gold: 1, city: '东莞', salary: '15-25K', sMin: 15, sMax: 25, exp: '3-5年', edu: '本科', size: '500+', type: '全职', role: '研发', cat: '研发', field: '硅橡胶', tags: ['五险一金', '年终奖', '带薪年假', '股权激励'], hot: 1, date: '2026-10-21', fav: false },
  { id: 2, t: '有机硅外贸业务员（英语）', co: '蓝途有机硅材料有限公司', gold: 1, city: '深圳', salary: '8-15K', sMin: 8, sMax: 15, exp: '1-3年', edu: '大专', size: '100-499', type: '全职', role: '外贸', cat: '外贸', field: '硅油', tags: ['五险一金', '绩效奖金', '双休'], hot: 0, date: '2026-10-21', fav: false },
  { id: 3, t: 'DMC/环体生产操作工', co: '亿硅达新材料集团', gold: 1, city: '宜昌', salary: '6-9K', sMin: 6, sMax: 9, exp: '不限', edu: '高中', size: '500+', type: '全职', role: '生产', cat: '生产', field: '有机硅单体/中间体', tags: ['包吃住', '五险一金', '带薪年假'], hot: 1, date: '2026-10-20', fav: false },
  { id: 4, t: '硅油/硅树脂应用工程师', co: '硅创化学科技有限公司', gold: 0, city: '广州', salary: '12-20K', sMin: 12, sMax: 20, exp: '3-5年', edu: '本科', size: '100-499', type: '全职', role: '研发', cat: '研发', field: '硅油', tags: ['五险一金', '年终奖', '双休'], hot: 0, date: '2026-10-20', fav: false },
  { id: 5, t: '有机硅销售工程师', co: '宏晟硅橡胶制品有限公司', gold: 1, city: '上海', salary: '20-40K', sMin: 20, sMax: 40, exp: '5-10年', edu: '大专', size: '500+', type: '全职', role: '销售', cat: '销售', field: '硅橡胶', tags: ['绩效奖金', '房补餐补', '股权激励'], hot: 0, date: '2026-10-18', fav: false },
  { id: 6, t: '白炭黑研发工程师', co: '科硅新材料有限公司', gold: 0, city: '苏州', salary: '10-18K', sMin: 10, sMax: 18, exp: '1-3年', edu: '硕士', size: '100-499', type: '全职', role: '研发', cat: '研发', field: '有机硅助剂/填料', tags: ['五险一金', '年终奖'], hot: 0, date: '2026-10-18', fav: false },
  { id: 7, t: '硅烷偶联剂合成研究员', co: '华硅新材料科技股份有限公司', gold: 1, city: '宁波', salary: '12-22K', sMin: 12, sMax: 22, exp: '3-5年', edu: '硕士', size: '500+', type: '全职', role: '研发', cat: '研发', field: '硅烷', tags: ['五险一金', '带薪年假', '股权激励'], hot: 0, date: '2026-10-16', fav: false },
  { id: 8, t: '质检员（GC/HPLC/粘度仪）', co: '蓝途有机硅材料有限公司', gold: 0, city: '淄博', salary: '5-8K', sMin: 5, sMax: 8, exp: '1-3年', edu: '大专', size: '20-99', type: '全职', role: '品控', cat: '品控', field: '硅树脂', tags: ['五险一金', '双休'], hot: 0, date: '2026-10-16', fav: false },
  { id: 9, t: '压敏胶（PSA）应用工程师', co: '硅创化学科技有限公司', gold: 0, city: '佛山', salary: '11-19K', sMin: 11, sMax: 19, exp: '3-5年', edu: '本科', size: '100-499', type: '全职', role: '研发', cat: '研发', field: '胶粘剂', tags: ['五险一金', '年终奖', '绩效奖金'], hot: 1, date: '2026-10-14', fav: false },
  { id: 10, t: '有机硅项目经理', co: '亿硅达新材料集团', gold: 1, city: '深圳', salary: '25-45K', sMin: 25, sMax: 45, exp: '10年以上', edu: '本科', size: '500+', type: '全职', role: '管理', cat: '管理', field: '有机硅单体/中间体', tags: ['股权激励', '绩效奖金', '带薪年假'], hot: 0, date: '2026-10-14', fav: false },
  { id: 11, t: '硅胶制品销售代表', co: '宏晟硅橡胶制品有限公司', gold: 0, city: '东莞', salary: '6-12K', sMin: 6, sMax: 12, exp: '不限', edu: '高中', size: '100-499', type: '兼职', role: '销售', cat: '销售', field: '硅橡胶', tags: ['绩效奖金', '弹性工作'], hot: 0, date: '2026-10-13', fav: false },
  { id: 12, t: '有机硅外贸跟单实习生', co: '蓝途有机硅材料有限公司', gold: 0, city: '广州', salary: '3-5K', sMin: 3, sMax: 5, exp: '应届', edu: '大专', size: '100-499', type: '实习', role: '外贸', cat: '外贸', field: '硅油', tags: ['双休', '餐补'], hot: 0, date: '2026-10-07', fav: false },
  { id: 13, t: '硅胶挤出设备工程师', co: '亿硅达新材料集团', gold: 0, city: '东莞', salary: '9-16K', sMin: 9, sMax: 16, exp: '3-5年', edu: '大专', size: '500+', type: '全职', role: '生产', cat: '生产', field: '机械设备', tags: ['五险一金', '包吃住', '绩效奖金'], hot: 0, date: '2026-10-15', fav: false },
]

export const DEMO_COMPANIES: DemoCompany[] = [
  { n: '华硅新材料科技股份有限公司', j: 6, g: 1 },
  { n: '亿硅达新材料集团', j: 5, g: 1 },
  { n: '蓝途有机硅材料有限公司', j: 4, g: 1 },
  { n: '宏晟硅橡胶制品有限公司', j: 3, g: 1 },
  { n: '硅创化学科技有限公司', j: 4, g: 0 },
]

export const DEMO_TALENTS: DemoTalent[] = [
  { id: 1, name: '张先生', av: '张', pos: '硅橡胶配方研发工程师', city: '东莞', expNum: 12, exp: '12年经验', edu: '本科', sMin: 20, sMax: 30, salary: '20-30K', st: '离职-随时到岗', stCls: 'st-green', role: '研发', cat: '研发', field: '硅橡胶', skills: ['HTV/LSR', '双螺杆挤出', '铂金硫化', 'IATF16949'], date: '2026-10-21', fav: false },
  { id: 2, name: '李女士', av: '李', pos: '有机硅外贸业务员', city: '深圳', expNum: 6, exp: '6年经验', edu: '本科', sMin: 15, sMax: 20, salary: '15-20K', st: '在职-看机会', stCls: 'st-blue', role: '外贸', cat: '外贸', field: '硅油', skills: ['英语专八', '海外大客户', '出口跟单'], date: '2026-10-21', fav: false },
  { id: 3, name: '王先生', av: '王', pos: '硅烷偶联剂合成研究员', city: '宁波', expNum: 8, exp: '8年经验', edu: '硕士', sMin: 18, sMax: 25, salary: '18-25K', st: '离职-随时到岗', stCls: 'st-green', role: '研发', cat: '研发', field: '硅烷', skills: ['KH-550/570', '合成工艺', '中试放大'], date: '2026-10-20', fav: false },
  { id: 4, name: '陈先生', av: '陈', pos: '硅油应用工程师', city: '广州', expNum: 4, exp: '4年经验', edu: '大专', sMin: 12, sMax: 18, salary: '12-18K', st: '在职-看机会', stCls: 'st-blue', cat: '硅油应用', field: '硅油', skills: ['纺织硅油', '消泡剂', '配方复配'], date: '2026-10-20', fav: false },
  { id: 5, name: '赵女士', av: '赵', pos: '白炭黑检测工程师', city: '苏州', expNum: 3, exp: '3年经验', edu: '本科', sMin: 10, sMax: 15, salary: '10-15K', st: '离职-随时到岗', stCls: 'st-green', role: '品控', cat: '品控', field: '有机硅助剂/填料', skills: ['BET比表面积', '吸油值', '气相法'], date: '2026-10-18', fav: false },
  { id: 6, name: '孙先生', av: '孙', pos: '有机硅销售总监', city: '上海', expNum: 15, exp: '15年经验', edu: '大专', sMin: 30, sMax: 40, salary: '30-40K', st: '在职-看机会', stCls: 'st-blue', role: '销售', cat: '销售', field: '有机硅单体/中间体', skills: ['团队管理', '大客户', '全产业链'], date: '2026-10-18', fav: false },
  { id: 7, name: '周先生', av: '周', pos: 'DMC 生产班长', city: '宜昌', expNum: 8, exp: '8年经验', edu: '高中', sMin: 8, sMax: 12, salary: '8-12K', st: '离职-随时到岗', stCls: 'st-green', cat: '生产操作', field: '有机硅单体/中间体', skills: ['DCS操作', '精馏', '安全生产'], date: '2026-10-16', fav: false },
  { id: 8, name: '吴女士', av: '吴', pos: '压敏胶应用工程师', city: '佛山', expNum: 5, exp: '5年经验', edu: '本科', sMin: 14, sMax: 20, salary: '14-20K', st: '在职-看机会', stCls: 'st-blue', role: '研发', cat: '研发', field: '胶粘剂', skills: ['PSA', '离型纸', '涂布工艺'], date: '2026-10-16', fav: false },
  { id: 9, name: '郑先生', av: '郑', pos: '质检主管', city: '淄博', expNum: 10, exp: '10年经验', edu: '大专', sMin: 10, sMax: 15, salary: '10-15K', st: '离职-随时到岗', stCls: 'st-green', role: '品控', cat: '品控', field: '硅树脂', skills: ['GC/HPLC', 'ISO体系', '质量管理'], date: '2026-10-14', fav: false },
  { id: 10, name: '冯女士', av: '冯', pos: '有机硅外贸跟单', city: '宁波', expNum: 3, exp: '3年经验', edu: '本科', sMin: 8, sMax: 12, salary: '8-12K', st: '离职-随时到岗', stCls: 'st-green', role: '外贸', cat: '外贸', field: '硅树脂', skills: ['英语六级', '国际站', '单证报关'], date: '2026-10-14', fav: false },
  { id: 11, name: '何先生', av: '何', pos: '硅树脂研发工程师', city: '广州', expNum: 6, exp: '6年经验', edu: '硕士', sMin: 18, sMax: 28, salary: '18-28K', st: '在职-看机会', stCls: 'st-blue', role: '研发', cat: '研发', field: '硅树脂', skills: ['MQ硅树脂', '有机硅涂料', '固化体系'], date: '2026-10-13', fav: false },
  { id: 12, name: '罗女士', av: '罗', pos: '销售内勤/跟单', city: '东莞', expNum: 0, exp: '应届生', edu: '大专', sMin: 5, sMax: 8, salary: '5-8K', st: '应届生', stCls: 'st-orange', role: '销售', cat: '销售', field: '硅橡胶', skills: ['ERP', '订单管理', '客户维护'], date: '2026-10-07', fav: false },
  { id: 13, name: '蒋先生', av: '蒋', pos: '硅胶挤出设备工程师', city: '东莞', expNum: 9, exp: '9年经验', edu: '大专', sMin: 12, sMax: 18, salary: '12-18K', st: '离职-随时到岗', stCls: 'st-green', cat: '生产操作', field: '机械设备', skills: ['螺杆挤出', '模具设计', '设备维保'], date: '2026-10-17', fav: false },
]

export function salaryMatch(j: { sMin: number; sMax: number }, v: string) {
  if (v === '全部') return true
  if (v === '5k') return j.sMax < 5
  if (v === '5-8') return j.sMin >= 5 && j.sMax <= 8
  if (v === '8-12') return j.sMin >= 8 && j.sMax <= 12
  if (v === '12-20') return j.sMin >= 12 && j.sMax <= 20
  if (v === '20+') return j.sMin >= 20
  return true
}

export function sizeMatch(j: { size: string }, v: string) {
  if (v === '全部') return true
  if (v === '20-') return j.size === '20-99' || j.size === '20人以下'
  return j.size === v
}

export function talSalaryMatch(t: { sMin: number; sMax: number }, v: string) {
  return salaryMatch(t, v)
}

export function talExpMatch(t: { expNum: number }, v: string) {
  if (v === '全部') return true
  if (v === '应届') return t.expNum <= 1
  if (v === '1-3') return t.expNum >= 1 && t.expNum <= 3
  if (v === '3-5') return t.expNum >= 3 && t.expNum <= 5
  if (v === '5-10') return t.expNum >= 5 && t.expNum <= 10
  if (v === '10+') return t.expNum >= 10
  return true
}

export function timeAgo(ds: string) {
  const d = new Date(`${ds}T00:00:00`)
  const now = new Date()
  now.setHours(0, 0, 0, 0)
  const days = Math.floor((+now - +d) / 86400000)
  if (days <= 0) return '今天'
  if (days === 1) return '昨天'
  if (days < 7) return `${days}天前`
  if (days < 30) return `${Math.floor(days / 7)}周前`
  if (days < 365) return `${Math.floor(days / 30)}个月前`
  return `${Math.floor(days / 365)}年前`
}

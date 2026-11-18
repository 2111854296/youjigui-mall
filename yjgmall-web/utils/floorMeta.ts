import { FLOOR_GROUPS } from './floorGroups'

/** 首页材料楼层左侧渐变（频道楼层共用） */
export const FLOOR_SIDE_GRAD: Record<string, string> = {
  '有机硅单体/中间体': 'var(--brand-grad)',
  '硅油': 'linear-gradient(160deg,#F59E0B,#F97316)',
  '硅橡胶': 'linear-gradient(160deg,#10B981,#34D399)',
  '有机硅助剂/填料': 'linear-gradient(160deg,#8B5CF6,#A78BFA)',
  '机械设备': 'linear-gradient(160deg,#06B6D4,#3B82F6)',
  '硅烷': 'linear-gradient(160deg,#F04B3E,#FB7185)',
  '胶粘剂': 'linear-gradient(160deg,#2952CC,#4A7BF7)',
  '硅树脂': 'linear-gradient(160deg,#DB2777,#F472B6)',
}

export function floorSideGrad(floor: any) {
  const name = String(floor?.category?.name || '')
  return FLOOR_SIDE_GRAD[name] || 'var(--brand-grad)'
}

/** 楼层左侧多行卖点文案（对齐线上橙字层次） */
export const FLOOR_SLOGANS: Record<string, string[]> = {
  '有机硅单体/中间体': ['DMC / D4 中间体现货', '甲基单体 · 苯基单体', '稳定货源 吨级起订'],
  '硅油': ['甲基硅油 · 107胶', '含氢 / 改性硅油', '羟基硅油 粘度可选'],
  '硅橡胶': ['混炼胶 / 液体硅橡胶', '模具硅胶 · LSR', '耐高温 · 高抗撕'],
  '有机硅助剂/填料': ['铂金催化剂 · 白炭黑', '色母 · 抑制剂', '封头剂现货供应'],
  '机械设备': ['真空捏合机 / 反应釜', '行星分散 · 挤出机', '实验到量产设备'],
  '硅烷': ['偶联剂 / 交联剂', 'KH系列齐全', '电子级可选'],
  '胶粘剂': ['密封胶 · 灌封胶', '玻璃胶 · 电子胶', '中性耐候系列'],
  '硅树脂': ['MQ硅树脂系列', '甲基苯基硅树脂', '透明树脂现货'],
}

/** 品牌行中间主推大字 */
export const FLOOR_HIGHLIGHT: Record<string, string> = {
  '有机硅单体/中间体': 'DMC中间体',
  '硅油': '107硅油',
  '硅橡胶': '液体硅橡胶',
  '有机硅助剂/填料': '铂金催化剂',
  '机械设备': '真空捏合机',
  '硅烷': '硅烷偶联剂',
  '胶粘剂': '硅酮密封胶',
  '硅树脂': 'MQ硅树脂',
}

/** 楼层左栏原站大图（禁止本地假图顶替） */
export const FLOOR_LEFT_IMG: Record<string, string> = {
  '有机硅单体/中间体': '/file/upload/201707/18/165732731.jpg',
  '硅油': '/file/upload/201707/28/175710281.jpg',
  '硅橡胶': '/file/upload/201707/28/175757871.jpg',
  '有机硅助剂/填料': '/file/upload/201707/28/175340481.jpg',
  '机械设备': '/file/upload/201707/28/175650671.jpg',
  '硅烷': '/file/upload/201707/28/175508761.jpg',
  '胶粘剂': '/file/upload/201707/28/175218661.jpg',
  '硅树脂': '/file/upload/201707/28/175437121.jpg',
}

export function floorLeftImage(floor: any, fallback = '/img/logo-yjg.png'): string {
  const name = floor?.category?.name || ''
  const api = String(floor?.image || '').trim()
  if (api && !/\.svg($|\?)/i.test(api) && !/placehold/i.test(api)) return api
  if (FLOOR_LEFT_IMG[name]) return FLOOR_LEFT_IMG[name]
  return fallback
}

/** 企业短名展示（去掉后缀/括号，避免「橙天新材料（」半截名） */
export function companyShortName(c: { name?: string; area?: string } | null): string {
  const raw = String(c?.name || '').trim()
  if (!raw) return ''
  const map: [RegExp, string][] = [
    [/强力化工/, '黄山强力'],
    [/德鑫/, '东莞德鑫'],
    [/东晟/, '东莞东晟'],
    [/华丰/, '无锡华丰'],
    [/百纯/, '上海百纯'],
    [/豪达/, '太仓豪达'],
    [/荣兴/, '如皋荣兴'],
    [/莱宝/, '广州莱宝'],
    [/乐迪雅/, '江西乐迪雅'],
    [/硅众/, '东莞硅众'],
    [/睿恪/, '广州睿恪'],
    [/合创/, '嘉兴合创'],
    [/凯华/, '淄博凯华'],
    [/子轩/, '东莞子轩'],
    [/橙天新材料/, '橙天新材料'],
    [/正飞包/, '东莞正飞包装'],
    [/飞力橡塑/, '广东飞力橡塑'],
  ]
  for (const [re, short] of map) {
    if (re.test(raw)) return short
  }
  let name = raw
    .replace(/有限责任公司|股份有限公司|有限公司|集团有限公司/g, '')
    .replace(/[（(][^）)]*[）)]/g, '')
    .replace(/[（(][^）)]*$/g, '')
    .replace(/(厂|店)$/g, '')
    .trim()
  if (!name) name = raw
  if (name.length <= 12) return name
  return `${name.slice(0, 11)}…`
}

export function catFlyoutGroups(catName: string) {
  return FLOOR_GROUPS[catName] || []
}

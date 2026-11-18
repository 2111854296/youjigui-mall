/** 首页左侧父级分类图标。key 存在分类的 icon 字段里。 */
export const CATEGORY_ICONS: Record<string, string> = {
  molecule: '<path d="M12 3.2l7.2 4.15v8.3L12 19.8 4.8 15.65v-8.3L12 3.2z"/><circle cx="12" cy="12" r="2.2"/>',
  droplet: '<path d="M12 3c-3.8 5.2-6 8.6-6 11.2a6 6 0 0012 0C18 11.6 15.8 8.2 12 3z"/>',
  layers: '<path d="M12 4.5l8 3.8-8 3.8-8-3.8 8-3.8z"/><path d="M4 13.2l8 3.8 8-3.8"/><path d="M4 17l8 3.8 8-3.8"/>',
  particles: '<circle cx="8" cy="8" r="2.4"/><circle cx="16.5" cy="7.5" r="1.8"/><circle cx="12" cy="14.5" r="3"/><circle cx="18" cy="15.5" r="1.4"/>',
  spark: '<path d="M12 3v3.2M12 17.8V21M3 12h3.2M17.8 12H21"/><path d="M6.2 6.2l2.2 2.2M15.6 15.6l2.2 2.2M17.8 6.2l-2.2 2.2M8.4 15.6l-2.2 2.2"/><circle cx="12" cy="12" r="2.6"/>',
  chain: '<path d="M10 13a5 5 0 007.4.4l1.8-1.8a5 5 0 00-7.1-7.1L10.8 5.8"/><path d="M14 11a5 5 0 00-7.4-.4L4.8 12.4a5 5 0 007.1 7.1l1.3-1.3"/>',
  tube: '<path d="M9 3h6v3.5l2 3V19a2 2 0 01-2 2H9a2 2 0 01-2-2v-9.5l2-3V3z"/><path d="M9 6.5h6"/>',
  gear: '<circle cx="12" cy="12" r="3"/><path d="M12 3.5v2.2M12 18.3v2.2M3.5 12h2.2M18.3 12h2.2M6 6l1.6 1.6M16.4 16.4L18 18M18 6l-1.6 1.6M7.6 16.4L6 18"/>',
  hex: '<path d="M12 3l7.5 4.3v8.6L12 20.2 4.5 15.9V7.3L12 3z"/>',
  box: '<path d="M3.5 8.5L12 4l8.5 4.5v9L12 22l-8.5-4.5v-9z"/><path d="M3.5 8.5L12 13l8.5-4.5M12 13v9"/>',
}

export const CATEGORY_ICON_OPTIONS = [
  { key: 'molecule', label: '分子' },
  { key: 'droplet', label: '液滴' },
  { key: 'layers', label: '层叠' },
  { key: 'particles', label: '颗粒' },
  { key: 'spark', label: '星芒' },
  { key: 'chain', label: '链条' },
  { key: 'tube', label: '试管' },
  { key: 'gear', label: '齿轮' },
  { key: 'hex', label: '六边形' },
  { key: 'box', label: '箱体' },
]

const FALLBACK = [
  CATEGORY_ICONS.molecule, CATEGORY_ICONS.droplet, CATEGORY_ICONS.layers, CATEGORY_ICONS.particles,
  CATEGORY_ICONS.spark, CATEGORY_ICONS.chain, CATEGORY_ICONS.tube, CATEGORY_ICONS.box,
]

export function categoryIconSvg(icon: string | undefined, name: string, index = 0) {
  const key = String(icon || '')
  if (key && CATEGORY_ICONS[key]) return CATEGORY_ICONS[key]
  const n = String(name || '')
  if (/单体|中间体|DMC|D4|环硅/.test(n)) return CATEGORY_ICONS.molecule
  if (/硅油|二甲基硅油|油类/.test(n)) return CATEGORY_ICONS.droplet
  if (/橡胶|混炼|生胶|107/.test(n)) return CATEGORY_ICONS.layers
  if (/白炭黑|填料|粉体|气相|沉淀/.test(n)) return CATEGORY_ICONS.particles
  if (/助剂|添加剂/.test(n)) return CATEGORY_ICONS.spark
  if (/硅烷/.test(n)) return CATEGORY_ICONS.chain
  if (/胶粘|密封胶|玻璃胶|灌封/.test(n)) return CATEGORY_ICONS.tube
  if (/机械|设备|模具/.test(n)) return CATEGORY_ICONS.gear
  if (/树脂/.test(n)) return CATEGORY_ICONS.hex
  return FALLBACK[index % FALLBACK.length]
}

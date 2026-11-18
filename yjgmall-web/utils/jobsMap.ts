import type { DemoJob, DemoTalent } from '~/utils/jobsDemo'
import { FIELD_CATS } from '~/utils/jobsDemo'

function parseSalary(raw: string | null | undefined): { text: string; sMin: number; sMax: number } {
  const s = String(raw || '').trim()
  if (!s) return { text: '面议', sMin: 0, sMax: 0 }
  const m = s.match(/(\d+(?:\.\d+)?)\s*[-~～到至]\s*(\d+(?:\.\d+)?)/)
  if (m) {
    let a = Number(m[1])
    let b = Number(m[2])
    if (/万/.test(s)) {
      a *= 10
      b *= 10
    }
    return { text: `${a}-${b}K`, sMin: a, sMax: b }
  }
  const one = s.match(/(\d+(?:\.\d+)?)/)
  if (one) {
    let n = Number(one[1])
    if (/万/.test(s)) n *= 10
    return { text: `${n}K`, sMin: n, sMax: n }
  }
  return { text: s, sMin: 0, sMax: 0 }
}

function guessField(text: string): string {
  for (const cat of FIELD_CATS) {
    if (cat === '全部') continue
    if (text.includes(cat.replace(/\/.*/, '')) || text.includes(cat)) return cat
  }
  const map: [string, string][] = [
    ['硅橡胶', '硅橡胶'],
    ['硅油', '硅油'],
    ['硅烷', '硅烷'],
    ['DMC', '有机硅单体/中间体'],
    ['单体', '有机硅单体/中间体'],
    ['白炭黑', '有机硅助剂/填料'],
    ['助剂', '有机硅助剂/填料'],
    ['胶粘', '胶粘剂'],
    ['硅树脂', '硅树脂'],
    ['挤出', '机械设备'],
    ['设备', '机械设备'],
  ]
  for (const [k, v] of map) {
    if (text.includes(k)) return v
  }
  return '其他'
}

function cityFromArea(area: string): string {
  const a = String(area || '')
  const cities = ['东莞', '深圳', '广州', '上海', '苏州', '宁波', '杭州', '宜昌', '淄博', '佛山', '惠州']
  for (const c of cities) {
    if (a.includes(c)) return c
  }
  return a.slice(0, 8) || '全国'
}

function dateStr(v: any): string {
  const s = String(v || '')
  if (!s) return ''
  return s.slice(0, 10)
}

export function mapApiJob(row: any): DemoJob {
  const title = String(row.title || '')
  const company = String(row.company_name || '企业')
  const sal = parseSalary(row.salary)
  const sMin = Number(row.salary_min) > 0 ? Number(row.salary_min) : sal.sMin
  const sMax = Number(row.salary_max) > 0 ? Number(row.salary_max) : sal.sMax
  const salaryText =
    sMin || sMax
      ? sMin && sMax && sMin !== sMax
        ? `${sMin}-${sMax}K`
        : `${sMin || sMax}K`
      : sal.text
  const blob = `${title} ${row.summary || ''} ${row.content || ''}`
  const field = String(row.job_category || '').trim() || guessField(blob)
  const role = String(row.job_role || '').trim()
  const tags = String(row.summary || '')
    .split(/[,，、\s]+/)
    .map((t) => t.trim())
    .filter((t) => t.length >= 2 && t.length <= 8)
    .slice(0, 4)
  const slug = String(row.company_slug || row.company_id || '').trim()
  return {
    id: Number(row.id),
    t: title,
    co: company,
    companySlug: slug || undefined,
    gold: Number(row.is_sticky || row.is_sticky_active) ? 1 : 0,
    city: cityFromArea(row.area),
    salary: salaryText,
    sMin,
    sMax,
    exp: String(row.experience || '不限'),
    edu: String(row.education || '不限'),
    size: String(row.company_size || '—'),
    type: String(row.job_nature || '全职'),
    role,
    cat: role || field,
    field,
    tags: tags.length ? tags : ['五险一金'],
    hot: Number(row.is_sticky || row.is_sticky_active) ? 1 : 0,
    date: dateStr(row.created_at || row.updated_at),
    fav: false,
    peerUserId: Number(row.user_id || row.peer_user_id || 0) || undefined,
  }
}

export function mapApiTalent(row: any): DemoTalent {
  const name = String(row.truename || '求职者')
  const pos = String(row.title || '求职意向')
  const min = Number(row.minsalary || 0)
  const max = Number(row.maxsalary || 0)
  let sMin = min
  let sMax = max
  if (sMin > 1000) sMin = Math.round(sMin / 1000)
  if (sMax > 1000) sMax = Math.round(sMax / 1000)
  const salary = sMin || sMax ? `${sMin || sMax}-${sMax || sMin}K` : '面议'
  const expRaw = String(row.experience || '')
  const expNum = Number((expRaw.match(/(\d+)/) || [])[1] || 0)
  const field = String(row.job_category || '').trim() || guessField(`${pos} ${row.summary || ''}`)
  const role = String(row.job_role || '').trim()
  const skills = String(row.summary || '')
    .split(/[,，、\s]+/)
    .map((t) => t.trim())
    .filter((t) => t.length >= 2 && t.length <= 12)
    .slice(0, 6)
  return {
    id: Number(row.id),
    name,
    av: name.slice(0, 1) || '求',
    pos,
    city: cityFromArea(row.area),
    expNum,
    exp: expRaw || (expNum ? `${expNum}年经验` : '经验不限'),
    edu: String(row.education || '不限'),
    sMin,
    sMax,
    salary,
    st: '求职中',
    stCls: 'st-blue',
    role,
    cat: role || field,
    field,
    skills: skills.length ? skills : [field],
    date: dateStr(row.updated_at || row.created_at),
    fav: false,
    peerUserId: Number(row.peer_user_id || row.user_id || 0) || undefined,
  }
}

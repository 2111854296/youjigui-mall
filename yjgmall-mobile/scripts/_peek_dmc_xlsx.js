const XLSX = require('xlsx')
const path =
  'c:\\Users\\Administrator\\xwechat_files\\wxid_cxod157ic1kd22_434b\\msg\\file\\2026-11\\2026年DMC报价(1).xlsx'
const wb = XLSX.readFile(path)
console.log('sheets', wb.SheetNames)
for (const name of wb.SheetNames) {
  const ws = wb.Sheets[name]
  const rows = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '', raw: false })
  console.log('===', name, 'rows', rows.length)
  const show = (idx) => {
    const r = rows[idx] || []
    const vals = r.slice(0, 18).map((v) => String(v).slice(0, 36))
    if (vals.some((v) => v !== '')) console.log(String(idx + 1).padStart(3), vals.join(' | '))
  }
  for (let i = 0; i < Math.min(60, rows.length); i++) show(i)
  console.log('--- more ---')
  ;[70, 90, 110, 130, 150, 180, 210, 240, 270, 300, 330, 360].forEach((n) => {
    if (n < rows.length) show(n)
  })
  console.log('--- last ---')
  for (let i = Math.max(0, rows.length - 8); i < rows.length; i++) show(i)
  // detect month headers / sheet titles
  const monthish = []
  rows.forEach((r, i) => {
    const a = String(r[0] || '')
    if (/月|年|DMC|报价|日期/.test(a)) monthish.push([i + 1, a.slice(0, 60), ...(r.slice(1, 6).map((x) => String(x).slice(0, 20)))])
  })
  console.log('monthish markers', monthish.length)
  monthish.slice(0, 40).forEach((m) => console.log(JSON.stringify(m)))
}

const fs = require('fs')
const path = require('path')
const { execSync } = require('child_process')
const xlsx =
  'c:\\Users\\Administrator\\xwechat_files\\wxid_cxod157ic1kd22_434b\\msg\\file\\2026-11\\2026年DMC报价(1).xlsx'
const tmp = path.join(__dirname, '_dmc_xlsx_unzip')
fs.rmSync(tmp, { recursive: true, force: true })
fs.mkdirSync(tmp)
// use PowerShell Expand-Archive doesn't support xlsx; use node adm or unzip via tar
try {
  execSync(`tar -xf "${xlsx}" -C "${tmp}"`, { stdio: 'pipe' })
} catch (e) {
  // fallback copy as zip
  const zip = path.join(__dirname, '_dmc.zip')
  fs.copyFileSync(xlsx, zip)
  execSync(`tar -xf "${zip}" -C "${tmp}"`, { stdio: 'inherit' })
}
const sheet = fs.readFileSync(path.join(tmp, 'xl/worksheets/sheet1.xml'), 'utf8')
const rows = [...sheet.matchAll(/<row[^>]*r="(\d+)"[\s\S]*?<\/row>/g)].slice(0, 8)
for (const m of rows) {
  const cells = [...m[0].matchAll(/<c r="([A-Z]+)(\d+)"([^>]*)>(?:<v>([^<]*)<\/v>)?/g)]
  console.log(
    'row',
    m[1],
    cells
      .slice(0, 3)
      .map((c) => `${c[1]}${c[2]} t=${(c[3].match(/t="([^"]+)"/) || [])[1] || 'n'} v=${c[4] || ''}`)
      .join(' | '),
  )
}
// shared string sample for first cells
const ss = fs.readFileSync(path.join(tmp, 'xl/sharedStrings.xml'), 'utf8')
const texts = [...ss.matchAll(/<t[^>]*>([^<]*)<\/t>/g)].map((x) => x[1]).slice(0, 20)
console.log('sharedStrings head', texts)

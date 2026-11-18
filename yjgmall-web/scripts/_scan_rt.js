const fs = require('fs')
const path = require('path')
const dir = path.join(__dirname, '../.output/public/_nuxt')
const files = ['CTGmRJvc.js', 'C9ADQpVM.js']
for (const f of files) {
  const s = fs.readFileSync(path.join(dir, f), 'utf8')
  console.log('\n====', f, 'createStatic', s.includes('createStaticVNode') || s.includes('_createStaticVNode'))
  let i = 0
  let c = 0
  while ((i = s.indexOf('<svg', i)) !== -1 && c < 8) {
    console.log(s.slice(i, i + 240))
    console.log('---')
    i += 4
    c++
  }
  console.log('svg hits', c)
}

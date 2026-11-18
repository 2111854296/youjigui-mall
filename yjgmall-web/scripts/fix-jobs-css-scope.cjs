/**
 * Fix/scope jobs-channel.css under .jobs-ch so it cannot pollute homepage.
 * Safe to re-run.
 */
const fs = require('fs')
const path = require('path')
const file = path.resolve(__dirname, '../assets/css/jobs-channel.css')
let css = fs.readFileSync(file, 'utf8')

// Undo broken comment prefixes from previous bad transform
css = css.replace(/\.jobs-ch\s*\/\*/g, '/*')
css = css.replace(/\.jobs-ch\s*$/gm, '')

// Drop global reduced-motion nuke (was killing whole site)
css = css.replace(
  /@media\s*\(prefers-reduced-motion:reduce\)\s*\{\s*\*\{[^}]*\}\s*\}/g,
  '@media (prefers-reduced-motion:reduce){.jobs-ch,.jobs-ch *{animation:none!important;transition:none!important}}',
)

function prefixSel(sel) {
  return sel
    .split(',')
    .map((s) => {
      s = s.trim()
      if (!s) return s
      if (s.startsWith('.jobs-ch')) return s
      if (s.startsWith('@')) return s
      if (s === 'from' || s === 'to' || /^\d+%/.test(s)) return s
      return `.jobs-ch ${s}`
    })
    .join(', ')
}

function processBlock(src, inMedia = false) {
  let out = ''
  let i = 0
  while (i < src.length) {
    if (src.startsWith('/*', i)) {
      const end = src.indexOf('*/', i)
      if (end < 0) {
        out += src.slice(i)
        break
      }
      out += src.slice(i, end + 2)
      i = end + 2
      continue
    }
    if (/\s/.test(src[i]) && src.indexOf('{', i) < 0) {
      out += src[i]
      i++
      continue
    }
    // @media / @keyframes
    if (src.startsWith('@', i)) {
      const brace = src.indexOf('{', i)
      if (brace < 0) {
        out += src.slice(i)
        break
      }
      const header = src.slice(i, brace)
      let depth = 0
      let j = brace
      for (; j < src.length; j++) {
        if (src[j] === '{') depth++
        else if (src[j] === '}') {
          depth--
          if (depth === 0) {
            j++
            break
          }
        }
      }
      const inner = src.slice(brace + 1, j - 1)
      if (header.trim().startsWith('@keyframes')) {
        out += header + '{' + inner + '}'
      } else if (header.trim().startsWith('@media')) {
        out += header + '{' + processBlock(inner, true) + '}'
      } else {
        out += header + '{' + inner + '}'
      }
      i = j
      continue
    }
    const brace = src.indexOf('{', i)
    if (brace < 0) {
      out += src.slice(i)
      break
    }
    const rawSel = src.slice(i, brace)
    const lead = rawSel.match(/^\s*/)?.[0] || ''
    const sel = rawSel.trim()
    let depth = 0
    let j = brace
    for (; j < src.length; j++) {
      if (src[j] === '{') depth++
      else if (src[j] === '}') {
        depth--
        if (depth === 0) {
          j++
          break
        }
      }
    }
    const body = src.slice(brace + 1, j - 1)
    if (!sel) {
      out += src.slice(i, j)
    } else {
      out += lead + prefixSel(sel) + '{' + body + '}'
    }
    i = j
  }
  return out
}

css = processBlock(css)
// pretty: ensure comment newlines
css = css.replace(/\*\//g, '*/\n')
css = css.replace(/\}\.jobs-ch/g, '}\n.jobs-ch')
css = css.replace(/\}@/g, '}\n@')

fs.writeFileSync(file, css)
console.log('scoped OK', file, 'bytes', css.length)

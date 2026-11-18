const fs = require('fs')
const path = require('path')
const https = require('https')

const logPath = path.resolve(__dirname, '../../debug-b1a1b9.log')
function log(hypothesisId, location, message, data) {
  const line = JSON.stringify({
    sessionId: 'b1a1b9',
    hypothesisId,
    location,
    message,
    data,
    timestamp: Date.now(),
    runId: 'probe3-api',
  })
  fs.appendFileSync(logPath, line + '\n')
  console.log(line)
}

function get(urlPath) {
  return new Promise((resolve, reject) => {
    https
      .get(
        {
          hostname: 'g.aifeili.cn',
          path: urlPath,
          headers: { Accept: 'application/json' },
          timeout: 15000,
        },
        (res) => {
          let body = ''
          res.setEncoding('utf8')
          res.on('data', (c) => (body += c))
          res.on('end', () => resolve({ status: res.statusCode, body: body.slice(0, 800) }))
        },
      )
      .on('error', reject)
  })
}

;(async () => {
  try {
    const menu = await get('/api/v1/admin/menu')
    log('C', 'api:menu', 'public menu response (expect 401)', {
      status: menu.status,
      body: menu.body,
      hasQ: menu.body.includes('?'),
      hasCjk: /[\u4e00-\u9fff]/.test(menu.body),
    })
  } catch (e) {
    log('C', 'api:menu', 'failed', { e: String(e) })
  }
})()

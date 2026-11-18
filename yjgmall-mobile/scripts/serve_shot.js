const http = require('http')
const fs = require('fs')
const path = require('path')
const root = 'C:\\Users\\Administrator\\.cursor\\projects\\c-Users-Administrator-Desktop\\assets'
const file = path.join(root, 'user-shot.png')
http
  .createServer((req, res) => {
    if (!fs.existsSync(file)) {
      res.writeHead(404)
      return res.end('missing')
    }
    res.writeHead(200, { 'Content-Type': 'image/png' })
    fs.createReadStream(file).pipe(res)
  })
  .listen(8790, '127.0.0.1', () => console.log('ok http://127.0.0.1:8790/'))

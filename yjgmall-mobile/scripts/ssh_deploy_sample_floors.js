/**
 * Deploy sample-zone floor ad slots.
 * Usage: node scripts/ssh_deploy_sample_floors.js
 */
const fs = require('fs')
const path = require('path')
const { Client } = require('ssh2')

const sibling = fs.readFileSync(path.join(__dirname, 'ssh_deploy_floor_slots.js'), 'utf8')
const fallback = (sibling.match(/YJG_SSH_PASS \|\| '([^']*)'/) || [])[1] || ''
const password = process.env.YJG_SSH_PASS || fallback
const thinkRoot = path.resolve(__dirname, '../../yjgmall-think')
const remoteRoot = '/www/wwwroot/g.aifeili.cn'
const php = '/www/server/php/83/bin/php'

const files = [
  'app/controller/api/v1/Mall.php',
  'scripts/upgrade_sample_floors.php',
]

function upload(sftp, local, remote) {
  return new Promise((resolve, reject) => {
    sftp.fastPut(local, remote, (err) => (err ? reject(err) : resolve()))
  })
}
function exec(conn, cmd) {
  return new Promise((resolve, reject) => {
    conn.exec(cmd, (err, stream) => {
      if (err) return reject(err)
      let out = ''
      stream.on('data', (d) => { out += d; process.stdout.write(d) })
      stream.stderr.on('data', (d) => { out += d; process.stderr.write(d) })
      stream.on('close', (code) => resolve({ code: code || 0, out }))
    })
  })
}

const conn = new Client()
conn
  .on('ready', async () => {
    try {
      const sftp = await new Promise((resolve, reject) =>
        conn.sftp((e, s) => (e ? reject(e) : resolve(s))),
      )
      for (const rem of files) {
        const local = path.join(thinkRoot, rem)
        if (!fs.existsSync(local)) throw new Error('missing ' + local)
        const remote = `${remoteRoot}/${rem}`
        await exec(conn, `mkdir -p ${remote.substring(0, remote.lastIndexOf('/'))}`)
        console.log('upload', rem)
        await upload(sftp, local, remote)
      }
      await exec(conn, `chown www:www ${files.map((f) => remoteRoot + '/' + f).join(' ')}`)
      for (const rem of files.filter((f) => f.endsWith('.php'))) {
        const res = await exec(conn, `${php} -l ${remoteRoot}/${rem}`)
        if (res.code !== 0 || !String(res.out).includes('No syntax errors')) {
          throw new Error('php -l failed ' + rem)
        }
      }
      const up = await exec(conn, `cd ${remoteRoot} && ${php} scripts/upgrade_sample_floors.php`)
      if (up.code !== 0 || !String(up.out).includes('SAMPLE_FLOORS_OK')) throw new Error('upgrade failed')
      await exec(conn, `rm -rf ${remoteRoot}/runtime/schema ${remoteRoot}/runtime/route`)
      await exec(conn, `kill -USR2 $(cat /www/server/php/83/var/run/php-fpm.pid) 2>/dev/null || true`)
      console.log('SAMPLE_FLOORS_DEPLOY_OK')
      conn.end()
    } catch (e) {
      console.error(e)
      conn.end()
      process.exit(1)
    }
  })
  .on('error', (e) => {
    console.error(e)
    process.exit(1)
  })
  .connect({ host: '114.215.177.87', port: 22, username: 'root', password })

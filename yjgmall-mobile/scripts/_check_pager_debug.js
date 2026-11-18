const https = require('https');
function get(u) {
  return new Promise((res, rej) => {
    https.get(u, (r) => {
      let d = '';
      r.on('data', (c) => (d += c));
      r.on('end', () => res({ status: r.statusCode, d }));
    }).on('error', rej);
  });
}
(async () => {
  const h = await get('https://g.aifeili.cn/companies/');
  const js = [...h.d.matchAll(/_nuxt\/([A-Za-z0-9_-]+\.js)/g)].map((x) => x[1]);
  console.log('status', h.status, 'js', js.length);
  console.log(js.filter((n, i, a) => a.indexOf(n) === i).slice(0, 40).join('\n'));
  console.log('has BjPvfOiz', h.d.includes('BjPvfOiz'));
  console.log('has CQrhweCc', h.d.includes('CQrhweCc'));
  console.log('has fetchStart string in html', h.d.includes('fetchStart'));
  // check companies page payload / entry
  const entry = js.find((n) => n.startsWith('BjP') || n.includes('companies')) || js[js.length - 1];
  for (const name of ['BjPvfOiz.js', 'CQrhweCc.js']) {
    const r = await get('https://g.aifeili.cn/_nuxt/' + name);
    console.log(name, 'status', r.status, 'ingest', r.d.includes('7691/ingest'), 'fetchStart', r.d.includes('fetchStart'), 'pager click', r.d.includes('pager click'));
  }
})().catch((e) => console.error(e));

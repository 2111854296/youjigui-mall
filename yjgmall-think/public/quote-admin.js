(function () {
  var css = [
    'body.qa-on{background:#eef3fb!important}',
    'body.qa-on .panel{background:transparent!important;border:0!important;box-shadow:none!important;padding:0!important}',
    'body.qa-on .toolbar{background:#fff;border:1px solid #e7eef8;border-radius:16px;padding:14px 16px;box-shadow:0 8px 22px rgba(20,45,90,.04);margin-bottom:12px}',
    'body.qa-on .ptitle{font-size:18px!important;letter-spacing:.02em}',
    '#quote-admin-stats{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin:0 0 14px}',
    '#quote-admin-stats .qa-card{display:flex;align-items:center;gap:12px;min-height:84px;padding:14px 14px 14px 16px;background:#fff;border:1px solid #e7eef8;border-radius:16px;box-shadow:0 10px 24px rgba(20,45,90,.05);position:relative;overflow:hidden}',
    '#quote-admin-stats .qa-card:before{content:"";position:absolute;left:0;top:0;bottom:0;width:4px}',
    '#quote-admin-stats .qa-card i{width:36px;height:36px;border-radius:12px;display:block;flex:none}',
    '#quote-admin-stats .qa-card span{display:block!important;color:#7d8da6!important;font-size:12px!important;line-height:1.3!important;font-weight:700}',
    '#quote-admin-stats .qa-card b{display:block!important;margin-top:3px;color:#13294b!important;font-size:26px!important;line-height:1.1!important;letter-spacing:-.03em}',
    '#quote-admin-stats .c1:before{background:#2468f2}#quote-admin-stats .c1 i{background:#e8f0ff}',
    '#quote-admin-stats .c2:before{background:#14b968}#quote-admin-stats .c2 i{background:#e7f8f1}',
    '#quote-admin-stats .c3:before{background:#98a5bc}#quote-admin-stats .c3 i{background:#f2f4f8}',
    '#quote-admin-stats .c4:before{background:#7c5cff}#quote-admin-stats .c4 i{background:#f1edff}',
    '#quote-admin-stats .c5:before{background:#0f9f6e}#quote-admin-stats .c5 i{background:#e7f8f1}',
    '#quote-admin-stats .c6:before{background:#e23b32}#quote-admin-stats .c6 i{background:#fdeceb}',
    'body.qa-on .el-table{border-radius:16px;overflow:hidden;border:1px solid #e7eef8!important;box-shadow:0 12px 28px rgba(20,45,90,.05)}',
    'body.qa-on .el-table th.el-table__cell{background:#f4f8ff!important;color:#3d5278!important;font-weight:800!important}',
    'body.qa-on .el-table td.el-table__cell{padding:11px 0!important}',
    'body.qa-on .el-table .el-table__row:hover>td.el-table__cell{background:#f5f9ff!important}',
    'body.qa-on .quote-name{font-weight:800;color:#1d4fd7;background:none;border:0;padding:0;cursor:pointer;font:inherit}',
    'body.qa-on .qa-pill{display:inline-flex;align-items:center;height:22px;padding:0 8px;border-radius:999px;font-size:12px;font-weight:800;line-height:22px}',
    'body.qa-on .qa-up{color:#0f9f6e!important;background:#e8f8f1}',
    'body.qa-on .qa-down{color:#e23b32!important;background:#fdeceb}',
    'body.qa-on .qa-flat{color:#6b7c93!important;background:#f2f5fa}',
    'body.qa-on .qa-price{color:#1d4fd7;font-weight:800;font-variant-numeric:tabular-nums}',
    'body.qa-on .qa-off{color:#98a5bc}',
    'body.qa-on .ops{display:flex;flex-wrap:wrap;gap:2px 8px}',
    '@media(max-width:1100px){#quote-admin-stats{grid-template-columns:repeat(3,minmax(0,1fr))}}'
  ].join('')

  function token() {
    var m = document.cookie.match(/(?:^|; )yjgmall_admin_token=([^;]*)/)
    if (!m) return ''
    try { return decodeURIComponent(m[1]).replace(/^"|"$/g, '') } catch (e) { return m[1] }
  }
  function ensureCss() {
    if (document.getElementById('quote-admin-css')) return
    var tag = document.createElement('style')
    tag.id = 'quote-admin-css'
    tag.textContent = css
    document.head.appendChild(tag)
  }
  function render(data) {
    var panel = document.querySelector('.panel')
    if (!panel || document.getElementById('quote-admin-stats')) return
    var cards = [
      ['c1', '品种总数', data.products],
      ['c2', '启用品种', data.active],
      ['c3', '停用品种', data.inactive],
      ['c4', '报价记录', data.records],
      ['c5', '上涨品种', data.up],
      ['c6', '下跌品种', data.down]
    ]
    var bar = document.createElement('div')
    bar.id = 'quote-admin-stats'
    cards.forEach(function (c) {
      var el = document.createElement('div')
      el.className = 'qa-card ' + c[0]
      el.innerHTML = '<i></i><div><span>' + c[1] + '</span><b>' + c[2] + '</b></div>'
      bar.appendChild(el)
    })
    var toolbar = panel.querySelector('.toolbar')
    if (toolbar) toolbar.insertAdjacentElement('afterend', bar)
    else panel.insertBefore(bar, panel.firstChild)
  }
  function paintCell(cell) {
    if (!cell || cell.getAttribute('data-qa') === '1') return
    var text = (cell.textContent || '').replace(/\s+/g, '')
    var cls = ''
    if (text === '上涨') cls = 'qa-pill qa-up'
    else if (text === '下跌') cls = 'qa-pill qa-down'
    else if (text === '持平') cls = 'qa-pill qa-flat'
    else if (text === '停用') cls = 'qa-off'
    else if (text.charAt(0) === '¥') cls = 'qa-price'
    else if (text.charAt(0) === '+' && text.indexOf('%') > 0) cls = 'qa-up'
    else if (text.charAt(0) === '-' && text.indexOf('%') > 0) cls = 'qa-down'
    if (!cls) return
    cell.setAttribute('data-qa', '1')
    cell.innerHTML = '<span class="' + cls + '">' + text + '</span>'
  }
  function paint() {
    document.querySelectorAll('.el-table__body td .cell').forEach(paintCell)
  }
  function tick() {
    var on = location.pathname.indexOf('/admin/r/quote-products') === 0
    document.body.classList.toggle('qa-on', on)
    if (!on) {
      var old = document.getElementById('quote-admin-stats')
      if (old) old.remove()
      return
    }
    ensureCss()
    paint()
    if (document.getElementById('quote-admin-stats') || !token()) return
    fetch('/api/v1/admin/resources/quote-products/overview', { headers: { Accept: 'application/json', Authorization: 'Bearer ' + token() } })
      .then(function (r) { return r.ok ? r.json() : null })
      .then(function (j) { if (j && j.data) render(j.data) })
      .catch(function () {})
  }
  setInterval(tick, 800)
  tick()
})()

/* 资讯列表批量操作。正式页面自带 data-article-batch=native 时不再插入。 */
(function () {
  if (window.__yjgArticleBatch) return
  window.__yjgArticleBatch = true

  var selected = new Set()
  var selectAll = false
  var listQuery = {}
  var listTotal = 0
  var cats = []
  var catsLoaded = false
  var busy = false
  var timer = 0

  function onArticles() {
    return location.pathname.replace(/\/+$/, '') === '/admin/r/articles'
  }

  function apiBase() {
    var cfg = window.__NUXT__ && window.__NUXT__.config && window.__NUXT__.config.public
    return (cfg && cfg.apiBase) || 'https://g.aifeili.cn/api/v1'
  }

  function token() {
    var m = document.cookie.match(/(?:^|; )yjgmall_admin_token=([^;]*)/)
    return m ? decodeURIComponent(m[1]) : ''
  }

  function api(path, options) {
    options = options || {}
    var headers = { Accept: 'application/json' }
    var auth = token()
    if (auth) headers.Authorization = 'Bearer ' + auth
    if (options.body) headers['Content-Type'] = 'application/json'
    return fetch(apiBase() + path, {
      method: options.method || 'GET',
      headers: headers,
      body: options.body ? JSON.stringify(options.body) : undefined,
    }).then(function (res) {
      return res.json().catch(function () { return {} }).then(function (data) {
        if (!res.ok) {
          var err = new Error((data && data.message) || '请求失败')
          err.status = res.status
          err.data = data
          throw err
        }
        return data
      })
    })
  }

  var origFetch = window.fetch
  window.fetch = function (input, init) {
    var url = typeof input === 'string' ? input : (input && input.url) || ''
    var pending = origFetch.apply(this, arguments)
    var method = (init && init.method) || (input && input.method) || 'GET'
    if (method === 'GET' && url.indexOf('/admin/resources/articles') !== -1 && url.indexOf('/batch') === -1) {
      try {
        var parsed = new URL(url, location.origin)
        var next = {}
        parsed.searchParams.forEach(function (v, k) {
          if (k === 'page' || k === 'sort' || k === 'order') return
          if (k === 'q' || k === 'status' || k.indexOf('cf_') === 0) next[k] = v
        })
        listQuery = next
      } catch (e) {}
      pending.then(function (res) {
        res.clone().json().then(function (data) {
          var total = data && data.meta && data.meta.total
          if (typeof total === 'number') {
            listTotal = total
            if (selectAll && total < 1) selectAll = false
            syncCount()
          }
        }).catch(function () {})
      }).catch(function () {})
    }
    return pending
  }

  function ensureStyle() {
    if (document.getElementById('yjg-article-batch-style')) return
    var style = document.createElement('style')
    style.id = 'yjg-article-batch-style'
    style.textContent = [
      '.yjg-ab{display:flex;flex-wrap:wrap;align-items:center;gap:12px 16px;margin:0 0 12px;padding:14px 16px 12px;background:#fff;border:1px solid #e6ebf2;border-left:3px solid #1d4fd7;border-radius:12px;box-shadow:0 1px 2px rgba(15,23,42,.04);font-family:inherit}',
      '.yjg-ab-lead{display:flex;align-items:baseline;gap:6px;min-width:88px;color:#334155}',
      '.yjg-ab-lead b{font-size:22px;line-height:1;color:#1d4fd7}',
      '.yjg-ab-group,.yjg-ab-move{display:flex;flex-wrap:wrap;align-items:center;gap:8px}',
      '.yjg-ab-move{padding-left:14px;border-left:1px solid #e8edf5;color:#475569;font-size:13px}',
      '.yjg-ab button{height:32px;padding:0 14px;border-radius:6px;border:1px solid #d7e0ea;background:#fff;color:#1f2937;cursor:pointer;font-size:13px}',
      '.yjg-ab button:disabled{opacity:.45;cursor:not-allowed}',
      '.yjg-ab button.primary{background:#1d4fd7;border-color:#1d4fd7;color:#fff}',
      '.yjg-ab button.warn{background:#e85d04;border-color:#e85d04;color:#fff}',
      '.yjg-ab button.danger{color:#b42318;border-color:#f3c1bc;background:#fff}',
      '.yjg-ab button.yjg-ab-allfilter{color:#1d4fd7;border-color:#c9d7f5;background:#f5f8ff}',
      '.yjg-ab button.yjg-ab-allfilter.on{background:#1d4fd7;border-color:#1d4fd7;color:#fff}',
      '.yjg-ab select{height:32px;min-width:240px;max-width:320px;padding:0 8px;border:1px solid #d7e0ea;border-radius:6px;background:#fff;color:#1f2937}',
      '.yjg-ab-note{flex:1 1 100%;margin:0;color:#64748b;font-size:12px;line-height:1.5}',
      '.yjg-ab-check{width:15px;height:15px;margin:0 8px 0 0;vertical-align:-2px;accent-color:#1d4fd7;cursor:pointer}',
      '.yjg-ab-tag{display:inline-flex;align-items:center;justify-content:center;min-width:52px;padding:2px 8px;border-radius:999px;font-size:12px;line-height:20px;background:#f1f5f9;color:#475569}',
      '.yjg-ab-tag[data-tone="ok"]{background:#e8f7ee;color:#157a3a}',
      '.yjg-ab-tag[data-tone="wait"]{background:#fff6e8;color:#b45309}',
      '.yjg-ab-tag[data-tone="no"]{background:#fdecec;color:#b42318}',
      '.yjg-ab-tag[data-tone="off"]{background:#eef2f7;color:#475569}',
      '.yjg-ab-mask{position:fixed;inset:0;z-index:4000;background:rgba(15,23,42,.35);display:flex;align-items:center;justify-content:center;padding:24px}',
      '.yjg-ab-dialog{width:min(460px,100%);background:#fff;border-radius:12px;padding:18px 18px 14px;box-shadow:0 16px 40px rgba(15,23,42,.18)}',
      '.yjg-ab-dialog h3{margin:0 0 8px;font-size:16px;color:#1a2332}',
      '.yjg-ab-dialog p{margin:0 0 12px;color:#475569;font-size:14px;line-height:1.6}',
      '.yjg-ab-dialog label{display:block;margin:0 0 12px;color:#334155;font-size:13px}',
      '.yjg-ab-dialog input{display:block;width:100%;box-sizing:border-box;margin-top:6px;height:36px;padding:0 10px;border:1px solid #d7e0ea;border-radius:6px}',
      '.yjg-ab-dialog .ops{display:flex;justify-content:flex-end;gap:8px}',
      '.yjg-toast{position:fixed;top:20px;left:50%;transform:translateX(-50%);z-index:4100;padding:8px 14px;border-radius:8px;background:#1f2937;color:#fff;font-size:13px}',
    ].join('')
    document.head.appendChild(style)
  }

  function toast(text) {
    var old = document.querySelector('.yjg-toast')
    if (old) old.remove()
    var el = document.createElement('div')
    el.className = 'yjg-toast'
    el.textContent = text
    document.body.appendChild(el)
    setTimeout(function () { el.remove() }, 2600)
  }

  function tone(text) {
    if (text === '通过' || text === '上架') return 'ok'
    if (text === '待审' || text === '未上架') return 'wait'
    if (text === '拒绝') return 'no'
    if (text === '下架') return 'off'
    return 'muted'
  }

  function headerRow() {
    return document.querySelector('.el-table__header-wrapper thead tr')
  }

  function bodyRows() {
    return [...document.querySelectorAll('.el-table__body-wrapper tbody tr')]
  }

  function colIndex(label) {
    var row = headerRow()
    if (!row) return -1
    var cells = [...row.children]
    return cells.findIndex(function (th) { return th.textContent.replace(/\s/g, '') === label })
  }

  function rowId(tr) {
    var td = tr.querySelector('td')
    if (!td) return 0
    var m = td.textContent.match(/\d+/)
    return m ? Number(m[0]) : 0
  }

  function paintCell(td) {
    if (!td) return
    var text = (td.textContent || '').trim()
    if (!text || text === '—') return
    if (!/^(通过|待审|拒绝|草稿|上架|下架|未上架)$/.test(text) && td.querySelector('.yjg-ab-tag')) return
    if (td.querySelector('.yjg-ab-tag') && td.querySelector('.yjg-ab-tag').textContent === text) return
    var span = document.createElement('span')
    span.className = 'yjg-ab-tag'
    span.dataset.tone = tone(text)
    span.textContent = text
    var host = td.querySelector('.cell') || td
    host.textContent = ''
    host.appendChild(span)
  }

  function readTotal() {
    if (listTotal > 0) return listTotal
    var el = document.querySelector('.el-pagination__total')
    var m = el && el.textContent.match(/(\d+)/)
    if (m) return Number(m[0])
    return bodyRows().filter(function (tr) { return rowId(tr) }).length
  }

  function filterSummary() {
    var parts = []
    document.querySelectorAll('.filter-tag').forEach(function (tag) {
      var text = tag.textContent.replace(/\s+/g, ' ').trim()
      if (text) parts.push(text)
    })
    var input = document.querySelector('.filter-q input')
    if (input && input.value.trim()) parts.push('关键词「' + input.value.trim() + '」')
    document.querySelectorAll('.filter-bar .el-select').forEach(function (sel) {
      var item = sel.querySelector('.el-select__selected-item')
      var text = item ? item.textContent.trim() : ''
      if (!text) {
        var ph = sel.querySelector('.el-select__placeholder')
        if (ph && !ph.classList.contains('is-transparent')) text = ph.textContent.trim()
      }
      if (text && text !== '全部状态' && text !== '分类') parts.push(text)
    })
    return parts.length ? parts.join('，') : '当前列表'
  }

  function syncCount() {
    var count = selectAll ? readTotal() : selected.size
    var n = document.querySelector('.yjg-ab-lead b')
    if (n) n.textContent = String(count)
    var allBtn = document.querySelector('.yjg-ab-allfilter')
    if (allBtn) {
      allBtn.textContent = selectAll ? '取消全选' : '全选当前筛选的全部'
      allBtn.classList.toggle('on', selectAll)
    }
    var note = document.querySelector('.yjg-ab-note')
    if (note) {
      note.textContent = selectAll
        ? '已选中当前筛选下的全部资讯，翻页也还在。移动到新分类后，再批量上架，才会出现在那个分类的资讯板块。'
        : '表头勾选只选这一页。旁边的按钮选中当前筛选的全部页。移动到新分类后，再批量上架，才会出现在那个分类的资讯板块。'
    }
    var off = count === 0 || busy
    document.querySelectorAll('[data-ab-act]').forEach(function (btn) {
      var act = btn.getAttribute('data-ab-act')
      var sel = document.querySelector('.yjg-ab-cat')
      btn.disabled = off || (act === 'move' && !(sel && sel.value))
    })
    var moveBtn = document.querySelector('[data-ab-act="move"]')
    var sel = document.querySelector('.yjg-ab-cat')
    if (moveBtn) moveBtn.textContent = sel && sel.value ? '移动过去' : '先选分类'
    var rows = bodyRows().filter(function (tr) { return rowId(tr) })
    var all = rows.length > 0 && rows.every(function (tr) { return selected.has(rowId(tr)) })
    var some = rows.some(function (tr) { return selected.has(rowId(tr)) })
    var head = document.querySelector('.yjg-ab-all')
    if (head) {
      head.checked = all
      head.indeterminate = some && !all
    }
  }

  function enhance() {
    if (!onArticles() || document.querySelector('[data-article-batch="native"]')) {
      var stale = document.querySelector('.yjg-ab')
      if (stale) stale.remove()
      return
    }
    var table = document.querySelector('.el-table')
    if (!table) return
    ensureStyle()
    if (!document.querySelector('.yjg-ab')) mountBar(table)
    var head = headerRow()
    if (head) {
      var first = head.querySelector('th')
      if (first && /ID/.test(first.textContent) && !first.querySelector('.yjg-ab-all')) {
        var all = document.createElement('input')
        all.type = 'checkbox'
        all.className = 'yjg-ab-check yjg-ab-all'
        all.title = '全选这一页'
        all.addEventListener('click', function (e) { e.stopPropagation() })
        all.addEventListener('change', function () {
          if (!all.checked) selectAll = false
          bodyRows().forEach(function (tr) {
            var id = rowId(tr)
            if (!id) return
            if (all.checked) selected.add(id)
            else selected.delete(id)
            var box = tr.querySelector('.yjg-ab-row')
            if (box) box.checked = all.checked
          })
          syncCount()
        })
        var cell = first.querySelector('.cell') || first
        cell.insertBefore(all, cell.firstChild)
      }
    }
    var visible = new Set()
    bodyRows().forEach(function (tr) {
      var id = rowId(tr)
      if (!id) return
      visible.add(id)
      if (!tr.querySelector('.yjg-ab-row')) {
        var box = document.createElement('input')
        box.type = 'checkbox'
        box.className = 'yjg-ab-check yjg-ab-row'
        box.checked = selected.has(id)
        box.addEventListener('click', function (e) { e.stopPropagation() })
        box.addEventListener('change', function () {
          if (!box.checked && selectAll) selectAll = false
          if (box.checked) selected.add(id)
          else selected.delete(id)
          syncCount()
        })
        var cell = tr.querySelector('td .cell') || tr.querySelector('td')
        if (cell) cell.insertBefore(box, cell.firstChild)
      } else {
        tr.querySelector('.yjg-ab-row').checked = selected.has(id)
      }
    })
    if (selectAll) {
      bodyRows().forEach(function (tr) {
        var id = rowId(tr)
        if (!id) return
        selected.add(id)
        var box = tr.querySelector('.yjg-ab-row')
        if (box) box.checked = true
      })
    } else if (visible.size) {
      ;[...selected].forEach(function (id) { if (!visible.has(id)) selected.delete(id) })
    }
    ;['审核', '状态'].forEach(function (label) {
      var idx = colIndex(label)
      if (idx < 0) return
      bodyRows().forEach(function (tr) { paintCell(tr.children[idx]) })
    })
    syncCount()
  }

  function nest(rows) {
    var seen = {}
    var out = []
    function kids(pid) {
      return rows.filter(function (r) { return Number(r.parent_id) === pid })
    }
    function walk(row, prefix) {
      var id = Number(row.id)
      if (!id || seen[id]) return
      seen[id] = 1
      var name = String(row.label || row.name || '')
      out.push({ id: id, label: prefix ? prefix + ' / ' + name : name })
      kids(id).forEach(function (ch) { walk(ch, prefix ? prefix + ' / ' + name : name) })
    }
    rows.forEach(function (r) { if (!r.parent_id) walk(r, '') })
    rows.forEach(function (r) { walk(r, '') })
    return out
  }

  function loadCats(sel) {
    if (catsLoaded) {
      fillCats(sel)
      return
    }
    Promise.all([
      api('/admin/options/category?module=article&limit=400'),
      api('/admin/options/category?module=news&limit=400'),
    ]).then(function (pair) {
      var map = {}
      pair.forEach(function (res) {
        ;((res && res.data) || []).forEach(function (row) { map[row.id] = row })
      })
      cats = nest(Object.keys(map).map(function (k) { return map[k] }))
      catsLoaded = true
      fillCats(sel)
    }).catch(function () {})
  }

  function fillCats(sel) {
    if (!sel) return
    var current = sel.value
    sel.innerHTML = '<option value="">选择要放入的分类</option>'
    cats.forEach(function (c) {
      var opt = document.createElement('option')
      opt.value = String(c.id)
      opt.textContent = c.label
      sel.appendChild(opt)
    })
    if (current) sel.value = current
  }

  function mountBar(table) {
    var bar = document.createElement('div')
    bar.className = 'yjg-ab'
    bar.innerHTML = [
      '<div class="yjg-ab-lead"><b>0</b><span>篇已选</span></div>',
      '<button type="button" class="yjg-ab-allfilter">全选当前筛选的全部</button>',
      '<div class="yjg-ab-group">',
      '<button type="button" class="primary" data-ab-act="publish">批量上架</button>',
      '<button type="button" data-ab-act="offline">批量下架</button>',
      '<button type="button" class="danger" data-ab-act="delete">批量删除</button>',
      '</div>',
      '<div class="yjg-ab-move"><span>改到别的资讯分类</span>',
      '<select class="yjg-ab-cat"></select>',
      '<button type="button" class="warn" data-ab-act="move">先选分类</button>',
      '</div>',
      '<p class="yjg-ab-note">表头勾选只选这一页。旁边的按钮选中当前筛选的全部页。移动到新分类后，再批量上架，才会出现在那个分类的资讯板块。</p>',
    ].join('')
    table.parentNode.insertBefore(bar, table)
    var sel = bar.querySelector('.yjg-ab-cat')
    sel.addEventListener('change', syncCount)
    bar.querySelectorAll('[data-ab-act]').forEach(function (btn) {
      btn.addEventListener('click', function () { run(btn.getAttribute('data-ab-act')) })
    })
    bar.querySelector('.yjg-ab-allfilter').addEventListener('click', function () {
      selectAll = !selectAll
      if (selectAll) {
        bodyRows().forEach(function (tr) {
          var id = rowId(tr)
          if (!id) return
          selected.add(id)
          var box = tr.querySelector('.yjg-ab-row')
          if (box) box.checked = true
        })
      } else {
        selected.clear()
        document.querySelectorAll('.yjg-ab-row,.yjg-ab-all').forEach(function (box) { box.checked = false })
      }
      syncCount()
    })
    loadCats(sel)
    syncCount()
  }

  function ask(opts) {
    ensureStyle()
    return new Promise(function (resolve) {
      var mask = document.createElement('div')
      mask.className = 'yjg-ab-mask'
      mask.innerHTML = [
        '<div class="yjg-ab-dialog" role="dialog">',
        '<h3></h3><p></p>',
        '<label class="yjg-pass">二级密码<input type="password" autocomplete="current-password" placeholder="请输入二级密码"></label>',
        '<div class="ops"><button type="button" class="ghost">取消</button><button type="button" class="primary ok">确认</button></div>',
        '</div>',
      ].join('')
      mask.querySelector('h3').textContent = opts.title
      mask.querySelector('p').textContent = opts.message
      var pass = mask.querySelector('.yjg-pass')
      if (!opts.password) pass.style.display = 'none'
      var input = mask.querySelector('input')
      function close(val) { mask.remove(); resolve(val) }
      mask.querySelector('.ghost').addEventListener('click', function () { close(null) })
      mask.addEventListener('click', function (e) { if (e.target === mask) close(null) })
      mask.querySelector('.ok').addEventListener('click', function () {
        close(opts.password ? input.value.trim() : true)
      })
      document.body.appendChild(mask)
      if (opts.password) input.focus()
    })
  }

  function reloadList() {
    var btn = [...document.querySelectorAll('.filter-bar button, .toolbar button')].find(function (b) {
      return b.textContent.trim() === '查询'
    })
    selectAll = false
    selected.clear()
    syncCount()
    if (btn) btn.click()
  }

  function catName() {
    var sel = document.querySelector('.yjg-ab-cat')
    if (!sel || !sel.value) return ''
    var hit = cats.find(function (c) { return String(c.id) === sel.value })
    return hit ? hit.label : ''
  }

  async function ensureDelete() {
    var status = await api('/admin/security')
    var data = (status && status.data) || status || {}
    if (!data.has_second_password) {
      toast('请先在站点设置里设置二级密码')
      return false
    }
    if (data.verified) return true
    var password = await ask({
      title: '验证二级密码',
      message: '删除需要验证二级密码。验证通过后，24 小时内不用再填。',
      password: true,
    })
    if (!password || password.length < 6) return false
    await api('/admin/security/verify', { method: 'POST', body: { password: password } })
    return true
  }

  async function run(action) {
    var count = selectAll ? readTotal() : selected.size
    if (busy || !count) {
      toast(selectAll ? '当前筛选下没有资讯' : '请先勾选要处理的资讯')
      return
    }
    var n = count
    var name = catName()
    if (action === 'move' && !name) {
      toast('请先选择要放入的资讯分类')
      return
    }
    var who = selectAll ? ('当前筛选的 ' + n + ' 篇（' + filterSummary() + '）') : ('已选的 ' + n + ' 篇')
    var copy = {
      publish: ['批量上架', '确认上架' + who + '？上架后会出现在对应分类的资讯板块里。'],
      offline: ['批量下架', '确认下架' + who + '？下架后前台不再显示。'],
      delete: ['批量删除', '确认删除' + who + '？删除后可以在回收站还原。'],
      move: ['移动分类', '确认把' + who + '移动到「' + name + '」？移动本身不会上架，需要再点一次批量上架，才会出现在这个分类的资讯板块。'],
    }
    var ok = await ask({ title: copy[action][0], message: copy[action][1] })
    if (!ok) return
    busy = true
    syncCount()
    try {
      if (action === 'delete') {
        var allowed = await ensureDelete()
        if (!allowed) return
      }
      var sel = document.querySelector('.yjg-ab-cat')
      var body = selectAll
        ? Object.assign({ scope: 'filter', action: action }, listQuery)
        : { ids: [...selected], action: action }
      if (action === 'move' && sel) body.category_id = Number(sel.value)
      var res = await api('/admin/resources/articles/batch', {
        method: 'POST',
        body: body,
      })
      toast((res && res.message) || '已处理')
      reloadList()
    } catch (err) {
      toast((err && err.message) || '批量失败')
    } finally {
      busy = false
      syncCount()
    }
  }

  var painting = false
  var queued = false
  function schedule() {
    if (painting) {
      queued = true
      return
    }
    clearTimeout(timer)
    timer = setTimeout(function () {
      painting = true
      try { enhance() } finally {
        painting = false
        if (queued) {
          queued = false
          schedule()
        }
      }
    }, 80)
  }

  var watching = false
  function arm() {
    if (watching) return
    watching = true
    new MutationObserver(schedule).observe(document.documentElement, { childList: true, subtree: true })
  }
  function routeChange() {
    if (onArticles()) arm()
    schedule()
  }
  var push = history.pushState
  var replace = history.replaceState
  history.pushState = function () {
    var ret = push.apply(this, arguments)
    routeChange()
    return ret
  }
  history.replaceState = function () {
    var ret = replace.apply(this, arguments)
    routeChange()
    return ret
  }
  window.addEventListener('popstate', routeChange)
  if (onArticles()) arm()
  schedule()
})()

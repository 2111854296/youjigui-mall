/**
 * 行情详情：半年/一年/两年/五年 显示「详情展开」，弹层放大可横滑折线图。
 * SPA 全量构建受阻时的线上热挂；与 pages/quotes/[id].vue 源码能力对齐。
 */
(function () {
  if (window.__quoteChartExpandInstalled) return
  window.__quoteChartExpandInstalled = true

  var LONG = { '半年': 1, '一年': 1, '两年': 1, '五年': 1 }
  var BTN_CLS = 'qce-expand-btn'
  var MASK_ID = 'qce-expand-mask'

  function isQuoteDetail() {
    return /^\/quotes\/[^/]+\/?$/.test(location.pathname || '')
  }

  function $(sel, root) {
    return (root || document).querySelector(sel)
  }

  function $all(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel))
  }

  function chartCard() {
    return $('.dt-chart-card') || $('.dt-card.dt-chart-card')
  }

  function toolsBar(card) {
    return card && (card.querySelector('.chart-tools') || card.querySelector('.dt-h-row'))
  }

  function activeLongRange(card) {
    var on = card && card.querySelector('.range-tabs button.on')
    if (!on) return ''
    var t = (on.textContent || '').trim()
    return LONG[t] ? t : ''
  }

  function ensureBtn(card) {
    var tools = toolsBar(card)
    if (!tools) return null
    var btn = tools.querySelector('.' + BTN_CLS)
    if (btn) return btn
    btn = document.createElement('button')
    btn.type = 'button'
    btn.className = BTN_CLS
    btn.textContent = '详情展开'
    btn.addEventListener('click', function (e) {
      e.preventDefault()
      e.stopPropagation()
      openExpand(card)
    })
    tools.appendChild(btn)
    return btn
  }

  function syncBtn() {
    if (!isQuoteDetail()) {
      closeExpand()
      return
    }
    var card = chartCard()
    if (!card) return
    // 正式 SPA 已带「详情展开」时不再挂热补按钮，避免重复
    if (card.querySelector('.chart-expand-btn')) {
      var old = card.querySelector('.' + BTN_CLS)
      if (old) old.remove()
      return
    }
    var long = activeLongRange(card)
    var hasChart = !!(card.querySelector('.line-wrap') || card.querySelector('.chart-scroll') || card.querySelector('svg.line-chart'))
    var gate = card.querySelector('.gate')
    var btn = ensureBtn(card)
    if (!btn) return
    btn.style.display = long && hasChart && !gate ? '' : 'none'
  }

  function closeExpand() {
    var mask = document.getElementById(MASK_ID)
    if (mask) mask.remove()
    document.body.style.overflow = ''
  }

  /** 把铺满宽度的折线，按点距拉开成可横滑长图 */
  function widenSvgForScroll(svg) {
    var SLOT = 44
    var NH = 360
    var padT = 16
    var padB = 28
    var inner = 10
    var path = svg.querySelector('path[stroke="#2468F2"], path[stroke="#2468f2"]')
    if (!path) path = svg.querySelector('path[fill="none"]')
    if (!path) return
    var d = path.getAttribute('d') || ''
    var pts = []
    d.replace(/([ML])\s*([-\d.]+)\s*,\s*([-\d.]+)/g, function (_, cmd, x, y) {
      pts.push({ x: Number(x), y: Number(y) })
      return _
    })
    if (pts.length < 2) return
    var ys = pts.map(function (p) { return p.y })
    var yMin = Math.min.apply(null, ys)
    var yMax = Math.max.apply(null, ys)
    var ySpan = Math.max(yMax - yMin, 1)
    var n = pts.length
    var plotW = Math.max(n - 1, 1) * SLOT
    var chartW = plotW + inner * 2
    var line = pts.map(function (p, i) {
      var nx = inner + (n === 1 ? plotW / 2 : SLOT * i)
      var ny = padT + ((p.y - yMin) / ySpan) * (NH - padT - padB)
      return (i ? 'L' : 'M') + nx.toFixed(2) + ',' + ny.toFixed(2)
    }).join(' ')
    var lastX = inner + SLOT * (n - 1)
    var firstX = inner
    var baseY = NH - padB
    var area = line + ' L' + lastX.toFixed(2) + ',' + baseY + ' L' + firstX.toFixed(2) + ',' + baseY + ' Z'
    path.setAttribute('d', line)
    var areaPath = svg.querySelector('path[fill^="url"], path[fill*="quoteLineFill"], path[fill*="Fill"]')
    if (!areaPath) {
      var fills = svg.querySelectorAll('path')
      for (var i = 0; i < fills.length; i++) {
        if (fills[i] !== path && (fills[i].getAttribute('fill') || '') !== 'none') {
          areaPath = fills[i]
          break
        }
      }
    }
    if (areaPath) areaPath.setAttribute('d', area)
    svg.setAttribute('viewBox', '0 0 ' + chartW + ' ' + NH)
    svg.setAttribute('width', String(chartW))
    svg.setAttribute('height', String(NH))
    svg.style.width = chartW + 'px'
    svg.style.height = NH + 'px'
    // 隐藏旧的交叉准星，避免坐标错位
    var gHover = svg.querySelector('g[pointer-events="none"]')
    if (gHover) gHover.remove()
  }

  function openExpand(card) {
    closeExpand()
    var wrap = card.querySelector('.line-wrap') || card.querySelector('.chart-wrap')
    if (!wrap) return
    var titleEl = card.querySelector('.dt-h-row > span, .dt-h > span')
    var title = (titleEl && titleEl.textContent) || '价格走势'
    var range = activeLongRange(card) || ''

    var mask = document.createElement('div')
    mask.id = MASK_ID
    mask.className = 'qce-mask'
    mask.setAttribute('role', 'dialog')
    mask.setAttribute('aria-modal', 'true')
    mask.innerHTML =
      '<div class="qce-panel">' +
      '<div class="qce-head">' +
      '<div class="qce-titles"><strong></strong><span>左右滑动查看整段起伏，悬停可读当天价格</span></div>' +
      '<button type="button" class="qce-close">关闭</button>' +
      '</div>' +
      '<div class="qce-body"></div>' +
      '</div>'
    mask.querySelector('.qce-titles strong').textContent = title + (range ? '' : '')
    var body = mask.querySelector('.qce-body')
    var clone = wrap.cloneNode(true)
    clone.classList.add('qce-expanded')
    // 去掉登录遮罩等
    $all('.chart-gate, .gate', clone).forEach(function (n) { n.remove() })
    body.appendChild(clone)

    // 主图是「整段铺满」；弹层改成点距拉开的可横滑细看（后台那种放大滑轮）
    $all('.chart-scroll', clone).forEach(function (sc) {
      sc.classList.remove('fit')
      sc.style.overflowX = 'auto'
      sc.style.maxHeight = 'none'
    })
    $all('svg.line-chart', clone).forEach(function (svg) {
      widenSvgForScroll(svg)
    })
    $all('.y-col', clone).forEach(function (col) {
      col.style.height = '360px'
    })
    $all('.x-axis', clone).forEach(function (xa) {
      var svg = clone.querySelector('svg.line-chart')
      if (svg) xa.style.width = (svg.getAttribute('width') || '') + 'px'
    })

    mask.addEventListener('click', function (e) {
      if (e.target === mask) closeExpand()
    })
    mask.querySelector('.qce-close').addEventListener('click', closeExpand)
    document.body.appendChild(mask)
    document.body.style.overflow = 'hidden'

    // 滚到最新（右侧）
    requestAnimationFrame(function () {
      $all('.chart-scroll', clone).forEach(function (sc) {
        sc.scrollLeft = sc.scrollWidth
      })
    })
  }

  function onKey(e) {
    if (e.key === 'Escape') closeExpand()
  }

  // 样式一次注入
  if (!document.getElementById('qce-style')) {
    var st = document.createElement('style')
    st.id = 'qce-style'
    st.textContent = [
      '.' + BTN_CLS + '{height:28px;padding:0 12px;border-radius:8px;border:1px solid #2468f2;background:#eef4ff;color:#2468f2;font-size:12.5px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;margin-left:4px}',
      '.' + BTN_CLS + ':hover{background:#2468f2;color:#fff}',
      '.qce-mask{position:fixed;inset:0;z-index:80;display:flex;align-items:center;justify-content:center;padding:24px 16px;background:rgba(15,23,42,.48);backdrop-filter:blur(2px)}',
      '.qce-panel{width:min(1120px,100%);max-height:min(92vh,760px);overflow:auto;padding:18px 18px 12px;border-radius:16px;background:#fff;box-shadow:0 24px 64px rgba(15,23,42,.28)}',
      '.qce-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px}',
      '.qce-titles{display:flex;flex-direction:column;gap:4px;min-width:0}',
      '.qce-titles strong{font-size:16px;color:#13294b;font-weight:800;line-height:1.35}',
      '.qce-titles span{font-size:12.5px;color:#7a889f;font-weight:600}',
      '.qce-close{flex-shrink:0;height:32px;padding:0 14px;border-radius:8px;border:1px solid #d7dee9;background:#fff;color:#5a6b85;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit}',
      '.qce-close:hover{border-color:#2468f2;color:#2468f2}',
      '.qce-body .qce-expanded{min-height:420px}',
      '.qce-body .chart-scroll{overflow-x:auto!important;scrollbar-width:thin}',
    ].join('')
    document.documentElement.appendChild(st)
  }

  document.addEventListener('keydown', onKey, true)
  // SPA 路由 / 区间切换后同步按钮
  var obs = new MutationObserver(function () { syncBtn() })
  function boot() {
    obs.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] })
    syncBtn()
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot)
  else boot()
  window.addEventListener('popstate', function () { setTimeout(syncBtn, 50) })
  ;['pushState', 'replaceState'].forEach(function (k) {
    var raw = history[k]
    if (typeof raw !== 'function') return
    history[k] = function () {
      var r = raw.apply(this, arguments)
      setTimeout(syncBtn, 80)
      return r
    }
  })
})()

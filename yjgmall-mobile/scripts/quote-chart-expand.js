/**
 * 详情展开：弹窗挂载原 QuoteKlineChart（expanded + 折线）。
 * candles 来自主图组件写入的 window.__YJG_QUOTE_CANDLES__（生产环境 DOM 无 __vueParentComponent）。
 */
(function () {
  if (window.__quoteChartExpandV8) return
  window.__quoteChartExpandV8 = true

  var LONG = { 半年: 1, 一年: 1, 两年: 1, 五年: 1 }
  var BTN_CLS = 'qce-expand-btn'
  var MASK_ID = 'qce-expand-mask'
  var mountedApp = null
  var mountedEl = null

  function isQuoteDetail() {
    return /^\/quotes\/[^/]+\/?$/.test(location.pathname || '')
  }

  function $(sel, root) {
    return (root || document).querySelector(sel)
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

  function productName() {
    var h = $('h1')
    return (h && (h.textContent || '').trim()) || ''
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
    if (card.querySelector('.chart-expand-btn')) {
      var old = card.querySelector('.' + BTN_CLS)
      if (old) old.remove()
      return
    }
    var long = activeLongRange(card)
    var hasChart = !!(card.querySelector('.line-wrap') || card.querySelector('svg.line-chart'))
    var gate = card.querySelector('.gate')
    var btn = ensureBtn(card)
    if (!btn) return
    btn.style.display = long && hasChart && !gate ? '' : 'none'
  }

  function teardownMount() {
    try {
      if (mountedApp && typeof mountedApp.unmount === 'function') mountedApp.unmount()
    } catch (e) {}
    mountedApp = null
    if (mountedEl) {
      try {
        mountedEl.innerHTML = ''
      } catch (e2) {}
    }
    mountedEl = null
  }

  function closeExpand() {
    teardownMount()
    var mask = document.getElementById(MASK_ID)
    if (mask) mask.remove()
    document.body.style.overflow = ''
  }

  function readCandles() {
    var c = window.__YJG_QUOTE_CANDLES__
    if (c && c.length >= 2) return c.slice()
    // 兜底：从 Vue 树找（开发环境）
    var wrap = document.querySelector('.line-wrap')
    if (!wrap) return null
    var n = wrap.__vueParentComponent
    var guard = 0
    while (n && guard++ < 40) {
      if (n.props && n.props.candles && n.props.candles.length >= 2) return n.props.candles.slice()
      n = n.parent
    }
    return null
  }

  function readAppContext() {
    try {
      var root = document.querySelector('#__nuxt') || document.querySelector('#__nuxt') || document.body
      if (root && root.__vue_app__) return root.__vue_app__._context
      var wrap = document.querySelector('.line-wrap')
      if (wrap && wrap.__vueParentComponent) return wrap.__vueParentComponent.appContext
    } catch (e) {}
    return null
  }

  function chartScriptUrl() {
    var links = document.querySelectorAll('script[src*="C-jR4i7n"]')
    for (var i = 0; i < links.length; i++) {
      var s = links[i].getAttribute('src') || ''
      if (s) return s
    }
    return '/_nuxt/C-jR4i7n.fit9.js'
  }

  function openExpand(card) {
    closeExpand()
    var candles = readCandles()
    if (!candles || candles.length < 2) {
      // 再等一帧：主图刚算完
      requestAnimationFrame(function () {
        candles = readCandles()
        if (!candles || candles.length < 2) {
          console.warn('[qce] no candles', window.__YJG_QUOTE_META__)
          alert('走势数据尚未就绪，请稍等主图加载完再点「详情展开」')
          return
        }
        doOpen(card, candles)
      })
      return
    }
    doOpen(card, candles)
  }

  function doOpen(card, candles) {
    var name = productName()
    var titleEl = card.querySelector('.dt-h-row > span, .dt-h > span')
    var chartTitle = (titleEl && titleEl.textContent) || '价格走势'
    var range = activeLongRange(card) || ''
    var head =
      (name ? name + ' · ' : '') + (chartTitle || '价格走势') + (range ? ' · ' + range : '')

    var mask = document.createElement('div')
    mask.id = MASK_ID
    mask.className = 'qce-mask'
    mask.setAttribute('role', 'dialog')
    mask.setAttribute('aria-modal', 'true')
    mask.dataset.path = location.pathname
    mask.dataset.product = name
    mask.innerHTML =
      '<div class="qce-panel">' +
      '<div class="qce-head">' +
      '<div class="qce-titles"><strong></strong><span>左右滑动查看整段起伏，悬停可读当天价格</span></div>' +
      '<button type="button" class="qce-close">关闭</button>' +
      '</div>' +
      '<div class="qce-body"><div class="qce-mount"></div></div>' +
      '</div>'
    mask.querySelector('.qce-titles strong').textContent = head
    mask.querySelector('.qce-close').addEventListener('click', closeExpand)
    mask.addEventListener('click', function (e) {
      if (e.target === mask) closeExpand()
    })
    document.body.appendChild(mask)
    document.body.style.overflow = 'hidden'

    var mount = mask.querySelector('.qce-mount')
    mountedEl = mount
    var appContext = readAppContext()

    Promise.all([import('/_nuxt/0a3-bBq-.js'), import(chartScriptUrl())])
      .then(function (mods) {
        var vue = mods[0]
        var Chart = mods[1] && (mods[1]._ || mods[1].default)
        if (!Chart) throw new Error('no chart')
        var createApp = vue.b7 || vue.createApp
        if (!createApp) throw new Error('no createApp')

        var app = createApp(Chart, {
          candles: candles,
          mode: 'line',
          expanded: true,
          revealed: true,
          ariaLabel: head,
        })
        if (appContext && appContext.provides) {
          app._context.provides = Object.create(appContext.provides)
        }
        mountedApp = app
        app.mount(mount)

        requestAnimationFrame(function () {
          var sc = mount.querySelector('.chart-scroll') || mount.querySelector('.line-stage')
          if (sc) sc.scrollLeft = sc.scrollWidth
        })
      })
      .catch(function (err) {
        console.error('[qce] mount failed', err)
        closeExpand()
        alert('展开失败：' + (err && err.message ? err.message : '请刷新重试'))
      })
  }

  function onKey(e) {
    if (e.key === 'Escape') closeExpand()
  }

  function invalidateIfStale() {
    var mask = document.getElementById(MASK_ID)
    if (!mask) return
    if (mask.dataset.path !== location.pathname) {
      closeExpand()
      return
    }
    var name = productName()
    if (mask.dataset.product && name && mask.dataset.product !== name) closeExpand()
  }

  if (!document.getElementById('qce-style')) {
    var st = document.createElement('style')
    st.id = 'qce-style'
    st.textContent = [
      '.' + BTN_CLS + '{height:28px;padding:0 12px;border-radius:8px;border:1px solid #2468f2;background:#eef4ff;color:#2468f2;font-size:12.5px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;margin-left:4px}',
      '.' + BTN_CLS + ':hover{background:#2468f2;color:#fff}',
      '.qce-mask{position:fixed;inset:0;z-index:80;display:flex;align-items:center;justify-content:center;padding:24px 16px;background:rgba(15,23,42,.48);backdrop-filter:blur(2px)}',
      '.qce-panel{width:min(1120px,100%);max-height:min(92vh,760px);overflow:auto;padding:18px 18px 14px;border-radius:16px;background:#fff;box-shadow:0 24px 64px rgba(15,23,42,.28)}',
      '.qce-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px}',
      '.qce-titles{display:flex;flex-direction:column;gap:4px;min-width:0}',
      '.qce-titles strong{font-size:16px;color:#13294b;font-weight:800;line-height:1.35}',
      '.qce-titles span{font-size:12.5px;color:#7a889f;font-weight:600}',
      '.qce-close{flex-shrink:0;height:32px;padding:0 14px;border-radius:8px;border:1px solid #d7dee9;background:#fff;color:#5a6b85;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit}',
      '.qce-close:hover{border-color:#2468f2;color:#2468f2}',
      '.qce-body .qce-mount{min-height:420px}',
      '.qce-body .line-wrap.expanded{min-height:420px}',
    ].join('')
    document.documentElement.appendChild(st)
  }

  document.addEventListener('keydown', onKey, true)
  var obs = new MutationObserver(function () {
    syncBtn()
    invalidateIfStale()
  })
  function boot() {
    obs.observe(document.body, {
      childList: true,
      subtree: true,
      attributes: true,
      attributeFilter: ['class'],
    })
    syncBtn()
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot)
  else boot()
  window.addEventListener('popstate', function () {
    closeExpand()
    setTimeout(syncBtn, 50)
  })
  ;['pushState', 'replaceState'].forEach(function (k) {
    var raw = history[k]
    if (typeof raw !== 'function') return
    history[k] = function () {
      var r = raw.apply(this, arguments)
      closeExpand()
      setTimeout(syncBtn, 80)
      return r
    }
  })

  function syncBoardClass() {
    if (!isQuoteDetail()) return
    var long = false
    try {
      document.querySelectorAll('button').forEach(function (el) {
        var tx = (el.textContent || '').trim()
        if (!LONG[tx]) return
        var cls = el.className || ''
        if (
          cls.indexOf('active') >= 0 ||
          cls.indexOf('on') >= 0 ||
          el.getAttribute('aria-pressed') === 'true'
        )
          long = true
      })
    } catch (e) {}
    document.querySelectorAll('.line-wrap').forEach(function (w) {
      if (w.closest('.qce-mask') || w.closest('.qce-mount')) return
      if (w.classList.contains('expanded')) {
        w.classList.remove('board')
        return
      }
      if (long) w.classList.add('board')
      else w.classList.remove('board')
    })
  }
  document.addEventListener(
    'click',
    function () {
      setTimeout(function () {
        syncBoardClass()
        invalidateIfStale()
      }, 80)
    },
    true
  )
  setInterval(function () {
    syncBoardClass()
    invalidateIfStale()
  }, 1200)
  setTimeout(syncBoardClass, 500)
})()

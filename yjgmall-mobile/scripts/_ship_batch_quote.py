#!/usr/bin/env python3
"""Live: add batch bind 行情品种 next to article category move."""
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn")
nuxt = root / "public" / "_nuxt"

js = r"""
(function(){
  if (window.__articleBatchQuoteV1) return;
  window.__articleBatchQuoteV1 = 1;

  function token(){
    try {
      var m = document.cookie.match(/(?:^|; )yjgmall_admin_token=([^;]*)/);
      if (m) return decodeURIComponent(m[1]).replace(/^"|"$/g,'');
    } catch(e) {}
    return '';
  }
  function authHeaders(){
    var t = token();
    var h = { 'Accept': 'application/json', 'Content-Type': 'application/json' };
    if (t) h.Authorization = t.indexOf('Bearer ')===0 ? t : ('Bearer ' + t);
    return h;
  }
  function onArticles(){
    return /\/admin\/r\/articles\/?$/.test(location.pathname.replace(/\/+$/, '') + (location.pathname.endsWith('/')?'':''))
      || location.pathname.indexOf('/admin/r/articles') === 0;
  }

  var quotes = [];
  var quotesLoaded = false;

  function loadQuotes(){
    if (quotesLoaded) return Promise.resolve(quotes);
    return fetch('/api/v1/admin/options/quoteProduct?limit=100', {
      headers: authHeaders(), credentials: 'include'
    }).then(function(r){ return r.json(); }).then(function(j){
      quotes = ((j && j.data) || []).map(function(o){
        return { id: Number(o.id), label: String(o.label||'').replace(/\s*#\d+\s*$/,'').trim() };
      }).filter(function(o){ return o.id > 0; });
      quotesLoaded = true;
      return quotes;
    }).catch(function(){ quotes = []; return quotes; });
  }

  function selectedIds(){
    var ids = [];
    document.querySelectorAll('.el-table__body-wrapper .el-checkbox.is-checked, .el-table__body .el-checkbox.is-checked').forEach(function(box){
      var tr = box.closest('tr');
      if (!tr) return;
      var cell = tr.querySelector('td');
      // id often in first data column
      var tds = tr.querySelectorAll('td');
      for (var i=0;i<Math.min(3, tds.length);i++){
        var txt = (tds[i].textContent||'').trim();
        if (/^\d+$/.test(txt) && Number(txt) > 0) { ids.push(Number(txt)); break; }
      }
    });
    // also try Vue selection via data
    return Array.from(new Set(ids));
  }

  function pickCountLabel(bar){
    var b = bar.querySelector('.ab-lead b');
    return b ? Number(b.textContent||0) : 0;
  }

  function enhance(bar){
    if (!bar || bar.getAttribute('data-quote-batch') === '1') return;
    bar.setAttribute('data-quote-batch', '1');
    var wrap = document.createElement('div');
    wrap.className = 'ab-move ab-quote-live';
    wrap.innerHTML = '<span>绑定行情品种</span>'
      + '<select class="ab-quote-sel"><option value="">选择行情品种（DMC/生胶等）</option></select>'
      + '<button type="button" class="ab-quote-btn" disabled>先选品种</button>';
    var note = bar.querySelector('.ab-note');
    if (note) bar.insertBefore(wrap, note);
    else bar.appendChild(wrap);

    var sel = wrap.querySelector('select');
    var btn = wrap.querySelector('button');
    loadQuotes().then(function(rows){
      rows.forEach(function(o){
        var opt = document.createElement('option');
        opt.value = String(o.id);
        opt.textContent = o.label;
        sel.appendChild(opt);
      });
    });
    sel.addEventListener('change', function(){
      var v = sel.value;
      btn.disabled = !v;
      btn.textContent = v ? '绑定品种' : '先选品种';
    });
    btn.addEventListener('click', function(){
      var qp = Number(sel.value||0);
      if (!qp) { alert('请先选择行情品种'); return; }
      var name = (sel.options[sel.selectedIndex]||{}).textContent || '';
      var allOn = !!bar.querySelector('.ab-all.on');
      var nShow = pickCountLabel(bar);
      var ids = selectedIds();
      var body;
      if (allOn && nShow > 0) {
        if (!confirm('确认把当前筛选的 '+nShow+' 篇绑定到行情品种「'+name+'」？')) return;
        body = { scope: 'filter', action: 'set_quote', quote_product_id: qp };
        try {
          var u = new URL(location.href);
          ['q','status'].forEach(function(k){ if (u.searchParams.get(k)) body[k]=u.searchParams.get(k); });
          u.searchParams.forEach(function(v,k){ if (k.indexOf('cf_')===0) body[k]=v; });
        } catch(e) {}
      } else {
        if (!ids.length) {
          alert('请先在表格里勾选资讯');
          return;
        }
        if (!confirm('确认把已选的 '+ids.length+' 篇绑定到行情品种「'+name+'」？')) return;
        body = { ids: ids, action: 'set_quote', quote_product_id: qp };
      }
      btn.disabled = true;
      btn.textContent = '处理中…';
      fetch('/api/v1/admin/resources/articles/batch', {
        method: 'POST',
        headers: authHeaders(),
        credentials: 'include',
        body: JSON.stringify(body)
      }).then(function(r){ return r.json().then(function(j){ return { ok:r.ok, j:j }; }); })
        .then(function(pack){
          if (!pack.ok) throw new Error((pack.j && pack.j.message) || '绑定失败');
          alert((pack.j && pack.j.message) || '绑定完成');
          location.reload();
        })
        .catch(function(e){
          alert(e.message || '绑定失败');
          btn.disabled = !sel.value;
          btn.textContent = sel.value ? '绑定品种' : '先选品种';
        });
    });
  }

  function scan(){
    if (!onArticles()) return;
    document.querySelectorAll('.article-batch').forEach(enhance);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ setTimeout(scan, 400); });
  else setTimeout(scan, 400);
  setInterval(scan, 1200);
})();
""".lstrip()

css = r"""
.ab-quote-live{display:flex;flex-wrap:wrap;align-items:center;gap:8px;padding-left:14px;border-left:1px solid #e8edf5;color:#475569;font-size:13px}
.ab-quote-sel{min-width:240px;height:32px;border:1px solid #d7e2f5;border-radius:8px;padding:0 10px;background:#fff;font:inherit}
.ab-quote-btn{height:32px;border:0;border-radius:8px;padding:0 14px;background:#16a34a;color:#fff;font:inherit;font-weight:700;cursor:pointer}
.ab-quote-btn:disabled{opacity:.45;cursor:not-allowed}
""".strip() + "\n"

(nuxt / "article-batch-quote.fix.js").write_text(js, encoding="utf-8")
(nuxt / "article-batch-quote.fix.css").write_text(css, encoding="utf-8")
print("wrote assets")

tag = (
    '<link rel="stylesheet" href="/_nuxt/article-batch-quote.fix.css?v=1">\n'
    '<script src="/_nuxt/article-batch-quote.fix.js?v=1" defer></script>'
)
for html in list((root / "public").rglob("admin/**/index.html")) + [
    root / "public/index.html",
    root / "public/200.html",
]:
    if not html.exists():
        continue
    h = html.read_text(encoding="utf-8", errors="ignore")
    if "article-batch-quote.fix.js" in h:
        h2 = re.sub(r"article-batch-quote\.fix\.(css|js)\?v=\d+", r"article-batch-quote.fix.\1?v=1", h)
        if h2 != h:
            html.write_text(h2, encoding="utf-8")
            print("bump", html)
        continue
    if "</head>" not in h:
        continue
    html.write_text(h.replace("</head>", tag + "\n</head>", 1), encoding="utf-8")
    print("link", html)
print("ok")

#!/usr/bin/env python3
"""Fix admin 行情品种 chips: cookie token + exact DB names + prefetch full list."""
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn")
nuxt = root / "public" / "_nuxt"

css = r"""
.admin-form .rel-search{display:none!important}
.admin-form .rel{
  display:grid!important;gap:12px!important;
  max-width:640px;padding:12px 14px;border-radius:12px;
  background:linear-gradient(180deg,#fafbfc 0%,#f4f7fb 100%);
  border:1px solid #e8eef6;box-sizing:border-box;
}
.admin-form .rel .el-select{width:100%;max-width:100%}
.admin-form .rel .el-select .el-select__wrapper{
  min-height:42px!important;border-radius:10px!important;
  box-shadow:0 0 0 1px #dbe3ef inset!important;padding:6px 14px!important;
  background:#fff!important;
}
.admin-form .rel .el-select .el-select__wrapper:hover{
  box-shadow:0 0 0 1px #94a3b8 inset!important;
}
.admin-form .rel .el-select .el-select__wrapper.is-focused{
  box-shadow:0 0 0 1px #e85d04 inset,0 0 0 3px rgba(232,93,4,.14)!important;
}
.rel-chips-live{
  display:flex;flex-wrap:wrap;gap:8px;margin:0;
  padding-top:2px;border-top:1px dashed #e2e8f0;
}
.rel-chips-live::before{
  content:"常用品种（点一下即可）";flex:0 0 100%;font-size:11px;color:#94a3b8;
  letter-spacing:.04em;margin:2px 0 2px;
}
.rel-chip-live{
  border:1px solid #e2e8f0;background:#fff;color:#475569;
  border-radius:999px;padding:7px 13px;font:inherit;font-size:12.5px;font-weight:600;
  cursor:pointer;line-height:1.2;transition:border-color .15s,background .15s,color .15s,box-shadow .15s;
}
.rel-chip-live:hover{border-color:#fdba74;background:#fff7ed;color:#c2410c}
.rel-chip-live.on{
  border-color:#e85d04;background:#fff7ed;color:#c2410c;
  box-shadow:0 0 0 2px rgba(232,93,4,.14);
}
.admin-form .field:has(.rel) .hint{color:#64748b;line-height:1.55;max-width:640px}
""".strip() + "\n"

js = r"""
(function(){
  if (window.__quotePickUiV3) return;
  window.__quotePickUiV3 = 1;

  // chip, fullName(与库一致), 已知 id（加速；接口也能覆盖）
  var CHIPS = [
    ['DMC','二甲基环硅氧烷混合物（DMC）',1],
    ['D4','八甲基环四硅氧烷（D4）',2],
    ['生胶','甲基乙烯基硅橡胶（生胶）',4],
    ['107胶','107室温硫化硅橡胶（107胶）',3],
    ['二甲基硅油','二甲基硅油',5],
    ['外资硅油','外资品牌二甲基硅油',10],
    ['混炼胶','硅橡胶混炼胶',11],
    ['气相白炭黑','气相法二氧化硅（气相白炭黑）',8],
    ['沉淀白炭黑','沉淀二氧化硅（沉淀白炭黑）',12],
    ['421#金属硅','421#金属硅',13],
    ['工业硅期货','工业硅期货',14],
    ['一氯甲烷','一氯甲烷',15],
    ['有机硅助剂','有机硅助剂',16],
    ['裂解料硅油','裂解料二甲基硅油',17],
    ['废硅胶毛边','废硅胶毛边',18]
  ];

  var catalog = null; // [{id,label,name}]
  var catalogPromise = null;

  function cookieToken(){
    try {
      var m = document.cookie.match(/(?:^|; )yjgmall_admin_token=([^;]*)/);
      if (m) return decodeURIComponent(m[1]).replace(/^"|"$/g,'');
    } catch(e) {}
    return '';
  }
  function storageToken(){
    try {
      var keys = Object.keys(localStorage || {});
      for (var i=0;i<keys.length;i++){
        var k = keys[i], v = localStorage.getItem(k) || '';
        if (/token|auth|admin/i.test(k) && v && v.length > 8) {
          return String(v).replace(/^"|"$/g,'');
        }
      }
      return localStorage.getItem('admin_token')
        || localStorage.getItem('token')
        || sessionStorage.getItem('admin_token')
        || '';
    } catch(e) { return ''; }
  }
  function token(){ return cookieToken() || storageToken(); }
  function authHeaders(){
    var t = token();
    var h = { 'Accept': 'application/json' };
    if (t) h.Authorization = t.indexOf('Bearer ')===0 ? t : ('Bearer ' + t);
    return h;
  }

  function shortName(label){
    return String(label || '').replace(/\s*#\d+\s*$/, '').trim();
  }

  function loadCatalog(force){
    if (catalog && !force) return Promise.resolve(catalog);
    if (catalogPromise && !force) return catalogPromise;
    catalogPromise = fetch('/api/v1/admin/options/quoteProduct?limit=100', {
      headers: authHeaders(),
      credentials: 'include'
    }).then(function(r){ return r.json().then(function(j){ return { ok: r.ok, j: j }; }); })
      .then(function(pack){
        var rows = (pack.j && pack.j.data) || [];
        catalog = rows.map(function(o){
          return { id: o.id, label: o.label, name: shortName(o.label) };
        });
        return catalog;
      })
      .catch(function(){
        catalog = catalog || [];
        return catalog;
      });
    return catalogPromise;
  }

  function findModel(el){
    var cur = el;
    while (cur) {
      var c = cur.__vueParentComponent;
      if (c) {
        var st = c.setupState || {};
        if (st.model && typeof st.model === 'object') {
          if ('quote_product_id' in st.model) return st.model;
          if (st.visibleFields || st.fields || st.relationCache) return st.model;
        }
        if (st.form && typeof st.form === 'object' && 'quote_product_id' in st.form) return st.form;
      }
      cur = cur.parentElement;
    }
    return null;
  }

  function findSetup(el){
    var cur = el;
    while (cur) {
      var c = cur.__vueParentComponent;
      if (c && c.setupState && (c.setupState.relationCache || c.setupState.model)) return c.setupState;
      cur = cur.parentElement;
    }
    return null;
  }

  function injectCache(rel, hit){
    var st = findSetup(rel);
    if (!st || !st.relationCache) return;
    var key = 'quoteProduct';
    var list = st.relationCache[key];
    if (!Array.isArray(list)) {
      try { st.relationCache[key] = [hit]; } catch(e) {}
      return;
    }
    if (!list.some(function(o){ return String(o.id) === String(hit.id); })) {
      list.unshift(hit);
    }
  }

  function injectAll(rel, rows){
    var st = findSetup(rel);
    if (!st || !st.relationCache) return;
    try {
      st.relationCache.quoteProduct = rows.map(function(o){
        return { id: o.id, label: o.label || (o.name + ' #' + o.id) };
      });
    } catch(e) {}
  }

  function emitSelect(rel, id){
    var model = findModel(rel);
    if (model) model.quote_product_id = String(id);
    var sel = rel.querySelector('.el-select');
    if (!sel) return;
    var n = 0, cur = sel;
    while (cur && n < 10) {
      var inst = cur.__vueParentComponent;
      if (inst && inst.vnode && inst.vnode.props) {
        var fn = inst.vnode.props['onUpdate:modelValue'];
        if (typeof fn === 'function') {
          try { fn(String(id)); } catch (e) {}
        }
        if (inst.emit) {
          try { inst.emit('update:modelValue', String(id)); } catch (e2) {}
        }
      }
      cur = cur.parentElement;
      n++;
    }
  }

  function setNativeInput(input, val){
    var setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
    setter.call(input, val);
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function matchHit(rows, chip, full, knownId){
    if (knownId != null) {
      var byId = rows.find(function(o){ return String(o.id) === String(knownId); });
      if (byId) return byId;
    }
    var hit = rows.find(function(o){
      var name = o.name || shortName(o.label);
      return name === full || name.indexOf(full) >= 0 || full.indexOf(name) >= 0;
    });
    if (hit) return hit;
    hit = rows.find(function(o){
      var name = o.name || shortName(o.label);
      var lab = String(o.label || '');
      return name.indexOf(chip) >= 0 || lab.indexOf(chip) >= 0
        || (chip === 'DMC' && /DMC|二甲基环硅氧烷/.test(name))
        || (chip === 'D4' && /D4|八甲基环四硅氧烷/.test(name))
        || (chip === '生胶' && /生胶|甲基乙烯基硅橡胶/.test(name))
        || (chip === '混炼胶' && /混炼胶/.test(name));
    });
    return hit || null;
  }

  function enhance(rel){
    if (!rel) return;
    var search = rel.querySelector('.rel-search');
    if (search) search.style.display = 'none';

    var field = rel.closest('.field');
    var label = field ? ((field.querySelector('label')||{}).textContent || '') : '';
    var hint = field ? ((field.querySelector('.hint')||{}).textContent || '') : '';
    var isQuote = /行情品种/.test(label) || /价格行情资讯|行情品种|点下方标签/.test(hint);
    if (!isQuote) return;
    if (rel.getAttribute('data-quote-ui') === '3') return;
    rel.setAttribute('data-quote-ui', '3');
    rel.querySelectorAll('.rel-chips-live').forEach(function(n){ n.remove(); });

    var chips = document.createElement('div');
    chips.className = 'rel-chips-live';
    CHIPS.forEach(function(pair){
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'rel-chip-live';
      btn.textContent = pair[0];
      btn.title = pair[1];
      btn.addEventListener('click', function(){ pick(rel, pair[0], pair[1], pair[2], btn); });
      chips.appendChild(btn);
    });
    rel.appendChild(chips);

    // 预加载完整列表，写入 Vue relationCache，下拉才齐
    loadCatalog().then(function(rows){
      if (rows && rows.length) injectAll(rel, rows);
      // 顺带触发旧搜索框，让原组件也刷新一次
      var input = rel.querySelector('.rel-search input');
      var searchBtn = rel.querySelector('.rel-search button');
      if (input && searchBtn) {
        setNativeInput(input, '');
        try { searchBtn.click(); } catch(e) {}
      }
      syncActive(rel);
    });
  }

  function syncActive(rel){
    var model = findModel(rel);
    var id = model && model.quote_product_id != null && model.quote_product_id !== ''
      ? String(model.quote_product_id) : '';
    var name = '';
    if (id && catalog) {
      var row = catalog.find(function(o){ return String(o.id) === id; });
      if (row) name = row.name;
    }
    if (!name) {
      var wrap = rel.querySelector('.el-select__wrapper');
      if (wrap) name = (wrap.textContent || '').trim();
    }
    rel.querySelectorAll('.rel-chip-live').forEach(function(b){
      var on = false;
      if (id) {
        var pair = CHIPS.find(function(p){ return p[0] === b.textContent; });
        if (pair && String(pair[2]) === id) on = true;
      }
      if (!on && name) on = name.indexOf(b.textContent) >= 0 || name.indexOf(b.title) >= 0;
      b.classList.toggle('on', !!on);
    });
  }

  function applyHit(rel, hit, btn){
    var pack = { id: hit.id, label: hit.label || (hit.name + ' #' + hit.id) };
    injectCache(rel, pack);
    emitSelect(rel, hit.id);
    // 旧搜索框也刷一下，保证 option 在原组件缓存里
    var input = rel.querySelector('.rel-search input');
    var searchBtn = rel.querySelector('.rel-search button');
    if (input && searchBtn) {
      setNativeInput(input, shortName(pack.label));
      try { searchBtn.click(); } catch(e) {}
    }
    setTimeout(function(){
      injectCache(rel, pack);
      emitSelect(rel, hit.id);
      syncActive(rel);
    }, 280);
    if (btn) {
      rel.querySelectorAll('.rel-chip-live').forEach(function(b){ b.classList.remove('on'); });
      btn.classList.add('on');
    }
  }

  function pick(rel, chip, full, knownId, btn){
    loadCatalog().then(function(rows){
      var hit = matchHit(rows, chip, full, knownId);
      if (hit) {
        applyHit(rel, hit, btn);
        return;
      }
      // 再按关键词搜一次
      var qs = [full, chip, chip === 'DMC' ? '二甲基环硅氧烷' : ''].filter(Boolean);
      var chain = Promise.resolve(null);
      qs.forEach(function(q){
        chain = chain.then(function(found){
          if (found) return found;
          return fetch('/api/v1/admin/options/quoteProduct?limit=50&q=' + encodeURIComponent(q), {
            headers: authHeaders(),
            credentials: 'include'
          }).then(function(r){ return r.json(); }).then(function(j){
            var list = ((j && j.data) || []).map(function(o){
              return { id: o.id, label: o.label, name: shortName(o.label) };
            });
            if (list.length && catalog) {
              list.forEach(function(o){
                if (!catalog.some(function(x){ return String(x.id)===String(o.id); })) catalog.push(o);
              });
            }
            return matchHit(list, chip, full, knownId);
          }).catch(function(){ return null; });
        });
      });
      return chain.then(function(found){
        if (found) applyHit(rel, found, btn);
        else {
          var t = token();
          alert(t
            ? ('未找到品种：' + chip + '（请到「行情报价」确认该品种是否存在）')
            : '未登录或登录已失效，请重新登录后台后再选品种');
          if (btn) btn.classList.remove('on');
        }
      });
    });
  }

  function scan(){
    document.querySelectorAll('.admin-form .rel, .field .rel').forEach(enhance);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ setTimeout(scan, 400); });
  else setTimeout(scan, 400);
  setInterval(scan, 1200);
})();
""".lstrip()

(nuxt / "quote-pick.fix.css").write_text(css, encoding="utf-8")
(nuxt / "quote-pick.fix.js").write_text(js, encoding="utf-8")
print("wrote assets v3")

# also raise options limit slightly on PHP if present
php = root / "app/controller/api/v1/AdminResource.php"
if php.exists():
    t = php.read_text(encoding="utf-8", errors="ignore")
    nt = t
    # bump max limit for quoteProduct options
    old = "$limit = min(50, max(5, (int)$this->request->param('limit', 30)));\n        if ($relation === 'quoteProduct') {"
    new = "$limit = min(200, max(5, (int)$this->request->param('limit', 30)));\n        if ($relation === 'quoteProduct') {"
    if old in nt:
        nt = nt.replace(old, new, 1)
    # when searching quoteProduct, also try loose match / don't require exact
    # ensure empty q returns all active up to limit (already does)
    if nt != t:
        php.write_text(nt, encoding="utf-8")
        print("php options limit bumped")
    else:
        print("php options unchanged or pattern miss")

tag_css = "quote-pick.fix.css?v=3"
tag_js = "quote-pick.fix.js?v=3"
link_block = (
    f'<link rel="stylesheet" href="/_nuxt/{tag_css}">\n'
    f'<script src="/_nuxt/{tag_js}" defer></script>'
)
for html in list((root / "public").rglob("admin/**/index.html")) + [
    root / "public/index.html",
    root / "public/200.html",
]:
    if not html.exists():
        continue
    h = html.read_text(encoding="utf-8", errors="ignore")
    if "quote-pick.fix.js" in h:
        h2 = re.sub(r"quote-pick\.fix\.css\?v=\d+", tag_css, h)
        h2 = re.sub(r"quote-pick\.fix\.js\?v=\d+", tag_js, h2)
        if h2 != h:
            html.write_text(h2, encoding="utf-8")
            print("bump", html)
        continue
    if "</head>" not in h:
        continue
    html.write_text(h.replace("</head>", link_block + "\n</head>", 1), encoding="utf-8")
    print("link", html)

print("ok")

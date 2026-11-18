#!/usr/bin/env python3
"""Live admin: nicer quote product picker (hide dual search, chips, style)."""
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn")
nuxt = root / "public" / "_nuxt"

css = r"""
.admin-form .rel-search{display:none!important}
.admin-form .rel{display:grid!important;gap:10px!important;max-width:560px}
.admin-form .rel .el-select{width:100%;max-width:560px}
.admin-form .rel .el-select .el-select__wrapper{
  min-height:40px!important;border-radius:10px!important;
  box-shadow:0 0 0 1px #dbe3ef inset!important;padding:4px 12px!important;
}
.admin-form .rel .el-select .el-select__wrapper:hover{box-shadow:0 0 0 1px #94a3b8 inset!important}
.admin-form .rel .el-select .el-select__wrapper.is-focused{
  box-shadow:0 0 0 1px #e85d04 inset,0 0 0 3px rgba(232,93,4,.12)!important;
}
.rel-chips-live{display:flex;flex-wrap:wrap;gap:8px;margin-top:2px}
.rel-chip-live{
  border:1px solid #e2e8f0;background:#f8fafc;color:#475569;
  border-radius:999px;padding:6px 12px;font:inherit;font-size:12.5px;font-weight:600;
  cursor:pointer;line-height:1.2;
}
.rel-chip-live:hover{border-color:#fdba74;background:#fff7ed;color:#c2410c}
.rel-chip-live.on{border-color:#e85d04;background:#fff7ed;color:#c2410c;box-shadow:0 0 0 2px rgba(232,93,4,.12)}
""".strip() + "\n"

js = r"""
(function(){
  if (window.__quotePickUiV1) return;
  window.__quotePickUiV1 = 1;

  var CHIPS = [
    ['DMC','二甲基环硅氧烷混合物'],
    ['D4','八甲基环四硅氧烷'],
    ['生胶','甲基乙烯基硅橡胶'],
    ['107胶','107室温硫化硅橡胶'],
    ['二甲基硅油','二甲基硅油'],
    ['外资硅油','外资品牌二甲基硅油'],
    ['混炼胶','硅橡胶混炼胶'],
    ['气相白炭黑','气相法二氧化硅'],
    ['沉淀白炭黑','沉淀二氧化硅'],
    ['421#金属硅','421#金属硅'],
    ['工业硅期货','工业硅期货'],
    ['一氯甲烷','一氯甲烷'],
    ['有机硅助剂','有机硅助剂'],
    ['裂解料硅油','裂解料二甲基硅油'],
    ['废硅胶毛边','废硅胶毛边']
  ];

  function token(){
    try {
      return localStorage.getItem('admin_token')
        || localStorage.getItem('token')
        || sessionStorage.getItem('admin_token')
        || '';
    } catch(e) { return ''; }
  }

  function authHeaders(){
    var t = token();
    var h = { 'Accept': 'application/json' };
    if (t) h.Authorization = (t.indexOf('Bearer ')===0 ? t : ('Bearer ' + t));
    return h;
  }

  function findModel(el){
    var cur = el;
    while (cur) {
      var c = cur.__vueParentComponent;
      if (c) {
        var st = c.setupState || {};
        if (st.model && typeof st.model === 'object' && ('quote_product_id' in st.model || Array.isArray(st.fields) || st.visibleFields)) {
          return st.model;
        }
        // parent articles edit form
        if (st.form && typeof st.form === 'object' && 'quote_product_id' in st.form) return st.form;
      }
      cur = cur.parentElement;
    }
    return null;
  }

  function currentId(model){
    if (!model) return '';
    var v = model.quote_product_id;
    return v == null || v === '' ? '' : String(v);
  }

  function enhance(rel){
    if (!rel || rel.getAttribute('data-quote-ui') === '1') return;
    // only enhance quote product fields (hint text or nearby label)
    var field = rel.closest('.field');
    var label = field ? (field.querySelector('label')||{}).textContent || '' : '';
    var hint = field ? (field.querySelector('.hint')||{}).textContent || '' : '';
    var isQuote = /行情品种/.test(label) || /价格行情资讯|行情品种/.test(hint);
    if (!isQuote) {
      // still hide awkward search for all relation fields
      var s = rel.querySelector('.rel-search');
      if (s) s.style.display = 'none';
      return;
    }
    rel.setAttribute('data-quote-ui', '1');
    var search = rel.querySelector('.rel-search');
    if (search) search.style.display = 'none';

    var chips = document.createElement('div');
    chips.className = 'rel-chips-live';
    CHIPS.forEach(function(pair){
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'rel-chip-live';
      btn.textContent = pair[0];
      btn.title = pair[1];
      btn.addEventListener('click', function(){ pick(rel, pair[0], pair[1], btn); });
      chips.appendChild(btn);
    });
    rel.appendChild(chips);
    syncActive(rel);
  }

  function syncActive(rel){
    var model = findModel(rel);
    var id = currentId(model);
    var chips = rel.querySelectorAll('.rel-chip-live');
    if (!id) {
      chips.forEach(function(b){ b.classList.remove('on'); });
      return;
    }
    // resolve label from select selected text
    var selText = '';
    var selected = rel.querySelector('.el-select__selected-item, .el-select__placeholder, .el-select .el-select__selection-text');
    // Element Plus 2.x
    var wrap = rel.querySelector('.el-select__wrapper');
    if (wrap) selText = (wrap.textContent || '').trim();
    chips.forEach(function(b){
      var on = selText.indexOf(b.textContent) >= 0 || selText.indexOf(b.title) >= 0;
      b.classList.toggle('on', !!on);
    });
  }

  function setSelectValue(rel, id, label){
    var model = findModel(rel);
    if (model) {
      model.quote_product_id = String(id);
    }
    // also try to reflect in el-select UI by dispatching input on underlying
    var selectRoot = rel.querySelector('.el-select');
    if (selectRoot) {
      var c = selectRoot.__vueParentComponent;
      // walk up a few for ElSelect
      var n = 0, cur = selectRoot;
      while (cur && n < 6) {
        var inst = cur.__vueParentComponent;
        if (inst && inst.props && 'modelValue' in inst.props) {
          try {
            // emit update
            if (inst.emit) inst.emit('update:modelValue', String(id));
            if (inst.props && inst.vnode && inst.vnode.props && inst.vnode.props['onUpdate:modelValue']) {
              inst.vnode.props['onUpdate:modelValue'](String(id));
            }
          } catch (e) {}
        }
        cur = cur.parentElement;
        n++;
      }
    }
    // Ensure option exists: mutate select options list if empty match
    setTimeout(function(){ syncActive(rel); }, 80);
  }

  function pick(rel, chip, full, btn){
    btn.classList.add('on');
    fetch('/api/v1/admin/options/quoteProduct?limit=50&q=' + encodeURIComponent(full), {
      headers: authHeaders(),
      credentials: 'include'
    }).then(function(r){ return r.json(); }).then(function(j){
      var rows = (j && j.data) || [];
      var hit = rows.find(function(o){
        var lab = String(o.label||'');
        return lab.indexOf(full) >= 0 || lab.indexOf(chip) >= 0;
      }) || rows[0];
      if (!hit) {
        alert('未找到品种：' + chip + '，请确认行情品种库已有该品种');
        return;
      }
      setSelectValue(rel, hit.id, hit.label);
      // refresh native select options by triggering search box if present
      var input = rel.querySelector('.rel-search input');
      if (input) {
        input.value = full;
        input.dispatchEvent(new Event('input', { bubbles: true }));
      }
    }).catch(function(){
      alert('加载品种失败，请重新登录后台后再试');
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
print("wrote assets")

tag = (
    '<link rel="stylesheet" href="/_nuxt/quote-pick.fix.css?v=1">\n'
    '<script src="/_nuxt/quote-pick.fix.js?v=1" defer></script>'
)
for name in ["index.html", "200.html", "admin/index.html", "admin/articles/edit/index.html", "admin/r/index.html"]:
    html = root / "public" / name
    # glob admin html
for html in list((root / "public").rglob("admin/**/index.html")) + [root / "public/index.html", root / "public/200.html"]:
    if not html.exists():
        continue
    h = html.read_text(encoding="utf-8", errors="ignore")
    if "quote-pick.fix.js" in h:
        h2 = re.sub(r"quote-pick\.fix\.(css|js)\?v=\d+", r"quote-pick.fix.\1?v=1", h)
        if h2 != h:
            html.write_text(h2, encoding="utf-8")
        continue
    if "</head>" not in h:
        continue
    html.write_text(h.replace("</head>", tag + "\n</head>", 1), encoding="utf-8")
    print("link", html)
print("ok")

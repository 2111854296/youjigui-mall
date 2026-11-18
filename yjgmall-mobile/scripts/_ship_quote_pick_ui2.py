#!/usr/bin/env python3
"""Live admin: quote product picker UX — hide dual search, chips, bind select."""
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn")
nuxt = root / "public" / "_nuxt"

css = r"""
/* 隐藏旧版「搜索框 + 搜按钮」，只留可搜索下拉 */
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
.admin-form .rel .el-select .el-select__placeholder,
.admin-form .rel .el-select .el-select__selected-item{
  font-size:14px!important;
}
.rel-chips-live{
  display:flex;flex-wrap:wrap;gap:8px;margin:0;
  padding-top:2px;border-top:1px dashed #e2e8f0;
}
.rel-chips-live::before{
  content:"常用品种";flex:0 0 100%;font-size:11px;color:#94a3b8;
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
  if (window.__quotePickUiV2) return;
  window.__quotePickUiV2 = 1;

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
      var keys = Object.keys(localStorage);
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
  function authHeaders(){
    var t = token();
    var h = { 'Accept': 'application/json' };
    if (t) h.Authorization = t.indexOf('Bearer ')===0 ? t : ('Bearer ' + t);
    return h;
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

  function findRelationCache(el){
    var cur = el;
    while (cur) {
      var c = cur.__vueParentComponent;
      if (c && c.setupState && c.setupState.relationCache) return c.setupState.relationCache;
      cur = cur.parentElement;
    }
    return null;
  }

  function setNativeInput(input, val){
    var setter = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value').set;
    setter.call(input, val);
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
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

  function injectOption(rel, hit){
    var cache = findRelationCache(rel);
    if (cache) {
      var key = 'quoteProduct';
      // relation key may vary; try common
      var list = cache.quoteProduct || cache[Object.keys(cache).find(function(k){ return /quote/i.test(k); }) || ''];
      if (!Array.isArray(list)) {
        // assign fresh
        try { cache.quoteProduct = [hit]; } catch (e) {}
        return;
      }
      if (!list.some(function(o){ return String(o.id) === String(hit.id); })) {
        list.unshift(hit);
      }
    }
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
    if (rel.getAttribute('data-quote-ui') === '2') return;
    rel.setAttribute('data-quote-ui', '2');

    // remove older v1 chips if any
    rel.querySelectorAll('.rel-chips-live').forEach(function(n){ n.remove(); });

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

    // nicer placeholder on select input
    var ph = rel.querySelector('.el-select__placeholder, .el-select .el-select__input');
    var wrap = rel.querySelector('.el-select__wrapper');
    if (wrap && !wrap.getAttribute('data-ph')) {
      wrap.setAttribute('data-ph', '1');
    }
  }

  function syncActive(rel, label){
    var text = label || '';
    if (!text) {
      var wrap = rel.querySelector('.el-select__wrapper');
      if (wrap) text = (wrap.textContent || '').trim();
    }
    rel.querySelectorAll('.rel-chip-live').forEach(function(b){
      var on = text.indexOf(b.textContent) >= 0 || text.indexOf(b.title) >= 0;
      b.classList.toggle('on', !!on);
    });
  }

  function pick(rel, chip, full, btn){
    rel.querySelectorAll('.rel-chip-live').forEach(function(b){ b.classList.remove('on'); });
    btn.classList.add('on');

    var searchWrap = rel.querySelector('.rel-search');
    var input = searchWrap && searchWrap.querySelector('input');
    var searchBtn = searchWrap && searchWrap.querySelector('button');
    if (input && searchBtn) {
      setNativeInput(input, full);
      try { searchBtn.click(); } catch (e) {}
    }

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
        btn.classList.remove('on');
        return;
      }
      injectOption(rel, hit);
      emitSelect(rel, hit.id);
      setTimeout(function(){
        injectOption(rel, hit);
        emitSelect(rel, hit.id);
        syncActive(rel, String(hit.label || full));
      }, 380);
    }).catch(function(e){
      console.warn('[quote-pick]', e);
      alert('加载品种失败，请确认已登录后台');
      btn.classList.remove('on');
    });
  }

  function scan(){
    document.querySelectorAll('.admin-form .rel, .field .rel').forEach(enhance);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ setTimeout(scan, 400); });
  else setTimeout(scan, 400);
  setInterval(scan, 1000);
})();
""".lstrip()

(nuxt / "quote-pick.fix.css").write_text(css, encoding="utf-8")
(nuxt / "quote-pick.fix.js").write_text(js, encoding="utf-8")
print("wrote assets v2")

# hint
fields = root / "config/admin_resource_fields.php"
if fields.exists():
    t = fields.read_text(encoding="utf-8")
    new = "用于「价格行情资讯」：点下方标签快捷选择，或在框内搜索品种名。其它栏目子集请在「所属分类」选子类。"
    nt = t
    for o in [
        "「价格行情资讯」按品种挂文：选 DMC/生胶等。其它栏目子集请在「所属分类」选子类（如 网评 › 日评、产能产量 › 开工率）",
        "「价格行情资讯」按品种选文：选DMC/生胶等。其它栏目子集请在「所属分类」选子类（如网评、日评、产能产量、开工率）",
        "「价格行情资讯」按品种挂文：选 DMC/生胶等。其它栏目子集请在「所属分类」选子类（如 网评 › 日评、产能产量 › 开工率）",
    ]:
        if o in nt:
            nt = nt.replace(o, new)
    if "点下方标签快捷选择" not in nt and "quote_product_id" in nt:
        nt = re.sub(
            r"('name'\s*=>\s*'quote_product_id'[\s\S]{0,320}?'hint'\s*=>\s*)'[^']*'",
            r"\1'" + new + "'",
            nt,
            count=1,
        )
    if nt != t:
        fields.write_text(nt, encoding="utf-8")
        print("hint updated")
    else:
        print("hint ok")

tag_css = 'quote-pick.fix.css?v=2'
tag_js = 'quote-pick.fix.js?v=2'
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

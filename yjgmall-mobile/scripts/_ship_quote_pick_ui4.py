#!/usr/bin/env python3
"""行情品种：按线上真实 DOM（原生 select + l.value）重做，不依赖 el-select / 额外鉴权。"""
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
.admin-form .rel > select,
.admin-form .rel .qp-select{
  width:100%;max-width:100%;height:42px;box-sizing:border-box;
  border:1px solid #dbe3ef;border-radius:10px;padding:0 12px;
  font:inherit;font-size:14px;background:#fff;color:#0f172a;
}
.admin-form .rel > select:focus,
.admin-form .rel .qp-select:focus{
  outline:none;border-color:#e85d04;box-shadow:0 0 0 3px rgba(232,93,4,.14);
}
.rel-chips-live{
  display:flex;flex-wrap:wrap;gap:8px;margin:0;
  padding-top:4px;border-top:1px dashed #e2e8f0;
}
.rel-chips-live::before{
  content:"常用品种（点选即可）";flex:0 0 100%;font-size:11px;color:#94a3b8;margin:2px 0;
}
.rel-chip-live{
  border:1px solid #e2e8f0;background:#fff;color:#475569;
  border-radius:999px;padding:7px 13px;font:inherit;font-size:12.5px;font-weight:600;
  cursor:pointer;line-height:1.2;
}
.rel-chip-live:hover{border-color:#fdba74;background:#fff7ed;color:#c2410c}
.rel-chip-live.on{
  border-color:#e85d04;background:#fff7ed;color:#c2410c;
  box-shadow:0 0 0 2px rgba(232,93,4,.14);
}
""".strip() + "\n"

js = r"""
(function(){
  if (window.__quotePickUiV4) return;
  window.__quotePickUiV4 = 1;

  // 与库中 quote_products 一致（启用中的行情品种）
  var PRODUCTS = [
    {id:1,  name:'二甲基环硅氧烷混合物（DMC）', chip:'DMC'},
    {id:2,  name:'八甲基环四硅氧烷（D4）', chip:'D4'},
    {id:4,  name:'甲基乙烯基硅橡胶（生胶）', chip:'生胶'},
    {id:3,  name:'107室温硫化硅橡胶（107胶）', chip:'107胶'},
    {id:5,  name:'二甲基硅油', chip:'二甲基硅油'},
    {id:10, name:'外资品牌二甲基硅油', chip:'外资硅油'},
    {id:11, name:'硅橡胶混炼胶', chip:'混炼胶'},
    {id:8,  name:'气相法二氧化硅（气相白炭黑）', chip:'气相白炭黑'},
    {id:12, name:'沉淀二氧化硅（沉淀白炭黑）', chip:'沉淀白炭黑'},
    {id:13, name:'421#金属硅', chip:'421#金属硅'},
    {id:14, name:'工业硅期货', chip:'工业硅期货'},
    {id:15, name:'一氯甲烷', chip:'一氯甲烷'},
    {id:16, name:'有机硅助剂', chip:'有机硅助剂'},
    {id:17, name:'裂解料二甲基硅油', chip:'裂解料硅油'},
    {id:18, name:'废硅胶毛边', chip:'废硅胶毛边'}
  ];

  function findAdminForm(el){
    var cur = el;
    while (cur) {
      var c = cur.__vueParentComponent;
      if (c && c.setupState) {
        var st = c.setupState;
        // 线上编译后 model 叫 l，选项缓存叫 d
        if (st.l && st.l.value && typeof st.l.value === 'object' && st.d) {
          return { st: st, model: st.l.value, opts: st.d, load: st.ne || st.M };
        }
        if (st.model && typeof st.model === 'object' && ('quote_product_id' in st.model || st.relationCache)) {
          return { st: st, model: st.model, opts: st.relationCache || st.d, load: st.loadRelation || st.ne };
        }
      }
      cur = cur.parentElement;
    }
    return null;
  }

  function ensureOptions(ctx){
    if (!ctx || !ctx.opts) return;
    var rows = PRODUCTS.map(function(p){
      return { id: p.id, label: p.name + ' #' + p.id };
    });
    try {
      ctx.opts.quoteProduct = rows;
    } catch (e) {}
  }

  function setValue(rel, id){
    var ctx = findAdminForm(rel);
    var sid = String(id);
    if (ctx && ctx.model) {
      ctx.model.quote_product_id = sid;
      // 有的版本用数字
      try {
        var n = Number(id);
        if (String(n) === sid) ctx.model.quote_product_id = n;
      } catch (e) {}
      // 再写一遍字符串，兼容 option value 字符串化
      ctx.model.quote_product_id = sid;
      ensureOptions(ctx);
    }
    var sel = rel.querySelector('select');
    if (sel) {
      // 确保有对应 option
      var has = Array.prototype.some.call(sel.options, function(o){ return String(o.value) === sid; });
      if (!has) {
        var p = PRODUCTS.find(function(x){ return String(x.id) === sid; });
        var opt = document.createElement('option');
        opt.value = sid;
        opt.textContent = p ? (p.name + ' #' + p.id) : ('#' + sid);
        sel.appendChild(opt);
      }
      sel.value = sid;
      sel.dispatchEvent(new Event('input', { bubbles: true }));
      sel.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  function syncChips(rel){
    var ctx = findAdminForm(rel);
    var id = '';
    if (ctx && ctx.model && ctx.model.quote_product_id != null && ctx.model.quote_product_id !== '') {
      id = String(ctx.model.quote_product_id);
    } else {
      var sel = rel.querySelector('select');
      if (sel && sel.value) id = String(sel.value);
    }
    rel.querySelectorAll('.rel-chip-live').forEach(function(b){
      b.classList.toggle('on', String(b.getAttribute('data-id')) === id);
    });
  }

  function enhance(rel){
    if (!rel) return;
    var field = rel.closest('.field');
    var label = field ? ((field.querySelector('label')||{}).textContent || '') : '';
    var hint = field ? ((field.querySelector('.hint')||{}).textContent || '') : '';
    var isQuote = /行情品种/.test(label) || /价格行情资讯|行情品种|点下方标签|快捷选择/.test(hint);
    var search = rel.querySelector('.rel-search');
    if (search) search.style.display = 'none';
    if (!isQuote) return;
    if (rel.getAttribute('data-quote-ui') === '4') {
      // 仍确保选项齐全
      ensureOptions(findAdminForm(rel));
      return;
    }
    rel.setAttribute('data-quote-ui', '4');
    rel.querySelectorAll('.rel-chips-live').forEach(function(n){ n.remove(); });

    var ctx = findAdminForm(rel);
    ensureOptions(ctx);

    // 把原生 select 选项补全（若 Vue 还没刷出来）
    var sel = rel.querySelector('select');
    if (sel) {
      PRODUCTS.forEach(function(p){
        var sid = String(p.id);
        var exists = Array.prototype.some.call(sel.options, function(o){ return String(o.value) === sid; });
        if (!exists) {
          var opt = document.createElement('option');
          opt.value = sid;
          opt.textContent = p.name + ' #' + p.id;
          sel.appendChild(opt);
        }
      });
      sel.addEventListener('change', function(){ syncChips(rel); });
    }

    var chips = document.createElement('div');
    chips.className = 'rel-chips-live';
    PRODUCTS.forEach(function(p){
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'rel-chip-live';
      btn.textContent = p.chip;
      btn.title = p.name;
      btn.setAttribute('data-id', String(p.id));
      btn.addEventListener('click', function(){
        setValue(rel, p.id);
        syncChips(rel);
      });
      chips.appendChild(btn);
    });
    rel.appendChild(chips);
    syncChips(rel);

    // 触发一次原组件空搜，尽量用接口再刷一遍（有登录时）
    if (ctx && typeof ctx.load === 'function') {
      try { ctx.load('quoteProduct'); } catch (e) {}
      try { ctx.load('quoteProduct', ''); } catch (e2) {}
    } else if (search) {
      var btn = search.querySelector('button');
      if (btn) try { btn.click(); } catch (e3) {}
    }
  }

  function scan(){
    document.querySelectorAll('.admin-form .rel, .field .rel').forEach(enhance);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ setTimeout(scan, 300); });
  else setTimeout(scan, 300);
  setInterval(scan, 1000);
})();
""".lstrip()

(nuxt / "quote-pick.fix.css").write_text(css, encoding="utf-8")
(nuxt / "quote-pick.fix.js").write_text(js, encoding="utf-8")
print("wrote v4 assets")

# 顺手把 BQtXj7ai 初始加载 limit 40→100，并保证空搜也能列出
chunk = nuxt / "BQtXj7ai.js"
if chunk.exists():
    t = chunk.read_text(encoding="utf-8", errors="ignore")
    nt = t
    nt2 = nt.replace(
        "query:{q:v||void 0,limit:40}",
        "query:{q:v||void 0,limit:100}",
    )
    if nt2 != nt:
        chunk.write_text(nt2, encoding="utf-8")
        print("patched BQtXj7ai limit")
    else:
        print("BQtXj7ai limit pattern miss")

tag_css = "quote-pick.fix.css?v=4"
tag_js = "quote-pick.fix.js?v=4"
for html in list((root / "public").rglob("admin/**/index.html")) + [
    root / "public/index.html",
    root / "public/200.html",
]:
    if not html.exists():
        continue
    h = html.read_text(encoding="utf-8", errors="ignore")
    if "quote-pick.fix.js" not in h:
        if "</head>" in h:
            block = f'<link rel="stylesheet" href="/_nuxt/{tag_css}">\n<script src="/_nuxt/{tag_js}" defer></script>\n'
            html.write_text(h.replace("</head>", block + "</head>", 1), encoding="utf-8")
            print("link", html)
        continue
    h2 = re.sub(r"quote-pick\.fix\.css\?v=\d+", tag_css, h)
    h2 = re.sub(r"quote-pick\.fix\.js\?v=\d+", tag_js, h2)
    if h2 != h:
        html.write_text(h2, encoding="utf-8")
        print("bump", html)

print("ok")

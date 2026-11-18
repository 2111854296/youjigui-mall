#!/usr/bin/env python3
"""最终修复：1) 去掉线上 chunk 的双重搜索 2) chips 用原生 select change 写入 3) 去重选项"""
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn")
nuxt = root / "public" / "_nuxt"
chunk = nuxt / "BQtXj7ai.js"

# --- patch AdminResourceForm chunk: remove rel-search UI ---
if chunk.exists():
    t = chunk.read_text(encoding="utf-8", errors="ignore")
    # exact compiled fragment from live
    old = (
        'e.type==="relation"?(o(),i("div",ct,[p("div",mt,[D(p("input",{"onUpdate:modelValue":t=>s(U)[e.relation||""]=t,type:"search",placeholder:"搜索…",disabled:b(e),onKeyup:Ea(t=>M(e.relation||""),["enter"])},null,40,vt),[[ue,s(U)[e.relation||""]]]),p("button",{type:"button",disabled:b(e),onClick:t=>M(e.relation||"")},"搜",8,pt)]),D(p("select",'
    )
    new = (
        'e.type==="relation"?(o(),i("div",ct,[D(p("select",'
    )
    if old in t:
        t = t.replace(old, new, 1)
        print("removed rel-search from chunk")
    else:
        print("rel-search pattern miss")

    # ensure limit 100
    t2 = t.replace("query:{q:v||void 0,limit:40}", "query:{q:v||void 0,limit:100}")
    t2 = t2.replace("query:{q:v||void 0,limit:100}", "query:{q:v||void 0,limit:100}")  # idempotent
    if "limit:100" not in t2:
        # already patched maybe with 100
        pass
    chunk.write_text(t2, encoding="utf-8")
    print("chunk written", "limit100", "limit:100" in t2)

css = r"""
.admin-form .rel-search{display:none!important}
.admin-form .rel{
  display:grid!important;gap:12px!important;
  max-width:640px;padding:12px 14px;border-radius:12px;
  background:linear-gradient(180deg,#fafbfc 0%,#f4f7fb 100%);
  border:1px solid #e8eef6;box-sizing:border-box;
}
.admin-form .rel > select{
  width:100%;height:42px;box-sizing:border-box;
  border:1px solid #dbe3ef;border-radius:10px;padding:0 12px;
  font:inherit;font-size:14px;background:#fff;color:#0f172a;
}
.admin-form .rel > select:focus{
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
  if (window.__quotePickUiV5) return;
  window.__quotePickUiV5 = 1;

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

  function isQuoteRel(rel){
    var field = rel.closest('.field');
    var label = field ? ((field.querySelector('label')||{}).textContent || '') : '';
    var hint = field ? ((field.querySelector('.hint')||{}).textContent || '') : '';
    return /行情品种/.test(label) || /价格行情资讯|行情品种|点下方标签|快捷选择/.test(hint);
  }

  function setSelectValue(sel, id){
    if (!sel) return false;
    var sid = String(id);
    // 若选项里没有，补一条（避免接口未返回时点不动）
    var has = false;
    for (var i=0;i<sel.options.length;i++){
      if (String(sel.options[i].value) === sid) { has = true; break; }
    }
    if (!has) {
      var p = PRODUCTS.find(function(x){ return String(x.id) === sid; });
      var opt = document.createElement('option');
      opt.value = sid;
      opt.textContent = p ? (p.name + ' #' + p.id) : ('#' + sid);
      sel.appendChild(opt);
    }
    // 用 Vue v-model select 能吃到的方式改值
    sel.value = sid;
    for (var j=0;j<sel.options.length;j++){
      sel.options[j].selected = (String(sel.options[j].value) === sid);
    }
    sel.dispatchEvent(new Event('input', { bubbles: true }));
    sel.dispatchEvent(new Event('change', { bubbles: true }));
    return String(sel.value) === sid;
  }

  function syncChips(rel){
    var sel = rel.querySelector('select');
    var id = sel ? String(sel.value || '') : '';
    rel.querySelectorAll('.rel-chip-live').forEach(function(b){
      b.classList.toggle('on', String(b.getAttribute('data-id')) === id && id !== '' && id !== '请选择' && id !== 'null');
    });
  }

  function ensureProductOptions(sel){
    if (!sel) return;
    var existing = {};
    for (var i=0;i<sel.options.length;i++){
      existing[String(sel.options[i].value)] = true;
    }
    PRODUCTS.forEach(function(p){
      var sid = String(p.id);
      if (existing[sid]) return;
      var opt = document.createElement('option');
      opt.value = sid;
      opt.textContent = p.name + ' #' + p.id;
      sel.appendChild(opt);
    });
  }

  function enhance(rel){
    if (!rel || !isQuoteRel(rel)) {
      var s = rel && rel.querySelector('.rel-search');
      if (s) s.style.display = 'none';
      return;
    }
    var search = rel.querySelector('.rel-search');
    if (search) search.style.display = 'none';

    var sel = rel.querySelector('select');
    ensureProductOptions(sel);

    if (rel.getAttribute('data-quote-ui') === '5') {
      syncChips(rel);
      return;
    }
    rel.setAttribute('data-quote-ui', '5');
    rel.querySelectorAll('.rel-chips-live').forEach(function(n){ n.remove(); });

    if (sel && !sel.__qpBound) {
      sel.__qpBound = 1;
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
      btn.addEventListener('click', function(ev){
        ev.preventDefault();
        var ok = setSelectValue(sel, p.id);
        syncChips(rel);
        if (!ok) {
          // 极端情况再试一次
          setTimeout(function(){ setSelectValue(sel, p.id); syncChips(rel); }, 50);
        }
      });
      chips.appendChild(btn);
    });
    rel.appendChild(chips);
    syncChips(rel);
  }

  function scan(){
    document.querySelectorAll('.admin-form .rel, .field .rel').forEach(enhance);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ setTimeout(scan, 250); });
  else setTimeout(scan, 250);
  setInterval(scan, 1200);
})();
""".lstrip()

(nuxt / "quote-pick.fix.css").write_text(css, encoding="utf-8")
(nuxt / "quote-pick.fix.js").write_text(js, encoding="utf-8")
print("wrote fix v5")

tag_css = "quote-pick.fix.css?v=5"
tag_js = "quote-pick.fix.js?v=5"
for html in list((root / "public").rglob("admin/**/index.html")) + [
    root / "public/index.html",
    root / "public/200.html",
]:
    if not html.exists():
        continue
    h = html.read_text(encoding="utf-8", errors="ignore")
    if "quote-pick.fix.js" not in h:
        continue
    h2 = re.sub(r"quote-pick\.fix\.css\?v=\d+", tag_css, h)
    h2 = re.sub(r"quote-pick\.fix\.js\?v=\d+", tag_js, h2)
    if h2 != h:
        html.write_text(h2, encoding="utf-8")
        print("bump", html)

# bust chunk cache by renaming? HTML references BQtXj7ai.js without hash query - nuxt uses content hash in filename already
print("ok")

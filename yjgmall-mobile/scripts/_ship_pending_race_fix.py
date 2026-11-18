#!/usr/bin/env python3
"""Hotfix articles pending filter race: stale unfiltered responses overwrite pending."""
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn")
chunk = root / "public/_nuxt/DLdwU6hU.js"
s = chunk.read_text(encoding="utf-8", errors="ignore")

old_ol = (
    'function Ol(){/*pendingFix*/const _qs=d.query.status,_es=(_qs==null||_qs===""?"":String(Array.isArray(_qs)?_qs[0]:_qs))||c.value;'
)
new_ol = (
    'function Ol(){/*pendingFix2*/'
    'let _qs=d.query.status;'
    'if(_qs==null||_qs===""){try{_qs=new URLSearchParams(location.search).get("status")||""}catch(e){_qs=""}}'
    'const _es=(_qs==null||_qs===""?"":String(Array.isArray(_qs)?_qs[0]:_qs))||c.value;'
)
if old_ol in s:
    s = s.replace(old_ol, new_ol, 1)
    print("patched Ol")
elif "pendingFix2" in s:
    print("Ol already pendingFix2")
else:
    # try without pendingFix marker
    alt = 'function Ol(){const _qs=d.query.status,_es='
    if alt in s:
        s = s.replace(
            'function Ol(){const _qs=d.query.status,_es=',
            new_ol.replace("function Ol(){/*pendingFix2*/", "function Ol(){/*pendingFix2*/").split("const _es=")[0]
            + "const _es=",
            1,
        )
        print("patched Ol alt")
    else:
        print("WARN Ol pattern missing")

old_fetch = "()=>o(`/admin/resources/${r.value}`,{query:Ol()})"
new_fetch = (
    "async()=>{if(typeof window.__artListSeq!=='number')window.__artListSeq=0;"
    "const my=++window.__artListSeq;const qobj=Ol();"
    "const res=await o(`/admin/resources/${r.value}`,{query:qobj});"
    "if(my!==window.__artListSeq)return Dt.value??res;"
    "if(qobj&&qobj.status&&Array.isArray(res&&res.data)){"
    "const want=String(qobj.status);"
    "const filtered=res.data.filter(x=>String((x&&x.status)||'')===want);"
    "if(filtered.length!==res.data.length){"
    "fetch('http://127.0.0.1:7691/ingest/950e390d-371a-4a7d-9f1d-32686b206508',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'d7e34e'},body:JSON.stringify({sessionId:'d7e34e',runId:'post-fix',hypothesisId:'C',location:'chunk:stale-filter',message:'filtered mismatched rows',data:{want,before:res.data.length,after:filtered.length},timestamp:Date.now()})}).catch(()=>{});"
    "return Object.assign({},res,{data:filtered});"
    "}}"
    "fetch('http://127.0.0.1:7691/ingest/950e390d-371a-4a7d-9f1d-32686b206508',{method:'POST',headers:{'Content-Type':'application/json','X-Debug-Session-Id':'d7e34e'},body:JSON.stringify({sessionId:'d7e34e',runId:'post-fix',hypothesisId:'C',location:'chunk:fetch-done',message:'articles fetch kept',data:{status:qobj&&qobj.status||null,seq:my,latest:window.__artListSeq,n:(res&&res.data&&res.data.length)||0},timestamp:Date.now()})}).catch(()=>{});"
    "return res;}"
)
if old_fetch in s:
    s = s.replace(old_fetch, new_fetch, 1)
    print("patched fetch")
elif "window.__artListSeq" in s:
    print("fetch already patched")
else:
    print("WARN fetch pattern missing")

old_items = "Dl=w(()=>Dt.value?.data||[])"
new_items = (
    "Dl=w(()=>{const rows=Dt.value?.data||[];"
    "let want='';"
    "try{"
    "const qs=d.query.status;"
    "want=(qs==null||qs===''?(new URLSearchParams(location.search).get('status')||''):String(Array.isArray(qs)?qs[0]:qs))||c.value||'';"
    "}catch(e){want=c.value||''}"
    "return r.value==='articles'&&want?rows.filter(x=>String((x&&x.status)||'')===String(want)):rows})"
)
if old_items in s:
    s = s.replace(old_items, new_items, 1)
    print("patched items")
elif "r.value==='articles'&&want" in s:
    print("items already patched")
else:
    print("WARN items pattern missing")

old_sel = (
    '"onUpdate:modelValue":n[1]||(n[1]=p=>fe(c)?c.value=p:null),clearable:"",placeholder:"全部状态",class:"filter-sel"'
)
new_sel = (
    '"onUpdate:modelValue":n[1]||(n[1]=p=>{fe(c)?c.value=p:null;Je&&Je()}),clearable:"",placeholder:"全部状态",class:"filter-sel"'
)
if old_sel in s:
    s = s.replace(old_sel, new_sel, 1)
    print("patched status select change")
elif "Je&&Je()" in s:
    print("status select already patched")
else:
    print("WARN status select pattern missing")

chunk.write_text(s, encoding="utf-8")
print("wrote", chunk)

# bump debug hook version + mark post-fix
dbg = root / "public/_nuxt/admin-pending-debug.js"
if dbg.exists():
    t = dbg.read_text(encoding="utf-8")
    t = t.replace("runId: 'pre-fix'", "runId: 'post-fix'")
    t = t.replace('runId: "pre-fix"', 'runId: "post-fix"')
    dbg.write_text(t, encoding="utf-8")
    print("bumped debug runId")

for html in list((root / "public").rglob("admin/**/index.html")) + [
    root / "public/index.html",
    root / "public/200.html",
]:
    if not html.exists():
        continue
    h = html.read_text(encoding="utf-8", errors="ignore")
    h2 = h
    if "admin-pending-debug.js" in h2:
        h2 = re.sub(
            r"/_nuxt/admin-pending-debug\.js\?v=\d+",
            "/_nuxt/admin-pending-debug.js?v=2",
            h2,
        )
    # cache-bust the main chunk via a tiny query on debug script only is not enough;
    # users Ctrl+F5. Also bump ops css if present.
    if h2 != h:
        html.write_text(h2, encoding="utf-8")
        print("bump", html)

print("ok")

#!/usr/bin/env python3
"""Hotpatch similar URL/filter race issues beyond status=pending."""
from pathlib import Path
import time

nuxt = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
chunk = nuxt / "DLdwU6hU.js"
layout = nuxt / "DGDn1Ahr.js"
dash = nuxt / "DV6p7Vej.js"
steps = []


def backup(p: Path, text: str):
    bak = p.with_suffix(p.suffix + ".bak_similar_%d" % int(time.time()))
    bak.write_text(text, encoding="utf-8")
    return bak.name


# ---------------- resource chunk ----------------
s = chunk.read_text(encoding="utf-8", errors="ignore")
orig = s

# 1) honor tab: remove pe()
old_ul = "function Ul(s){K.value=s,c.value=s===\"all\"?\"\":s,z.value=1,Be({path:d.path,query:{...d.query,tab:s===\"all\"?void 0:s,status:void 0}}),pe()}"
new_ul = "function Ul(s){K.value=s,c.value=s===\"all\"?\"\":s,z.value=1,X.value=[],Be({path:d.path,query:{...d.query,tab:s===\"all\"?void 0:s,status:void 0},replace:!0})}"
if old_ul in s:
    s = s.replace(old_ul, new_ul, 1)
    steps.append("honor-no-pe")
else:
    i = s.find("function Ul(s){")
    j = s.find("}async function jl", i) if i >= 0 else -1
    if i >= 0 and j > i:
        body = s[i:j]
        if ",pe()" in body:
            body2 = body.replace(",pe()", "", 1)
            body2 = body2.replace(
                "Be({path:d.path,query:{...d.query,tab:s===\"all\"?void 0:s,status:void 0}})",
                "Be({path:d.path,query:{...d.query,tab:s===\"all\"?void 0:s,status:void 0},replace:!0})",
                1,
            )
            if "X.value=[]" not in body2:
                body2 = body2.replace("z.value=1,", "z.value=1,X.value=[],", 1)
            s = s[:i] + body2 + s[j:]
            steps.append("honor-no-pe-soft")
        else:
            steps.append("honor-already")
    else:
        steps.append("WARN-honor")

# 2) toolbar: hide module when mt, hide position when group locked
# current: Ll=w(()=>qt.value.filter(s=>!(s.type!=="select"||!Object.keys(s.options||{}).length||["id","title","name","username"].includes(s.key)||Le.value&&s.key==="position")))
old_tb = 'Ll=w(()=>qt.value.filter(s=>!(s.type!=="select"||!Object.keys(s.options||{}).length||["id","title","name","username"].includes(s.key)||Le.value&&s.key==="position")))'
new_tb = (
    'Ll=w(()=>qt.value.filter(s=>!(s.type!=="select"||!Object.keys(s.options||{}).length||'
    '["id","title","name","username"].includes(s.key)||'
    '(Le.value||V.value)&&s.key==="position"||'
    'mt.value&&s.key==="module")))'
)
if old_tb in s:
    s = s.replace(old_tb, new_tb, 1)
    steps.append("toolbar-lock")
elif "mt.value&&s.key===\"module\"" in s:
    steps.append("toolbar-already")
else:
    steps.append("WARN-toolbar")

# 3) show group tag; prefer group over position tag
old_tags = (
    'e(Le)?(m(),G(Ze,{key:4,type:"info",effect:"plain",class:"filter-tag"},'
    '{default:_(()=>[A("位置："+$(e(We)),1)]),_:1})):M("",!0)'
)
new_tags = (
    'e(V)?(m(),G(Ze,{key:4,type:"info",effect:"plain",class:"filter-tag"},'
    '{default:_(()=>[A("区域："+$(e(qe)||e(V)),1)]),_:1})):'
    'e(Le)?(m(),G(Ze,{key:5,type:"info",effect:"plain",class:"filter-tag"},'
    '{default:_(()=>[A("位置："+$(e(We)),1)]),_:1})):M("",!0)'
)
# We is positionLabel; groupLabel might be qe - check
# From source groupLabel computed - find live name
# Earlier dump had We for position. Search group label computed.
if "区域：" in s:
    steps.append("group-tag-already")
elif old_tags in s:
    # try detect groupLabel var near positionLabel
    # safer: use V.value code directly if qe unknown
    new_tags_safe = (
        'e(V)?(m(),G(Ze,{key:4,type:"info",effect:"plain",class:"filter-tag"},'
        '{default:_(()=>[A("区域："+$(e(V)),1)]),_:1})):'
        'e(Le)?(m(),G(Ze,{key:5,type:"info",effect:"plain",class:"filter-tag"},'
        '{default:_(()=>[A("位置："+$(e(We)),1)]),_:1})):M("",!0)'
    )
    s = s.replace(old_tags, new_tags_safe, 1)
    steps.append("group-tag")
else:
    steps.append("WARN-group-tag")

# 4) broaden Dl items filter for vip/module/position
dl_start = s.find("Dl=w(()=>{")
dl_end = s.find("}),Mt=", dl_start) if dl_start >= 0 else -1
if dl_start >= 0 and dl_end > dl_start:
    dl_end += 2
    new_dl = (
        "Dl=w(()=>{const rows=Dt.value?.data||[];"
        "function _q(k){const v=d.query[k];if(v!=null&&v!=='')return String(Array.isArray(v)?v[0]:v);"
        "try{return new URLSearchParams(location.search).get(k)||''}catch(e){return ''}}"
        "const st=_q('status')||c.value||'';"
        "const vv=_q('vip')||a.value||'';"
        "const mm=_q('module')||v.value||'';"
        "const gg=_q('group')||V.value||'';"
        "const pp=gg?'':(_q('position')||C.value||'');"
        "let out=rows;"
        "if(st)out=out.filter(x=>String((x&&x.status)||'')===String(st));"
        "if(vv==='1')out=out.filter(x=>Number((x&&x.vip_level)||0)>0);"
        "if(mm)out=out.filter(x=>String((x&&x.module)||'')===String(mm));"
        "if(pp){if(pp==='home'||pp==='home_banner')out=out.filter(x=>['home','home_banner'].includes(String((x&&x.position)||'')));"
        "else out=out.filter(x=>String((x&&x.position)||'')===String(pp));}"
        "return out})"
    )
    s = s[:dl_start] + new_dl + s[dl_end:]
    steps.append("items-multi")
else:
    steps.append("WARN-items")

# 5) broaden fetcher post-filter similarly (keep seq)
fetch_start = s.find("async()=>{if(typeof window.__artListSeq")
if fetch_start >= 0:
    j = s.find("return res;}", fetch_start)
    if j > fetch_start and j - fetch_start < 2500:
        fetch_end = j + len("return res;}")
        new_fetch = (
            "async()=>{"
            "if(typeof window.__artListSeq!=='number')window.__artListSeq=0;"
            "const my=++window.__artListSeq;"
            "const qobj=Ol();"
            "const res=await o(`/admin/resources/${r.value}`,{query:qobj});"
            "if(my!==window.__artListSeq)return Dt.value??res;"
            "if(qobj&&Array.isArray(res&&res.data)){"
            "let out=res.data;"
            "if(qobj.status){const want=String(qobj.status);out=out.filter(x=>String((x&&x.status)||'')===want)}"
            "if(qobj.vip==='1'||qobj.vip===1)out=out.filter(x=>Number((x&&x.vip_level)||0)>0);"
            "if(qobj.module){const want=String(qobj.module);out=out.filter(x=>String((x&&x.module)||'')===want)}"
            "if(qobj.position){const want=String(qobj.position);"
            "if(want==='home'||want==='home_banner')out=out.filter(x=>['home','home_banner'].includes(String((x&&x.position)||'')));"
            "else out=out.filter(x=>String((x&&x.position)||'')===want)}"
            "if(out.length!==res.data.length)return Object.assign({},res,{data:out});"
            "}"
            "return res;}"
        )
        s = s[:fetch_start] + new_fetch + s[fetch_end:]
        steps.append("fetcher-multi")
    else:
        steps.append("WARN-fetcher")
else:
    steps.append("WARN-fetcher-missing")

# 6) ae: clear conflicting colFilters for module/position when URL locked
ae_start = s.find("function ae(){/*pendingFinalSync*/")
if ae_start < 0:
    ae_start = s.find("function ae(){")
ae_end = s.find("}ae();", ae_start) if ae_start >= 0 else -1
if ae_start >= 0 and ae_end > ae_start:
    ae_end += 1
    new_ae = (
        "function ae(){/*pendingFinalSync*/"
        "function _q(k){const v=d.query[k];"
        "if(v!=null&&v!==\"\")return String(Array.isArray(v)?v[0]:v);"
        "try{return new URLSearchParams(location.search).get(k)||\"\"}catch(e){return \"\"}}"
        "c.value=_q(\"status\");"
        "a.value=_q(\"vip\");"
        "v.value=_q(\"module\");"
        "C.value=_q(\"position\");"
        "V.value=_q(\"group\");"
        "K.value=_q(\"tab\")||\"all\";"
        "r.value===\"company-honors\"&&K.value!==\"all\"&&!c.value&&"
        "(c.value=K.value===\"rejected\"?\"rejected\":K.value===\"expired\"?\"expired\":"
        "K.value===\"pending\"?\"pending\":K.value===\"offline\"?\"offline\":c.value);"
        "if(v.value&&Y&&\"module\"in Y)Y.module=\"\";"
        "if((C.value||V.value)&&Y&&\"position\"in Y)Y.position=\"\";"
        "z.value=1;X.value=[]}"
    )
    s = s[:ae_start] + new_ae + s[ae_end:]
    steps.append("ae-clear-cf")
else:
    steps.append("WARN-ae")

if s != orig:
    print("chunk bak", backup(chunk, orig))
    chunk.write_text(s, encoding="utf-8")
    print("chunk wrote delta", len(s) - len(orig))
else:
    print("chunk NO CHANGES")

# ---------------- layout menuMatch ----------------
lt = layout.read_text(encoding="utf-8", errors="ignore")
lorig = lt
old_h = 'function h(v){const d=T();if(v.includes("?"))return d===v||d.startsWith(v+"&");const[k]=d.split("?");return k===v}'
new_h = (
    'function h(v){const d=T();'
    'if(v.includes("?"))return d===v||d.startsWith(v+"&");'
    'const[k]=d.split("?");'
    'if(k!==v)return!1;'
    'if(!d.includes("?"))return!0;'
    'return!m.value.some(g=>(g.items||[]).some(i=>{const t=String(i&&i.to||"");'
    'return t.includes("?")&&(d===t||d.startsWith(t+"&"))}))}'
)
if old_h in lt:
    lt = lt.replace(old_h, new_h, 1)
    print("layout bak", backup(layout, lorig))
    layout.write_text(lt, encoding="utf-8")
    steps.append("menuMatch")
    print("layout patched")
elif "return!m.value.some(g=>(g.items||[]).some" in lt:
    steps.append("menuMatch-already")
else:
    steps.append("WARN-menuMatch")
    print("WARN menuMatch pattern missing")

# ---------------- dashboard pending links ----------------
dt = dash.read_text(encoding="utf-8", errors="ignore")
dorig = dt
repls = [
    ('to:"/admin/r/users"', 'to:"/admin/r/users?status=pending"'),
    ('to:"/admin/r/supplies"', 'to:"/admin/r/supplies?status=pending"'),
    ('to:"/admin/r/purchases"', 'to:"/admin/r/purchases?status=pending"'),
]
dn = 0
for a, b in repls:
    if a in dt and b not in dt:
        dt = dt.replace(a, b, 1)
        dn += 1
if dn:
    print("dash bak", backup(dash, dorig))
    dash.write_text(dt, encoding="utf-8")
    steps.append("dash-%d" % dn)
    print("dash patched", dn)
elif 'to:"/admin/r/users?status=pending"' in dt:
    steps.append("dash-already")
else:
    steps.append("WARN-dash")

# verify
s2 = chunk.read_text(encoding="utf-8", errors="ignore")
lt2 = layout.read_text(encoding="utf-8", errors="ignore")
dt2 = dash.read_text(encoding="utf-8", errors="ignore")
_ui = s2.find("function Ul(s){")
_ul_body = s2[_ui:_ui + 280] if _ui >= 0 else ""
_di = s2.find("Dl=w(()=>{")
_dl_body = s2[_di:_di + 900] if _di >= 0 else ""
checks = {
    "honor_pe": ",pe()" in _ul_body,
    "items_vip": "vip_level" in _dl_body,
    "toolbar_module": 'mt.value&&s.key==="module"' in s2,
    "menu_prefer": "return!m.value.some(g=>(g.items||[]).some" in lt2,
    "dash_users_pending": 'to:"/admin/r/users?status=pending"' in dt2,
    "ae_pe_gone": "ae(),pe()" not in s2,
    "pendingFinal": s2.count("pendingFinal"),
}
print("steps:", ", ".join(steps))
print("checks:", checks)
bad = []
if checks["honor_pe"]:
    bad.append("honor still pe")
if not checks["items_vip"]:
    bad.append("items vip")
if not checks["toolbar_module"]:
    bad.append("toolbar")
if not checks["menu_prefer"]:
    bad.append("menuMatch")
if not checks["dash_users_pending"]:
    bad.append("dash")
print("FAIL", bad) if bad else print("PASS")

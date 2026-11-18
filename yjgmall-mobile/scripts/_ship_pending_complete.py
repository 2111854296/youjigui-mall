#!/usr/bin/env python3
"""Complete pending-filter fix for live DLdwU6hU.js."""
from pathlib import Path
import re
import time

chunk = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js")
s = chunk.read_text(encoding="utf-8", errors="ignore")
orig = s
steps = []

# ---------- 1) Ol: URL-first ----------
ol_start = s.find("function Ol(){")
if ol_start < 0:
    raise SystemExit("Ol start missing")
ol_end = s.find("const{data:Dt", ol_start)
if ol_end < 0:
    raise SystemExit("Ol end marker missing")
new_ol = (
    "function Ol(){/*pendingFinal*/"
    "function _q(k){const v=d.query[k];"
    "if(v!=null&&v!==\"\")return String(Array.isArray(v)?v[0]:v);"
    "try{return new URLSearchParams(location.search).get(k)||\"\"}catch(e){return \"\"}}"
    "const _es=_q(\"status\")||c.value||\"\";"
    "const _ev=_q(\"vip\")||a.value||\"\";"
    "const _em=_q(\"module\")||v.value||\"\";"
    "const _ep=_q(\"position\")||C.value||\"\";"
    "const _eg=_q(\"group\")||V.value||\"\";"
    "if(_es)c.value=_es;"
    "const s={q:f.value||void 0,status:_es||void 0,vip:_ev||void 0,module:_em||void 0,"
    "position:_eg?void 0:_ep||void 0,group:_eg||void 0,page:z.value,sort:E.value||void 0,order:T.value||void 0};"
    "we.value&&!ge.value&&Object.entries(D).forEach(([n,b])=>{b!==\"\"&&b!=null&&(s[n]=b)});"
    "Object.entries(Y).forEach(([n,b])=>{b!==\"\"&&b!=null&&(s[\"cf_\"+n]=b)});"
    "return s}"
)
s = s[:ol_start] + new_ol + s[ol_end:]
steps.append("Ol")

# ---------- 2) ae: location fallback ----------
ae_start = s.find("function ae(){")
if ae_start < 0:
    raise SystemExit("ae missing")
ae_end = s.find("}ae();const te=", ae_start)
if ae_end < 0:
    ae_end = s.find("}ae();", ae_start)
    if ae_end < 0:
        raise SystemExit("ae end missing")
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
    "z.value=1;X.value=[]}"
)
s = s[:ae_start] + new_ae + s[ae_end:]
steps.append("ae")

# ---------- 3) fetcher ----------
fetch_start = s.find("async()=>{if(typeof window.__artListSeq")
if fetch_start < 0:
    plain = "()=>o(`/admin/resources/${r.value}`,{query:Ol()})"
    if plain not in s:
        raise SystemExit("fetcher start missing")
    fetch_start = s.find(plain)
    fetch_end = fetch_start + len(plain)
else:
    fetch_end = -1
    i = fetch_start
    while True:
        j = s.find("return res;}", i)
        if j < 0 or j - fetch_start > 6000:
            break
        j2 = j + len("return res;}")
        if s[j2 : j2 + 12].startswith(",{watch:"):
            fetch_end = j2
            break
        i = j2
    if fetch_end < 0:
        j = s.find("return res;}", fetch_start)
        if j < 0:
            raise SystemExit("fetcher end missing")
        fetch_end = j + len("return res;}")

new_fetch = (
    "async()=>{"
    "if(typeof window.__artListSeq!=='number')window.__artListSeq=0;"
    "const my=++window.__artListSeq;"
    "const qobj=Ol();"
    "const res=await o(`/admin/resources/${r.value}`,{query:qobj});"
    "if(my!==window.__artListSeq)return Dt.value??res;"
    "if(qobj&&qobj.status&&Array.isArray(res&&res.data)){"
    "const want=String(qobj.status);"
    "const filtered=res.data.filter(x=>String((x&&x.status)||'')===want);"
    "if(filtered.length!==res.data.length)return Object.assign({},res,{data:filtered});"
    "}"
    "return res;}"
)
s = s[:fetch_start] + new_fetch + s[fetch_end:]
steps.append("fetcher")

# ---------- 4) watch + key ----------
old_watch = "{watch:[r,z,c,a,v,C,V,K,E,T],server:!1}"
new_watch = "{watch:[()=>d.fullPath,r,z,f,E,T,K],server:!1}"
if old_watch in s:
    s = s.replace(old_watch, new_watch, 1)
    steps.append("watch")
elif "{watch:[()=>d.fullPath" in s:
    steps.append("watch-already")
else:
    m = re.search(r"\{watch:\[r,z,c,[^\]]+\],server:!1\}", s)
    if m:
        s = s[: m.start()] + new_watch + s[m.end() :]
        steps.append("watch-re")
    else:
        steps.append("WARN-watch")

old_key = "`admin-res-${r.value}-${z.value}-${c.value}-${a.value}-${v.value}-${C.value}-${V.value}-${f.value}-${E.value}-${T.value}-${JSON.stringify(D)}-${JSON.stringify(Y)}-${K.value}`"
new_key = "`admin-res-${r.value}-${z.value}-${(d.query.status||c.value||'')}-${a.value}-${v.value}-${C.value}-${V.value}-${f.value}-${E.value}-${T.value}-${JSON.stringify(D)}-${JSON.stringify(Y)}-${K.value}-${d.fullPath}`"
if old_key in s:
    s = s.replace(old_key, new_key, 1)
    steps.append("key")
else:
    steps.append("WARN-key")

# ---------- 5) items ----------
dl_start = s.find("Dl=w(()=>{")
if dl_start < 0:
    raise SystemExit("Dl missing")
dl_end = s.find("}),Mt=", dl_start)
if dl_end < 0:
    raise SystemExit("Dl end missing")
dl_end += 2
new_dl = (
    "Dl=w(()=>{const rows=Dt.value?.data||[];"
    "let want='';"
    "try{const v=d.query.status;"
    "want=(v!=null&&v!==''?String(Array.isArray(v)?v[0]:v):(new URLSearchParams(location.search).get('status')||''))||c.value||''"
    "}catch(e){want=c.value||''}"
    "return want?rows.filter(x=>String((x&&x.status)||'')===String(want)):rows})"
)
s = s[:dl_start] + new_dl + s[dl_end:]
steps.append("items")

# ---------- 6) fullPath remove pe() ----------
old_fp = 'ce(()=>d.fullPath,()=>{ae(),pe(),q.value=!1,Z.value=null,Object.keys(S).forEach(s=>delete S[s]),zl()},{flush:"sync"})'
new_fp = 'ce(()=>d.fullPath,()=>{ae(),q.value=!1,Z.value=null,Object.keys(S).forEach(s=>delete S[s]),zl()},{flush:"sync"})'
if old_fp in s:
    s = s.replace(old_fp, new_fp, 1)
    steps.append("fullPath")
elif "ce(()=>d.fullPath,()=>{ae(),q.value=!1" in s:
    steps.append("fullPath-already")
else:
    m = re.search(r"ce\(\(\)=>d\.fullPath,\(\)=>\{ae\(\),pe\(\),", s)
    if m:
        s = s[: m.start()] + "ce(()=>d.fullPath,()=>{ae()," + s[m.end() :]
        steps.append("fullPath-soft")
    else:
        steps.append("WARN-fullPath")

# ---------- 7) unlock status select ----------
s2, n = re.subn(r"(e\(se\)\?\.status&&)!e\(te\)&&", r"\1", s, count=3)
if n:
    s = s2
    steps.append("unlock-select:%d" % n)
else:
    s2, n = re.subn(r"(e\(se\)\.status&&)!e\(te\)&&", r"\1", s, count=3)
    if n:
        s = s2
        steps.append("unlock-select2:%d" % n)
    else:
        steps.append("note-select-unlock")

# ---------- 8) status select handler (clean, no debug) ----------
sel_re = re.compile(
    r'"onUpdate:modelValue":n\[1\]\|\|\(n\[1\]=p=>\{.*?\}\),clearable:"",placeholder:"全部状态",class:"filter-sel"',
    re.S,
)
new_sel = (
    '"onUpdate:modelValue":n[1]||(n[1]=p=>{'
    "const next=p==null||p===\"\"?\"\":String(p);"
    "fe(c)?c.value=next:null;z.value=1;X.value=[];"
    "const q=Object.assign({},d.query);"
    "if(next)q.status=next;else delete q.status;"
    "Be({path:d.path,query:q,replace:!0});"
    '}),clearable:"",placeholder:"全部状态",class:"filter-sel"'
)
m = sel_re.search(s)
if m:
    s = s[: m.start()] + new_sel + s[m.end() :]
    steps.append("select-handler")
elif 'Be({path:d.path,query:q,replace:!0})' in s and 'placeholder:"全部状态"' in s:
    steps.append("select-ok")
else:
    steps.append("WARN-select")

# strip leftover ingest calls (best-effort, non-fatal)
while "127.0.0.1:7691/ingest" in s:
    a = s.find("fetch(")
    found = False
    while a >= 0:
        snippet = s[a : a + 80]
        if "127.0.0.1:7691/ingest" in snippet or (
            a < len(s) - 200 and "127.0.0.1:7691/ingest" in s[a : a + 200]
        ):
            # find end ).catch(()=>{});
            end_token = ").catch(()=>{});"
            b = s.find(end_token, a)
            if b > a and b - a < 1200:
                s = s[:a] + s[b + len(end_token) :]
                found = True
                break
        a = s.find("fetch(", a + 5)
    if not found:
        break
    steps.append("strip-ingest")

if s == orig:
    print("NO CHANGES?")
else:
    bak = chunk.with_suffix(chunk.suffix + ".bak_pending_%d" % int(time.time()))
    bak.write_text(orig, encoding="utf-8")
    chunk.write_text(s, encoding="utf-8")
    print("wrote", chunk, "delta", len(s) - len(orig), "bak", bak.name)

print("steps:", ", ".join(steps))
print("pendingFinal", s.count("pendingFinal"))
print("ae_pe", "ae(),pe()" in s)
print("select_locked", "e(se)?.status&&!e(te)" in s)
print("select_open", "e(se)?.status&&!e(pt)" in s)
print("fullPath_watch", "{watch:[()=>d.fullPath" in s)
print("items_want", "return want?rows.filter" in s)

dbg = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/admin-pending-debug.js")
dbg.write_text("/* pending filter debug disabled after complete fix */\n", encoding="utf-8")
print("ok")

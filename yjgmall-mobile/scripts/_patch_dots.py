#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Hotpatch QuoteKlineChart: board mode (g>=100) = admin pad + 首中尾 + dots."""
from pathlib import Path
import shutil, time

paths = [
    Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js"),
    Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js"),
    Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit8.js"),
]
ts = str(int(time.time()))
old_pad = "const Z=Math.max(rawSpan*(o.length>=100?.06:.12),Math.abs(mid0)*.0025,1);x-=Z,k+=Z}"
new_pad = "const Z=Math.max(rawSpan*(o.length>=100?.18:.12),Math.abs(mid0)*.004,1);x-=Z,k+=Z}"

old_xlab = '''_t=(()=>{const step=g<=10?1:g<=20?2:g<=40?3:g<=110?5:g<=200?10:g<=400?15:Math.max(20,Math.ceil(g/14)),MIN=72,idx=new Set([0,g-1]);for(let i=step;i<g-1;i+=step)idx.add(i);const cand=[...idx].sort((a,b)=>a-b).map(i=>({x:_[i].x,t:_[i].label})),out=[];for(let i=0;i<cand.length;i++){const lb=cand[i],isLast=i===cand.length-1;if(!out.length){out.push(lb);continue}const prev=out[out.length-1];if(lb.x-prev.x<MIN){if(isLast)out[out.length-1]=lb;continue}out.push(lb)}return out})();'''

new_xlab = '''_t=(()=>{if(g>=100&&g>=3){const marks=[0,Math.floor((g-1)/2),g-1];return marks.map(i=>({x:_[i].x,t:_[i].label}))}const step=g<=10?1:g<=20?2:g<=40?3:g<=110?5:g<=200?10:g<=400?15:Math.max(20,Math.ceil(g/14)),MIN=72,idx=new Set([0,g-1]);for(let i=step;i<g-1;i+=step)idx.add(i);const cand=[...idx].sort((a,b)=>a-b).map(i=>({x:_[i].x,t:_[i].label})),out=[];for(let i=0;i<cand.length;i++){const lb=cand[i],isLast=i===cand.length-1;if(!out.length){out.push(lb);continue}const prev=out[out.length-1];if(lb.x-prev.x<MIN){if(isLast)out[out.length-1]=lb;continue}out.push(lb)}return out})();'''

old_ret = "return{points:_,line:U,area:pt,yLeft:gt,yRight:vt,xLabels:_t,gridY:kt,yOf:M,mid:p,w:W}"
new_ret = "return{points:_,line:U,area:pt,yLeft:gt,yRight:vt,xLabels:_t,gridY:kt,yOf:M,mid:p,w:W,showDots:!P.value&&g>=100,h:g>=100?280:z}"

old_line = '''n(u).line?(l(),a("path",{key:1,d:n(u).line,fill:"none",stroke:"#2468F2","stroke-width":"2.4","stroke-linejoin":"round","stroke-linecap":"round"},null,8,At)):L("",!0)],64))'''

new_line = '''n(u).line?(l(),a("path",{key:1,d:n(u).line,fill:"none",stroke:"#2468F2","stroke-width":"2.4","stroke-linejoin":n(u).showDots?"miter":"round","stroke-linecap":n(u).showDots?"butt":"round"},null,8,At)):L("",!0),n(u).showDots?(l(),a("g",{key:2},[(l(!0),a(v,null,T(n(u).points,(t,r)=>(l(),a("circle",{key:"d"+r,cx:t.x,cy:t.y,r:"4.5",fill:"#2468F2",stroke:"#fff","stroke-width":"2"},null,8,["cx","cy"]))),128))])):L("",!0)],64))'''

# Also need yOf to use board pads when g>=100 - replace M= and use local Ht Ot zt
# Current: M=s=>H+(1-(s-x)/Y)*(z-H-O)
# Board: padT=18 padB=36 h=280
old_M = "M=s=>H+(1-(s-x)/Y)*(z-H-O)"
new_M = "Ht=(g>=100?18:H),Ot=(g>=100?36:O),zt=(g>=100?280:z),M=s=>Ht+(1-(s-x)/Y)*(zt-Ht-Ot)"

# area baseY uses tt=z-O → should use zt-Ot
old_tt = "U=_.map((s,c)=>`${c?\"L\":\"M\"}${s.x.toFixed(2)},${s.y.toFixed(2)}`).join(\" \"),tt=z-O,pt="
new_tt = "U=_.map((s,c)=>`${c?\"L\":\"M\"}${s.x.toFixed(2)},${s.y.toFixed(2)}`).join(\" \"),tt=zt-Ot,pt="

# viewBox height - find binding
# Empty return should have showDots:false
old_empty = "return{points:[],line:\"\",area:\"\",yLeft:[],yRight:[],xLabels:[],gridY:[],yOf:s=>0,mid:0,w:Math.max(vw.value||0,240)}"
new_empty = "return{points:[],line:\"\",area:\"\",yLeft:[],yRight:[],xLabels:[],gridY:[],yOf:s=>0,mid:0,w:Math.max(vw.value||0,240),showDots:!1,h:z}"

for path in paths:
    if not path.exists():
        print("skip missing", path)
        continue
    s = path.read_text(encoding="utf-8", errors="ignore")
    bak = path.with_suffix(path.suffix + ".bak_dots_" + ts)
    shutil.copy2(path, bak)
    n = s
    for name, a, b in [
        ("pad", old_pad, new_pad),
        ("M", old_M, new_M),
        ("tt", old_tt, new_tt),
        ("xlab", old_xlab, new_xlab),
        ("ret", old_ret, new_ret),
        ("line", old_line, new_line),
        ("empty", old_empty, new_empty),
    ]:
        if a not in n:
            print("MISSING", name, "in", path.name)
        else:
            n = n.replace(a, b, 1)
            print("OK", name, path.name)
    # viewBox: often `viewBox:"0 0 "+n(u).w+" "+z` or similar
    if 'viewBox:`0 0 ${' in n or 'viewBox:"0 0 "' in n or "viewBox:" in n:
        pass
    # try replace viewBox height z with chart h
    vb_old = None
    for cand in [
        'viewBox:`0 0 ${n(u).w} ${z}`',
        'viewBox:"0 0 "+n(u).w+" "+z',
        '["0 0 "+n(u).w+" "+z]',
    ]:
        if cand in n:
            vb_old = cand
            break
    # search
    idx = n.find("viewBox")
    print("VBctx", n[idx:idx+100] if idx>=0 else None)
    # in render: viewBox: "0 0 " + ...
    import re
    m = re.search(r'viewBox:[^,]+', n)
    if m:
        print("VBre", m.group()[:120])
        oldvb = m.group()
        if "n(u).h" not in oldvb and "+z" in oldvb:
            newvb = oldvb.replace("+z", "+(n(u).h||z)")
            n = n.replace(oldvb, newvb, 1)
            print("OK viewBox", path.name)
        elif "${z}" in oldvb:
            newvb = oldvb.replace("${z}", "${n(u).h||z}")
            n = n.replace(oldvb, newvb, 1)
            print("OK viewBox tpl", path.name)
    path.write_text(n, encoding="utf-8")
    print("wrote", path, "delta", len(n)-len(s))

# CSS: board height via injected stylesheet in quote-chart-expand or patch css
css = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/QuoteKlineChart.KSdW9eP7.css")
if css.exists():
    shutil.copy2(css, str(css)+".bak_dots_"+ts)
    c = css.read_text(encoding="utf-8")
    extra = ".y-col[data-v-15c55ada]{height:200px}.line-stage[data-v-15c55ada].board .y-col[data-v-15c55ada],.line-wrap[data-v-15c55ada].board .y-col[data-v-15c55ada]{height:280px}"
    # simpler: always allow 280 when svg taller - patch base y-col to min-height and use inline
    if ".board" not in c:
        c = c.replace(
            ".y-col[data-v-15c55ada]{position:relative;height:200px;pointer-events:none}",
            ".y-col[data-v-15c55ada]{position:relative;height:200px;pointer-events:none}.line-wrap[data-v-15c55ada].board .y-col[data-v-15c55ada]{height:280px}.line-wrap[data-v-15c55ada].board{min-height:332px}",
        )
        css.write_text(c, encoding="utf-8")
        print("OK css board")
    else:
        print("css already board")
print("DONE")

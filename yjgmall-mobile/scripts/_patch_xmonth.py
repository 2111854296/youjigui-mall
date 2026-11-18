#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
import shutil, time
p = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/eu_lJI6T.js")
s = p.read_text(encoding="utf-8")
ts = str(int(time.time()))

old = '''let L=[];if(h.overview&&f>=3)L=[0,Math.floor((f-1)/2),f-1].map(u=>({x:_[u].x,t:_[u].label}));else{const i=f<=10?1:f<=20?2:f<=40?3:f<=110?5:f<=200?10:f<=400?15:Math.max(20,Math.ceil(f/14)),u=72,y=new Set([0,f-1]);for(let d=i;d<f-1;d+=i)y.add(d);const m=[...y].sort((d,w)=>d-w).map(d=>({x:_[d].x,t:_[d].label}));for(let d=0;d<m.length;d++){const w=m[d],I=d===m.length-1;if(!L.length){L.push(w);continue}const O=L[L.length-1];if(w.x-O.x<u){I&&(L[L.length-1]=w);continue}L.push(w)}}'''

new = '''let L=[];{const MIN=h.overview?56:72;const pushSpaced=m=>{for(let d=0;d<m.length;d++){const w=m[d],I=d===m.length-1;if(!L.length){L.push(w);continue}const O=L[L.length-1];if(w.x-O.x<MIN){I&&(L[L.length-1]=w);continue}L.push(w)}};if(h.overview&&f>=3){const monthFirst=new Map;for(let i=0;i<f;i++){const lb=String(_[i].label||""),m=lb.match(/^(\\d{1,2})-(\\d{1,2})$/);if(!m)continue;const mk=m[1].padStart(2,"0"),day=+m[2],prev=monthFirst.get(mk);if(prev==null){monthFirst.set(mk,i);continue}const prevDay=+(String(_[prev].label).split("-")[1]||99);if(day===1&&prevDay!==1)monthFirst.set(mk,i);else if(day!==1&&prevDay!==1&&day<prevDay)monthFirst.set(mk,i)}const y=new Set([0,f-1]);for(const idx of monthFirst.values())y.add(idx);pushSpaced([...y].sort((a,b)=>a-b).map(d=>({x:_[d].x,t:_[d].label})))}else{const i=f<=10?1:f<=20?2:f<=40?3:f<=110?5:f<=200?10:f<=400?15:Math.max(20,Math.ceil(f/14)),y=new Set([0,f-1]);for(let d=i;d<f-1;d+=i)y.add(d);pushSpaced([...y].sort((a,b)=>a-b).map(d=>({x:_[d].x,t:_[d].label})))}}'''

if old not in s:
    print("MISS old block")
    print("has overview mid", "Math.floor((f-1)/2)" in s)
else:
    shutil.copy2(p, str(p)+".bak_xmonth_"+ts)
    p.write_text(s.replace(old, new, 1), encoding="utf-8")
    t = p.read_text(encoding="utf-8")
    print("OK", "monthFirst" in t, "Math.floor((f-1)/2)" in t)

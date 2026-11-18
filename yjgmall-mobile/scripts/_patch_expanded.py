#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Add expanded prop to live QuoteKlineChart; when expanded: scrollable slot layout, h=360, no board dots."""
from pathlib import Path
import shutil, time
ts = str(int(time.time()))

paths = [
  Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js"),
  Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js"),
  Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit8.js"),
]

old_props = 'props:{candles:{},mode:{default:"line"},ariaLabel:{default:"价格走势"},revealed:{type:[Boolean,null],default:null},loginRedirect:{default:""},gate:{default:"login"}}'
new_props = 'props:{candles:{},mode:{default:"line"},ariaLabel:{default:"价格走势"},revealed:{type:[Boolean,null],default:null},loginRedirect:{default:""},gate:{default:"login"},expanded:{type:Boolean,default:!1}}'

# fit width: exclude expanded
old_need = 'need=((g<=31||g>=100)?Math.max((vw.value||0)-N-j,160):(P.value?g:Math.max(g-1,1))*56),D=need,W=((g<=31||g>=100)?Math.max(vw.value||0,240):D+N+j),J=P.value?D/Math.max(g,1):g<=1?D:D/(g-1)'
new_need = 'exp=!!f.expanded,slot=exp?44:56,need=((!exp&&(g<=31||g>=100))?Math.max((vw.value||0)-N-j,160):(P.value?g:Math.max(g-1,1))*slot),D=need,W=((!exp&&(g<=31||g>=100))?Math.max(vw.value||0,240):D+N+j),J=P.value?D/Math.max(g,1):g<=1?D:D/(g-1)'

# board pads / height / showDots only when NOT expanded
old_Ht = "Ht=(g>=100?18:H),Ot=(g>=100?36:O),zt=(g>=100?280:z)"
new_Ht = "Ht=(exp?16:g>=100?18:H),Ot=(exp?28:g>=100?36:O),zt=(exp?360:g>=100?280:z)"

old_ret = "showDots:!P.value&&g>=100,h:g>=100?280:z"
new_ret = "showDots:!exp&&!P.value&&g>=100,h:exp?360:g>=100?280:z,scrollable:exp||!(!exp&&(g<=31||g>=100))&&W>(vw.value||0)+1"

# xLabels: board first/mid/last only when !expanded && g>=100
old_xlab_head = "_t=(()=>{if(g>=100&&g>=3){"
new_xlab_head = "_t=(()=>{if(!exp&&g>=100&&g>=3){"

# pin fit
old_pin = "fit=n<=31||n>=100"
new_pin = "fit=!f.expanded&&(n<=31||n>=100)"

# class board only when showDots (already), add expanded class
old_cls = 'class:$t(["line-wrap",{locked:!n(m),board:!!n(u).showDots}])'
new_cls = 'class:$t(["line-wrap",{locked:!n(m),board:!!n(u).showDots,expanded:!!f.expanded}])'

# svg width: when scrollable/expanded use chart.w
old_w = 'width:((f.candles||[]).length>31?n(u).w:"100%")'
new_w = 'width:(f.expanded||(f.candles||[]).length>31&&!n(u).showDots?n(u).w:(n(u).showDots?"100%":((f.candles||[]).length>31?n(u).w:"100%")))'
# simpler: width: (f.expanded || (n scrollable))? n(u).w : showDots?100%: ...
# Even simpler matching source: scrollable ? chart.w : '100%'
# Live doesn't have scrollable in template. Use:
# width:(f.expanded||(!(n(u).showDots)&&(f.candles||[]).length>31)?n(u).w:"100%")
old_w = 'width:((f.candles||[]).length>31?n(u).w:"100%")'
new_w = 'width:(f.expanded||((f.candles||[]).length>31&&!n(u).showDots)?n(u).w:"100%")'

for path in paths:
  if not path.exists():
    print("skip", path); continue
  s = path.read_text(encoding="utf-8")
  shutil.copy2(path, str(path)+".bak_exp_"+ts)
  n = s
  for name,a,b in [
    ("props", old_props, new_props),
    ("need", old_need, new_need),
    ("Ht", old_Ht, new_Ht),
    ("ret", old_ret, new_ret),
    ("xlab", old_xlab_head, new_xlab_head),
    ("pin", old_pin, new_pin),
    ("cls", old_cls, new_cls),
    ("width", old_w, new_w),
  ]:
    if a not in n:
      print("MISS", name, path.name)
    else:
      n = n.replace(a, b, 1)
      print("OK", name, path.name)
  path.write_text(n, encoding="utf-8")
  print("wrote", path.name, len(n)-len(s))

# CSS for expanded
css = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/QuoteKlineChart.KSdW9eP7.css")
c = css.read_text(encoding="utf-8")
if ".line-wrap[data-v-15c55ada].expanded" not in c:
  c += "\n.line-wrap[data-v-15c55ada].expanded{min-height:420px}.line-wrap[data-v-15c55ada].expanded .y-col[data-v-15c55ada]{height:360px!important}.line-wrap[data-v-15c55ada].expanded .line-chart[data-v-15c55ada]{height:360px!important;width:auto!important;max-width:none!important}\n"
  css.write_text(c, encoding="utf-8")
  print("OK css expanded")
else:
  print("css ok")
print("DONE")

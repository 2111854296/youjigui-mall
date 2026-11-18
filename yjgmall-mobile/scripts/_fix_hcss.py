#!/usr/bin/env python3
from pathlib import Path
p=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/QuoteKlineChart.0h5oYOfJ.css")
c=p.read_text(encoding="utf-8")
old=".line-chart[data-v-e8aab47a]{cursor:crosshair;display:block;height:200px;max-width:none;touch-action:pan-x pan-y;z-index:1}"
new=".line-chart[data-v-e8aab47a]{cursor:crosshair;display:block;height:200px;max-width:none;touch-action:pan-x pan-y;z-index:1}.line-wrap.overview .line-chart[data-v-e8aab47a]{height:280px}.line-wrap.expanded .line-chart[data-v-e8aab47a]{height:360px}"
if old not in c:
  print("MISS", "already" if "expanded .line-chart" in c else c[c.find("line-chart"):c.find("line-chart")+120])
else:
  p.write_text(c.replace(old,new,1),encoding="utf-8")
  print("OK patched css", "expanded .line-chart" in p.read_text())
# also nuke leftover !important 200 from old css if any affects new scope - old scope different
oldcss=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/QuoteKlineChart.KSdW9eP7.css")
if oldcss.exists():
  t=oldcss.read_text(encoding="utf-8")
  if "height:200px!important" in t:
    t=t.replace("height:200px!important","height:200px")
    # add expanded/overview for old scope too just in case
    if ".expanded .line-chart" not in t:
      t+="\n.line-wrap.expanded .line-chart[data-v-15c55ada],.line-wrap[data-v-15c55ada].expanded .line-chart[data-v-15c55ada]{height:360px!important}\n.line-wrap.overview .line-chart[data-v-15c55ada],.line-wrap[data-v-15c55ada].overview .line-chart[data-v-15c55ada],.line-wrap[data-v-15c55ada].board .line-chart[data-v-15c55ada]{height:280px!important}\n"
    oldcss.write_text(t,encoding="utf-8")
    print("OK old css")

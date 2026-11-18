#!/usr/bin/env python3
from pathlib import Path
import re, time
nuxt=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
files=sorted(nuxt.glob("C-jR4i7n*.js"))
old_need='need=(g<=31?Math.max((vw.value||0)-N-j,160):(P.value?g:Math.max(g-1,1))*56),D=need,W=(g<=31?Math.max(vw.value||0,240):D+N+j)'
new_need='need=((g<=31||g>=100)?Math.max((vw.value||0)-N-j,160):(P.value?g:Math.max(g-1,1))*56),D=need,W=((g<=31||g>=100)?Math.max(vw.value||0,240):D+N+j)'
old_pad='const Z=Math.max(rawSpan*.12,Math.abs(mid0)*.004,1)'
new_pad='const Z=Math.max(rawSpan*(o.length>=100?.06:.12),Math.abs(mid0)*.0025,1)'
# also template fit class if based on g<=31
n_ok=0
for f in files:
  s=f.read_text(encoding="utf-8",errors="ignore")
  orig=s
  if old_need not in s:
    print("MISS need", f.name)
  else:
    s=s.replace(old_need,new_need,1)
  if old_pad in s:
    s=s.replace(old_pad,new_pad,1)
  else:
    print("MISS pad", f.name)
  # force fit class: chart-scroll :class="{fit: g<=31}" patterns
  # search
  if 'g<=31' in s:
    # replace remaining width-fit related for scrollable hint - optional
    pass
  # common: class:{fit:!Oe} or similar from scrollable computed
  if s!=orig:
    bak=f.with_suffix(f.suffix+".bak_overview_%d"%int(time.time()))
    # only one bak name collision ok
    if not bak.exists():
      bak.write_text(orig,encoding="utf-8")
    f.write_text(s,encoding="utf-8")
    n_ok+=1
    print("patched", f.name, "delta", len(s)-len(orig))
  else:
    print("nochange", f.name)
print("done", n_ok, "/", len(files))
# verify fit9
s=(nuxt/"C-jR4i7n.fit9.js").read_text(encoding="utf-8",errors="ignore")
print("verify", "g>=100" in s, "o.length>=100" in s)
# find how fit class decided
i=s.find("chart-scroll")
print("scroll cls", s[i:i+200] if i>=0 else None)
# maybe :class in render
for m in re.finditer(r'fit.{0,40}', s):
  if "class" in m.group(0) or "chart" in m.group(0) or "<=" in m.group(0):
    print(m.group(0)[:80])

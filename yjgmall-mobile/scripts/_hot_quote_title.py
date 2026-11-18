#!/usr/bin/env python3
from pathlib import Path
# hotpatch all CPYqRJ-v*.js: make title bind with key + after vip-hint inject coverage is hard
# simpler: ensure at title uses D.value title with explicit text update by replacing span render
# Also patch: after pick set D, force at by ensuring picked stays
files=list(Path("/www/wwwroot/g.aifeili.cn/public/_nuxt").glob("CPYqRJ-v*.js"))
# Replace title span to include D.value in key path - in render: e("span",null,l(n(at))+"价格走势",1)
# -> e("span",{key:"rt-"+D.value},l(n(at))+"价格走势"+("("+D.value+")"),1)  too ugly
old='e("span",null,l(n(at))+"价格走势",1)'
new='e("span",{key:"rt-"+n(D)},l(n(at))+"价格走势",1)'
n=0
for f in files:
  s=f.read_text(encoding="utf-8",errors="ignore")
  if old in s:
    f.write_text(s.replace(old,new,1),encoding="utf-8")
    n+=1
    print("patched title key", f.name)
  elif 'key:"rt-"' in s:
    print("already", f.name)
  else:
    print("MISS", f.name)
print("done", n)
# verify year force still on board
print("board force", "强制按此年" in Path("/www/wwwroot/g.aifeili.cn/public/admin-quote-board.html").read_text(encoding="utf-8",errors="ignore"))
print("php force", Path("/www/wwwroot/g.aifeili.cn/app/common/QuotePriceImport.php").read_text(encoding="utf-8",errors="ignore").count("forceYear"))

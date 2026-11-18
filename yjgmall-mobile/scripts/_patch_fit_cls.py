#!/usr/bin/env python3
from pathlib import Path
nuxt=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
old='fit=n<=31'
new='fit=n<=31||n>=100'
# also variants
olds=[
  'fit=n<=31',
  'fit:n<=31',
  'fit=g<=31',
  '{fit:g<=31}',
]
n=0
for f in sorted(nuxt.glob("C-jR4i7n*.js")):
  if f.name.endswith('.bak_overview') or '.bak_' in f.name: continue
  s=f.read_text(encoding="utf-8",errors="ignore")
  orig=s
  if 'fit=n<=31;s.classList.toggle("fit",fit)' in s:
    s=s.replace('fit=n<=31;s.classList.toggle("fit",fit)','fit=n<=31||n>=100;s.classList.toggle("fit",fit)',1)
  elif 'fit=n<=31' in s:
    s=s.replace('fit=n<=31','fit=n<=31||n>=100',1)
  # vue class binding patterns
  if 'class:{fit:' in s:
    print("class fit ctx", f.name, s[s.find("class:{fit:"):s.find("class:{fit:")+80])
  if s!=orig:
    f.write_text(s,encoding="utf-8")
    n+=1
    print("fit-cls", f.name)
  else:
    # show how fit is set
    i=s.find('fit')
    idxs=[]
    start=0
    while True:
      i=s.find('fit', start)
      if i<0: break
      idxs.append(s[max(0,i-30):i+50])
      start=i+3
      if len(idxs)>6: break
    print("nofitpatch", f.name, idxs[:3])
print("done", n)
# verify fit9
s=(nuxt/"C-jR4i7n.fit9.js").read_text(encoding="utf-8",errors="ignore")
print("verify fit", "n>=100" in s and "fit=n<=31||n>=100" in s)
print("snippet", s[s.find("fit="):s.find("fit=")+80] if "fit=" in s else "no fit=")

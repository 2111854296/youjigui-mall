#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
import os,re
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js").read_text(encoding="utf-8",errors="ignore")
# find padding consts near start of setup
m=re.search(r"H=\d+|padT|const H|z=\d{2,3},|N=\d+,j=\d+", s)
print("m", m.group() if m else None)
# extract chart height vars used in template y1:H
idx=s.find("y1:H,")
print("y1H context", s[max(0,idx-200):idx+80])
# find where H,N,j,O,z defined - likely at top of setup
idx=s.find(",H=")
print("H=", s[idx:idx+80])
idx=s.find("N=")
# try vue const destructuring
for pat in [r"H=16", r"H=18", r"z=200", r"z=240", r"z=280", r"O=28", r"O=36", r"N=10", r"j=10"]:
  print(pat, s.find(pat[2:] if False else pat), s.count(pat.split("=")[0].replace("H","") and pat))
print("counts z=200", s.count("z=200"), "200)", "fit", s.count("o.length>=100"))
# need=((g<=31||g>=
idx=s.find("need=((")
print("NEED", s[idx:idx+280])
idx=s.find("rawSpan*(o.length>=100")
print("PADR", s[idx-40:idx+100])
# who imports C-jR4i7n
os.system("grep -l 'C-jR4i7n' /www/wwwroot/g.aifeili.cn/public/_nuxt/*.js 2>/dev/null | head -20")
os.system("ls /www/wwwroot/g.aifeili.cn/public/_nuxt/builds 2>/dev/null | head")
os.system("grep -rn 'C-jR4i7n' /www/wwwroot/g.aifeili.cn/public/_nuxt/builds 2>/dev/null | head -10")
os.system("grep -rn 'fit9\\|C-jR4i7n.js' /www/wwwroot/g.aifeili.cn/public/index.html /www/wwwroot/g.aifeili.cn/public/**/*.html 2>/dev/null | head -20")
# check symlink
os.system("ls -la /www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js /www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js")

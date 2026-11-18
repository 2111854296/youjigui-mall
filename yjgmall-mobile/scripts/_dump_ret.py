#!/usr/bin/env python3
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js").read_text(encoding="utf-8",errors="ignore")
# full return at end of chart computed
i=s.find("return{points:r")
print("RETURN1", s[i:i+350] if i>=0 else "none")
i=s.find("scrollable:")
print("scroll", s[i-80:i+120] if i>=0 else "none")
# constants H pad
for key in ["const H=","const N=","const j=","const O=","const z=","padL","padR"]:
  ii=s.find(key)
  print(key, s[ii:ii+60] if ii>=0 else "nf")
# which file is served - check HTML references
html=Path("/www/wwwroot/g.aifeili.cn/public").joinpath("index.html")
# find entry referencing C-jR4i7n
import subprocess
r=subprocess.check_output("grep -l 'C-jR4i7n' /www/wwwroot/g.aifeili.cn/public/_nuxt/*.js /www/wwwroot/g.aifeili.cn/public/**/*.html 2>/dev/null | head -20", shell=True, text=True)
print("REFS", r)
# manifest
r2=subprocess.check_output("grep -rn 'C-jR4i7n' /www/wwwroot/g.aifeili.cn/public/_nuxt/*.mjs /www/wwwroot/g.aifeili.cn/public/_nuxt/builds 2>/dev/null | head -15", shell=True, text=True)
print("MAN", r2[:800])

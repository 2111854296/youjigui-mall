#!/usr/bin/env python3
from pathlib import Path
# bump cache marker
index=Path("/www/wwwroot/g.aifeili.cn/public/index.html")
html=index.read_text(encoding="utf-8",errors="ignore")
for old in ["<!-- pending-filter-fix-v3 -->","<!-- similar-filter-fix-v1 -->"]:
    html=html.replace(old,"")
if "</head>" in html:
    html=html.replace("</head>","<!-- similar-filter-fix-v1 --></head>",1)
else:
    html+="<!-- similar-filter-fix-v1 -->"
index.write_text(html,encoding="utf-8")
print("index ok", "similar-filter-fix-v1" in index.read_text(encoding="utf-8",errors="ignore"))
# also ship source AdminResource pending honors tab optional - skip
# verify Ul
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js").read_text(encoding="utf-8",errors="ignore")
i=s.find("function Ul(s){")
print("Ul", s[i:i+200])

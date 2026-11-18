#!/usr/bin/env python3
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js").read_text(encoding="utf-8")
i=s.find("exp=!!f.expanded")
print(s[i:i+350] if i>=0 else "NO EXP")
i=s.find("showDots:!exp")
print("ret", s[i:i+120] if i>=0 else "NO")

#!/usr/bin/env python3
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js").read_text(encoding="utf-8")
i=s.find('r:"4.5"')
print(s[i-200:i+180] if i>=0 else "no dots")

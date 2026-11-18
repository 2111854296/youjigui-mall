#!/usr/bin/env python3
from pathlib import Path
t=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DGDn1Ahr.js").read_text(encoding="utf-8",errors="ignore")
# find match helpers
for key in ['includes("?")', "includes('?')", 'startsWith', 'routeFull', 'fullPath']:
    print(key, t.count(key))
i=t.find('includes("?")')
if i<0: i=t.find("includes('?')")
print("CTX", t[max(0,i-400):i+500])

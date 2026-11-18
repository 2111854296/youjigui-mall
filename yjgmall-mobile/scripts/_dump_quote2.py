#!/usr/bin/env python3
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/CPYqRJ-v.js").read_text(encoding="utf-8",errors="ignore")
# dump from U= through series filter
i=s.find('U=[{key:"7d"')
print(s[i:i+1800])
print("=====")
# click handler for range
i=s.find('aria-label:"时间范围"')
print(s[max(0,i-100):i+900])

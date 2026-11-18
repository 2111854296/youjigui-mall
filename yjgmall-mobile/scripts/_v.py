#!/usr/bin/env python3
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/eu_lJI6T.js").read_text(encoding="utf-8")
i=s.find("monthFirst")
print(s[i:i+420])

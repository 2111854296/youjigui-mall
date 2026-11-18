#!/usr/bin/env python3
from pathlib import Path
html=Path("/www/wwwroot/g.aifeili.cn/public/index.html").read_text(encoding="utf-8",errors="ignore")
print(html[:1500])
print("---tail---")
print(html[-800:])

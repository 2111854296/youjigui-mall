#!/usr/bin/env python3
from pathlib import Path
# any chart-expand in public
import subprocess,os
os.system("grep -rl 'chart-expand\\|qce-expand\\|详情展开' /www/wwwroot/g.aifeili.cn/public --include='*.js' --include='*.html' --include='*.css' 2>/dev/null | head -30")
# line-stage overflow pin parent
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js").read_text(encoding="utf-8")
i=s.find("function pin")
print(s[i:i+450])

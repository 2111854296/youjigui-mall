#!/usr/bin/env python3
from pathlib import Path
import re
s = Path('/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js').read_text(encoding='utf-8', errors='ignore')
m = re.search(r'.{0,80}vip-pay-orders.{0,80}', s)
print('VIP', m.group(0) if m else None)
# look for open/router helpers
for m in re.finditer(r'.{0,40}vip-applications.{0,120}', s):
    print('VA', m.group(0))
# ae full until next function
idx = s.find('function ae(){')
print(s[idx:idx+700])

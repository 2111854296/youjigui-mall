#!/usr/bin/env python3
from pathlib import Path
import re
s = Path('/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js').read_text(encoding='utf-8', errors='ignore')
# find status select render condition near 全部状态
i = s.find('placeholder:"全部状态"')
print('SELECT_BLOCK\n', s[max(0,i-1200):i-400])
print('---')
# te usage
print('te usages sample:')
for m in re.finditer(r'.{0,30}e\(te\).{0,40}', s):
    print(m.group(0)[:80])
print('---ae full---')
i = s.find('function ae(){')
print(s[i:i+900])

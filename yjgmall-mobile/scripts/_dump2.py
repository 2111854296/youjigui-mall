#!/usr/bin/env python3
from pathlib import Path
import re
s = Path('/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js').read_text(encoding='utf-8', errors='ignore')
# ae / syncQueryFilters
for name in ['function ae()', 'ae=()=>', 'function ae(', 'ae=function']:
    i = s.find(name)
    print(name, i)
i = s.find('function ae(')
if i < 0:
    # try other
    m = re.search(r'function ae\(\)\{.{0,500}', s)
    print('ae match', m.group(0) if m else None)
else:
    print(s[i:i+600])
print('---te---')
m = re.search(r'te=w\(\(\)=>.{0,200}', s)
print(m.group(0) if m else 'no te')
print('---select context---')
i = s.find('placeholder:"全部状态"')
print(s[max(0,i-500):i+100])
print('---meta status v-if---')
# find e(se).status
for m in re.finditer(r'e\(se\)\.status.{0,40}', s):
    print(m.group(0))

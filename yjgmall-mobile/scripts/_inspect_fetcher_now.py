#!/usr/bin/env python3
from pathlib import Path
import re
s = Path('/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js').read_text(encoding='utf-8', errors='ignore')
print('pendingFinal', 'pendingFinal' in s)
print('__artListSeq', s.count('__artListSeq'))
i = s.find('__artListSeq')
print('around seq', s[max(0,i-80):i+500])
print('---')
i = s.find('admin/resources/${r.value}')
print('around api', s[max(0,i-120):i+200])
print('---')
i = s.find('function Ol(){')
print('Ol', s[i:i+600])
print('---')
print('fullPath pe', 'ae(),pe()' in s, 'ae(),q.value' in s)
print('Dl items', re.search(r'Dl=w\(\(\)=>\{.{0,200}', s).group(0) if re.search(r'Dl=w\(\(\)=>\{.{0,200}', s) else None)

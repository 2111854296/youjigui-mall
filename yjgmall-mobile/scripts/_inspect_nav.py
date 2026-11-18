#!/usr/bin/env python3
from pathlib import Path
import re
s = Path('/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js').read_text(encoding='utf-8', errors='ignore')
for pat in ['navigateTo', 'router.push', 'router.replace', 'useRouter']:
    print(pat, s.count(pat))
# show imports / helpers near top of setup - search replaceState
print('replaceState', s.count('replaceState'))
# find pe refresh and ae full
m = re.search(r'function ae\(\)\{.*?\}\s*const te=', s)
if m:
    print('AE_FULL', m.group(0)[:800])
# resource watch
m = re.search(r'ce\(r,\(\)=>\{ae\(\),.{0,300}', s)
print('RES_WATCH', m.group(0) if m else None)

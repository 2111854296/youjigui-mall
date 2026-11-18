#!/usr/bin/env python3
from pathlib import Path
import re
s = Path('/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js').read_text(encoding='utf-8', errors='ignore')
# dump key regions to /tmp
Path('/tmp/_dump_chunk_regions.txt').write_text('', encoding='utf-8')
out = []
# Ol full
i = s.find('function Ol(){')
j = s.find('return s}', i) + len('return s}')
out.append('===OL===\n' + s[i:j] + '\n')
# fetcher: find useAsyncData key area
m = re.search(r'admin-res-\$\{r\.value\}.*?async\(\)=>\{.{0,2500}', s)
if m:
    out.append('===FETCH_START===\n' + m.group(0)[:2500] + '\n')
# find return res after artListSeq
i = s.find("async()=>{if(typeof window.__artListSeq")
if i>=0:
    # take 2000 chars
    out.append('===SEQ_FETCH===\n' + s[i:i+2000] + '\n')
# Dl items
m = re.search(r'Dl=w\(\(\)=>\{.{0,800}', s)
if m:
    out.append('===DL===\n' + m.group(0) + '\n')
# fullPath
m = re.search(r'ce\(\(\)=>d\.fullPath,.\{0,200\}', s)
if m:
    out.append('===FULLPATH===\n' + m.group(0) + '\n')
# status select
for pat in ['全部状态', 'onUpdate:modelValue', 'Be({path:d.path']:
    idxs = [m.start() for m in re.finditer(re.escape(pat) if pat!='全部状态' else pat, s)]
    out.append(f'===count {pat}: {len(idxs)}===\n')
i = s.find('placeholder:"全部状态"')
if i>=0:
    out.append('===SELECT===\n' + s[max(0,i-400):i+80] + '\n')
# ae(),pe()
out.append(f'ae(),pe() count={s.count("ae(),pe()")}\n')
out.append(f'pendingFinal={("pendingFinal" in s)} pendingFix2={("pendingFix2" in s)}\n')
# te locked
out.append(f'!e(te)&& count={s.count("!e(te)&&")}\n')
Path('/tmp/_dump_chunk_regions.txt').write_text('\n'.join(out), encoding='utf-8')
print('wrote', len('\n'.join(out)))
print('\n'.join(out)[:8000])

#!/usr/bin/env python3
from pathlib import Path
import subprocess
# search g and www for range title
for base in ["/www/wwwroot/g.aifeili.cn/public","/www/wwwroot/www.aifeili.cn","/www/wwwroot/v.aifeili.cn/public"]:
  p=Path(base)
  if not p.exists():
    print("missing", base); continue
  print("==", base)
  # ripgrep if available
  r=subprocess.run(["rg","-l","近两年|近 1 月|RANGE_OPTS",str(p),"-g","*.js","-g","*.html"],stdout=subprocess.PIPE,stderr=subprocess.PIPE,universal_newlines=True)
  print((r.stdout or r.stderr or "none")[:500])

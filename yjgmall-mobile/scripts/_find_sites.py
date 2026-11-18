#!/usr/bin/env python3
from pathlib import Path
# find quotes page hosts / paths
for root in [Path("/www/wwwroot")]:
  for p in root.iterdir():
    if p.is_dir():
      q=list(p.rglob("quotes/**"))[:3] if False else []
      # quick
      for cand in [p/"public", p]:
        if (cand/"_nuxt").exists() or (cand/"index.html").exists():
          print("site", p.name, cand)

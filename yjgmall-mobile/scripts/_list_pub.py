#!/usr/bin/env python3
import os
base="/www/wwwroot/g.aifeili.cn/public"
# list top-level custom files not in typical nuxt
for n in sorted(os.listdir(base)):
  print(n)
print("---")
# quote-chart-expand?
print("qce", os.path.exists(base+"/quote-chart-expand.js"))

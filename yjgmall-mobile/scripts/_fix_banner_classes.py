#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
p = "/www/wwwroot/g.aifeili.cn/public/_nuxt/rMEP6MmM.ss.js"
s = open(p, encoding="utf-8").read()
i = s.find('class:"banner-carousel"')
j = s.find('class:"dots"', i)
region = s[i:j]
print("remaining on:m", region.count("on:m===a(A)"))
print("region snippet:")
# print occurrences
idx = 0
while True:
    k = region.find("on:m===a(A)", idx)
    if k < 0:
        break
    print(region[max(0, k - 60) : k + 40])
    idx = k + 1
# force replace any leftover in whole file near banner
s2 = s.replace('class:N(["slide img-slide",{on:m===a(A)}])', 'class:"slide img-slide on"')
# also without N - some may be different
import re
s2 = re.sub(
    r'class:N\(\["slide img-slide",\{on:m===a\(A\)\}\]\)',
    'class:"slide img-slide on"',
    s2,
)
# div variant might use same
open(p, "w", encoding="utf-8").write(s2)
# sync base name too
open("/www/wwwroot/g.aifeili.cn/public/_nuxt/rMEP6MmM.js", "w", encoding="utf-8").write(s2)
i = s2.find('class:"banner-carousel"')
j = s2.find('class:"dots"', i)
print("after remaining", s2[i:j].count("on:m===a(A)"))
print("single-slide", "/*single-slide*/" in s2)

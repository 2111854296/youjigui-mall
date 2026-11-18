#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import subprocess
import json
import urllib.request

host, db, user, pwd = "127.0.0.1", "yjgmall_g", "yjgmall_g", "TkzDCT7GkRf5TPer"

def mysql(sql):
    cmd = ["mysql", "-h", host, "-u", user, f"-p{pwd}", db, "-N", "-B", "-e", sql]
    out = subprocess.check_output(cmd, stderr=subprocess.STDOUT, timeout=20)
    return out.decode("utf-8", "ignore")

print("==== DB home_banner ====")
print(mysql("SELECT id,title,sort,link,updated_at FROM ads WHERE position='home_banner' ORDER BY sort,id;"))

print("==== API home ====")
with urllib.request.urlopen("https://g.aifeili.cn/api/v1/home", timeout=15) as r:
    data = json.loads(r.read().decode("utf-8", "ignore"))
hb = ((data.get("data") or {}).get("ads") or {}).get("home_banner") or []
for b in hb:
    print(b.get("id"), b.get("title"), "=>", b.get("link"))

print("==== API ads ====")
with urllib.request.urlopen("https://g.aifeili.cn/api/v1/ads?position=home_banner", timeout=15) as r:
    data = json.loads(r.read().decode("utf-8", "ignore"))
for b in data.get("data") or []:
    print(b.get("id"), b.get("title"), "=>", b.get("link"))

#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import subprocess
import os
import re

host, db, user, pwd = "127.0.0.1", "yjgmall_g", "yjgmall_g", "TkzDCT7GkRf5TPer"

def mysql(sql):
    cmd = ["mysql", "-h", host, "-u", user, f"-p{pwd}", db, "-N", "-B", "-e", sql]
    out = subprocess.check_output(cmd, stderr=subprocess.STDOUT, timeout=20)
    return out.decode("utf-8", "ignore")

print("==== sms settings ====")
print(mysql("SELECT `key`, LEFT(`value`,120) FROM site_settings WHERE `key` LIKE '%sms%' OR `key` LIKE '%aliyun%';"))

root = "/www/wwwroot/g.aifeili.cn"
hits = []
for dirpath, dirnames, filenames in os.walk(os.path.join(root, "app")):
    dirnames[:] = [d for d in dirnames if d not in ("vendor",)]
    for f in filenames:
        if not f.endswith(".php"):
            continue
        p = os.path.join(dirpath, f)
        s = open(p, encoding="utf-8", errors="ignore").read()
        if re.search(r"forgot-password|forgotPassword|reset-password|sms_mode|SmsService", s):
            hits.append(p)
print("php hits", hits)
for p in hits:
    s = open(p, encoding="utf-8", errors="ignore").read()
    print("====", p)
    for pat in ["forgot-password", "forgotPassword", "function forgot", "sms_mode", "gateway", "fixed", "aliyun", "data.code", "'code'"]:
        idx = 0
        n = 0
        while n < 3:
            i = s.lower().find(pat.lower(), idx)
            if i < 0:
                break
            print("---", pat, "@", i)
            print(s[max(0, i - 60) : i + 220].replace("\n", " "))
            idx = i + 1
            n += 1

# routes
for rp in [
    os.path.join(root, "route/app.php"),
    os.path.join(root, "route/api.php"),
]:
    if not os.path.isfile(rp):
        continue
    s = open(rp, encoding="utf-8", errors="ignore").read()
    print("====", rp)
    for m in re.finditer(r".{0,30}forgot.{0,90}|.{0,30}reset-password.{0,90}", s, re.I):
        print(m.group(0))

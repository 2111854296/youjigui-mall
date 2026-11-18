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

print(mysql("DESC site_settings;"))
print("==== sms rows ====")
print(mysql("SELECT * FROM site_settings WHERE `key` LIKE '%sms%' OR `key` LIKE '%aliyun%' OR name LIKE '%sms%' OR `key` LIKE '%code%';"))

# search forgot in all php
root = "/www/wwwroot/g.aifeili.cn/app"
hits = []
for dirpath, dirnames, filenames in os.walk(root):
    dirnames[:] = [d for d in dirnames if d != "vendor"]
    for f in filenames:
        if not f.endswith(".php"):
            continue
        p = os.path.join(dirpath, f)
        try:
            s = open(p, encoding="utf-8", errors="ignore").read()
        except Exception:
            continue
        if "forgot" in s.lower() or "resetPassword" in s or "reset-password" in s or "sms_mode" in s:
            hits.append(p)
print("hits", hits)
for p in hits[:8]:
    s = open(p, encoding="utf-8", errors="ignore").read()
    print("====", p)
    for m in re.finditer(r".{0,40}(forgot|resetPassword|sms_mode|gateway|fixed_code|aliyun).{0,120}", s, re.I):
        print(m.group(0)[:200])

# route
for p in [
    "/www/wwwroot/g.aifeili.cn/route/app.php",
    "/www/wwwroot/g.aifeili.cn/route/api.php",
]:
    if os.path.isfile(p):
        s = open(p, encoding="utf-8", errors="ignore").read()
        print("==== route", p)
        for m in re.finditer(r".{0,20}forgot.{0,80}|.{0,20}reset-password.{0,80}|.{0,20}sms.{0,80}", s, re.I):
            print(m.group(0))

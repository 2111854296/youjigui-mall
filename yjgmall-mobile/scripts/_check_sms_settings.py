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

print("==== settings keys related to sms ====")
try:
    print(mysql("SHOW TABLES LIKE '%setting%'; SHOW TABLES LIKE '%config%';"))
except Exception as e:
    print(e)

# common tables
for t in ["settings", "site_settings", "configs", "options", "system_settings"]:
    try:
        print(mysql(f"SHOW TABLES LIKE '{t}';"))
    except Exception:
        pass

# try find sms keys
sqls = [
    "SELECT `key`,`value` FROM settings WHERE `key` LIKE '%sms%' OR `key` LIKE '%aliyun%';",
    "SELECT name,value FROM settings WHERE name LIKE '%sms%' OR name LIKE '%aliyun%';",
    "SELECT * FROM settings LIMIT 5",
]
for s in sqls:
    try:
        print("Q", s)
        print(mysql(s)[:1500])
    except Exception as e:
        print("err", getattr(e, "output", b"").decode("utf-8", "ignore")[:300] or e)

# also check Auth.php on server for forgot-password
import os
for p in [
    "/www/wwwroot/g.aifeili.cn/app/controller/api/v1/Auth.php",
    "/www/wwwroot/g.aifeili.cn/app/controller/api/Auth.php",
]:
    if os.path.isfile(p):
        print("====", p)
        txt = open(p, encoding="utf-8", errors="ignore").read()
        for key in ["forgot", "sms_mode", "gateway", "fixed", "aliyun", "demo"]:
            print(key, txt.lower().count(key.lower()))
        i = txt.find("forgot")
        if i < 0:
            i = txt.find("Forgot")
        print(txt[max(0, i - 50) : i + 800] if i >= 0 else "no forgot")

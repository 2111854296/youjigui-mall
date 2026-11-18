#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import subprocess

host, db, user, pwd = "127.0.0.1", "yjgmall_g", "yjgmall_g", "TkzDCT7GkRf5TPer"

def mysql(sql):
    cmd = ["mysql", "-h", host, "-u", user, f"-p{pwd}", db, "-N", "-e", sql]
    out = subprocess.check_output(cmd, stderr=subprocess.STDOUT, timeout=20)
    return out.decode("utf-8", "ignore")

print("before:")
print(mysql("SELECT id,title,link FROM ads WHERE id=18;"))
print("companies 1688:")
print(mysql("SELECT id,name,website FROM companies WHERE IFNULL(website,'') LIKE '%1688%' OR IFNULL(website,'') LIKE '%alibaba%' LIMIT 20;"))
print("friend_links 1688:")
try:
    print(mysql("SELECT id,name,url FROM friend_links WHERE IFNULL(url,'') LIKE '%1688%' OR IFNULL(url,'') LIKE '%alibaba%' LIMIT 20;"))
except Exception as e:
    print(e)

# Clear alibaba link on ad 18 — leave empty so front won't jump out
print(mysql("UPDATE ads SET link='' WHERE id=18 AND link LIKE '%1688%'; SELECT ROW_COUNT();"))
print("after:")
print(mysql("SELECT id,title,link FROM ads WHERE id=18;"))
print("remaining 1688 in ads:")
print(mysql("SELECT id,position,link FROM ads WHERE IFNULL(link,'') LIKE '%1688%' OR IFNULL(link,'') LIKE '%alibaba%';"))

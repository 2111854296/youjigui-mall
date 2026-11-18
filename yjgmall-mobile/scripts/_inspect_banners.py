#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json
import urllib.request
import subprocess
import os

# home payload banners
u = "https://g.aifeili.cn/api/v1/home"
with urllib.request.urlopen(u, timeout=20) as r:
    data = json.loads(r.read().decode("utf-8", "ignore"))
payload = data.get("data") or data
banners = payload.get("banners") or []
ads = payload.get("ads") or {}
print("home.ads keys", list(ads.keys()) if isinstance(ads, dict) else type(ads))
print("home.ads.home_banner count", len(ads.get("home_banner") or []))
print("home.banners count", len(banners))
for b in banners:
    print("banner", b.get("id"), b.get("title"), "=>", b.get("link"), "img", str(b.get("image") or "")[:80])

# query DB via php/think or mysql cli
envp = "/www/wwwroot/g.aifeili.cn/.env"
# find real api env
candidates = [
    "/www/wwwroot/g.aifeili.cn/api/.env",
    "/www/wwwroot/g.aifeili.cn/backend/.env",
    "/www/wwwroot/g.aifeili.cn/think/.env",
    "/www/wwwroot/g.aifeili.cn/server/.env",
]
for root, dirs, files in os.walk("/www/wwwroot/g.aifeili.cn"):
    if ".env" in files and "vendor" not in root:
        p = os.path.join(root, ".env")
        if "node_modules" in p:
            continue
        candidates.append(p)
    if len(candidates) > 20:
        break
print("env candidates")
for c in candidates:
    print(" ", c, os.path.isfile(c))

# try mysql from .env in api
cfg = {}
for envp in candidates:
    if not os.path.isfile(envp):
        continue
    cfg = {}
    for line in open(envp, encoding="utf-8", errors="ignore"):
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        k, v = line.split("=", 1)
        cfg[k] = v.strip().strip('"').strip("'")
    if cfg.get("DB_DATABASE") or cfg.get("DATABASE_NAME") or cfg.get("DB_NAME"):
        print("using", envp, cfg.get("DB_DATABASE") or cfg.get("DATABASE_NAME") or cfg.get("DB_NAME"))
        break

db = cfg.get("DB_DATABASE") or cfg.get("DATABASE_NAME") or cfg.get("DB_NAME") or ""
user = cfg.get("DB_USERNAME") or cfg.get("DB_USER") or "root"
pwd = cfg.get("DB_PASSWORD") or cfg.get("DB_PASS") or ""
host = cfg.get("DB_HOST") or "127.0.0.1"
print("dbmeta", host, db, user, "pwd_len", len(pwd))
if db:
    sql = "SELECT id,title,link,position,is_active FROM banners ORDER BY id; SELECT id,title,link,position,is_active FROM ads WHERE link LIKE '%1688%' OR link LIKE '%alibaba%' OR link='/' OR link IS NULL OR link='';"
    cmd = ["mysql", "-h", host, "-u", user, f"-p{pwd}", db, "-e", sql]
    try:
        out = subprocess.check_output(cmd, stderr=subprocess.STDOUT, timeout=20)
        print(out.decode("utf-8", "ignore"))
    except Exception as e:
        print("mysql err", e)
        if getattr(e, "output", None):
            print(e.output.decode("utf-8", "ignore"))

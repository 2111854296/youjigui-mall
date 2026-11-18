#!/usr/bin/env python3
"""Inspect announcement table + recent API/PHP errors related to announcements."""
import os
import re
from pathlib import Path

# Try DB via PHP artisan / think or mysql from .env
env_paths = [
    Path("/www/wwwroot/g.aifeili.cn/server/.env"),
    Path("/www/wwwroot/yjgmall-think/.env"),
    Path("/www/wwwroot/api.aifeili.cn/.env"),
]
for p in Path("/www/wwwroot").rglob(".env"):
    if "think" in str(p) or "api" in str(p) or "g.aifeili" in str(p):
        env_paths.append(p)

seen = set()
envs = []
for p in env_paths:
    try:
        rp = str(p.resolve())
    except Exception:
        continue
    if rp in seen or not p.exists():
        continue
    seen.add(rp)
    envs.append(p)

print("env files:")
for p in envs[:20]:
    print(" ", p)

def parse_env(p: Path):
    d = {}
    for line in p.read_text(encoding="utf-8", errors="ignore").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        k, v = line.split("=", 1)
        d[k.strip()] = v.strip().strip('"').strip("'")
    return d

db = None
for p in envs:
    d = parse_env(p)
    host = d.get("DB_HOST") or d.get("DATABASE_HOSTNAME") or d.get("hostname")
    name = d.get("DB_DATABASE") or d.get("DATABASE_DATABASE") or d.get("database")
    user = d.get("DB_USERNAME") or d.get("DATABASE_USERNAME") or d.get("username")
    pwd = d.get("DB_PASSWORD") or d.get("DATABASE_PASSWORD") or d.get("password")
    if name and user:
        db = (host or "127.0.0.1", name, user, pwd or "", p)
        print("picked db from", p, "->", name, user)
        break

if not db:
    # fallback known from prior sessions
    print("no env db; trying common")
    db = ("127.0.0.1", "yjgmall", "root", "", None)

host, name, user, pwd, _ = db
import subprocess
sql = r"""
SHOW COLUMNS FROM announcements;
SELECT id,title,width,sort,is_active,LEFT(content,40) c FROM announcements ORDER BY id DESC LIMIT 8;
"""
cmd = ["mysql", "-h", host, "-u", user, name, "-e", sql]
if pwd:
    cmd = ["mysql", "-h", host, "-u", user, f"-p{pwd}", name, "-e", sql]
try:
    out = subprocess.check_output(cmd, stderr=subprocess.STDOUT, text=True)
    print(out)
except Exception as e:
    print("mysql fail", e)
    # try with think path php
    print("trying find table via php")

# Recent logs
log_globs = [
    "/www/wwwroot/**/runtime/log/**/*.log",
    "/www/wwwlogs/*.log",
    "/www/server/nginx/logs/*.log",
]
hits = []
for base in [Path("/www/wwwroot"), Path("/www/wwwlogs"), Path("/www/server/nginx/logs")]:
    if not base.exists():
        continue
    for f in base.rglob("*.log"):
        try:
            if f.stat().st_mtime < __import__("time").time() - 86400 * 2:
                continue
        except Exception:
            continue
        try:
            text = f.read_text(encoding="utf-8", errors="ignore")
        except Exception:
            continue
        if "announce" in text.lower() or "Announcement" in text:
            for line in text.splitlines()[-400:]:
                if re.search(r"announce|Announcement|网站公告", line, re.I):
                    hits.append((str(f), line[-300:]))
print("log hits", len(hits))
for f, line in hits[-30:]:
    print(f"--- {f}")
    print(line)

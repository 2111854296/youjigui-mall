#!/usr/bin/env python3
import os, re, subprocess
from pathlib import Path

# Locate think app
candidates = [
    Path("/www/wwwroot/g.aifeili.cn/server"),
    Path("/www/wwwroot/g.aifeili.cn/api"),
    Path("/www/wwwroot/yjgmall-think"),
    Path("/www/wwwroot/api"),
]
for p in Path("/www/wwwroot").iterdir():
    if (p / "config/admin_resources.php").exists() or (p / "app/model/Announcement.php").exists():
        candidates.append(p)

roots = []
seen = set()
for p in candidates:
    if not p.exists():
        continue
    rp = str(p.resolve())
    if rp in seen:
        continue
    seen.add(rp)
    roots.append(p)
    print("root", p)

for root in roots:
    for rel in [
        "config/admin_resources.php",
        "config/admin_resource_fields.php",
        "app/model/Announcement.php",
        ".env",
    ]:
        f = root / rel
        if f.exists():
            print("found", f)

# Find announcements config snippet
for root in roots:
    ar = root / "config/admin_resources.php"
    if not ar.exists():
        continue
    t = ar.read_text(encoding="utf-8", errors="ignore")
    m = re.search(r"'announcements'\s*=>\s*\[[^\]]*(?:\[[^\]]*\][^\]]*)*\]", t, re.S)
    # simpler extract
    i = t.find("'announcements'")
    if i >= 0:
        print("==== admin_resources announcements from", root)
        print(t[i : i + 900])

    af = root / "config/admin_resource_fields.php"
    if af.exists():
        t2 = af.read_text(encoding="utf-8", errors="ignore")
        j = t2.find("'announcements'")
        if j >= 0:
            print("==== fields announcements from", root)
            print(t2[j : j + 1200])

# DB
env = None
for root in roots:
    if (root / ".env").exists():
        env = root / ".env"
        break
# also g.aifeili
for p in [
    Path("/www/wwwroot/g.aifeili.cn/.env"),
    Path("/www/wwwroot/g.aifeili.cn/server/.env"),
]:
    if p.exists():
        env = p
        break

def parse_env(p):
    d = {}
    for line in p.read_text(encoding="utf-8", errors="ignore").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        k, v = line.split("=", 1)
        d[k.strip()] = v.strip().strip('"').strip("'")
    return d

if env:
    print("env", env)
    d = parse_env(env)
    host = d.get("DB_HOST") or d.get("hostname") or "127.0.0.1"
    name = d.get("DB_DATABASE") or d.get("database") or ""
    user = d.get("DB_USERNAME") or d.get("username") or "root"
    pwd = d.get("DB_PASSWORD") or d.get("password") or ""
    prefix = d.get("DB_PREFIX") or d.get("prefix") or ""
    print("db", host, name, user, "prefix", prefix)
    table = f"{prefix}announcements" if prefix else "announcements"
    # try both
    for tbl in [table, "announcements", "dt_announce", "destoon_announce"]:
        sql = f"SHOW COLUMNS FROM `{tbl}`;"
        cmd = ["mysql", "-h", host, "-u", user]
        if pwd:
            cmd.append(f"-p{pwd}")
        cmd += [name, "-e", sql]
        try:
            out = subprocess.check_output(cmd, stderr=subprocess.STDOUT, text=True)
            print("TABLE", tbl)
            print(out)
            out2 = subprocess.check_output(
                cmd[:-1] + [name, "-e", f"SELECT id,title,sort,is_active FROM `{tbl}` ORDER BY id DESC LIMIT 5;"],
                stderr=subprocess.STDOUT,
                text=True,
            )
            print(out2)
            break
        except Exception as e:
            print("no", tbl, str(e)[:120])

# Recent php logs mentioning SQL/announce
log_dirs = [
    Path("/www/wwwlogs"),
]
for root in roots:
    log_dirs.append(root / "runtime/log")

import time
now = time.time()
for ld in log_dirs:
    if not ld.exists():
        continue
    files = sorted(ld.rglob("*.log"), key=lambda x: x.stat().st_mtime, reverse=True)[:8]
    for f in files:
        if f.stat().st_mtime < now - 3 * 86400:
            continue
        text = f.read_text(encoding="utf-8", errors="ignore")
        # last 80 lines with error
        lines = text.splitlines()
        interesting = [ln for ln in lines[-500:] if re.search(r"announce|SQLSTATE|Unknown column|width|网站公告|AdminResource", ln, re.I)]
        if interesting:
            print("LOG", f)
            for ln in interesting[-25:]:
                print(ln[-400:])

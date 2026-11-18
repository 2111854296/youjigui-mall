#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import subprocess

host, db, user, pwd = "127.0.0.1", "yjgmall_g", "yjgmall_g", "TkzDCT7GkRf5TPer"
sql = r"""
SELECT 'ads' AS src, id, title, position, link, is_active, updated_at FROM ads
WHERE link LIKE '%1688%' OR link LIKE '%alibaba%' OR website LIKE '%1688%'
ORDER BY id;
SELECT 'ads_all_banner' AS src, id, title, position, link, is_active, updated_at FROM ads WHERE position='home_banner' ORDER BY sort,id;
SELECT 'companies' AS src, id, name, website FROM companies WHERE website LIKE '%1688%' OR website LIKE '%alibaba%' LIMIT 30;
SELECT 'friend_links' AS src, id, name, url FROM friend_links WHERE url LIKE '%1688%' OR url LIKE '%alibaba%' LIMIT 30;
"""
# companies may not have website - handle errors per query
queries = [
    "SELECT id,title,position,link,is_active,updated_at FROM ads WHERE IFNULL(link,'') LIKE '%1688%' OR IFNULL(link,'') LIKE '%alibaba%' OR IFNULL(link,'') LIKE '%ali.ba%';",
    "SELECT id,title,position,link,is_active,updated_at FROM ads WHERE position IN ('home_banner','home_portal','company_top','mall_top','news_top') ORDER BY position,sort,id;",
    "SHOW COLUMNS FROM companies LIKE '%web%';",
    "SHOW COLUMNS FROM companies LIKE '%url%';",
    "SHOW COLUMNS FROM companies LIKE '%link%';",
]
for q in queries:
    print("====", q[:80])
    cmd = ["mysql", "-h", host, "-u", user, f"-p{pwd}", db, "-N", "-e", q]
    try:
        out = subprocess.check_output(cmd, stderr=subprocess.STDOUT, timeout=20)
        print(out.decode("utf-8", "ignore") or "(empty)")
    except subprocess.CalledProcessError as e:
        print(e.output.decode("utf-8", "ignore"))

# also dump Ad model slides on server
import os
p = "/www/wwwroot/g.aifeili.cn/app/model/Ad.php"
print("==== Ad.php ====")
print(open(p, encoding="utf-8", errors="ignore").read())

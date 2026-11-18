#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import shutil

ROOT = "/www/wwwroot/g.aifeili.cn"

# --- admin_resources.php ads block ---
cfg = os.path.join(ROOT, "config/admin_resources.php")
s = open(cfg, encoding="utf-8").read()
bak = cfg + ".bak_ads_hint"
if not os.path.isfile(bak):
    open(bak, "w", encoding="utf-8").write(s)

old_status = "'status' => ['field' => 'is_active', 'options' => ['1' => '启用', '0' => '停用'], 'bool' => true],\n        'actions' => ['approve' => true, 'offline' => false],\n        'editable' => ['title', 'position', 'type', 'image', 'link', 'sort', 'is_active'],"
# only replace inside ads — find ads section
m = re.search(r"'ads'\s*=>\s*\[(.*?)\n    \],\n\n    'ad-bookings'", s, re.S)
if not m:
    print("ADS_BLOCK_NOT_FOUND")
else:
    block = m.group(0)
    nb = block
    nb = nb.replace("['key' => 'is_active', 'label' => '启用']", "['key' => 'sort', 'label' => '排序'],\n            ['key' => 'is_active', 'label' => '上架']")
    nb = nb.replace(
        "'options' => ['1' => '启用', '0' => '停用']",
        "'options' => ['1' => '上架', '0' => '下架']",
    )
    if "'hint'" not in nb:
        nb = nb.replace(
            "'fillable' => ['title', 'position', 'type', 'image', 'link', 'sort', 'is_active', 'starts_at', 'ends_at'],",
            "'fillable' => ['title', 'position', 'type', 'image', 'link', 'sort', 'is_active', 'starts_at', 'ends_at'],\n"
            "        'hint' => '标记为「上架」的广告会全部按「排序」进入对应广告位轮播，不会因数量多而被截掉；不想起作用请改「下架」。',",
        )
    s = s[: m.start()] + nb + s[m.end() :]
    open(cfg, "w", encoding="utf-8").write(s)
    print("admin_resources ads updated", "hint" in nb, "上架" in nb)

# --- meta() include hint ---
for rel in ["app/controller/api/AdminResource.php", "app/controller/api/v1/AdminResource.php"]:
    p = os.path.join(ROOT, rel)
    if not os.path.isfile(p):
        print("MISS", rel)
        continue
    t = open(p, encoding="utf-8").read()
    bak = p + ".bak_hint"
    if not os.path.isfile(bak):
        open(bak, "w", encoding="utf-8").write(t)
    if "'hint'" in t and "cfg['hint']" in t:
        print(rel, "hint already")
        continue
    needle = "'default_status' => $cfg['default_status'] ?? null,"
    if needle in t and "cfg['hint']" not in t:
        t = t.replace(
            needle,
            needle + "\n            'hint' => $cfg['hint'] ?? null,",
        )
        open(p, "w", encoding="utf-8").write(t)
        print(rel, "hint added")
    else:
        print(rel, "NO_NEEDLE", needle in t)

# --- hot patch admin resource vue chunk if present ---
# look for pageTitle / 示意图 nearby and inject hint text via CSS+DOM is hard;
# instead patch minified string for ads status 启用 if present
nuxt = os.path.join(ROOT, "public/_nuxt")
if os.path.isdir(nuxt):
    hit = 0
    for f in os.listdir(nuxt):
        if not f.endswith(".js"):
            continue
        p = os.path.join(nuxt, f)
        try:
            js = open(p, encoding="utf-8", errors="ignore").read()
        except Exception:
            continue
        # inject fallback hint near ads schematic flag
        if "admin/r/" in js or "admin-meta-" in js or "showAdSchematic" in js or "示意图" in js and "ads" in js:
            if "不会因数量多而被截掉" in js:
                print("chunk already", f)
                continue
        # lighter: nothing reliable without exact symbols
    print("nuxt skip heavy inject")

os.system("rm -rf /www/wwwroot/g.aifeili.cn/runtime/cache/* 2>/dev/null; echo ok")

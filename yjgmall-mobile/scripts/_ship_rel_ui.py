#!/usr/bin/env python3
"""Update quote_product hint on live; note AdminResourceForm needs frontend rebuild."""
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn")
fields = root / "config/admin_resource_fields.php"
t = fields.read_text(encoding="utf-8")
old = "「价格行情资讯」按品种挂文：选 DMC/生胶等。其它栏目子集请在「所属分类」选子类（如 网评 › 日评、产能产量 › 开工率）"
# also previous variants
new = "用于「价格行情资讯」：点下方标签快捷选择，或在框内搜索品种名。其它栏目子集请在「所属分类」选子类。"
nt = t
for o in [
    old,
    "「价格行情资讯」按品种选文：选DMC/生胶等。其它栏目子集请在「所属分类」选子类（如网评、日评、产能产量、开工率）",
    "「价格行情资讯」按品种挂文：选 DMC/生胶等。其它栏目子集请在「所属分类」选子类（如 网评 › 日评、产能产量 › 开工率）",
]:
    if o in nt:
        nt = nt.replace(o, new)
if "点下方标签快捷选择" not in nt and "quote_product_id" in nt:
    nt = re.sub(
        r"('name'\s*=>\s*'quote_product_id'[\s\S]{0,280}?'hint'\s*=>\s*)'[^']*'",
        r"\1'" + new + "'",
        nt,
        count=1,
    )
if nt != t:
    fields.write_text(nt, encoding="utf-8")
    print("hint updated")
else:
    print("hint already ok or not found")

# Find admin form chunk mentioning rel-search
nuxt = root / "public/_nuxt"
hits = []
for f in nuxt.glob("*.js"):
    try:
        s = f.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        continue
    if "rel-search" in s or ("relationCache" in s and "请选择" in s):
        hits.append(f.name)
print("admin form chunks", hits[:20])
print("ok")

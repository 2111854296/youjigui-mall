#!/usr/bin/env python3
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js").read_text(encoding="utf-8",errors="ignore")
i=s.find("127.0.0.1:7691")
print(repr(s[max(0,i-120):i+400]))
# try remove from fetch( to first ); after catch
a=s.rfind("fetch(", 0, i+10)
print("fetch at", a, "i", i)
print(repr(s[a:a+50]))
# find .catch
c=s.find(".catch(", a)
print("catch", c, repr(s[c:c+40]))
# end might be }).catch(()=>{}) or .catch(()=>{})
for end in [").catch(()=>{});", "}).catch(()=>{});", ".catch(()=>{});"]:
    b=s.find(end, a)
    print("try", end, b, b-a if b>=0 else None)

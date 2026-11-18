#!/usr/bin/env python3
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
# Find every occurrence of announcements||[]).slice
for f in sorted(root.glob("*.js")):
    t = f.read_text(encoding="utf-8", errors="ignore")
    if "announcements" not in t:
        continue
    hits = list(re.finditer(r"announcements\|\|\[\]\)\.slice\(0,\s*(\d+)\)", t))
    if not hits:
        # alternate compile forms
        hits2 = list(re.finditer(r"announcements.{0,40}slice\(0,\s*(\d+)\)", t))
        if hits2:
            print(f.name, "alt hits", [(m.group(1), m.group(0)[:80]) for m in hits2[:5]])
        continue
    print(f.name, [(m.group(1), m.start()) for m in hits])
    nt = re.sub(
        r"(announcements\|\|\[\]\)\.slice\(0,\s*)\d+(\))",
        r"\g<1>10\2",
        t,
    )
    if nt != t:
        f.write_text(nt, encoding="utf-8")
        print("  forced -> 10")

# Also inject runtime: expand notice list from API if fewer than api
js = r"""
(function(){
  if (window.__noticeMoreV1) return;
  window.__noticeMoreV1 = 1;
  function fill(){
    var card = document.querySelector('.notice-card');
    if (!card) return;
    var ul = card.querySelector('ul');
    if (!ul) return;
    fetch('/api/v1/home').then(function(r){return r.json()}).then(function(j){
      var list = (j && j.data && j.data.announcements) || [];
      if (!list.length) return;
      list = list.slice(0, 10);
      // rebuild list items
      var more = card.querySelector('.more-link');
      ul.innerHTML = '';
      list.forEach(function(a){
        var li = document.createElement('li');
        var link = document.createElement('a');
        var href = String(a.link||'').trim();
        if (!/^https?:\/\//i.test(href)) href = a.id ? ('/announce?id=' + a.id) : '/announce';
        link.setAttribute('href', href);
        link.textContent = a.title || '';
        li.appendChild(link);
        ul.appendChild(li);
      });
    }).catch(function(){});
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ setTimeout(fill, 600); });
  else setTimeout(fill, 600);
  setInterval(fill, 4000);
})();
""".lstrip()
(root / "notice-more.fix.js").write_text(js, encoding="utf-8")
print("wrote notice-more.fix.js")

public = Path("/www/wwwroot/g.aifeili.cn/public")
tag = '<script src="/_nuxt/notice-more.fix.js?v=1" defer></script>'
for name in ["index.html", "200.html"]:
    html = public / name
    h = html.read_text(encoding="utf-8", errors="ignore")
    if "notice-more.fix.js" in h:
        h2 = re.sub(r"notice-more\.fix\.js\?v=\d+", "notice-more.fix.js?v=1", h)
        if h2 != h:
            html.write_text(h2, encoding="utf-8")
        continue
    if "</head>" in h:
        html.write_text(h.replace("</head>", tag + "\n</head>", 1), encoding="utf-8")
        print("link", name)
print("ok")

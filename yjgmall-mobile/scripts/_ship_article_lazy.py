#!/usr/bin/env python3
"""Live: unwrap Destoon lazy images in article HTML + client-side safety net."""
from pathlib import Path
import re
import shutil

root = Path("/www/wwwroot/g.aifeili.cn")

# --- PHP helpers already in uploaded files; also inject client fix ---
nuxt = root / "public" / "_nuxt"
js = r"""
(function(){
  if (window.__articleLazyFixV1) return;
  window.__articleLazyFixV1 = 1;
  function realOf(img){
    var attrs=['original','data-original','data-src','data-lazy','data-url'];
    for (var i=0;i<attrs.length;i++){
      var v=(img.getAttribute(attrs[i])||'').trim();
      if (v && !/lazy\.gif|blank\.gif|spacer|loading\.gif/i.test(v)) return v;
    }
    return '';
  }
  function fix(root){
    var scope = root || document;
    var list = scope.querySelectorAll
      ? scope.querySelectorAll('img.lazy, img[original], img[data-src], img[data-original]')
      : [];
    list.forEach(function(img){
      var real = realOf(img);
      if (!real) return;
      var cur = img.getAttribute('src') || '';
      if (cur === real) return;
      if (!cur || /lazy\.gif|blank\.gif|spacer|loading\.gif/i.test(cur) || img.classList.contains('lazy')) {
        img.setAttribute('src', real);
        img.loading = 'eager';
      }
    });
  }
  function scan(){
    fix(document);
    document.querySelectorAll('.art-body, .announce-body, .dt-body, .rich-body').forEach(fix);
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ setTimeout(scan, 50); });
  else setTimeout(scan, 50);
  setInterval(scan, 800);
  // SPA 路由后也会扫
  document.addEventListener('click', function(){ setTimeout(scan, 120); }, true);
})();
""".lstrip()

(nuxt / "article-lazy.fix.js").write_text(js, encoding="utf-8")
print("wrote article-lazy.fix.js")

tag = '<script src="/_nuxt/article-lazy.fix.js?v=1" defer></script>'
for html in list((root / "public").rglob("**/index.html")) + [root / "public/index.html", root / "public/200.html"]:
    if not html.exists():
        continue
    # only site pages, skip admin if desired - actually fix everywhere is fine
    h = html.read_text(encoding="utf-8", errors="ignore")
    if "article-lazy.fix.js" in h:
        h2 = re.sub(r"article-lazy\.fix\.js\?v=\d+", "article-lazy.fix.js?v=1", h)
        if h2 != h:
            html.write_text(h2, encoding="utf-8")
            print("bump", html)
        continue
    if "</head>" not in h:
        continue
    # skip pure admin login maybe still ok
    html.write_text(h.replace("</head>", tag + "\n</head>", 1), encoding="utf-8")
    print("link", html)

print("ok")

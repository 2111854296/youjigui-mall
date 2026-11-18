#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Ensure banner clicks use current slide link: CSS + runtime fixer + HTML inject."""
import os
import re
import shutil

ROOT = "/www/wwwroot/g.aifeili.cn/public"
NUXT = os.path.join(ROOT, "_nuxt")

CSS = (
    ".banner-carousel .slide{pointer-events:none!important;z-index:0!important}"
    ".banner-carousel .slide.on{pointer-events:auto!important;z-index:1!important}\n"
)
JS = r"""
(function(){
  function fixBannerClicks(){
    var box=document.querySelector('.banner-carousel');
    if(!box) return;
    var slides=box.querySelectorAll('.slide');
    for(var i=0;i<slides.length;i++){
      var el=slides[i];
      if(el.classList.contains('on')){
        el.style.pointerEvents='auto';
        el.style.zIndex='1';
      }else{
        el.style.pointerEvents='none';
        el.style.zIndex='0';
      }
    }
  }
  setInterval(fixBannerClicks, 250);
  document.addEventListener('DOMContentLoaded', fixBannerClicks);
  if(document.readyState!=='loading') fixBannerClicks();
})();
"""

# write assets
open(os.path.join(NUXT, "banner-pe.fix.css"), "w", encoding="utf-8").write(CSS)
open(os.path.join(NUXT, "banner-pe.fix.js"), "w", encoding="utf-8").write(JS)

# inject into index.html
html_path = os.path.join(ROOT, "index.html")
if os.path.isfile(html_path):
    h = open(html_path, encoding="utf-8", errors="ignore").read()
    bak = html_path + ".bak_banner_click"
    if not os.path.isfile(bak):
        open(bak, "w", encoding="utf-8").write(h)
    # remove old pe tags then re-add
    h = re.sub(r'<link[^>]+banner-pe\.fix\.css[^>]*>\s*', '', h)
    h = re.sub(r'<script[^>]+banner-pe\.fix\.js[^>]*>\s*</script>\s*', '', h)
    inject = (
        '<link rel="stylesheet" href="/_nuxt/banner-pe.fix.css?v=10">\n'
        '<script src="/_nuxt/banner-pe.fix.js?v=10" defer></script>\n'
    )
    if "</head>" in h:
        h = h.replace("</head>", inject + "</head>", 1)
    else:
        h = inject + h
    open(html_path, "w", encoding="utf-8").write(h)
    print("index.html injected")

# append CSS force to entry/index css files
for f in os.listdir(NUXT):
    if not f.endswith(".css"):
        continue
    p = os.path.join(NUXT, f)
    s = open(p, encoding="utf-8", errors="ignore").read()
    if "banner-carousel" not in s:
        continue
    if "pointer-events:none!important" in s and "banner-pe-force" in s:
        print("css already", f)
        continue
    marker = "/*banner-pe-force*/" + CSS
    if "banner-pe-force" in s:
        s = re.sub(r"/\*banner-pe-force\*/[\s\S]*?(?=\Z)", marker, s, count=1)
    else:
        s = s + "\n" + marker
    open(p, "w", encoding="utf-8").write(s)
    print("css", f)

# inject loader into main app js (0a3*)
inject_boot = (
    ';(()=>{var c=document.createElement("link");c.rel="stylesheet";c.href="/_nuxt/banner-pe.fix.css?v=10";'
    'document.head.appendChild(c);var s=document.createElement("script");s.src="/_nuxt/banner-pe.fix.js?v=10";'
    'document.head.appendChild(s);})();'
)
for f in os.listdir(NUXT):
    if not f.endswith(".js"):
        continue
    if not (f.startswith("0a3") or f.startswith("entry")):
        continue
    p = os.path.join(NUXT, f)
    s = open(p, encoding="utf-8", errors="ignore").read()
    if "banner-pe.fix.js?v=10" in s:
        print("js already", f)
        continue
    # strip older injects
    s = re.sub(r';\(\(\)=>\{var l=document\.createElement\("link"\);l\.rel="stylesheet";l\.href="/_nuxt/banner-pe\.fix\.css[^"]*";document\.head\.appendChild\(l\);\}\)\(\);?', '', s)
    bak = p + ".bak_banner_click"
    if not os.path.isfile(bak):
        open(bak, "w", encoding="utf-8").write(s)
    open(p, "w", encoding="utf-8").write(inject_boot + s)
    print("js injected", f)

print("done")

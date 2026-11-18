#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Final reliable fix:
1) Restore homepage chunk from original bak (working carousel)
2) Only change: inactive slides pointer-events:none + click guard strips inactive href
3) Clean index.html entry back to 0a3-bBq-.js with pe assets
"""
import os
import re
import shutil

ROOT = "/www/wwwroot/g.aifeili.cn/public"
NUXT = os.path.join(ROOT, "_nuxt")
BAK = os.path.join(NUXT, "rMEP6MmM.js.bak_single_slide")
if not os.path.isfile(BAK):
    # try other baks
    for n in os.listdir(NUXT):
        if n.startswith("rMEP6MmM") and n.endswith(".bak_single_slide"):
            BAK = os.path.join(NUXT, n)
            break

if not os.path.isfile(BAK):
    raise SystemExit("NO_BAK")

raw = open(BAK, encoding="utf-8").read()
# Ensure original v-for (no broken filter / ternary)
if "q(a(le),(n,m)=>(r(),o(S,{key:n.id||m}" not in raw:
    raise SystemExit("BAK_UNEXPECTED")

# Write clean chunk names used by entry
for name in ["rMEP6MmM.js", "rMEP6MmM.ss.js", "rMEP6MmM.ss2.js", "rMEP6MmM.ss3.js"]:
    open(os.path.join(NUXT, name), "w", encoding="utf-8").write(raw)
print("restored chunks from bak", len(raw))

# Fix entries to import rMEP6MmM.js (canonical)
for f in os.listdir(NUXT):
    if not f.startswith("0a3") or not f.endswith(".js"):
        continue
    p = os.path.join(NUXT, f)
    t = open(p, encoding="utf-8", errors="ignore").read()
    t2 = re.sub(r"rMEP6MmM(?:\.ss3|\.ss2|\.ss)?\.js", "rMEP6MmM.js", t)
    # strip broken stamp boots that might break parsing if duplicated oddly
    open(p, "w", encoding="utf-8").write(t2)
    print("entry normalize", f)

# Strong click guard (this is the actual fix)
CSS = (
    ".banner-carousel .slide{pointer-events:none!important;z-index:0!important}"
    ".banner-carousel .slide.on{pointer-events:auto!important;z-index:2!important}\n"
)
JS = r"""
(function(){
  function fix(){
    var box=document.querySelector('.banner-carousel');
    if(!box) return;
    var slides=box.querySelectorAll('.slide');
    for(var i=0;i<slides.length;i++){
      var el=slides[i];
      var on=el.classList.contains('on');
      el.style.pointerEvents = on ? 'auto' : 'none';
      el.style.zIndex = on ? '2' : '0';
      if(el.tagName==='A'){
        if(on){
          if(el.dataset._href){ el.setAttribute('href', el.dataset._href); }
        } else {
          var href=el.getAttribute('href');
          if(href){ el.dataset._href=href; el.removeAttribute('href'); }
        }
      }
    }
  }
  if(window.__bannerFixV12) return;
  window.__bannerFixV12=1;
  document.addEventListener('click', function(e){
    var a=e.target && e.target.closest && e.target.closest('.banner-carousel a.slide, .banner-carousel a.img-slide');
    if(!a) return;
    if(!a.classList.contains('on')){
      e.preventDefault();
      e.stopImmediatePropagation();
      return false;
    }
  }, true);
  setInterval(fix, 150);
  document.addEventListener('DOMContentLoaded', fix);
  if(document.readyState!=='loading') fix();
})();
"""
open(os.path.join(NUXT, "banner-pe.fix.css"), "w", encoding="utf-8").write(CSS)
open(os.path.join(NUXT, "banner-pe.fix.js"), "w", encoding="utf-8").write(JS)

# Clean index.html: one entry + pe assets
html = os.path.join(ROOT, "index.html")
h = open(html, encoding="utf-8", errors="ignore").read()
# normalize entry script to 0a3-bBq-.js
h = re.sub(r"0a3-bBq-(?:b\d+\.)?js", "0a3-bBq-.js", h)
# remove old pe tags
h = re.sub(r'<link[^>]+banner-pe\.fix\.css[^>]*>\s*', '', h)
h = re.sub(r'<script[^>]+banner-pe\.fix\.js[^>]*>\s*</script>\s*', '', h)
inject = (
    '<link rel="stylesheet" href="/_nuxt/banner-pe.fix.css?v=12">\n'
    '<script src="/_nuxt/banner-pe.fix.js?v=12" defer></script>\n'
)
if "</head>" in h:
    h = h.replace("</head>", inject + "</head>", 1)
open(html, "w", encoding="utf-8").write(h)
print("html cleaned")

# Append pe boot to 0a3 once
boot = (
    ';(()=>{var c=document.createElement("link");c.rel="stylesheet";c.href="/_nuxt/banner-pe.fix.css?v=12";'
    'document.head.appendChild(c);var s=document.createElement("script");s.src="/_nuxt/banner-pe.fix.js?v=12";'
    's.defer=true;document.head.appendChild(s);})();'
)
p = os.path.join(NUXT, "0a3-bBq-.js")
t = open(p, encoding="utf-8", errors="ignore").read()
t = re.sub(
    r';\(\(\)=>\{var c=document\.createElement\("link"\);c\.rel="stylesheet";c\.href="/_nuxt/banner-pe\.fix\.css\?v=\d+";document\.head\.appendChild\(c\);var s=document\.createElement\("script"\);s\.src="/_nuxt/banner-pe\.fix\.js\?v=\d+";[^)]*\}\)\(\);?',
    '',
    t,
)
t = re.sub(r";window\.__BANNER_SINGLE_SLIDE=\d+;", "", t)
if "banner-pe.fix.js?v=12" not in t:
    t = boot + t
open(p, "w", encoding="utf-8").write(t)
print("0a3 boot ok", "rMEP6MmM.js" in t)

# append force css to entry css
for f in ["entry.DRch0_HE.css", "index.RB4plv3A.css"]:
    p = os.path.join(NUXT, f)
    if not os.path.isfile(p):
        continue
    css = open(p, encoding="utf-8", errors="ignore").read()
    css = re.sub(r"/\*banner-pe-force\*/[\s\S]*$", "", css)
    open(p, "w", encoding="utf-8").write(css + "\n/*banner-pe-force*/" + CSS)
    print("css", f)

print("OK")

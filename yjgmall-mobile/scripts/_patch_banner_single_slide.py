#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Patch live homepage chunk: only render current banner slide (no stacked clickable layers).
Also reinforce CSS/JS click guard.
"""
import os
import re
import shutil

ROOT = "/www/wwwroot/g.aifeili.cn/public"
NUXT = os.path.join(ROOT, "_nuxt")
CHUNK = os.path.join(NUXT, "rMEP6MmM.js")

# --- 1) Patch Vue render: v-for all banners -> only current index ---
# Old pattern starts with: q(a(le),(n,m)=>(r(),o(S,{key:n.id||m},[...
# We need the exact substring from the file.

s = open(CHUNK, encoding="utf-8").read()
bak = CHUNK + ".bak_single_slide"
if not os.path.isfile(bak):
    open(bak, "w", encoding="utf-8").write(s)

marker = 'class:"banner-carousel"'
i = s.find(marker)
if i < 0:
    raise SystemExit("banner-carousel not found")

# Find the q(a(le),(n,m)=> loop near banner
j = s.find("q(a(le),(n,m)=>", i)
if j < 0:
    # maybe already patched
    if "currentBanner" in s or "a(le)[a(A)]" in s or "((n=a(le)[a(A)])" in s:
        print("chunk maybe already single-slide")
    else:
        # print nearby for debug
        print(s[i : i + 900])
        raise SystemExit("v-for loop not found")
else:
    # Extract until matching close of q(...): look for "),128))" after this q(
    # The structure is: q(a(le),(n,m)=>(r(),o(S,{key:...},[...])),128))
    # Replace with rendering only current item.

    # Find end: "),128))" right after the slide loop (first such after j)
    end = s.find("),128))", j)
    if end < 0:
        raise SystemExit("loop end not found")
    end = end + len("),128))")
    old = s[j:end]
    print("OLD_LEN", len(old))
    print("OLD_HEAD", old[:180])
    print("OLD_TAIL", old[-80:])

    # New: only current banner as single element in fragment
    # le = banners, A = bannerIndex
    # Use: (n=a(le)[a(A)],n)?(r(),o(S,{key:n.id||a(A)},[...same body with m=== replaced by true...])):C("",!0)
    #
    # Simpler approach: change q(a(le),(n,m)=>...) to q([a(le)[a(A)]].filter(Boolean),(n,m)=>...)
    # so only one item (current) is iterated; class on always true since m===0 and only one.
    new = old.replace(
        "q(a(le),(n,m)=>",
        "q([a(le)[a(A)]].filter(Boolean),(n,m)=>",
        1,
    )
    # Force .on class always for the single rendered slide
    new = new.replace('{on:m===a(A)}', "{on:!0}")
    new = new.replace("{on:m===a(A)}", "{on:!0}")
    if new == old:
        # try without escape differences
        print("WARN replace produced same; trying alternate")
    s = s[:j] + new + s[end:]
    # bust name
    dst = os.path.join(NUXT, "rMEP6MmM.ss.js")
    open(CHUNK, "w", encoding="utf-8").write(s)
    shutil.copy2(CHUNK, dst)
    # rewrite refs
    nref = 0
    for f in os.listdir(NUXT):
        if not f.endswith(".js"):
            continue
        p = os.path.join(NUXT, f)
        t = open(p, encoding="utf-8", errors="ignore").read()
        if "rMEP6MmM.js" in t and f != "rMEP6MmM.ss.js":
            open(p, "w", encoding="utf-8").write(t.replace("rMEP6MmM.js", "rMEP6MmM.ss.js"))
            print("bust", f)
            nref += 1
    print("chunk patched", "refs", nref, "has_filter", "[a(le)[a(A)]].filter(Boolean)" in s)

# --- 2) Aggressive click guard ---
CSS = (
    ".banner-carousel .slide{pointer-events:none!important;z-index:0!important}"
    ".banner-carousel .slide.on{pointer-events:auto!important;z-index:1!important}\n"
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
      el.style.pointerEvents=on?'auto':'none';
      el.style.zIndex=on?'1':'0';
      if(el.tagName==='A'){
        if(on){
          if(el.dataset._href && !el.getAttribute('href')) el.setAttribute('href', el.dataset._href);
        }else{
          var href=el.getAttribute('href');
          if(href){ el.dataset._href=href; el.removeAttribute('href'); }
        }
      }
    }
  }
  if(!window.__bannerClickGuard){
    window.__bannerClickGuard=true;
    document.addEventListener('click', function(e){
      var a=e.target && e.target.closest && e.target.closest('.banner-carousel a.slide');
      if(!a) return;
      if(!a.classList.contains('on')){
        e.preventDefault();
        e.stopPropagation();
      }
    }, true);
    setInterval(fix, 200);
    document.addEventListener('DOMContentLoaded', fix);
    if(document.readyState!=='loading') fix();
  }
})();
"""
open(os.path.join(NUXT, "banner-pe.fix.css"), "w", encoding="utf-8").write(CSS)
open(os.path.join(NUXT, "banner-pe.fix.js"), "w", encoding="utf-8").write(JS)

html_path = os.path.join(ROOT, "index.html")
if os.path.isfile(html_path):
    h = open(html_path, encoding="utf-8", errors="ignore").read()
    h = re.sub(r'<link[^>]+banner-pe\.fix\.css[^>]*>\s*', '', h)
    h = re.sub(r'<script[^>]+banner-pe\.fix\.js[^>]*>\s*</script>\s*', '', h)
    inject = (
        '<link rel="stylesheet" href="/_nuxt/banner-pe.fix.css?v=11">\n'
        '<script src="/_nuxt/banner-pe.fix.js?v=11" defer></script>\n'
    )
    if "</head>" in h:
        h = h.replace("</head>", inject + "</head>", 1)
    open(html_path, "w", encoding="utf-8").write(h)
    print("html ok")

# re-inject boot into 0a3
boot = (
    ';(()=>{var c=document.createElement("link");c.rel="stylesheet";c.href="/_nuxt/banner-pe.fix.css?v=11";'
    'document.head.appendChild(c);var s=document.createElement("script");s.src="/_nuxt/banner-pe.fix.js?v=11";'
    'document.head.appendChild(s);})();'
)
for f in os.listdir(NUXT):
    if not f.startswith("0a3") or not f.endswith(".js"):
        continue
    p = os.path.join(NUXT, f)
    t = open(p, encoding="utf-8", errors="ignore").read()
    t = re.sub(
        r';\(\(\)=>\{var c=document\.createElement\("link"\);c\.rel="stylesheet";c\.href="/_nuxt/banner-pe\.fix\.css\?v=\d+";document\.head\.appendChild\(c\);var s=document\.createElement\("script"\);s\.src="/_nuxt/banner-pe\.fix\.js\?v=\d+";document\.head\.appendChild\(s\);\}\)\(\);?',
        '',
        t,
    )
    t = re.sub(
        r';\(\(\)=>\{var l=document\.createElement\("link"\);l\.rel="stylesheet";l\.href="/_nuxt/banner-pe\.fix\.css[^"]*";document\.head\.appendChild\(l\);var s=document\.createElement\("script"\);s\.src="/_nuxt/banner-pe\.fix\.js[^"]*";document\.head\.appendChild\(s\);\}\)\(\);?',
        '',
        t,
    )
    if "banner-pe.fix.js?v=11" not in t:
        open(p, "w", encoding="utf-8").write(boot + t)
        print("0a3 boot", f)
    else:
        print("0a3 already", f)

print("DONE")

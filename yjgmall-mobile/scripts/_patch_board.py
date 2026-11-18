#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
import re

paths = [
    Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js"),
    Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js"),
    Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit8.js"),
]

old_cls = 'class:"line-wrap",{locked:!n(m)}'
new_cls = 'class:"line-wrap",{locked:!n(m),board:!!n(u).showDots}'

# crosshair vertical line
old_ch = 'y1:H,x2:n(d).x,y2:z-O'
new_ch = 'y1:(n(u).showDots?18:H),x2:n(d).x,y2:(n(u).h||z)-(n(u).showDots?36:O)'

for path in paths:
    s = path.read_text(encoding="utf-8")
    ok = True
    if old_cls not in s:
        print("MISSING cls", path.name, "have board?" , "board:!!n(u).showDots" in s)
        ok = False
    else:
        s = s.replace(old_cls, new_cls, 1)
        print("OK cls", path.name)
    # may appear once for vertical crosshair
    c = s.count(old_ch)
    print("ch count", c, path.name)
    if old_ch in s:
        s = s.replace(old_ch, new_ch)
        print("OK ch", path.name)
    # also y2:z-O elsewhere?
    for m in re.finditer(r"y2:z-O|y1:H,", s):
        print(" leftover", m.group(), m.start())
    # verify showDots in file
    print("showDots", s.count("showDots"), "circle r", s.count('r:"4.5"'), path.name)
    path.write_text(s, encoding="utf-8")

# bump expand script cache + add board class observer as safety
exp = Path("/www/wwwroot/g.aifeili.cn/public/quote-chart-expand.js")
t = exp.read_text(encoding="utf-8")
# append board class sync if not present
if "qce-sync-board" not in t:
    addon = r'''

  /* 长周期：给图容器加 board 类，配合 CSS 280 高 */
  function syncBoardClass() {
    if (!isQuoteDetail()) return
    var long = false
    document.querySelectorAll("button, .range-btn, [class*=range]").forEach(function (el) {
      var tx = (el.textContent || "").trim()
      if (LONG[tx] && (el.classList.contains("active") || el.getAttribute("aria-pressed") === "true" || /active|on|current|is-active/.test(el.className))) long = true
    })
    document.querySelectorAll(".line-wrap").forEach(function (w) {
      if (long) w.classList.add("board")
      else w.classList.remove("board")
    })
  }
  document.addEventListener("click", function () { setTimeout(syncBoardClass, 50) }, true)
  setInterval(syncBoardClass, 1200)
  setTimeout(syncBoardClass, 400)
'''
    # insert before end of IIFE - last `})();`
    if t.rstrip().endswith("})();"):
        t = t.rstrip()[:-5] + addon + "\n})();"
        exp.write_text(t, encoding="utf-8")
        print("OK expand board sync, len", len(t))
    else:
        print("expand end unexpected", repr(t[-30:]))
else:
    print("expand already has sync")

# verify syntax-ish: showDots fragment balanced
s = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js").read_text(encoding="utf-8")
idx = s.find("showDots")
print("sample ret", s[s.find("showDots:!P"):s.find("showDots:!P")+80])
print("sample line", s[s.find('r:"4.5"')-120:s.find('r:"4.5"')+100])
print("sample vb", s[s.find("viewBox:`"):s.find("viewBox:`")+50])

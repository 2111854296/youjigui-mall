#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path

paths = [
    Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js"),
    Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js"),
    Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit8.js"),
]
old = 'class:$t(["line-wrap",{locked:!n(m)}])'
new = 'class:$t(["line-wrap",{locked:!n(m),board:!!n(u).showDots}])'
for path in paths:
    s = path.read_text(encoding="utf-8")
    if old not in s:
        print("MISS", path.name, "already" if "board:!!n(u).showDots" in s else "diff", repr(s[s.find("line-wrap")-20:s.find("line-wrap")+50]))
    else:
        path.write_text(s.replace(old, new, 1), encoding="utf-8")
        print("OK", path.name)

exp = Path("/www/wwwroot/g.aifeili.cn/public/quote-chart-expand.js")
t = exp.read_text(encoding="utf-8")
if "qce-sync-board" not in t and "syncBoardClass" not in t:
    addon = """
  /* qce-sync-board: 长周期加 board 类 */
  function syncBoardClass() {
    if (!isQuoteDetail()) return
    var long = false
    try {
      document.querySelectorAll("button").forEach(function (el) {
        var tx = (el.textContent || "").trim()
        if (!LONG[tx]) return
        var cls = el.className || ""
        if (cls.indexOf("active") >= 0 || cls.indexOf("on") >= 0 || el.getAttribute("aria-pressed") === "true") long = true
      })
    } catch (e) {}
    document.querySelectorAll(".line-wrap").forEach(function (w) {
      if (long) w.classList.add("board")
      else w.classList.remove("board")
    })
  }
  document.addEventListener("click", function () { setTimeout(syncBoardClass, 80) }, true)
  setInterval(syncBoardClass, 1500)
  setTimeout(syncBoardClass, 500)
"""
    if t.rstrip().endswith("})()"):
        t = t.rstrip()[:-4] + addon + "\n})()\n"
        exp.write_text(t, encoding="utf-8")
        print("OK expand", len(t))
    else:
        print("bad end", repr(t.rstrip()[-20:]))
else:
    print("expand skip")

# also horizontal crosshair y uses n(d).y which is fine
# touch tip rect etc OK
print("verify board class", "board:!!n(u).showDots" in Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js").read_text())
print("verify css", "280px" in Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/QuoteKlineChart.KSdW9eP7.css").read_text())

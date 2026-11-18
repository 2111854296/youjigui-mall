#!/usr/bin/env python3
"""Add region hint to quote detail chart/history titles on live."""
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn")
nuxt = root / "public" / "_nuxt"

# Try patch minified titles: "价格走势" / "历史报价" near region context is hard.
# Runtime patch is more reliable.
js = r"""
(function(){
  if (window.__regionHintV1) return;
  window.__regionHintV1 = 1;

  function regionFromPage(){
    try {
      var q = new URLSearchParams(location.search).get('region');
      if (q) return decodeURIComponent(q).trim();
    } catch (e) {}
    var btn = document.querySelector('.dt-switch-btn');
    if (!btn) return '';
    var t = (btn.textContent || '').replace(/[⇄▾]/g, '').trim();
    if (!t || t === '平均价格') return '';
    return t;
  }

  function apply(){
    if (!/^\/quotes\/[^\/]+/.test(location.pathname)) return;
    var region = regionFromPage();
    var hint = region ? (' (' + region + ')') : '';
    var heads = document.querySelectorAll('.dt-chart-card .dt-h > span, .dt-card .dt-h > span');
    heads.forEach(function(span){
      var raw = span.getAttribute('data-base-title');
      if (!raw) {
        raw = (span.textContent || '').replace(/\s*\([^)]*地区\)\s*$/, '').replace(/\s*\([^)]+\)\s*$/, '').trim();
        // only touch chart/history titles
        if (!/价格走势$/.test(raw) && raw !== '历史报价') return;
        span.setAttribute('data-base-title', raw);
      }
      var next = raw + hint;
      if (span.textContent !== next) span.textContent = next;
    });
  }

  function boot(){
    apply();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
  setInterval(apply, 800);
  document.addEventListener('click', function(){ setTimeout(apply, 200); }, true);
  window.addEventListener('popstate', function(){ setTimeout(apply, 200); });
})();
""".lstrip()

(nuxt / "region-hint.fix.js").write_text(js, encoding="utf-8")
print("wrote region-hint.fix.js")

# Also patch source-like strings in quote detail chunks if present
patched = 0
for f in nuxt.glob("*.js"):
    try:
        t = f.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        continue
    if "价格走势" not in t or "历史报价" not in t:
        continue
    if "dt-quote-hero" not in t and "rangeTitle" not in t and "active_region" not in t:
        continue
    nt = t
    # Vue compiled: rangeTitle+"价格走势"  or  similar
    # Add region hint after 价格走势 when we can find a simple pattern
    # Look for: 价格走势") without already having region
    if '价格走势"+' in nt or "价格走势'+" in nt:
        pass
    # Common: toDisplayString(rangeTitle)+"价格走势"
    for a, b in [
        ('+"价格走势"', '+"价格走势"+(activeRegion.value?(" ("+activeRegion.value+")"):"")'),
        ("+'价格走势'", "+'价格走势'+(activeRegion.value?(' ('+activeRegion.value+')'):'')"),
    ]:
        if a in nt and b not in nt:
            # too risky if activeRegion not in scope with that name
            pass
    # Safer leave to runtime
    if nt != t:
        f.write_text(nt, encoding="utf-8")
        patched += 1
        print("js", f.name)
print("chunk patched", patched)

tag = '<script src="/_nuxt/region-hint.fix.js?v=1" defer></script>'
for name in ["index.html", "200.html"]:
    html = root / "public" / name
    if not html.exists():
        continue
    h = html.read_text(encoding="utf-8", errors="ignore")
    if "region-hint.fix.js" in h:
        continue
    if "</head>" in h:
        html.write_text(h.replace("</head>", tag + "\n</head>", 1), encoding="utf-8")
        print("link", name)

# quotes detail shells
for html in (root / "public").rglob("quotes/**/index.html"):
    h = html.read_text(encoding="utf-8", errors="ignore")
    if "region-hint.fix.js" in h or "</head>" not in h:
        continue
    html.write_text(h.replace("</head>", tag + "\n</head>", 1), encoding="utf-8")
    print("link", html)
print("ok")

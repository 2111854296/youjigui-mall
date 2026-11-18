#!/usr/bin/env python3
"""Ship left-to-right chart draw animation as a live runtime patch."""
from pathlib import Path

root = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
public = Path("/www/wwwroot/g.aifeili.cn/public")

js = r"""
(function () {
  if (window.__chartDrawAnimV1) return;
  window.__chartDrawAnimV1 = 1;
  var MS = 1200;

  function ease(t) { return 1 - Math.pow(1 - t, 3); }

  function ensureClip(svg, uid) {
    var ns = 'http://www.w3.org/2000/svg';
    var defs = svg.querySelector('defs');
    if (!defs) {
      defs = document.createElementNS(ns, 'defs');
      svg.insertBefore(defs, svg.firstChild);
    }
    var id = 'liveDrawClip-' + uid;
    var clip = defs.querySelector('#' + id);
    if (!clip) {
      clip = document.createElementNS(ns, 'clipPath');
      clip.setAttribute('id', id);
      var rect = document.createElementNS(ns, 'rect');
      rect.setAttribute('x', '0');
      rect.setAttribute('y', '0');
      rect.setAttribute('height', String(svg.viewBox && svg.viewBox.baseVal ? svg.viewBox.baseVal.height : (svg.clientHeight || 200)));
      rect.setAttribute('width', '0');
      clip.appendChild(rect);
      defs.appendChild(clip);
    }
    return { id: id, rect: clip.querySelector('rect') };
  }

  function wrapPaths(svg, clipId) {
    var paths = [].slice.call(svg.querySelectorAll('path'));
    var line = null, area = null;
    paths.forEach(function (p) {
      if (p.closest('[data-draw-wrap]')) return;
      var fill = (p.getAttribute('fill') || '').trim();
      var stroke = (p.getAttribute('stroke') || '').trim();
      if (fill && fill !== 'none' && /url\(/.test(fill)) area = p;
      else if (stroke && stroke !== 'none' && fill === 'none') line = p;
      else if (stroke && stroke !== 'none' && (!fill || fill === 'none')) line = p;
    });
    if (!line && !area) return null;
    var ns = 'http://www.w3.org/2000/svg';
    var g = document.createElementNS(ns, 'g');
    g.setAttribute('data-draw-wrap', '1');
    g.setAttribute('clip-path', 'url(#' + clipId + ')');
    var anchor = line || area;
    anchor.parentNode.insertBefore(g, anchor);
    if (area) g.appendChild(area);
    if (line) g.appendChild(line);
    return g;
  }

  function animate(svg) {
    if (!svg || svg.getAttribute('data-draw-busy') === '1') return;
    var vb = svg.viewBox && svg.viewBox.baseVal;
    var w = vb && vb.width ? vb.width : (parseFloat(svg.getAttribute('width')) || svg.clientWidth || 520);
    var uid = svg.getAttribute('data-draw-uid');
    if (!uid) {
      uid = Math.random().toString(36).slice(2, 9);
      svg.setAttribute('data-draw-uid', uid);
    }
    var clip = ensureClip(svg, uid);
    wrapPaths(svg, clip.id);
    if (!clip.rect) return;
    svg.setAttribute('data-draw-busy', '1');
    var start = performance.now();
    clip.rect.setAttribute('width', '0');
    function tick(now) {
      var t = Math.min(1, (now - start) / MS);
      clip.rect.setAttribute('width', String(w * ease(t)));
      if (t < 1) requestAnimationFrame(tick);
      else svg.removeAttribute('data-draw-busy');
    }
    requestAnimationFrame(tick);
  }

  function fingerprint(svg) {
    var paths = [].slice.call(svg.querySelectorAll('path'));
    return paths.map(function (p) { return (p.getAttribute('d') || '').slice(0, 80); }).join('|');
  }

  function scan() {
    var svgs = document.querySelectorAll('svg.line-chart, svg.spark, .line-wrap svg, .qc-chart svg');
    svgs.forEach(function (svg) {
      var fp = fingerprint(svg);
      if (!fp) return;
      if (svg.getAttribute('data-draw-fp') === fp) return;
      svg.setAttribute('data-draw-fp', fp);
      // delay a tick so Vue finishes patching DOM
      setTimeout(function () { animate(svg); }, 30);
    });
  }

  var mo = new MutationObserver(function () {
    clearTimeout(window.__chartDrawT);
    window.__chartDrawT = setTimeout(scan, 80);
  });
  function boot() {
    scan();
    mo.observe(document.documentElement, { childList: true, subtree: true, attributes: true, attributeFilter: ['d'] });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
""".lstrip()

(root / "chart-draw.fix.js").write_text(js, encoding="utf-8")
print("wrote chart-draw.fix.js")

tag = '<script src="/_nuxt/chart-draw.fix.js?v=1" defer></script>'
n = 0
for html in [
    public / "index.html",
    public / "200.html",
    public / "news" / "index.html",
    public / "quotes" / "index.html",
]:
    if not html.exists():
        continue
    t = html.read_text(encoding="utf-8", errors="ignore")
    if "chart-draw.fix.js" in t:
        continue
    if "</head>" not in t:
        continue
    html.write_text(t.replace("</head>", tag + "\n</head>", 1), encoding="utf-8")
    n += 1
    print("linked", html)

# also link in any quotes/[id] shells
for html in public.rglob("quotes/**/index.html"):
    t = html.read_text(encoding="utf-8", errors="ignore")
    if "chart-draw.fix.js" in t or "</head>" not in t:
        continue
    html.write_text(t.replace("</head>", tag + "\n</head>", 1), encoding="utf-8")
    n += 1
print("linked total", n)
print("ok")

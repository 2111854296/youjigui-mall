#!/usr/bin/env python3
"""Fix announce click + richtext editor on live."""
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn")
public = root / "public"
nuxt = public / "_nuxt"

# 1) admin fields: content -> richtext
fields = root / "config/admin_resource_fields.php"
t = fields.read_text(encoding="utf-8")
bak = fields.with_suffix(".php.bak_announce_rich")
if not bak.exists():
    bak.write_text(t, encoding="utf-8")

# Replace announcements content textarea -> richtext (idempotent)
old = """      'name' => 'content',
      'label' => '内容',
      'type' => 'textarea',
    ),
    2 => 
    array (
      'name' => 'link',
      'label' => '链接',
      'type' => 'text',
    ),"""

# Try flexible regex within announcements block
m = re.search(r"'announcements'\s*=>\s*array\s*\((.*?)\)\s*,\s*'hotwords'", t, re.S)
if not m:
    print("ERROR: announcements block not found")
else:
    block = m.group(1)
    block2 = block
    block2 = re.sub(
        r"('name'\s*=>\s*'content'[\s\S]*?'type'\s*=>\s*)'textarea'",
        r"\1'richtext',\n      'placeholder' => '请输入公告内容，支持标题、列表、链接等排版'",
        block2,
        count=1,
    )
    block2 = re.sub(
        r"('name'\s*=>\s*'link'[\s\S]*?'label'\s*=>\s*)'链接'",
        r"\1'外链（可选）'",
        block2,
        count=1,
    )
    if "'hint'" not in block2.split("'name' => 'link'")[1][:400]:
        block2 = re.sub(
            r"('name'\s*=>\s*'link'[\s\S]*?'type'\s*=>\s*'text')\s*,",
            r"\1,\n      'hint' => '填写后首页点击会跳此外链；留空则进入本站公告详情',",
            block2,
            count=1,
        )
    if block2 != block:
        t = t[: m.start(1)] + block2 + t[m.end(1) :]
        fields.write_text(t, encoding="utf-8")
        print("patched admin_resource_fields announcements")
    else:
        print("fields already patched or pattern mismatch")
        # show snippet
        print(block[:500])

# 2) front runtime: rewrite home notice links + announce page open by ?id=
js = r"""
(function(){
  if (window.__announceFixV2) return;
  window.__announceFixV2 = 1;

  function homeFix(){
    var card = document.querySelector('.notice-card');
    if (!card) return;
    card.querySelectorAll('ul a[href]').forEach(function(a){
      var href = a.getAttribute('href') || '';
      // already detail
      if (/\/announce\/?\?id=/.test(href)) return;
      // try get id from nearby data or title match later
      var li = a.closest('li');
      if (!li) return;
      // If href is bare /announce or empty-ish, mark for resolve
      if (href === '/announce' || href === '/announce/' || href === '') {
        a.setAttribute('data-need-announce-id', '1');
      }
    });
  }

  // Resolve home links using announcements API once
  var cache = null;
  function loadAnnouncements(){
    if (cache) return Promise.resolve(cache);
    return fetch('/api/v1/announcements').then(function(r){return r.json()}).then(function(j){
      cache = (j && j.data) || [];
      return cache;
    }).catch(function(){ cache = []; return cache; });
  }

  function applyHomeIds(){
    var card = document.querySelector('.notice-card');
    if (!card) return;
    loadAnnouncements().then(function(list){
      card.querySelectorAll('ul a[href]').forEach(function(a){
        var href = a.getAttribute('href') || '';
        var title = (a.textContent || '').trim();
        var external = /^(https?:)?\/\//i.test(href);
        var custom = href.startsWith('/') && !/^\/announce\/?$/i.test(href) && href.indexOf('/announce?') < 0;
        if (external || custom) return;
        var hit = list.find(function(x){ return String(x.title||'').trim() === title; });
        if (hit && hit.id) {
          a.setAttribute('href', '/announce?id=' + hit.id);
        } else if (href === '/announce' || href === '/announce/' || !href) {
          // keep list page
        }
      });
    });
  }

  function openFromQuery(){
    if (!/^\/announce\/?$/.test(location.pathname)) return;
    var m = /[?&]id=(\d+)/.exec(location.search);
    if (!m) return;
    var id = Number(m[1]);
    if (!id) return;
    // If Vue already shows detail, skip
    if (document.querySelector('.dt-card .dt-body.announce-body, .dt-card .announce-body')) return;
    loadAnnouncements().then(function(list){
      var item = list.find(function(x){ return Number(x.id) === id; });
      if (!item) return;
      var panel = document.querySelector('.list-panel, .section .container');
      if (!panel) return;
      if (document.querySelector('[data-announce-detail="1"]')) return;
      var box = document.createElement('div');
      box.className = 'dt-card';
      box.setAttribute('data-announce-detail', '1');
      var content = String(item.content || '').trim();
      if (content && !/<[a-z][\s\S]*>/i.test(content)) {
        content = content.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
      }
      if (!content) content = '暂无正文';
      box.innerHTML =
        '<a href="/announce" class="btn-soft-link" style="margin-bottom:12px">« 返回列表</a>' +
        '<div class="dt-sum"><h1></h1><p class="meta"></p></div>' +
        '<div class="dt-body announce-body"></div>';
      box.querySelector('h1').textContent = item.title || '';
      box.querySelector('.meta').textContent = String(item.created_at || '').slice(0, 16);
      box.querySelector('.announce-body').innerHTML = content;
      // hide list if present
      var list = document.querySelector('.list-panel');
      if (list) list.style.display = 'none';
      var host = document.querySelector('.section .container') || document.querySelector('.ch-portal .container');
      if (host) host.insertBefore(box, host.firstChild);
    });
  }

  function boot(){
    homeFix();
    applyHomeIds();
    openFromQuery();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
  // SPA navigations
  document.addEventListener('click', function(){ setTimeout(boot, 350); }, true);
  window.addEventListener('popstate', function(){ setTimeout(boot, 200); });
})();
""".lstrip()

css = """
.announce-body p{margin:0 0 .85em;line-height:1.75}
.announce-body p:last-child{margin-bottom:0}
.announce-body ul,.announce-body ol{margin:0 0 .85em;padding-left:1.4em}
.announce-body h2,.announce-body h3{margin:1.1em 0 .5em;font-weight:800;line-height:1.35}
.announce-body blockquote{margin:.8em 0;padding:8px 12px;border-left:3px solid #f0d4b5;background:#fff8f0;color:#6b4a2e}
.announce-body a{color:#2468f2}
.dt-body{line-height:1.75;word-break:break-word}
""".strip() + "\n"

(nuxt / "announce-fix.js").write_text(js, encoding="utf-8")
(nuxt / "announce-fix.css").write_text(css, encoding="utf-8")
print("wrote announce-fix assets")

tag = (
    '<link rel="stylesheet" href="/_nuxt/announce-fix.css?v=2">\n'
    '<script src="/_nuxt/announce-fix.js?v=2" defer></script>'
)
linked = 0
for name in ["index.html", "200.html", "announce/index.html"]:
    html = public / name
    if not html.exists():
        continue
    h = html.read_text(encoding="utf-8", errors="ignore")
    if "announce-fix.js" in h:
        h2 = re.sub(r"announce-fix\.(css|js)\?v=\d+", r"announce-fix.\1?v=2", h)
        if h2 != h:
            html.write_text(h2, encoding="utf-8")
            print("bump", html)
        continue
    if "</head>" in h:
        html.write_text(h.replace("</head>", tag + "\n</head>", 1), encoding="utf-8")
        linked += 1
        print("link", html)
print("linked", linked)
print("ok")

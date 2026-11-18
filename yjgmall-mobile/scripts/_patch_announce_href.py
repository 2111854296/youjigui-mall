#!/usr/bin/env python3
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn")

# Update hint in fields
fields = root / "config/admin_resource_fields.php"
t = fields.read_text(encoding="utf-8")
t2 = t.replace(
    "填写后首页点击会跳此外链；留空则进入本站公告详情",
    "仅填 http(s) 外链时，首页点击会直接打开外链；站内路径请留空，点击会进入本条公告详情",
)
if t2 != t:
    fields.write_text(t2, encoding="utf-8")
    print("hint updated")

# Clear misleading internal /pages/help links so detail page is the default
php = r'''<?php
$env=file("/www/wwwroot/g.aifeili.cn/.env");$c=[];
foreach($env as $l){$l=trim($l);if($l===""||$l[0]==="#")continue;if(strpos($l,"=")===false)continue;[$k,$v]=explode("=",$l,2);$c[trim($k)]=trim($v," \t\"'");}
$m=new mysqli($c["DB_HOST"],$c["DB_USER"],$c["DB_PASS"],$c["DB_NAME"],(int)($c["DB_PORT"]??3306));
$m->query("UPDATE announcements SET link=NULL WHERE link='/pages/help' OR link='pages/help'");
echo "cleared rows=", $m->affected_rows, "\n";
$r=$m->query("SELECT id,title,link FROM announcements ORDER BY id");
while($row=$r->fetch_assoc()){echo json_encode($row,JSON_UNESCAPED_UNICODE),"\n";}
'''
Path("/tmp/_clr_ann_link.php").write_text(php, encoding="utf-8")
import subprocess
print(subprocess.check_output(["php", "/tmp/_clr_ann_link.php"], stderr=subprocess.STDOUT).decode("utf-8", "replace"))

# Update runtime JS to always prefer /announce?id=
js_path = root / "public/_nuxt/announce-fix.js"
js = r"""
(function(){
  if (window.__announceFixV3) return;
  window.__announceFixV3 = 1;

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
        if (/^https?:\/\//i.test(href)) return;
        var hit = list.find(function(x){ return String(x.title||'').trim() === title; });
        if (hit && hit.id) {
          a.setAttribute('href', '/announce?id=' + hit.id);
        }
      });
      // also "更多" stays /announce
    });
  }

  function bodyHtml(raw){
    var s = String(raw || '').trim();
    if (!s) return '暂无正文';
    if (/<[a-z][\s\S]*>/i.test(s)) return s;
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
  }

  function openFromQuery(){
    if (!/^\/announce\/?$/.test(location.pathname)) return;
    var m = /[?&]id=(\d+)/.exec(location.search);
    if (!m) {
      var injected = document.querySelector('[data-announce-detail="1"]');
      if (injected) injected.remove();
      var list = document.querySelector('.list-panel');
      if (list) list.style.display = '';
      return;
    }
    var id = Number(m[1]);
    if (!id) return;
    if (document.querySelector('[data-announce-detail="1"]')) return;
    // If Vue source already rendered detail with announce-body, skip
    if (document.querySelector('.dt-card .announce-body') && !document.querySelector('.list-panel')) return;
    loadAnnouncements().then(function(list){
      var item = list.find(function(x){ return Number(x.id) === id; });
      if (!item) return;
      if (document.querySelector('[data-announce-detail="1"]')) return;
      var box = document.createElement('div');
      box.className = 'dt-card';
      box.setAttribute('data-announce-detail', '1');
      box.innerHTML =
        '<a href="/announce" class="btn-soft-link" style="margin-bottom:12px">« 返回列表</a>' +
        '<div class="dt-sum"><h1></h1><p class="meta"></p></div>' +
        '<div class="dt-body announce-body"></div>' +
        (item.link && /^https?:\/\//i.test(String(item.link))
          ? ('<p style="margin-top:14px"><a href="'+String(item.link)+'" target="_blank" rel="noopener">相关链接</a></p>')
          : '');
      box.querySelector('h1').textContent = item.title || '';
      box.querySelector('.meta').textContent = String(item.created_at || '').slice(0, 16);
      box.querySelector('.announce-body').innerHTML = bodyHtml(item.content);
      var list = document.querySelector('.list-panel');
      if (list) list.style.display = 'none';
      var host = document.querySelector('.section .container') || document.querySelector('.ch-portal .container');
      if (host) host.insertBefore(box, host.firstChild);
    });
  }

  function boot(){
    applyHomeIds();
    openFromQuery();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
  document.addEventListener('click', function(){ setTimeout(boot, 350); }, true);
  window.addEventListener('popstate', function(){ setTimeout(boot, 200); });
})();
""".lstrip()
js_path.write_text(js, encoding="utf-8")
print("updated announce-fix.js v3")

# bump cache version in html
for name in ["index.html", "200.html", "announce/index.html"]:
    html = root / "public" / name
    if not html.exists():
        continue
    h = html.read_text(encoding="utf-8", errors="ignore")
    h2 = re.sub(r"announce-fix\.(css|js)\?v=\d+", r"announce-fix.\1?v=3", h)
    if "announce-fix.js" not in h2:
        tag = '<link rel="stylesheet" href="/_nuxt/announce-fix.css?v=3">\n<script src="/_nuxt/announce-fix.js?v=3" defer></script>\n'
        h2 = h2.replace("</head>", tag + "</head>", 1)
    if h2 != h:
        html.write_text(h2, encoding="utf-8")
        print("html", name)
print("ok")

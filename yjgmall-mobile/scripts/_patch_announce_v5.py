#!/usr/bin/env python3
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn")
js_path = root / "public/_nuxt/announce-fix.js"
js = r"""
(function(){
  if (window.__announceFixV5) return;
  window.__announceFixV5 = 1;

  var cache = null;
  function loadAnnouncements(){
    if (cache) return Promise.resolve(cache);
    return fetch('/api/v1/announcements').then(function(r){return r.json()}).then(function(j){
      cache = (j && j.data) || [];
      return cache;
    }).catch(function(){ cache = []; return cache; });
  }

  function findByTitle(list, title){
    title = String(title || '').trim();
    return list.find(function(x){ return String(x.title||'').trim() === title; });
  }

  function rewriteHomeHrefs(){
    var card = document.querySelector('.notice-card');
    if (!card) return;
    loadAnnouncements().then(function(list){
      card.querySelectorAll('ul a').forEach(function(a){
        var title = (a.textContent || '').trim();
        if (!title || title.indexOf('更多') === 0) return;
        var href = a.getAttribute('href') || '';
        if (/^https?:\/\//i.test(href)) return;
        var hit = findByTitle(list, title);
        if (hit && hit.id) {
          var next = '/announce?id=' + hit.id;
          if (href !== next) a.setAttribute('href', next);
        }
      });
    });
  }

  function bodyHtml(raw){
    var s = String(raw || '').trim();
    if (!s) return '暂无正文';
    if (/<[a-z][\s\S]*>/i.test(s)) return s;
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
  }

  function renderDetail(item){
    var host = document.querySelector('.section .container') || document.querySelector('.ch-portal .container');
    if (!host) return false;
    var existing = document.querySelector('[data-announce-detail="1"]');
    if (existing) existing.remove();
    var box = document.createElement('div');
    box.className = 'dt-card';
    box.setAttribute('data-announce-detail', '1');
    var linkHtml = '';
    if (item.link && /^https?:\/\//i.test(String(item.link))) {
      linkHtml = '<p style="margin-top:14px"><a href="'+String(item.link)+'" target="_blank" rel="noopener">相关链接</a></p>';
    }
    box.innerHTML =
      '<a href="/announce" class="btn-soft-link" style="margin-bottom:12px">« 返回列表</a>' +
      '<div class="dt-sum"><h1></h1><p class="meta"></p></div>' +
      '<div class="dt-body announce-body"></div>' + linkHtml;
    box.querySelector('h1').textContent = item.title || '';
    box.querySelector('.meta').textContent = String(item.created_at || '').slice(0, 16);
    box.querySelector('.announce-body').innerHTML = bodyHtml(item.content);
    var list = document.querySelector('.list-panel');
    if (list) list.style.display = 'none';
    host.insertBefore(box, host.firstChild);
    return true;
  }

  function openFromQuery(){
    var path = location.pathname.replace(/\/+$/, '') || '/';
    if (path !== '/announce') return;
    var m = /[?&]id=(\d+)/.exec(location.search);
    if (!m) {
      var injected = document.querySelector('[data-announce-detail="1"]');
      if (injected) injected.remove();
      var list = document.querySelector('.list-panel');
      if (list) list.style.display = '';
      return;
    }
    if (document.querySelector('[data-announce-detail="1"]')) return;
    var id = Number(m[1]);
    loadAnnouncements().then(function(list){
      if (document.querySelector('[data-announce-detail="1"]')) return;
      var item = list.find(function(x){ return Number(x.id) === id; });
      if (!item) return;
      renderDetail(item);
    });
  }

  document.addEventListener('click', function(e){
    var a = e.target && e.target.closest ? e.target.closest('.notice-card ul a') : null;
    if (!a) return;
    var title = (a.textContent || '').trim();
    if (!title || title.indexOf('更多') === 0) return;
    var href = a.getAttribute('href') || '';
    if (/^https?:\/\//i.test(href)) return;
    e.preventDefault();
    e.stopPropagation();
    loadAnnouncements().then(function(list){
      var hit = findByTitle(list, title);
      var url = hit && hit.id ? ('/announce?id=' + hit.id) : '/announce';
      location.href = url;
    });
  }, true);

  // list page item click -> detail with id
  document.addEventListener('click', function(e){
    var a = e.target && e.target.closest ? e.target.closest('.list-panel a.lp-item') : null;
    if (!a) return;
    var title = ((a.querySelector('.tt') || a).textContent || '').trim();
    if (!title) return;
    e.preventDefault();
    e.stopPropagation();
    loadAnnouncements().then(function(list){
      var hit = findByTitle(list, title);
      if (hit && hit.id) location.href = '/announce?id=' + hit.id;
    });
  }, true);

  function boot(){
    rewriteHomeHrefs();
    openFromQuery();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
  setInterval(boot, 800);
  window.addEventListener('popstate', function(){ setTimeout(boot, 120); });
})();
""".lstrip()
js_path.write_text(js, encoding="utf-8")
print("wrote v5")

for name in ["index.html", "200.html", "announce/index.html"]:
    html = root / "public" / name
    if not html.exists():
        continue
    h = html.read_text(encoding="utf-8", errors="ignore")
    h2 = re.sub(r"announce-fix\.(css|js)\?v=\d+", r"announce-fix.\1?v=5", h)
    if h2 != h:
        html.write_text(h2, encoding="utf-8")
        print("bump", name)
print("ok")

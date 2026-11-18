#!/usr/bin/env python3
"""Sync status dropdown to URL via navigateTo (Be), so ae() cannot wipe pending filter."""
from pathlib import Path
import re

chunk = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js")
s = chunk.read_text(encoding="utf-8", errors="ignore")

old_sel = (
    '"onUpdate:modelValue":n[1]||(n[1]=p=>{fe(c)?c.value=p:null;Je&&Je()}),clearable:"",placeholder:"全部状态",class:"filter-sel"'
)
new_sel = (
    '"onUpdate:modelValue":n[1]||(n[1]=p=>{'
    'const next=p==null||p===""?"":String(p);'
    'fe(c)?c.value=next:null;'
    'z.value=1;X.value=[];'
    'const q=Object.assign({},d.query);'
    'if(next)q.status=next;else delete q.status;'
    'Be({path:d.path,query:q,replace:!0});'
    'fetch("http://127.0.0.1:7691/ingest/950e390d-371a-4a7d-9f1d-32686b206508",{method:"POST",headers:{"Content-Type":"application/json","X-Debug-Session-Id":"d7e34e"},body:JSON.stringify({sessionId:"d7e34e",runId:"post-fix",hypothesisId:"E",location:"chunk:status-change",message:"status dropdown synced to URL",data:{next,path:d.path,queryStatus:q.status||null},timestamp:Date.now()})}).catch(()=>{});'
    '}),clearable:"",placeholder:"全部状态",class:"filter-sel"'
)

if old_sel in s:
    s = s.replace(old_sel, new_sel, 1)
    print("patched status select -> URL sync")
elif "status dropdown synced to URL" in s:
    print("status select already URL synced")
else:
    # try earlier Je-only variant
    old2 = (
        '"onUpdate:modelValue":n[1]||(n[1]=p=>fe(c)?c.value=p:null),clearable:"",placeholder:"全部状态",class:"filter-sel"'
    )
    if old2 in s:
        s = s.replace(old2, new_sel, 1)
        print("patched status select from plain variant")
    else:
        print("WARN status select pattern missing")

# Harden Ol: never drop status if c.value or URL has it (already pendingFix2)
# Harden items filter already present.

# Prevent fullPath ae+pe from racing: when query status present, Ol must use it (already does)

chunk.write_text(s, encoding="utf-8")
print("wrote chunk")

# Also strengthen debug hook to log location.search vs select
dbg_path = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/admin-pending-debug.js")
dbg = r"""
(function () {
  if (window.__dbgPendingHookV3) return;
  window.__dbgPendingHookV3 = true;
  var ENDPOINT = 'http://127.0.0.1:7691/ingest/950e390d-371a-4a7d-9f1d-32686b206508';
  function send(hypothesisId, location, message, data) {
    try {
      fetch(ENDPOINT, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': 'd7e34e' },
        body: JSON.stringify({
          sessionId: 'd7e34e',
          runId: 'post-fix',
          hypothesisId: hypothesisId,
          location: location,
          message: message,
          data: data || {},
          timestamp: Date.now(),
        }),
      }).catch(function () {});
    } catch (e) {}
  }
  function summarize(rows) {
    var statusCounts = {}, auditCounts = {};
    (rows || []).forEach(function (row) {
      var st = String((row && row.status) || '?');
      var au = String((row && row.audit_label) || '?');
      statusCounts[st] = (statusCounts[st] || 0) + 1;
      auditCounts[au] = (auditCounts[au] || 0) + 1;
    });
    return {
      statusCounts: statusCounts,
      auditCounts: auditCounts,
      sample: (rows || []).slice(0, 6).map(function (r) {
        return { id: r.id, status: r.status, audit: r.audit_label, shelf: r.shelf_label, title: String(r.title || '').slice(0, 24) };
      }),
    };
  }
  function inspectUrl(url) {
    try {
      var u = new URL(url, location.origin);
      if (u.pathname.indexOf('/admin/resources/articles') === -1) return null;
      if (u.pathname.indexOf('/meta') !== -1) return null;
      return {
        href: u.pathname + u.search,
        statusParam: u.searchParams.get('status'),
        page: u.searchParams.get('page'),
        path: location.pathname + location.search,
      };
    } catch (e) { return null; }
  }
  var origFetch = window.fetch;
  window.fetch = function () {
    var args = arguments;
    var input = args[0];
    var url = typeof input === 'string' ? input : input && input.url;
    var info = url ? inspectUrl(url) : null;
    if (info) send('A', 'pending-debug.js:fetch', 'articles request', info);
    return origFetch.apply(this, args).then(function (res) {
      if (!info) return res;
      try {
        res.clone().json().then(function (body) {
          var rows = (body && body.data) || [];
          var sum = summarize(rows);
          send('B', 'pending-debug.js:response', 'articles response', Object.assign({
            statusParam: info.statusParam,
            pageUrl: info.path,
            total: body && body.meta && body.meta.total,
            hasPublished: !!sum.statusCounts.published,
            mismatch: info.statusParam === 'pending' && !!sum.statusCounts.published,
          }, sum));
        }).catch(function () {});
      } catch (e) {}
      return res;
    });
  };
  send('D', 'pending-debug.js:boot', 'debug hook v3 installed', { href: location.href });
})();
"""
dbg_path.write_text(dbg.strip() + "\n", encoding="utf-8")
print("wrote debug v3")

for html in list(Path("/www/wwwroot/g.aifeili.cn/public").rglob("admin/**/index.html")) + [
    Path("/www/wwwroot/g.aifeili.cn/public/index.html"),
    Path("/www/wwwroot/g.aifeili.cn/public/200.html"),
]:
    if not html.exists():
        continue
    h = html.read_text(encoding="utf-8", errors="ignore")
    h2 = re.sub(
        r"/_nuxt/admin-pending-debug\.js\?v=\d+",
        "/_nuxt/admin-pending-debug.js?v=3",
        h,
    )
    if h2 != h:
        html.write_text(h2, encoding="utf-8")
        print("bump", html)
print("ok")

#!/usr/bin/env python3
"""Inject client debug hook + PHP status filter logging for pending-mix bug."""
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn")
nuxt = root / "public" / "_nuxt"

js = r"""
(function () {
  if (window.__dbgPendingHook) return;
  window.__dbgPendingHook = true;
  var ENDPOINT = 'http://127.0.0.1:7691/ingest/950e390d-371a-4a7d-9f1d-32686b206508';
  function send(hypothesisId, location, message, data) {
    try {
      fetch(ENDPOINT, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Debug-Session-Id': 'd7e34e',
        },
        body: JSON.stringify({
          sessionId: 'd7e34e',
          runId: 'pre-fix',
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
    var statusCounts = {};
    var auditCounts = {};
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
        return {
          id: r.id,
          status: r.status,
          audit: r.audit_label,
          shelf: r.shelf_label,
          title: String(r.title || '').slice(0, 24),
        };
      }),
    };
  }
  function inspectUrl(url) {
    try {
      var u = new URL(url, location.origin);
      if (u.pathname.indexOf('/admin/resources/articles') === -1) return null;
      return {
        href: u.pathname + u.search,
        statusParam: u.searchParams.get('status'),
        page: u.searchParams.get('page'),
        path: location.pathname + location.search,
      };
    } catch (e) {
      return null;
    }
  }
  var origFetch = window.fetch;
  window.fetch = function () {
    var args = arguments;
    var input = args[0];
    var url = typeof input === 'string' ? input : input && input.url;
    var info = url ? inspectUrl(url) : null;
    if (info) {
      send('A', 'pending-debug.js:fetch', 'articles request', info);
    }
    return origFetch.apply(this, args).then(function (res) {
      if (!info) return res;
      try {
        res
          .clone()
          .json()
          .then(function (body) {
            var rows = (body && body.data) || [];
            var sum = summarize(rows);
            send('B', 'pending-debug.js:response', 'articles response', {
              statusParam: info.statusParam,
              pageUrl: info.path,
              total: body && body.meta && body.meta.total,
              statusCounts: sum.statusCounts,
              auditCounts: sum.auditCounts,
              sample: sum.sample,
              hasPublished: !!sum.statusCounts.published,
              mismatch:
                info.statusParam === 'pending' && !!sum.statusCounts.published,
            });
          })
          .catch(function () {});
      } catch (e) {}
      return res;
    });
  };
  send('D', 'pending-debug.js:boot', 'debug hook installed', {
    href: location.href,
  });
})();
""".strip()

(nuxt / "admin-pending-debug.js").write_text(js + "\n", encoding="utf-8")
print("wrote js")

# PHP: log status filter application for articles
php_path = root / "app/controller/api/v1/AdminResource.php"
php = php_path.read_text(encoding="utf-8")
marker = "/*agentlog-pending*/"
if marker not in php:
    needle = "        $paginator = $q->paginate(['list_rows' => 20]);\n        $data = array_map(fn ($row) => $this->serialize($row, $cfg), $paginator->items());"
    if needle not in php:
        raise SystemExit("paginate needle missing")
    inject = """        $paginator = $q->paginate(['list_rows' => 20]);
        $data = array_map(fn ($row) => $this->serialize($row, $cfg), $paginator->items());
        /*agentlog-pending*/
        if ($resource === 'articles') {
            $____st = [];
            foreach ($data as $____row) {
                $____k = (string) ($____row['status'] ?? '?');
                $____st[$____k] = ($____st[$____k] ?? 0) + 1;
            }
            @file_put_contents('/tmp/debug-d7e34e-pending.log', json_encode([
                'ts' => date('c'),
                'param_status' => $this->request->param('status'),
                'statusField' => $statusField,
                'total' => (int) $paginator->total(),
                'statusCounts' => $____st,
                'uri' => (string) $this->request->url(true),
            ], JSON_UNESCAPED_UNICODE) . "\\n", FILE_APPEND);
        }
        /*agentlog-pending-end*/"""
    php = php.replace(needle, inject, 1)
    php_path.write_text(php, encoding="utf-8")
    print("patched php")
else:
    print("php already patched")

tag = '<script src="/_nuxt/admin-pending-debug.js?v=1"></script>'
for html in list((root / "public").rglob("admin/**/index.html")) + [
    root / "public/index.html",
    root / "public/200.html",
]:
    if not html.exists():
        continue
    h = html.read_text(encoding="utf-8", errors="ignore")
    if "admin-pending-debug.js" in h:
        h2 = re.sub(
            r"/_nuxt/admin-pending-debug\.js\?v=\d+",
            "/_nuxt/admin-pending-debug.js?v=1",
            h,
        )
        if h2 != h:
            html.write_text(h2, encoding="utf-8")
            print("bump", html)
        continue
    if "</head>" not in h:
        continue
    html.write_text(h.replace("</head>", tag + "\n</head>", 1), encoding="utf-8")
    print("link", html)
print("ok")

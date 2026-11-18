#!/usr/bin/env python3
"""Ship article disclaimer block to live news detail pages."""
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
public = Path("/www/wwwroot/g.aifeili.cn/public")

css = """
.art-disclaimer{
  margin-top:28px;padding:16px 18px;
  border:1px solid #f0d4b5;border-radius:10px;
  background:#fff8f0;color:#8a4b1a;
  font-size:13.5px;line-height:1.75;
}
.art-disclaimer p{margin:0 0 10px}
.art-disclaimer p:last-child{margin-bottom:0}
.art-disclaimer b{font-weight:700;color:#7a3f12}
.art-disclaimer .meta-line{margin-top:8px;margin-bottom:0;word-break:break-all}
.art-disclaimer .meta-line span{font-weight:600}
.art-disclaimer a{color:#c45a12;text-decoration:underline;text-underline-offset:2px}
.art-disclaimer a:hover{color:#a3460a}
""".strip() + "\n"

js = r"""
(function(){
  var TEXT1='有机硅商城一直努力为您提供准确、可靠的信息，力求内容客观、中立，但由于市场信息变化较快，我们无法保证信息在任何时点都完整无误，相关内容也可能根据实际情况适时调整。平台上的信息仅供您参考，不构成任何投资、经营或其他决策建议，请您结合自身情况独立判断，据此作出的任何决策及相应结果，均与有机硅商城无关。';
  var TEXT2='本文内容版权归有机硅商城所有，仅供授权网站用户阅读使用。如需转载或引用，欢迎通过官方渠道与我们联系取得授权；未经书面许可，请勿擅自传播、发布、复制或改写本文。感谢您的理解与配合，我们也会对侵犯平台权益或歪曲原文原意的行为保留依法追究的权利。';

  function isNewsDetail(){
    return /^\/news\/[^\/]+\/?$/.test(location.pathname);
  }

  function build(title){
    var box=document.createElement('aside');
    box.className='art-disclaimer';
    box.setAttribute('aria-label','免责声明');
    var p1=document.createElement('p');
    p1.innerHTML='<b>免责声明：</b>'+TEXT1;
    var p2=document.createElement('p');
    p2.textContent=TEXT2;
    box.appendChild(p1);
    box.appendChild(p2);
    if(title){
      var t=document.createElement('p');
      t.className='meta-line';
      t.innerHTML='<span>本文标题：</span>';
      t.appendChild(document.createTextNode(title));
      box.appendChild(t);
    }
    var u=document.createElement('p');
    u.className='meta-line';
    var sp=document.createElement('span');
    sp.textContent='本文地址：';
    var a=document.createElement('a');
    a.href=location.href;
    a.target='_blank';
    a.rel='noopener noreferrer';
    a.textContent=location.href;
    u.appendChild(sp);
    u.appendChild(a);
    box.appendChild(u);
    return box;
  }

  function mount(){
    if(!isNewsDetail()) return;
    var card=document.querySelector('.news-portal .art-card, article.art-card');
    if(!card) return;
    if(card.querySelector('.art-disclaimer')) return;
    var h1=card.querySelector('h1');
    var title=h1 ? (h1.textContent||'').trim() : '';
    var box=build(title);
    var more=card.querySelector('.more-link');
    if(more && more.parentNode===card){
      card.insertBefore(box, more);
    } else {
      var gate=card.querySelector('.report-gate');
      if(gate && gate.nextSibling){
        card.insertBefore(box, gate.nextSibling);
      } else {
        card.appendChild(box);
      }
    }
  }

  function boot(){
    mount();
    var tries=0;
    var timer=setInterval(function(){
      mount();
      if(++tries>40) clearInterval(timer);
    }, 400);
    document.addEventListener('click', function(){
      setTimeout(mount, 300);
    }, true);
    window.addEventListener('popstate', function(){ setTimeout(mount, 200); });
  }

  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', boot);
  else boot();
})();
""".lstrip()

(root / "article-disclaimer.fix.css").write_text(css, encoding="utf-8")
(root / "article-disclaimer.fix.js").write_text(js, encoding="utf-8")
print("wrote fix assets")

tag_css = '<link rel="stylesheet" href="/_nuxt/article-disclaimer.fix.css?v=1">'
tag_js = '<script src="/_nuxt/article-disclaimer.fix.js?v=1" defer></script>'

linked = 0
for html in public.rglob("*.html"):
    # Prefer news detail shells + spa shells
    path = str(html).replace("\\", "/")
    if not (
        path.endswith("/index.html")
        or "/news/" in path
        or path.endswith("/200.html")
        or path.endswith("/404.html")
    ):
        continue
    try:
        t = html.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        continue
    if "article-disclaimer.fix.js" in t:
        continue
    if "</head>" not in t:
        continue
    html.write_text(
        t.replace("</head>", tag_css + "\n" + tag_js + "\n</head>", 1),
        encoding="utf-8",
    )
    linked += 1
print("linked html", linked)
print("ok")

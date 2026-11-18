const fs = require("fs");
const path = require("path");

const htmlPath = path.resolve(__dirname, "../../用户需求等文件/index.html");
const dest = path.resolve(__dirname, "../../yjgmall-web/assets/css/jobs-channel.css");
const html = fs.readFileSync(htmlPath, "utf8");
const m = html.match(/<style>([\s\S]*?)<\/style>/);
if (!m) throw new Error("no style");
let css = m[1];

// Drop resets that fight site chrome
css = css.replace(/\*\{[^}]+\}/, "");
css = css.replace(/html\{[^}]+\}/, "");
css = css.replace(/body\{[\s\S]*?\}/, "");
css = css.replace(/a\{[^}]+\}/, "");
css = css.replace(
  /button\{[^}]+\}/,
  ".jobs-ch button{font-family:inherit;cursor:pointer;border:none;background:none;color:inherit}",
);
css = css.replace(
  /input,select,textarea\{[^}]+\}/,
  ".jobs-ch input,.jobs-ch select,.jobs-ch textarea{font-family:inherit;color:var(--ink)}",
);
css = css.replace(/ul\{[^}]+\}/, ".jobs-ch ul{list-style:none;margin:0;padding:0}");
css = css.replace(/img\{[^}]+\}/, "");
css = css.replace(/\/\* ============ 顶部导航[\s\S]*?(?=\/\* ============ 视角切换)/, "");
css = css.replace(/\/\* 页脚[\s\S]*?(?=\/\* ============ 简历)/, "");
css = css.replace(/:root\{[\s\S]*?\}/, "");
css = css.replace(
  /\.container\{[^}]+\}/g,
  ".jobs-ch .container{max-width:1240px;margin:0 auto;padding:0 24px}",
);
css = css.replace(/\.wrap\{[^}]+\}/g, "");

const head = `/* 招聘专区：自客户 HTML 原型迁移，不简化 */
.jobs-ch{
  --brand:#2468f2;--brand-strong:#1d4fd7;--brand-deep:#1443b8;--brand-light:#3f86f9;
  --brand-bg:#f6f9fe;--brand-bg2:#eef4ff;--brand-line:#d6e4ff;
  --ink:#13294b;--ink2:#5a6b85;--ink3:#94a3b8;--bg:#f3f6fb;--card:#ffffff;
  --line:#e9eef6;--line2:#eef2f8;--accent:#f04b3e;--accent2:#ea580c;--accent-bg:#fff4d6;
  --green:#10b981;--green-deep:#047857;
  --r-sm:8px;--r-md:12px;--r-lg:16px;--r-xl:20px;--r-pill:100px;
  --shadow-card:0 4px 16px rgba(19,41,75,.08);
  --shadow-pop:0 10px 30px -8px rgba(36,104,242,.22);
  --grad-brand:linear-gradient(135deg,#1d4fd7,#2f6fe8 55%,#3f86f9);
  --grad-hero:linear-gradient(135deg,#123c8f,#1d4fd7 45%,#2f6fe8 80%,#3f86f9);
  color:var(--ink);background:var(--bg);line-height:1.6;
  -webkit-font-smoothing:antialiased;padding-bottom:40px;
}
.jobs-ch .hidden{display:none!important}
.jobs-ch :focus-visible{outline:2px solid var(--brand);outline-offset:2px;border-radius:4px}
.jobs-ch a{color:inherit;text-decoration:none}
`;

fs.writeFileSync(dest, head + css);
console.log("OK", dest, fs.statSync(dest).size);

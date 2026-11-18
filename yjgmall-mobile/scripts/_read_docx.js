const fs = require("fs");
const path = require("path");
const os = require("os");
const { execSync } = require("child_process");

const docx = String.raw`c:\Users\Administrator\xwechat_files\wxid_cxod157ic1kd22_434b\msg\file\2026-11\有机硅商城-登录后体验审查-2026-11-06.docx`;
const tmp = path.join(os.tmpdir(), "docx_extract_login_review");
fs.rmSync(tmp, { recursive: true, force: true });
fs.mkdirSync(tmp, { recursive: true });
execSync(`tar -xf "${docx}" -C "${tmp}" word/document.xml`, { stdio: "inherit" });
let xml = fs.readFileSync(path.join(tmp, "word", "document.xml"), "utf8");
xml = xml.replace(/<w:tab\/>/g, "\t").replace(/<w:br\/>/g, "\n");
const paras = xml
  .split(/<w:p[ >]/)
  .map((block) => {
    const texts = [...block.matchAll(/<w:t[^>]*>([^<]*)<\/w:t>/g)].map((m) => m[1]);
    return texts
      .join("")
      .replace(/&amp;/g, "&")
      .replace(/&lt;/g, "<")
      .replace(/&gt;/g, ">")
      .trim();
  })
  .filter(Boolean);
console.log(paras.join("\n"));

const fs = require("fs");
const p = require("path").resolve(__dirname, "../../yjgmall-web/utils/jobsDemo.ts");
let t = fs.readFileSync(p, "utf8");
const pairs = [
  ["cat: '研发技术'", "role: '研发', cat: '研发'"],
  ["cat: '外贸业务'", "role: '外贸', cat: '外贸'"],
  ["cat: '生产制造'", "role: '生产', cat: '生产'"],
  ["cat: '销售业务'", "role: '销售', cat: '销售'"],
  ["cat: '质检品控'", "role: '品控', cat: '品控'"],
  ["cat: '运营管理'", "role: '管理', cat: '管理'"],
  ["cat: '硅橡胶配方'", "role: '研发', cat: '研发'"],
  ["cat: '研发合成'", "role: '研发', cat: '研发'"],
  ["cat: '销售管理'", "role: '销售', cat: '销售'"],
  ["cat: '质检检测'", "role: '品控', cat: '品控'"],
];
for (const [a, b] of pairs) t = t.split(a).join(b);
fs.writeFileSync(p, t);
console.log("patched", (t.match(/role:/g) || []).length);

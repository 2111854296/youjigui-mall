# 有机硅商城 · 移动端（uni-app）

Vue3 + Vite + TypeScript + **uview-plus**，对接同一套后端 `https://g.aifeili.cn/api/v1`。

## 目标端

- H5：`https://g.aifeili.cn/h5/`
- 微信小程序 / App：同一工程多端发行

## 视觉

- 品牌蓝 `#2769dc`（对齐 PC / 世界工厂系蓝，非橙灰）
- 信息架构参考 Destoon 移动端 + 世界工厂手机站：搜索、快捷入口、供应/采购/企业/会员 Tab

## 本地开发

```bash
# 使用项目 Node 22
npm install
npm run dev:h5
```

H5 默认 `base=/h5/`，开发代理见 `src/manifest.json` → `h5.devServer.proxy`。

## 构建 H5

```bash
npm run build:h5
# 产物：dist/build/h5 → 部署到服务器 /www/wwwroot/g.aifeili.cn/h5/
```

## nginx 片段

```nginx
location /h5/ {
    alias /www/wwwroot/g.aifeili.cn/h5/;
    try_files $uri $uri/ /h5/index.html;
}
```

API 仍走站内 `/api/v1`（或绝对地址 `https://g.aifeili.cn/api/v1`）。

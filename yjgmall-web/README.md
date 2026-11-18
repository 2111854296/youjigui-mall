# 有机硅商城前台（Nuxt 3）

用户可见站点。对接 Laravel API：`http://127.0.0.1:8080/api/v1`

## 环境

- Node.js **≥ 22.12**（仓库内已备 `.tools/node22`）
- 后端 `yjgmall-next` 需先启动（推荐 Nginx `:8080`，不要用空闲的 `:8000`）

## 启动

```powershell
# 终端 1：API（推荐 phpStudy Nginx，端口 8080）
# 或：scripts\start-api-nginx.bat
# 不推荐演示用 artisan serve（单线程且更慢）

# 终端 2：前台（请用 pnpm；本机 npm 可能踩 edgesOut 已知问题）
cd yjgmall-web
# .env 中 NUXT_PUBLIC_API_BASE 必须指向实际 API，例如：
# NUXT_PUBLIC_API_BASE=http://127.0.0.1:8080/api/v1
$env:Path = "C:\Users\Administrator\Desktop\有机硅商城\.tools\node22\node-v22.14.0-win-x64;" + $env:Path
pnpm install
pnpm run dev
```

- 前台：http://127.0.0.1:3000
- API / Filament：http://127.0.0.1:8080 （`admin@yjgmall.test` / `admin123`）
- Vue 运营后台：http://127.0.0.1:3000/admin
- 测试会员：`2111854296` / `123456`

## 测试账号说明

会员登录字段为用户名或邮箱；种子账号用户名 `2111854296`。

# 有机硅商城

正式交付形态：**Laravel API + Filament 后台 + Nuxt 3 前台**。

| 目录 | 职责 |
|------|------|
| `yjgmall-next/` | Laravel 11 API、领域模型、Filament `/admin` |
| `yjgmall-web/` | Nuxt 3 SSR 用户前台（交付主站） |
| `docs/正式技术规划.md` | 技术规划与质量标准 |
| Destoon / 线上站 | 只读对照，不改业务 |

Blade 前台仍在 `yjgmall-next` 内，**已 deprecated**，请以 Nuxt 为准。

## 快速启动

### 1. API（8000）

```powershell
cd yjgmall-next
D:\phpstudy_pro\Extensions\php\php8.3.14\php.exe artisan serve --host=127.0.0.1 --port=8000
```

### 2. 前台（3000）

需 Node ≥ 22.12。可用仓库内便携 Node：

```powershell
cd yjgmall-web
$env:Path = "C:\Users\Administrator\Desktop\有机硅商城\.tools\node22\node-v22.14.0-win-x64;" + $env:Path
pnpm install   # 首次
pnpm run dev
```

- 前台：http://127.0.0.1:3000
- API：http://127.0.0.1:8000/api/v1/home
- 后台：http://127.0.0.1:8000/admin  
  - `admin@yjgmall.test` / `admin123`
- 测试会员：`2111854296` / `123456`

## 说明

- 支付为本地模拟支付宝（可测下单闭环）
- GEO 仅预留 head / 插槽，后期对接已有 GEO 工程
- 包管理请用 **pnpm**（本机 npm 在部分依赖树上会触发已知 bug）

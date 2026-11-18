# 有机硅商城 · 后端（yjgmall-next）

> **交付前台请用** `../yjgmall-web`（Nuxt 3）。本目录 Blade 前台仅作对照/备用，已 deprecated。

## 技术栈
- PHP 8.3 + Laravel 11 + Sanctum API
- MySQL（库名 `yjgmall`）
- Filament 3 运营后台（`/admin`）
- 支付：`App\Services\Payment\AlipayGateway`（本地模拟）

## 本地启动

```powershell
D:\phpstudy_pro\Extensions\php\php8.3.14\php.exe artisan serve --host=127.0.0.1 --port=8000
```

- API：http://127.0.0.1:8000/api/v1/home
- 后台：http://127.0.0.1:8000/admin （`admin@yjgmall.test` / `admin123`）

完整双端说明见仓库根目录 `README.md`。

## 演示账号
| 角色 | 账号 | 密码 |
|------|------|------|
| 后台 | admin@yjgmall.test | admin123 |
| 会员 | 2111854296 | 123456 |

## GEO
Nuxt `app.head` 与后期插槽预留；Blade 侧组件仅备用。

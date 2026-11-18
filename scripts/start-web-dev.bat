@echo off
chcp 65001 >nul
REM 前台开发启动 + 预热（避免首次打开后台白屏一分钟）
set PATH=C:\Users\Administrator\Desktop\有机硅商城\.tools\node22\node-v22.14.0-win-x64;%PATH%
set NUXT_PUBLIC_API_BASE=http://127.0.0.1:8080/api/v1

cd /d "%~dp0..\yjgmall-web"

echo [1/2] 启动 Nuxt :3000 ...
start "yjgmall-web" cmd /k "pnpm run dev"

echo [2/2] 等待服务并预热后台依赖...
timeout /t 12 /nobreak >nul
curl.exe -s -o NUL http://127.0.0.1:3000/
curl.exe -s -o NUL http://127.0.0.1:3000/admin/login
curl.exe -s -o NUL "http://127.0.0.1:3000/_nuxt/layouts/admin.vue"
curl.exe -s -o NUL "http://127.0.0.1:3000/_nuxt/pages/admin/index.vue"

echo.
echo OK  前台 http://127.0.0.1:3000
echo     后台 http://127.0.0.1:3000/admin
echo 请先运行 scripts\start-api-nginx.bat（小皮 Nginx :8080）
pause

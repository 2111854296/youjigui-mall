@echo off
chcp 65001 >nul
REM 有机硅商城 - 快速 API（Nginx + PHP8.3 CGI，端口 8080）
REM 比 php artisan serve 快一个数量级（热请求约 0.1s）

set PHP_CGI=D:\phpstudy_pro\Extensions\php\php8.3.14\php-cgi.exe
set NGINX=D:\phpstudy_pro\Extensions\Nginx1.15.11\nginx.exe
set NGINX_DIR=D:\phpstudy_pro\Extensions\Nginx1.15.11
set LINK=D:\phpstudy_pro\WWW\yjgmall-api
set TARGET=C:\Users\Administrator\Desktop\有机硅商城\yjgmall-next

if not exist "%LINK%" (
  mklink /J "%LINK%" "%TARGET%"
)

echo [1/3] PHP 8.3 FastCGI 9003-9005...
for %%P in (9003 9004 9005) do (
  netstat -ano | findstr ":%%P" | findstr "LISTENING" >nul
  if errorlevel 1 (
    start "yjgmall-php83-%%P" /MIN "%PHP_CGI%" -b 127.0.0.1:%%P
  )
)
timeout /t 1 /nobreak >nul

echo [2/3] Reload Nginx...
cd /d "%NGINX_DIR%"
"%NGINX%" -t
"%NGINX%" -s reload

echo [3/3] Warm API...
curl.exe -s -o NUL http://127.0.0.1:8080/up
curl.exe -s -o NUL http://127.0.0.1:8080/api/v1/hotwords

echo.
echo OK  API = http://127.0.0.1:8080/api/v1
echo     Web = http://127.0.0.1:3000  (另开终端 pnpm dev)
echo 不要再用 artisan serve :8000 演示。

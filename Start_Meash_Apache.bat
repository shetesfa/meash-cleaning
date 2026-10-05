@echo off
chcp 65001 >nul
cd /d "%~dp0"
title MEASH CLEANING SOLUTION - NORMAL WEB (APACHE)
color 0b

echo =====================================================================
echo          MEASH CLEANING SOLUTION - NORMAL WEB (NO ARTISAN)
echo =====================================================================
echo.
echo [1/3] Ensuring MySQL Database Service is active...
net start MySQL >nul 2>&1
if %errorlevel% neq 0 (
    if exist "C:\xampp\mysql\bin\mysqld.exe" (
        start "" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone >nul 2>&1
        timeout /t 2 /nobreak >nul
    )
)

echo [2/3] Ensuring Apache Web Server is active...
net start Apache2.4 >nul 2>&1
if %errorlevel% neq 0 (
    if exist "C:\xampp\apache\bin\httpd.exe" (
        start "" "C:\xampp\apache\bin\httpd.exe" >nul 2>&1
        timeout /t 2 /nobreak >nul
    )
)

echo [3/3] Opening Meash Cleaning Solution in your browser...
start http://localhost:8000
start http://localhost:8000/app

echo.
echo =====================================================================
echo  Platform URLs (Powered by Apache Web Server - ZERO ARTISAN):
echo   - Customer Public Website: http://localhost:8000
echo   - Internal Business PWA:   http://localhost:8000/app
echo   - Telegram Mini App:       http://localhost:8000/telegram-miniapp
echo =====================================================================
echo.
echo Website is running normally on Apache!
timeout /t 4 >nul
exit

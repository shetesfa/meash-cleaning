@echo off
chcp 65001 >nul
cd /d "%~dp0"
title MEASH CLEANING SOLUTION - SERVER
color 0b

echo =====================================================================
echo          MEASH CLEANING SOLUTION - BUSINESS OPERATING PLATFORM      
echo =====================================================================
echo.
echo [1/3] Checking MySQL Database Service...
net start MySQL >nul 2>&1
if %errorlevel% neq 0 (
    if exist "C:\xampp\mysql\bin\mysqld.exe" (
        start "" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone >nul 2>&1
        timeout /t 2 /nobreak >nul
    )
)

echo [2/3] Opening Meash Cleaning Solution in your browser...
start http://localhost:8000
start http://localhost:8000/app

echo [3/3] Starting Laravel Central Server on http://localhost:8000 ...
echo.
echo =====================================================================
echo  Platform URLs:
echo   - Customer Public Website: http://localhost:8000
echo   - Internal Business PWA:   http://localhost:8000/app
echo   - Telegram Mini App:       http://localhost:8000/telegram-miniapp
echo =====================================================================
echo.
echo Press Ctrl+C to stop the server at any time.
echo.

php artisan serve --port=8000
pause

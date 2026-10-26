@echo off
chcp 65001 >nul
cd /d "%~dp0"
title MEASH CLEANING SOLUTION - SERVER
color 0b

:: Auto-detect and add XAMPP PHP & MySQL to PATH if not already in system environment
if exist "C:\xampp\php" set "PATH=C:\xampp\php;C:\xampp\mysql\bin;%PATH%"

echo =====================================================================
echo          MEASH CLEANING SOLUTION - BUSINESS OPERATING PLATFORM      
echo =====================================================================
echo.
echo [1/4] Checking MySQL Database Service...
net start MySQL >nul 2>&1
if %errorlevel% neq 0 (
    if exist "C:\xampp\mysql\bin\mysqld.exe" (
        start "" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file="C:\xampp\mysql\bin\my.ini" --standalone >nul 2>&1
        timeout /t 2 /nobreak >nul
    )
)

echo [2/4] Ensuring database and tables are ready...
mysql -u root -e "CREATE DATABASE IF NOT EXISTS meash_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" >nul 2>&1
php artisan migrate --force >nul 2>&1
php artisan db:seed --force >nul 2>&1

echo [3/4] Opening Meash Cleaning Solution in your browser...
start http://localhost:8000
start http://localhost:8000/app

echo [4/4] Starting Laravel Central Server on http://localhost:8000 ...
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

php artisan serve --host=0.0.0.0 --port=8000
pause

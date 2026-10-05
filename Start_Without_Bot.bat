@echo off
chcp 65001 >nul
cd /d "%~dp0"
title MEASH CLEANING SOLUTION - LOCAL / OFFLINE (NO BOT)
color 0b

echo =====================================================================
echo    MEASH CLEANING SOLUTION - LOCAL SERVER & ADMIN (NO BOT)
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

echo [2/3] Opening Meash 3D Website and Admin OS in your browser...
start http://localhost:8000
start http://localhost:8000/app

echo [3/3] Starting Local Server on http://localhost:8000 ...
echo.
echo =====================================================================
echo  STATUS: OFFLINE / LOCAL MODE ACTIVE (NO TELEGRAM BOT RUNNING)
echo   - 3D Customer Website: http://localhost:8000
echo   - Admin Business OS:   http://localhost:8000/app
echo =====================================================================
echo.
echo Press Ctrl+C to stop the server at any time.
echo.

php artisan serve --port=8000
pause

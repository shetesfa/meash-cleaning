@echo off
chcp 65001 >nul
cd /d "%~dp0"
title MEASH CLEANING SOLUTION - LOCAL / OFFLINE (NO BOT)
color 0b

:: Auto-detect and add XAMPP PHP & MySQL to PATH if not already in system environment
if exist "C:\xampp\php" set "PATH=C:\xampp\php;C:\xampp\mysql\bin;%PATH%"

echo =====================================================================
echo    MEASH CLEANING SOLUTION - LOCAL SERVER & ADMIN (NO BOT)
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
mysql -u root -e "CREATE DATABASE IF NOT EXISTS meash_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if %errorlevel% neq 0 (
    echo [ERROR] MySQL is not reachable. Please start MySQL in XAMPP.
    pause
    exit /b 1
)

php artisan migrate --force
if %errorlevel% neq 0 (
    echo [ERROR] Database migration failed!
    pause
    exit /b 1
)

echo [3/4] Launching Meash Central Server on port 8080...
start "Meash Local Server" php artisan serve --host=0.0.0.0 --port=8080

echo [4/4] Waiting for server health check (http://localhost:8080/health)...
powershell -Command "$i=0; while($i -lt 15) { try { $r = Invoke-WebRequest -Uri 'http://localhost:8080/health' -UseBasicParsing -TimeoutSec 2; if($r.StatusCode -eq 200) { exit 0 } } catch {} Start-Sleep -Seconds 1; $i++ }; exit 1"
if %errorlevel% neq 0 (
    echo [WARNING] Health check timed out, launching browser anyway...
) else (
    echo [OK] Server is ready and healthy!
)

start http://localhost:8080
start http://localhost:8080/app

echo.
echo =====================================================================
echo  STATUS: OFFLINE / LOCAL MODE ACTIVE (NO TELEGRAM BOT RUNNING)
echo   - 3D Customer Website: http://localhost:8080
echo   - Admin Business OS:   http://localhost:8080/app
echo =====================================================================
echo.
echo Server window is running. You can close this launcher window.
timeout /t 5 >nul
exit

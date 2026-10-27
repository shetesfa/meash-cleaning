@echo off
chcp 65001 >nul
cd /d "%~dp0"
title MEASH CLEANING SOLUTION - NORMAL WEB (APACHE)
color 0b

:: Auto-detect and add XAMPP PHP & MySQL to PATH if not already in system environment
if exist "C:\xampp\php" set "PATH=C:\xampp\php;C:\xampp\mysql\bin;%PATH%"

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

echo [3/3] Checking web server health before launching browser...
powershell -Command "$i=0; while($i -lt 10) { try { $r = Invoke-WebRequest -Uri 'http://localhost:8080/health' -UseBasicParsing -TimeoutSec 2; if($r.StatusCode -eq 200) { exit 0 } } catch {} Start-Sleep -Seconds 1; $i++ }; exit 1"
if %errorlevel% neq 0 (
    echo [WARNING] Health check timed out, launching browser anyway...
) else (
    echo [OK] Web server is active and reachable!
)

start http://localhost:8080
start http://localhost:8080/app

echo.
echo =====================================================================
echo  Platform URLs:
echo   - Customer Public Website: http://localhost:8080
echo   - Internal Business PWA:   http://localhost:8080/app
echo   - Mobile / LAN Access:     http://localhost:8080
echo   - Telegram Bot Link:       https://t.me/meash_cleaning_solution_bot
echo   - Telegram Mini App:       https://shetesfa.github.io/meash-mini-app/
echo =====================================================================
echo.
echo Website is running on Apache!
timeout /t 5 >nul
exit

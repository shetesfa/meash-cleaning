@echo off
chcp 65001 >nul
cd /d "%~dp0"
title MEASH CLEANING SOLUTION - TELEGRAM BOT CONTROLLER
color 0a

echo =====================================================================
echo    MEASH CLEANING SOLUTION - TELEGRAM BOT CONTROLLER (@meash)   
echo =====================================================================
echo.
echo [1] ቦቱን በ Local ኮምፒውተር ላይ በቀጥታ ማስኬድ (Local Polling - ነባሪ)
echo [2] የቦቱን ወቅታዊ ሁኔታ ማየት (Check Bot Status)
echo [3] ቦቱን ወደ Cloud Webhook ማገናኘት
echo.
set /p choice="ምርጫዎን ያስገቡ [1, 2 ወይም 3] (ነባሪ = 1): "

if "%choice%"=="2" goto CHECK_STATUS
if "%choice%"=="3" goto SET_RENDER
goto LOCAL_POLL

:SET_RENDER
echo.
echo 🌐 ቦቱን ወደ Render Cloud (24/7 Webhook) እያገናኘን ነው...
php artisan telegram:webhook set
echo.
echo ✅ ቦቱ አሁን በ Render ላይ 24 ሰዓት ያለ ማቋረጥ ይሰራል!
echo ኮምፒውተርዎ ቢጠፋ እንኳን ቴሌግራም ቦቱ መስራቱን ይቀጥላል።
echo.
pause
exit

:LOCAL_POLL
echo.
echo 💻 ቦቱን በ Local ኮምፒውተር ላይ እያስነሳን ነው (Local Polling)...
php artisan telegram:poll
echo.
echo -------------------------------------------------------------
echo ወደ Render Cloud Webhook መመለስ ይፈልጋሉ?
set /p restore="ወደ Render Webhook ይመለስ? (Y/N, ነባሪ Y): "
if /i "%restore%"=="n" goto END
php artisan telegram:webhook set
:END
pause
exit

:CHECK_STATUS
echo.
php artisan telegram:webhook status
echo.
pause
exit

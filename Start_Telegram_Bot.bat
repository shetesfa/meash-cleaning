@echo off
chcp 65001 >nul
cd /d "%~dp0"
title MEASH CLEANING SOLUTION - TELEGRAM BOT & INLINE ENGINE
color 0a

echo =====================================================================
echo    MEASH CLEANING SOLUTION - TELEGRAM BOT & INLINE ENGINE (@meash)   
echo =====================================================================
echo.
echo Starting real-time Telegram Bot listener...
echo.

php artisan telegram:poll
pause

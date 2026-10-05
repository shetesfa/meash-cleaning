@echo off
chcp 65001 >nul
cd /d "%~dp0"
title MEASH CLEANING SOLUTION - LAUNCH ALL
color 0e

echo =====================================================================
echo       STARTING MEASH CLEANING SOLUTION - COMPLETE ECOSYSTEM         
echo =====================================================================
echo.
echo [1/2] Starting Central Backend Server...
start "" "%~dp0Start_Meash_Server.bat"

echo [2/2] Starting Telegram Bot Engine...
start "" "%~dp0Start_Telegram_Bot.bat"

echo.
echo Both Central Server and Telegram Engine have been launched!
timeout /t 3 >nul
exit

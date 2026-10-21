@echo off
title DescargaSunat - Servicios
cd /d "%~dp0"

echo ==========================================
echo   DescargaSunat - Arranque de servicios
echo ==========================================
echo.
echo  - Web (artisan serve) .... http://127.0.0.1:8001
echo  - Queue worker ........... procesa descargas en segundo plano
echo  - Vite (npm dev) ......... assets con recarga en caliente
echo.
echo  Presiona Ctrl+C para detener todo.
echo.

call npm.cmd run dev:all

pause

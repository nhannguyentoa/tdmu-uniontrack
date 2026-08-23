@echo off
echo ============================================
echo   TDMU UnionTrack - Khoi dong moi truong dev
echo ============================================

netstat -an | findstr "127.0.0.1:3306" >nul
if %errorlevel%==0 (
    echo [1/2] MySQL da chay san, bo qua.
) else (
    echo [1/2] Khoi dong MySQL Server portable...
    start "MySQL - TDMU UnionTrack" /min "C:\tools\mysql\bin\mysqld.exe" --defaults-file=C:\tools\mysql\my.ini
    timeout /t 3 /nobreak >nul
)

echo [2/2] Khoi dong Laravel dev server tai http://127.0.0.1:8000 ...
cd /d "%~dp0"
php artisan serve --host=127.0.0.1 --port=8000

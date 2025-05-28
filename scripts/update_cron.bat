@echo off
cd /d %~dp0
"C:\MAMP\bin\php\php8.2.0\php.exe" update_all.php >> cron_update.log 2>&1 
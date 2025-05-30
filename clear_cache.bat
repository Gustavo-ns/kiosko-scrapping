@echo off
REM Script para limpiar cache automaticamente en Windows
REM Puede ser ejecutado desde el programador de tareas cada hora

echo Iniciando limpieza de cache - %date% %time%

REM Cambiar al directorio del proyecto
cd /d "c:\MAMP\htdocs\kiosko-scrapping"

REM 1. Llamar al script PHP de limpieza de cache
php clear_cache.php

REM 2. Tocar el archivo index.php para forzar actualizacion
copy /b index.php +,,

REM 3. Limpiar cache usando curl si esta disponible
curl -H "Cache-Control: no-cache" -H "Pragma: no-cache" http://localhost/kiosko-scrapping/ >nul 2>&1

echo Limpieza de cache completada - %date% %time%

@echo off
REM Script batch para limpieza diaria de registros
REM Se puede ejecutar manualmente o desde el programador de tareas

echo ==============================================
echo    LIMPIEZA DIARIA DE REGISTROS - INICIO
echo ==============================================
echo Fecha y hora: %date% %time%
echo.

REM Cambiar al directorio del proyecto
cd /d "c:\MAMP\htdocs\kiosko-scrapping"

REM Verificar que estamos en el directorio correcto
if not exist "daily_cleanup.php" (
    echo ERROR: No se encontro el archivo daily_cleanup.php
    echo Verificar que la ruta del proyecto sea correcta
    pause
    exit /b 1
)

echo Directorio actual: %CD%
echo.

REM Ejecutar limpieza de registros
echo Ejecutando limpieza de registros...
php daily_cleanup.php
if errorlevel 1 (
    echo ERROR: La limpieza de registros fallo
    echo Revisar los logs para mas detalles
    pause
    exit /b 1
)

echo.
echo Limpieza de registros completada exitosamente
echo.

REM Ejecutar limpieza de cache
echo Ejecutando limpieza de cache...
php clear_cache.php
if errorlevel 1 (
    echo ADVERTENCIA: La limpieza de cache fallo, pero continuamos
) else (
    echo Limpieza de cache completada
)

echo.
echo ==============================================
echo    LIMPIEZA DIARIA COMPLETADA
echo ==============================================
echo Fecha y hora: %date% %time%

REM Crear archivo de estado
echo %date% %time% > .last_cleanup_batch

REM Si se ejecuta manualmente, pausar para ver resultados
if "%1" neq "auto" (
    echo.
    echo Presiona cualquier tecla para continuar...
    pause >nul
)

exit /b 0

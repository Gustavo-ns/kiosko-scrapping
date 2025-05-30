# PowerShell script para limpiar cache automaticamente
# Puede ser ejecutado desde el programador de tareas de Windows cada hora

param(
    [string]$ProjectPath = "c:\MAMP\htdocs\kiosko-scrapping",
    [string]$BaseUrl = "http://localhost/kiosko-scrapping"
)

Write-Host "Iniciando limpieza de cache - $(Get-Date)" -ForegroundColor Green

try {
    # Cambiar al directorio del proyecto
    Set-Location $ProjectPath

    # 1. Ejecutar script PHP de limpieza de cache
    Write-Host "Ejecutando limpieza PHP..." -ForegroundColor Yellow
    $phpResult = php clear_cache.php
    Write-Host $phpResult

    # 2. Actualizar timestamp del archivo index.php para forzar actualizacion
    Write-Host "Actualizando timestamp de index.php..." -ForegroundColor Yellow
    (Get-Item "index.php").LastWriteTime = Get-Date

    # 3. Crear archivo de version de cache con timestamp actual
    $timestamp = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
    Set-Content -Path ".cache_version" -Value $timestamp
    Write-Host "Cache version actualizada: $timestamp" -ForegroundColor Cyan

    # 4. Limpiar cache del navegador simulando peticion
    Write-Host "Simulando peticion para limpiar cache del navegador..." -ForegroundColor Yellow
    try {
        $headers = @{
            'Cache-Control' = 'no-cache'
            'Pragma' = 'no-cache'
            'User-Agent' = 'Cache-Cleaner/1.0'
        }
        
        $response = Invoke-WebRequest -Uri $BaseUrl -Headers $headers -TimeoutSec 10 -UseBasicParsing
        Write-Host "Peticion HTTP exitosa - Status: $($response.StatusCode)" -ForegroundColor Green
    }
    catch {
        Write-Host "Advertencia: No se pudo hacer peticion HTTP - $($_.Exception.Message)" -ForegroundColor Yellow
    }

    # 5. Verificar servicios MAMP (opcional)
    $mampProcess = Get-Process | Where-Object { $_.Name -like "*apache*" -or $_.Name -like "*httpd*" }
    if ($mampProcess) {
        Write-Host "Servicios web detectados: $($mampProcess.Count) procesos" -ForegroundColor Cyan
    }

    Write-Host "Limpieza de cache completada exitosamente - $(Get-Date)" -ForegroundColor Green
    
    # Log del resultado
    $logEntry = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - Cache cleared successfully"
    Add-Content -Path "cache_clear.log" -Value $logEntry

}
catch {
    Write-Host "Error durante la limpieza de cache: $($_.Exception.Message)" -ForegroundColor Red
    
    # Log del error
    $errorEntry = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - ERROR: $($_.Exception.Message)"
    Add-Content -Path "cache_clear.log" -Value $errorEntry
    
    exit 1
}

# Mostrar estadisticas finales
Write-Host "`n=== ESTADISTICAS ===" -ForegroundColor Magenta
Write-Host "Proyecto: $ProjectPath"
Write-Host "URL Base: $BaseUrl"
Write-Host "Timestamp actual: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
Write-Host "Cache version: $timestamp"

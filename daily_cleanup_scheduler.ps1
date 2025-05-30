# PowerShell script para limpieza diaria automática de registros
# Se ejecuta todos los días a las 2:00 AM para limpiar registros antiguos

param(
    [string]$ProjectPath = "c:\MAMP\htdocs\kiosko-scrapping",
    [switch]$Verbose = $false
)

$ErrorActionPreference = "Stop"

# Función para escribir logs
function Write-Log {
    param([string]$Message, [string]$Level = "INFO")
    
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$Level] $Message"
    
    # Mostrar en consola
    switch ($Level) {
        "ERROR" { Write-Host $logMessage -ForegroundColor Red }
        "WARNING" { Write-Host $logMessage -ForegroundColor Yellow }
        "SUCCESS" { Write-Host $logMessage -ForegroundColor Green }
        default { Write-Host $logMessage -ForegroundColor White }
    }
    
    # Escribir al archivo de log
    $logFile = Join-Path $ProjectPath "logs\daily_cleanup_scheduler.log"
    $logDir = Split-Path $logFile -Parent
    
    if (!(Test-Path $logDir)) {
        New-Item -ItemType Directory -Path $logDir -Force | Out-Null
    }
    
    Add-Content -Path $logFile -Value $logMessage
}

try {
    Write-Log "=== INICIANDO LIMPIEZA DIARIA AUTOMÁTICA ===" "INFO"
    Write-Log "Ruta del proyecto: $ProjectPath" "INFO"
    
    # Cambiar al directorio del proyecto
    if (!(Test-Path $ProjectPath)) {
        throw "El directorio del proyecto no existe: $ProjectPath"
    }
    
    Set-Location $ProjectPath
    Write-Log "Directorio cambiado a: $(Get-Location)" "INFO"
    
    # Verificar que PHP esté disponible
    try {
        $phpVersion = php -v 2>$null
        if ($LASTEXITCODE -eq 0) {
            $phpVersionLine = ($phpVersion -split "`n")[0]
            Write-Log "PHP disponible: $phpVersionLine" "INFO"
        } else {
            throw "PHP no está disponible en el PATH"
        }
    } catch {
        throw "Error al verificar PHP: $($_.Exception.Message)"
    }
    
    # Ejecutar el script de limpieza
    Write-Log "Ejecutando limpieza de registros..." "INFO"
    
    $phpOutput = php daily_cleanup.php 2>&1
    $phpExitCode = $LASTEXITCODE
    
    if ($phpExitCode -eq 0) {
        Write-Log "Limpieza de registros completada exitosamente" "SUCCESS"
        if ($Verbose -and $phpOutput) {
            Write-Log "Salida del script PHP:" "INFO"
            $phpOutput -split "`n" | ForEach-Object { Write-Log "  $_" "INFO" }
        }
    } else {
        throw "El script de limpieza PHP falló con código de salida: $phpExitCode`nSalida: $phpOutput"
    }
    
    # Ejecutar limpieza de caché también
    Write-Log "Ejecutando limpieza de caché..." "INFO"
    
    $cacheOutput = php clear_cache.php 2>&1
    $cacheExitCode = $LASTEXITCODE
    
    if ($cacheExitCode -eq 0) {
        Write-Log "Limpieza de caché completada" "SUCCESS"
    } else {
        Write-Log "Advertencia: La limpieza de caché falló, pero continuamos" "WARNING"
    }
    
    # Estadísticas del sistema
    $memoryUsage = [math]::Round((Get-Process -Name "php" -ErrorAction SilentlyContinue | Measure-Object WorkingSet -Sum).Sum / 1MB, 2)
    if ($memoryUsage -gt 0) {
        Write-Log "Uso de memoria de PHP: ${memoryUsage} MB" "INFO"
    }
    
    # Verificar espacio en disco
    $diskSpace = Get-WmiObject -Class Win32_LogicalDisk -Filter "DeviceID='C:'" | Select-Object @{Name="FreeGB";Expression={[math]::Round($_.FreeSpace/1GB,2)}}
    Write-Log "Espacio libre en disco C: $($diskSpace.FreeGB) GB" "INFO"
    
    Write-Log "=== LIMPIEZA DIARIA COMPLETADA EXITOSAMENTE ===" "SUCCESS"
    
    # Crear archivo de estado para verificación
    $statusFile = Join-Path $ProjectPath ".last_cleanup"
    Set-Content -Path $statusFile -Value (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
    
} catch {
    Write-Log "ERROR DURANTE LA LIMPIEZA DIARIA: $($_.Exception.Message)" "ERROR"
    Write-Log "Stack trace: $($_.ScriptStackTrace)" "ERROR"
    
    # Crear archivo de error
    $errorFile = Join-Path $ProjectPath ".cleanup_error"
    $errorInfo = @{
        timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        error = $_.Exception.Message
        stackTrace = $_.ScriptStackTrace
    } | ConvertTo-Json
    
    Set-Content -Path $errorFile -Value $errorInfo
    
    exit 1
}

Write-Log "Script de limpieza diaria finalizado." "INFO"

#!/bin/bash

# Script para limpiar caché automáticamente
# Puede ser ejecutado desde cron cada hora

echo "Iniciando limpieza de caché - $(date)"

# 1. Llamar al script PHP de limpieza de caché
php clear_cache.php

# 2. Tocar el archivo index.php para forzar actualización
touch index.php

# 3. Si usas un servidor web como Apache, reiniciar (opcional)
# systemctl reload apache2  # Descomenta si tienes permisos

# 4. Limpiar caché del navegador usando curl (simular petición)
curl -H "Cache-Control: no-cache" -H "Pragma: no-cache" http://localhost/kiosko-scrapping/ > /dev/null 2>&1

echo "Limpieza de caché completada - $(date)"

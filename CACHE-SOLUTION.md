# Solución Anti-Caché para Sitio Web con Actualizaciones Horarias

Este sistema implementa una solución completa para evitar problemas de caché en un sitio web que se actualiza cada hora.

## 🎯 Problema Resuelto

- **Antes**: El sitio se guardaba en caché por 24 horas, impidiendo que los usuarios vieran contenido actualizado
- **Después**: El contenido dinámico no se cachea, pero los recursos estáticos (imágenes, CSS, JS) mantienen caché optimizado

## 🔧 Archivos Modificados

### 1. `cache_config.php`
- Cambió `CACHE_TIME_DATA` de 86400 a 0 segundos
- Agregó manejo específico para contenido HTML/dinámico
- Mantiene caché optimizado para recursos estáticos

### 2. `index.php`
- Headers anti-caché agresivos al inicio del archivo
- Timestamp dinámico en el hash de contenido
- Eliminación de validación ETag que impedía actualizaciones
- Cache-busting automático en el navegador
- Parámetro timestamp en archivos CSS

### 3. `.htaccess`
- Reglas específicas para prevenir caché de archivos PHP
- Caché optimizado para recursos estáticos
- Headers anti-caché para `index.php`

## 🛠️ Herramientas de Limpieza de Caché

### Automáticas

#### 1. `clear_cache.php`
Script PHP que limpia caché del servidor:
```bash
php clear_cache.php
```

#### 2. `clear_cache.ps1` (PowerShell - Recomendado para Windows)
```powershell
powershell -ExecutionPolicy Bypass -File "clear_cache.ps1"
```

#### 3. `clear_cache.bat` (Batch de Windows)
```cmd
clear_cache.bat
```

### Manual
- **Navegador**: `test-cache.php` - Página de pruebas con botones de limpieza
- **Servidor**: Ejecutar cualquiera de los scripts automáticos

## ⏰ Automatización (Windows)

### Opción 1: Programador de Tareas de Windows
1. Abrir "Programador de tareas" como administrador
2. Importar el archivo `CacheClearTask.xml`
3. Verificar que la ruta del script sea correcta

### Opción 2: Configuración Manual
```powershell
# Crear tarea que se ejecute cada hora
schtasks /create /tn "LimpiarCacheWeb" /tr "powershell -ExecutionPolicy Bypass -File 'c:\MAMP\htdocs\kiosko-scrapping\clear_cache.ps1'" /sc hourly /st 00:00
```

### Opción 3: Cron (si usas Linux/WSL)
```bash
# Agregar al crontab (cada hora)
0 * * * * /bin/bash /ruta/al/proyecto/clear_cache.sh
```

## 🧪 Verificación

### 1. Test de Headers HTTP
Visita: `http://localhost/kiosko-scrapping/test-cache.php`

**Headers esperados para contenido dinámico:**
```
Cache-Control: no-cache, no-store, must-revalidate, max-age=0
Pragma: no-cache
Expires: 0
```

### 2. Verificación Manual
```powershell
# Verificar sintaxis PHP
php -l index.php

# Probar limpieza de caché
php clear_cache.php

# Verificar headers con curl (si está instalado)
curl -I http://localhost/kiosko-scrapping/
```

## 📋 Headers Implementados

### Para Contenido Dinámico (PHP/HTML)
```http
Cache-Control: no-cache, no-store, must-revalidate, max-age=0
Pragma: no-cache
Expires: 0
Last-Modified: [timestamp actual]
```

### Para Recursos Estáticos (CSS/JS/Imágenes)
```http
Cache-Control: public, max-age=31536000, immutable
Expires: [1 año en el futuro]
```

## 🔍 Monitoreo

### Logs
- `cache_clear.log` - Log de ejecuciones del script de limpieza
- Headers HTTP en `test-cache.php`

### Verificación en Navegador
1. Abrir DevTools (F12)
2. Ir a la pestaña "Network"
3. Recargar la página
4. Verificar que `index.php` muestre `Cache-Control: no-cache`

## ⚡ Rendimiento

### Lo que SÍ se cachea (para velocidad):
- Imágenes (.jpg, .png, .gif, etc.)
- Archivos CSS y JavaScript
- Fuentes web
- Iconos

### Lo que NO se cachea (para frescura):
- Archivos PHP (contenido dinámico)
- Respuestas HTML
- APIs y datos JSON

## 🚨 Solución de Problemas

### Si el contenido sigue cacheado:

1. **Verificar configuración del servidor web**:
   ```powershell
   # Verificar que MAMP/Apache esté usando .htaccess
   # Buscar "AllowOverride All" en la configuración
   ```

2. **Limpiar caché manualmente**:
   ```powershell
   # Ejecutar script de limpieza
   powershell -File clear_cache.ps1
   
   # Forzar recarga en navegador
   Ctrl + Shift + R (o Ctrl + F5)
   ```

3. **Verificar headers**:
   - Visitar `test-cache.php`
   - Verificar que `Cache-Control` contenga `no-cache`

4. **Reiniciar servicios**:
   ```powershell
   # Si usas MAMP, reiniciar Apache desde el panel de control
   # O reiniciar el servicio manualmente si es necesario
   ```

### Si los scripts no se ejecutan:

1. **Permisos de PowerShell**:
   ```powershell
   Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
   ```

2. **Verificar rutas**:
   - Asegurar que las rutas en los scripts sean correctas
   - Verificar que PHP esté en el PATH del sistema

3. **Logs de errores**:
   - Revisar `cache_clear.log`
   - Verificar logs de Apache/MAMP

## 📞 Soporte

Si los problemas persisten:
1. Ejecutar `test-cache.php` y verificar los headers
2. Revisar el log `cache_clear.log`
3. Verificar la configuración del servidor web
4. Comprobar que los scripts tienen los permisos correctos

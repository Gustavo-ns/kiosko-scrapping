<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Headers de Caché</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header-info { background: #f0f0f0; padding: 15px; margin: 10px 0; border-left: 4px solid #007cba; }
        .timestamp { background: #e7f3ff; padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { color: #28a745; }
        .warning { color: #ffc107; }
        .error { color: #dc3545; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; }
        button:hover { background: #005a8b; }
    </style>
</head>
<body>
    <h1>Test de Configuración de Caché</h1>
    <p>Esta página verifica que los headers de caché estén configurados correctamente.</p>
    
    <div class="timestamp">
        <strong>Timestamp actual:</strong> <?= date('Y-m-d H:i:s') ?><br>
        <strong>Timestamp Unix:</strong> <?= time() ?><br>
        <strong>Cache Version:</strong> <?= file_exists('.cache_version') ? file_get_contents('.cache_version') : 'No definida' ?>
    </div>

    <div class="header-info">
        <h3>Headers de Respuesta HTTP:</h3>
        <div id="headers-info">
            <p>Los headers se mostrarán aquí...</p>
        </div>
    </div>

    <div class="header-info">
        <h3>Pruebas de Caché:</h3>
        <button onclick="testCacheHeaders()">Verificar Headers</button>
        <button onclick="clearCacheManual()">Limpiar Caché</button>
        <button onclick="forceReload()">Forzar Recarga</button>
        <button onclick="checkLastModified()">Ver Última Modificación</button>
    </div>

    <div id="test-results">
        <!-- Los resultados aparecerán aquí -->
    </div>

    <script>
        // Mostrar información del navegador y caché
        document.addEventListener('DOMContentLoaded', function() {
            const info = `
                <strong>User Agent:</strong> ${navigator.userAgent}<br>
                <strong>Página cargada:</strong> ${new Date().toLocaleString()}<br>
                <strong>Performance Timing:</strong> ${performance.timing.loadEventEnd - performance.timing.navigationStart}ms<br>
                <strong>Tipo de navegación:</strong> ${performance.navigation.type === 0 ? 'Normal' : performance.navigation.type === 1 ? 'Recarga' : 'Otra'}
            `;
            document.getElementById('headers-info').innerHTML = info;
        });

        async function testCacheHeaders() {
            try {
                const response = await fetch(window.location.href, {
                    method: 'HEAD',
                    cache: 'no-cache',
                    headers: {
                        'Cache-Control': 'no-cache',
                        'Pragma': 'no-cache'
                    }
                });

                const headers = {};
                for (let [key, value] of response.headers.entries()) {
                    headers[key] = value;
                }

                const resultDiv = document.getElementById('test-results');
                resultDiv.innerHTML = `
                    <div class="header-info">
                        <h3>Resultado del Test de Headers:</h3>
                        <p class="${headers['cache-control']?.includes('no-cache') ? 'success' : 'error'}">
                            <strong>Cache-Control:</strong> ${headers['cache-control'] || 'No definido'}
                        </p>
                        <p><strong>Pragma:</strong> ${headers['pragma'] || 'No definido'}</p>
                        <p><strong>Expires:</strong> ${headers['expires'] || 'No definido'}</p>
                        <p><strong>Last-Modified:</strong> ${headers['last-modified'] || 'No definido'}</p>
                        <p><strong>ETag:</strong> ${headers['etag'] || 'No definido'}</p>
                        <p><strong>Status:</strong> ${response.status}</p>
                    </div>
                `;
            } catch (error) {
                document.getElementById('test-results').innerHTML = `
                    <div class="header-info error">
                        <p>Error al verificar headers: ${error.message}</p>
                    </div>
                `;
            }
        }

        async function clearCacheManual() {
            try {
                const response = await fetch('clear_cache.php');
                const result = await response.json();
                
                document.getElementById('test-results').innerHTML = `
                    <div class="header-info ${result.success ? 'success' : 'error'}">
                        <h3>Resultado de Limpieza de Caché:</h3>
                        <p><strong>Estado:</strong> ${result.success ? 'Éxito' : 'Error'}</p>
                        <p><strong>Mensaje:</strong> ${result.message}</p>
                        <p><strong>Elementos limpiados:</strong> ${result.cleared ? result.cleared.join(', ') : 'Ninguno'}</p>
                        <p><strong>Timestamp:</strong> ${result.timestamp}</p>
                    </div>
                `;
            } catch (error) {
                document.getElementById('test-results').innerHTML = `
                    <div class="header-info error">
                        <p>Error al limpiar caché: ${error.message}</p>
                    </div>
                `;
            }
        }

        function forceReload() {
            // Agregar timestamp para forzar recarga
            const url = new URL(window.location);
            url.searchParams.set('_t', Date.now());
            window.location.href = url.toString();
        }

        function checkLastModified() {
            const lastModified = document.lastModified;
            document.getElementById('test-results').innerHTML = `
                <div class="header-info">
                    <h3>Información de Modificación:</h3>
                    <p><strong>Última modificación del documento:</strong> ${lastModified}</p>
                    <p><strong>Diferencia con ahora:</strong> ${Math.round((Date.now() - new Date(lastModified).getTime()) / 1000)} segundos</p>
                </div>
            `;
        }

        // Auto-test al cargar la página
        setTimeout(testCacheHeaders, 1000);
    </script>
</body>
</html>

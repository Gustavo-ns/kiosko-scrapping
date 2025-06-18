<?php
require_once '../cache_config.php';

// Verificar si es una petición POST para actualizar la configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $config = [
        'assets_version' => isset($_POST['assets_version']) ? $_POST['assets_version'] : '1.0.13',
        'cache_time_images' => (int)(isset($_POST['cache_time_images']) ? $_POST['cache_time_images'] : 86400),
        'cache_time_static' => (int)(isset($_POST['cache_time_static']) ? $_POST['cache_time_static'] : 86400),
        'cache_time_data' => (int)(isset($_POST['cache_time_data']) ? $_POST['cache_time_data'] : 0)
    ];

    if (updateCacheConfig($config)) {
        $message = "✅ Configuración actualizada exitosamente";
    } else {
        $error = "❌ Error al actualizar la configuración";
    }
}

// Obtener la configuración actual
try {
    $stmt = $pdo->query("SELECT name, value FROM configs WHERE name IN ('assets_version', 'cache_time_images', 'cache_time_static', 'cache_time_data')");
    $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $error = "Error al obtener la configuración: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Caché</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Configuración de Caché</h1>

        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="assets_version" class="form-label">Versión de Assets</label>
                <input type="text" class="form-control" id="assets_version" name="assets_version" 
                       value="<?php echo htmlspecialchars(isset($configs['assets_version']) ? $configs['assets_version'] : '1.0.13'); ?>" required>
                <div class="form-text">Versión actual de los assets para control de caché</div>
            </div>

            <div class="mb-3">
                <label for="cache_time_images" class="form-label">Tiempo de Caché para Imágenes (segundos)</label>
                <input type="number" class="form-control" id="cache_time_images" name="cache_time_images" 
                       value="<?php echo (int)(isset($configs['cache_time_images']) ? $configs['cache_time_images'] : 86400); ?>" required>
                <div class="form-text">Tiempo en segundos que se mantendrán las imágenes en caché (86400 = 1 día)</div>
            </div>

            <div class="mb-3">
                <label for="cache_time_static" class="form-label">Tiempo de Caché para Archivos Estáticos (segundos)</label>
                <input type="number" class="form-control" id="cache_time_static" name="cache_time_static" 
                       value="<?php echo (int)(isset($configs['cache_time_static']) ? $configs['cache_time_static'] : 86400); ?>" required>
                <div class="form-text">Tiempo en segundos que se mantendrán los archivos estáticos en caché (86400 = 1 día)</div>
            </div>

            <div class="mb-3">
                <label for="cache_time_data" class="form-label">Tiempo de Caché para Datos (segundos)</label>
                <input type="number" class="form-control" id="cache_time_data" name="cache_time_data" 
                       value="<?php echo (int)(isset($configs['cache_time_data']) ? $configs['cache_time_data'] : 0); ?>" required>
                <div class="form-text">Tiempo en segundos que se mantendrán los datos en caché (0 = sin caché)</div>
            </div>

            <button type="submit" class="btn btn-primary">Guardar Configuración</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validación del formulario
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html> 
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Portadas de Periódicos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .menu {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
        }
        .menu a {
            padding: 10px 20px;
            background-color: #0066cc;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .menu a:hover {
            background-color: #0052a3;
        }
        .status {
            margin-top: 40px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .status h2 {
            color: #333;
            margin-top: 0;
        }
        .status-item {
            margin-bottom: 10px;
            padding: 10px;
            background-color: white;
            border-left: 4px solid #0066cc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sistema de Portadas de Periódicos</h1>
        
        <div class="menu">
            <a href="/covers">Ver Portadas</a>
            <a href="/check">Verificar Sistema</a>
            <a href="/test">Probar Sitios</a>
        </div>

        <div class="status">
            <h2>Estado del Sistema</h2>
            <?php
            // Verificar directorios
            $directories = [
                'storage/images' => 'Directorio de imágenes',
                'storage/logs' => 'Directorio de logs',
                'logs' => 'Directorio de logs del sistema'
            ];

            foreach ($directories as $dir => $label) {
                $path = dirname(dirname(__DIR__)) . '/' . $dir;
                $exists = is_dir($path);
                $writable = is_writable($path);
                
                echo '<div class="status-item">';
                echo $label . ': ';
                if ($exists && $writable) {
                    echo '<span style="color: green;">✓ OK</span>';
                } else {
                    echo '<span style="color: red;">✗ Error</span>';
                    if (!$exists) {
                        echo ' (No existe)';
                    } elseif (!$writable) {
                        echo ' (Sin permisos de escritura)';
                    }
                }
                echo '</div>';
            }

            // Verificar conexión a la base de datos
            try {
                require_once dirname(dirname(__DIR__)) . '/app/config/DatabaseConnection.php';
                $db = DatabaseConnection::getInstance();
                $pdo = $db->getConnection();
                echo '<div class="status-item">Base de datos: <span style="color: green;">✓ Conectada</span></div>';
            } catch (Exception $e) {
                echo '<div class="status-item">Base de datos: <span style="color: red;">✗ Error de conexión</span></div>';
            }
            ?>
        </div>
    </div>
</body>
</html> 
<?php
// Crear estructura de directorios para carga progresiva
$directories = [
    'images/covers/previews',
    'images/melwater/previews'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Creado: $dir\n";
        } else {
            echo "❌ Error creando: $dir\n";
        }
    } else {
        echo "ℹ️  Ya existe: $dir\n";
    }
}

echo "✅ Estructura de directorios lista para carga progresiva\n";
?>

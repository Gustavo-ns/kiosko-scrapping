<?php
// Script para ejecutar limpieza manual de imágenes huérfanas
require_once 'scrape.php';

echo "Ejecutando limpieza de imágenes huérfanas...\n";
$deleted = cleanOrphanedImages();
echo "Proceso completado. Archivos eliminados: $deleted\n";
?>

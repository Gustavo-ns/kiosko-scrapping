<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de Public</h1>";
echo "<p>Si puedes ver esto, el acceso al directorio public está funcionando.</p>";
echo "<hr>";
echo "<h2>Información del Servidor:</h2>";
echo "<pre>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Directorio Actual: " . getcwd() . "\n";
echo "</pre>";
echo "<hr>";
echo "<h2>Prueba de .htaccess:</h2>";
echo "<p>Contenido del .htaccess:</p>";
echo "<pre>";
if (file_exists('.htaccess')) {
    echo htmlspecialchars(file_get_contents('.htaccess'));
} else {
    echo "No se encontró el archivo .htaccess";
}
echo "</pre>"; 
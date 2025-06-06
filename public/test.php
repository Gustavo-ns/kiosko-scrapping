<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de Acceso</h1>";
echo "<p>Si puedes ver esto, el PHP está funcionando correctamente.</p>";
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
echo "<h2>Prueba de mod_rewrite:</h2>";
echo "<p>mod_rewrite está " . (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules()) ? 'habilitado' : 'no se puede determinar') . "</p>"; 
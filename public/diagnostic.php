<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Diagnóstico del Servidor</h1>";

// Información básica de PHP
echo "<h2>Información de PHP</h2>";
echo "<pre>";
echo "Versión de PHP: " . phpversion() . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "error_reporting: " . ini_get('error_reporting') . "\n";
echo "</pre>";

// Información del servidor
echo "<h2>Variables del Servidor</h2>";
echo "<pre>";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "</pre>";

// Verificar permisos de directorios importantes
echo "<h2>Permisos de Directorios</h2>";
echo "<pre>";
$directories = [
    '.' => getcwd(),
    '../' => dirname(getcwd()),
    '../app' => dirname(getcwd()) . '/app',
    '../app/config' => dirname(getcwd()) . '/app/config',
    'assets' => getcwd() . '/assets',
    'assets/css' => getcwd() . '/assets/css'
];

foreach ($directories as $name => $path) {
    echo "$name: ";
    if (file_exists($path)) {
        echo "Existe - Permisos: " . decoct(fileperms($path) & 0777) . "\n";
    } else {
        echo "No existe\n";
    }
}
echo "</pre>";

// Verificar archivos de configuración
echo "<h2>Archivos de Configuración</h2>";
echo "<pre>";
$config_files = [
    '../app/config/config.php',
    '../app/config/bootstrap.php',
    '../app/config/cache_config.php',
    '.htaccess',
    '../.htaccess'
];

foreach ($config_files as $file) {
    echo "$file: ";
    if (file_exists($file)) {
        echo "Existe - Permisos: " . decoct(fileperms($file) & 0777) . "\n";
        if ($file === '../app/config/config.php') {
            echo "Contenido de config.php:\n";
            $config_content = file_get_contents(dirname(__DIR__) . '/app/config/config.php');
            echo htmlspecialchars($config_content) . "\n";
        }
    } else {
        echo "No existe\n";
    }
}
echo "</pre>";

// Verificar extensiones requeridas
echo "<h2>Extensiones PHP</h2>";
echo "<pre>";
$required_extensions = ['pdo', 'pdo_mysql', 'gd', 'curl'];
foreach ($required_extensions as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? "Cargada" : "No cargada") . "\n";
}
echo "</pre>";

// Verificar conexión a la base de datos
echo "<h2>Prueba de Conexión a la Base de Datos</h2>";
echo "<pre>";
try {
    // Cargar la configuración
    $config_file = dirname(__DIR__) . '/app/config/config.php';
    if (!file_exists($config_file)) {
        throw new Exception("El archivo config.php no existe");
    }

    // Cargar la configuración y verificar que sea un array
    $cfg = @include $config_file;
    if ($cfg === false) {
        throw new Exception("Error al cargar el archivo config.php");
    }
    if (!is_array($cfg)) {
        throw new Exception("El archivo config.php no devuelve un array");
    }
    
    echo "Configuración cargada:\n";
    var_export($cfg);
    echo "\n\n";

    if (!isset($cfg['db'])) {
        throw new Exception("La configuración de la base de datos no está definida");
    }

    // Verificar que todos los parámetros necesarios estén presentes
    $required_params = ['host', 'name', 'user', 'pass', 'charset'];
    foreach ($required_params as $param) {
        if (!isset($cfg['db'][$param])) {
            throw new Exception("Falta el parámetro '$param' en la configuración de la base de datos");
        }
    }

    // Intentar conexión
    $dsn = "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}";
    echo "DSN: " . $dsn . "\n";
    
    $pdo = new PDO(
        $dsn,
        $cfg['db']['user'],
        $cfg['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    echo "Conexión exitosa a la base de datos\n";
    
    // Probar una consulta simple
    $stmt = $pdo->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    echo "Versión de MySQL: " . $result['version'] . "\n";
    
} catch (Exception $e) {
    echo "Error de conexión: " . $e->getMessage() . "\n";
    echo "Traza del error:\n" . $e->getTraceAsString() . "\n";
}
echo "</pre>"; 
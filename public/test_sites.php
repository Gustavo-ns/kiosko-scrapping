<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definir la ruta base del proyecto
define('BASE_PATH', dirname(__FILE__));
$config = require BASE_PATH . '/app/config/config.php';

// Función para verificar accesibilidad de una URL
function checkUrl($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'error' => $error,
        'accessible' => ($httpCode >= 200 && $httpCode < 400)
    ];
}

echo "=== Test de Sitios Configurados ===\n\n";

$totalSites = 0;
$accessibleSites = 0;
$inaccessibleSites = [];

foreach ($config['sites'] as $country => $sites) {
    echo "\nPaís: " . strtoupper($country) . "\n";
    echo str_repeat("-", 50) . "\n";
    
    foreach ($sites as $index => $site) {
        $totalSites++;
        $url = $site['url'];
        echo ($index + 1) . ". " . $url . "\n";
        
        // Mostrar configuración del sitio
        echo "   Configuración:\n";
        if (isset($site['selector'])) echo "   - Selector: " . $site['selector'] . "\n";
        if (isset($site['multiple'])) echo "   - Multiple: " . ($site['multiple'] ? 'Sí' : 'No') . "\n";
        if (isset($site['use_xpath'])) echo "   - Usa XPath: Sí\n";
        if (isset($site['xpath'])) echo "   - XPath: " . $site['xpath'] . "\n";
        if (isset($site['custom_extractor'])) echo "   - Extractor personalizado: " . $site['custom_extractor'] . "\n";
        
        // Verificar accesibilidad
        $result = checkUrl($url);
        if ($result['accessible']) {
            echo "   [✓] Accesible (HTTP " . $result['code'] . ")\n";
            $accessibleSites++;
        } else {
            echo "   [✗] No accesible (HTTP " . $result['code'] . ")\n";
            if ($result['error']) echo "   Error: " . $result['error'] . "\n";
            $inaccessibleSites[] = [
                'country' => $country,
                'url' => $url,
                'code' => $result['code'],
                'error' => $result['error']
            ];
        }
        echo "\n";
    }
}

// Resumen
echo "\n=== Resumen ===\n";
echo "Total de sitios configurados: " . $totalSites . "\n";
echo "Sitios accesibles: " . $accessibleSites . "\n";
echo "Sitios no accesibles: " . count($inaccessibleSites) . "\n";

if (count($inaccessibleSites) > 0) {
    echo "\n=== Sitios con Problemas ===\n";
    foreach ($inaccessibleSites as $site) {
        echo "\nPaís: " . strtoupper($site['country']) . "\n";
        echo "URL: " . $site['url'] . "\n";
        echo "Código HTTP: " . $site['code'] . "\n";
        if ($site['error']) echo "Error: " . $site['error'] . "\n";
    }
}

// Guardar resultados en un archivo de log
$logContent = "=== Test de Sitios - " . date('Y-m-d H:i:s') . " ===\n";
$logContent .= "Total de sitios: " . $totalSites . "\n";
$logContent .= "Accesibles: " . $accessibleSites . "\n";
$logContent .= "No accesibles: " . count($inaccessibleSites) . "\n\n";

if (count($inaccessibleSites) > 0) {
    $logContent .= "Sitios con problemas:\n";
    foreach ($inaccessibleSites as $site) {
        $logContent .= "- {$site['url']} (País: {$site['country']}, HTTP: {$site['code']})\n";
    }
}

$logFile = BASE_PATH . '/logs/sites_test_' . date('Y-m-d') . '.log';
file_put_contents($logFile, $logContent, FILE_APPEND); 
<?php

$url = 'https://www.ip.gov.py/ip/';
$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 100,
    CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
]);

$html = curl_exec($ch);

if (curl_errno($ch)) {
    echo '❌ Error: ' . curl_error($ch);
} else {
    echo "✅ HTML cargado correctamente.<br>";
    echo htmlentities(substr($html, 0, 1400)); // mostrar parte del contenido
}

curl_close($ch);

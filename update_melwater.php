<?php
header('Content-Type: application/json');

require_once 'download_image.php';
require_once 'optimized-image-processor.php';

// Cargar configuración de la base de datos
$cfg = require 'config.php';

try {
    // Conexión a la base de datos
    $pdo = new PDO(
        "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}",
        $cfg['db']['user'],
        $cfg['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // URL de la API y API key
    $apiUrl = "https://api.meltwater.com/v3/exports/recurring";
    $apiKey = "8PMcUPYZ1M954yDpIh6mI8CE61fqwG2LFulSbPGo";

    // URLs específicas para cada horario
    $scheduledUrls = [
        '6:30' => 'https://downloads.exports.meltwater.com/v3/recurring/13742063?data_key=3f4aed98-80fe-3fb1-9cf0-ce4681a26d7c',
        '7:35' => 'https://downloads.exports.meltwater.com/v3/recurring/13820798?data_key=99180c35-19a8-3b35-8ae7-368a020d3f60',
        '8:30' => 'https://downloads.exports.meltwater.com/v3/recurring/13841083?data_key=1590e1c0-0060-3ce6-af3b-431a90b5e3e4',
        '9:35' => 'https://downloads.exports.meltwater.com/v3/recurring/13869250?data_key=262188f9-da14-3148-b734-e071ee3d9f7e',
        '10:35' => 'https://downloads.exports.meltwater.com/v3/recurring/13869241?data_key=7481954f-4c7c-34c4-89e8-59b79b063ae7'
    ];

    // Obtener la hora actual
    $currentHour = date('H:i');
    
    // Determinar qué URL usar basado en la hora actual
    $dataUrl = null;
    foreach ($scheduledUrls as $time => $url) {
        if ($currentHour >= $time) {
            $dataUrl = $url;
        }
    }

    // Si no se encontró una URL para la hora actual, usar la última URL
    if (!$dataUrl) {
        $dataUrl = end($scheduledUrls);
    }

    // Obtener los datos del JSON
    $response = file_get_contents($dataUrl);
    if ($response === FALSE) {
        throw new Exception("Error al obtener los datos del archivo JSON.");
    }

    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        throw new Exception("No se encontraron documentos en la respuesta.");
    }

    // Preparar la consulta de inserción
    $stmt = $pdo->prepare("INSERT INTO pk_melwater (
        external_id, published_date, source_id, social_network, 
        country_code, country_name, author_name, content_image, 
        content_text, url_destino, input_names
    ) VALUES (
        :external_id, :published_date, :source_id, :social_network,
        :country_code, :country_name, :author_name, :content_image,
        :content_text, :url_destino, :input_names
    ) ON DUPLICATE KEY UPDATE
        published_date = VALUES(published_date),
        source_id = VALUES(source_id),
        social_network = VALUES(social_network),
        country_code = VALUES(country_code),
        country_name = VALUES(country_name),
        author_name = VALUES(author_name),
        content_image = VALUES(content_image),
        content_text = VALUES(content_text),
        url_destino = VALUES(url_destino),
        input_names = VALUES(input_names)");

    $country_names = [
        'ar' => 'Argentina',
        'bo' => 'Bolivia',
        'br' => 'Brasil',
        'cl' => 'Chile',
        'co' => 'Colombia',
        'ec' => 'Ecuador',
        'us' => 'Estados Unidos',
        'mx' => 'México',
        'pa' => 'Panamá',
        'py' => 'Paraguay',
        'pe' => 'Perú',
        'do' => 'República Dominicana',
        'uy' => 'Uruguay',
        've' => 'Venezuela',
        'es' => 'España',
        'gb' => 'Reino Unido',
        'zz' => 'Desconocido'
    ];

    $updatedCount = 0;
    foreach ($data['documents'] as $doc) {
        $author_name = isset($doc['author']['name']) ? $doc['author']['name'] : 'N/A';
        $content_image = isset($doc['content']['image']) ? $doc['content']['image'] : null;
        $content_text = isset($doc['content']['opening_text']) ? $doc['content']['opening_text'] : '';
        $country_code = strtolower(isset($doc['location']['country_code']) ? $doc['location']['country_code'] : 'zz');
        $country_name = isset($country_names[$country_code]) ? $country_names[$country_code] : ucfirst($country_code);
        $url_destino = isset($doc['url']) ? $doc['url'] : '#';
        $external_id = isset($doc['author']['external_id']) ? $doc['author']['external_id'] : '';
        $published_date = isset($doc['published_date']) ? $doc['published_date'] : '';
        $source_id = isset($doc['source']['id']) ? $doc['source']['id'] : '';
        
        // Procesar la imagen si existe
        if ($content_image && $external_id) {
            try {
                // Crear directorios si no existen
                $melwater_dir = __DIR__ . '/images/melwater';
                $previews_dir = $melwater_dir . '/previews';
                foreach ([$melwater_dir, $previews_dir] as $dir) {
                    if (!file_exists($dir)) {
                        mkdir($dir, 0755, true);
                    }
                }

                // Descargar y procesar la imagen
                $image_data = file_get_contents($content_image);
                if ($image_data !== false) {
                    $temp_file = tempnam(sys_get_temp_dir(), 'melwater_');
                    file_put_contents($temp_file, $image_data);

                    // Procesar imagen original
                    $original_filename = $external_id . '_original.webp';
                    $original_path = $melwater_dir . '/' . $original_filename;
                    
                    if (convertToWebP($temp_file, $original_path, 90)) {
                        // Procesar preview - Asegurar que el nombre del archivo tenga el formato correcto
                        $preview_filename = $external_id . '_preview.webp';
                        $preview_path = $previews_dir . '/' . $preview_filename;
                        
                        // Verificar que el nombre del archivo tenga el formato correcto
                        if (strpos($preview_filename, '_preview.webp') === false) {
                            error_log("Error: Formato incorrecto del nombre del archivo preview para {$external_id}");
                            continue;
                        }
                        
                        if (convertToWebP($temp_file, $preview_path, 40, 320, 480)) {
                            // Actualizar la URL de la imagen en la base de datos
                            // Usar rutas relativas sin slash inicial
                            $content_image = 'images/melwater/' . $original_filename;
                            
                            // Verificar que los archivos existen y tienen el formato correcto
                            if (!file_exists($original_path) || !file_exists($preview_path)) {
                                error_log("Error: Archivos de imagen no creados correctamente para {$external_id}");
                                error_log("Original path: {$original_path}");
                                error_log("Preview path: {$preview_path}");
                            }
                        } else {
                            error_log("Error al crear preview para {$external_id}");
                        }
                    } else {
                        error_log("Error al crear imagen original para {$external_id}");
                    }

                    // Limpiar archivo temporal
                    unlink($temp_file);
                } else {
                    error_log("Error al descargar imagen para {$external_id}");
                }
            } catch (Exception $e) {
                error_log("Error procesando imagen para {$external_id}: " . $e->getMessage());
            }
        }
        
        // Debug para el ID específico
        if ($external_id === '8105922') {
            error_log("Datos para ID 8105922:");
            error_log("Content Image: " . print_r($content_image, true));
            error_log("Raw document data: " . print_r($doc, true));
        }
        
        // Extraer red social del source_id
        $social_network = '';
        if (!empty($source_id) && strpos($source_id, 'social:') === 0) {
            $parts = explode(':', $source_id);
            $social_network = isset($parts[1]) ? ucfirst($parts[1]) : '';
        }

        // Obtener los inputs names
        $input_names = [];
        if (isset($doc['matched']['inputs']) && is_array($doc['matched']['inputs'])) {
            foreach ($doc['matched']['inputs'] as $input) {
                if (isset($input['name'])) {
                    $input_names[] = $input['name'];
                }
            }
        }
        $input_names_str = implode(', ', $input_names);

        try {
            $stmt->execute([
                ':external_id' => $external_id,
                ':published_date' => $published_date,
                ':source_id' => $source_id,
                ':social_network' => $social_network,
                ':country_code' => $country_code,
                ':country_name' => $country_name,
                ':author_name' => $author_name,
                ':content_image' => $content_image,
                ':content_text' => $content_text,
                ':url_destino' => $url_destino,
                ':input_names' => $input_names_str
            ]);
            $updatedCount++;
        } catch (PDOException $e) {
            error_log("Error al actualizar registro {$external_id}: " . $e->getMessage());
            continue;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Se actualizaron {$updatedCount} registros correctamente."
    ]);

    // Llamar a process_portadas.php para actualizar la tabla portadas
    try {
        $process_portadas_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['PHP_SELF']) . "/process_portadas.php";
        $response = file_get_contents($process_portadas_url);
        if ($response === FALSE) {
            throw new Exception("Error al llamar a process_portadas.php");
        }
        $result = json_decode($response, true);
        if (!$result['success']) {
            throw new Exception("Error en process_portadas.php: " . $result['message']);
        }
        error_log("Tabla portadas actualizada correctamente a través de process_portadas.php");
    } catch (Exception $e) {
        error_log("Error al actualizar tabla portadas: " . $e->getMessage());
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 
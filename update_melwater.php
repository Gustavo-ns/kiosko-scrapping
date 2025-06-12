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
        '06:10' => 'https://downloads.exports.meltwater.com/v3/recurring/13961606?data_key=e2ca54bc-20f8-367a-b550-0ab225b41bb9',
        '06:45' => 'https://downloads.exports.meltwater.com/v3/recurring/13961613?data_key=4e5adc6f-6f70-3ead-9b00-54d9b52f1bc5',
        '07:15' => 'https://downloads.exports.meltwater.com/v3/recurring/13961617?data_key=1a6493d3-6f3f-304f-a4a8-9cb79092b6d9',
        '08:00' => 'https://downloads.exports.meltwater.com/v3/recurring/13961632?data_key=81f0dd53-1776-390a-839d-db13834d6e0a',
        '09:00' => 'https://downloads.exports.meltwater.com/v3/recurring/13961639?data_key=86e1afbc-aba6-36fb-b470-a86573ea1a52',
        '10:00' => 'https://downloads.exports.meltwater.com/v3/recurring/13961644?data_key=99cf4e6a-c04a-34a2-9f27-ffd950971fd0'
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

    // Log de la URL que se está utilizando
    error_log("Hora actual: " . $currentHour);
    error_log("URL de Meltwater seleccionada: " . $dataUrl);

    // Obtener los datos del JSON
    $response = file_get_contents($dataUrl);
    if ($response === FALSE) {
        throw new Exception("Error al obtener los datos del archivo JSON.");
    }

    $data = json_decode($response, true);
    if (!isset($data['documents'])) {
        throw new Exception("No se encontraron documentos en la respuesta.");
    }

    // Limpiar la tabla portadas antes de insertar nuevos datos
    try {
        $pdo->beginTransaction();
        
        // Obtener la fecha de inicio (ayer a las 18hs)
        $startDate = new DateTime();
        $startDate->setTime(18, 0, 0);
        $startDate->modify('-1 day');
        $startDateStr = $startDate->format('Y-m-d H:i:s');
        
        // Eliminar registros desde ayer a las 18hs hasta ahora
        $deleteStmt = $pdo->prepare("
            DELETE FROM portadas 
            WHERE published_date >= :start_date 
            AND published_date <= NOW()
        ");
        $deleteStmt->execute([':start_date' => $startDateStr]);
        
        $deletedCount = $deleteStmt->rowCount();
        error_log("Se eliminaron {$deletedCount} registros antiguos de la tabla portadas");
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error al limpiar la tabla portadas: " . $e->getMessage());
        throw new Exception("Error al limpiar la tabla portadas: " . $e->getMessage());
    }

    // Preparar la consulta de inserción
    $stmt = $pdo->prepare("INSERT INTO pk_melwater (
        external_id, published_date, indexed_date, 
        content_image, input_names
    ) VALUES (
        :external_id, :published_date, :indexed_date,
        :content_image, :input_names
    ) ON DUPLICATE KEY UPDATE
        published_date = VALUES(published_date),
        indexed_date = VALUES(indexed_date),
        content_image = VALUES(content_image),
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
        $content_image = isset($doc['content']['image']) ? $doc['content']['image'] : null;
        $external_id = isset($doc['author']['external_id']) ? $doc['author']['external_id'] : '';
        $published_date = isset($doc['published_date']) ? $doc['published_date'] : '';

        // Verificar si el registro ya existe
        $checkStmt = $pdo->prepare("SELECT 1 FROM pk_melwater WHERE external_id = :external_id");
        $checkStmt->execute([':external_id' => $external_id]);
        $exists = $checkStmt->fetchColumn();
        
        if ($exists) {
            error_log("Registro con external_id {$external_id} ya existe, actualizando...");
        } else {
            error_log("Nuevo registro con external_id {$external_id}, insertando...");
        }
        
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
            // Convertir la fecha de publicación
            $published_date = new DateTime($doc['published_date']);
            $published_date->setTimezone(new DateTimeZone('America/Argentina/Buenos_Aires'));
            
            // Convertir la fecha de indexación si existe
            $indexed_date = null;
            if (isset($doc['indexed_date'])) {
                $indexed_date = new DateTime($doc['indexed_date']);
                $indexed_date->setTimezone(new DateTimeZone('America/Argentina/Buenos_Aires'));
                $indexed_date_str = $indexed_date->format('Y-m-d H:i:s');
            } else {
                $indexed_date_str = null;
            }

            // Ejecutar la inserción
            $stmt->execute([
                'external_id' => $external_id,
                'published_date' => $published_date->format('Y-m-d H:i:s'),
                'indexed_date' => $indexed_date_str,
                'content_image' => $content_image,
                'input_names' => $input_names_str
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
        $process_portadas_path = __DIR__ . '/process_portadas.php';
        if (!file_exists($process_portadas_path)) {
            throw new Exception("No se encontró el archivo process_portadas.php");
        }
        
        // Ejecutar el script directamente
        $command = "php " . escapeshellarg($process_portadas_path);
        $output = [];
        $return_var = 0;
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            throw new Exception("Error al ejecutar process_portadas.php");
        }
        
        $response = implode("\n", $output);
        $result = json_decode($response, true);
        
        if (!$result || !isset($result['success']) || !$result['success']) {
            $error_message = isset($result['message']) ? $result['message'] : 'Error desconocido';
            throw new Exception("Error en process_portadas.php: " . $error_message);
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
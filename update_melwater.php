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
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => false, // Desactivar conexiones persistentes
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
        ]
    );

    // Configurar timeouts de sesión
    $pdo->exec("SET SESSION wait_timeout=60");
    $pdo->exec("SET SESSION interactive_timeout=60");

    // Función para cerrar la conexión explícitamente
    function closePDO() {
        global $pdo;
        if ($pdo !== null) {
            $pdo = null;
        }
    }

    // Registrar la función de cierre para que se ejecute al finalizar el script
    register_shutdown_function('closePDO');

    // URL de la API y API key
    $apiUrl = "https://api.meltwater.com/v3/exports/recurring";
    $apiKey = "8PMcUPYZ1M954yDpIh6mI8CE61fqwG2LFulSbPGo";

    // URLs específicas para cada horario
    $scheduledUrls = [
        '03:12' => 'https://downloads.exports.meltwater.com/v3/recurring/13961644?data_key=99cf4e6a-c04a-34a2-9f27-ffd950971fd0',
        '07:02' => 'https://downloads.exports.meltwater.com/v3/recurring/13961639?data_key=86e1afbc-aba6-36fb-b470-a86573ea1a52',
        '06:02' => 'https://downloads.exports.meltwater.com/v3/recurring/13961632?data_key=81f0dd53-1776-390a-839d-db13834d6e0a',
        '05:02' => 'https://downloads.exports.meltwater.com/v3/recurring/13961617?data_key=1a6493d3-6f3f-304f-a4a8-9cb79092b6d9',
        '04:16' => 'https://downloads.exports.meltwater.com/v3/recurring/13961613?data_key=4e5adc6f-6f70-3ead-9b00-54d9b52f1bc5',
        '03:46' => 'https://downloads.exports.meltwater.com/v3/recurring/13961606?data_key=e2ca54bc-20f8-367a-b550-0ab225b41bb9'
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
        content_image, preview_image, input_names
    ) VALUES (
        :external_id, :published_date, :indexed_date,
        :content_image, :preview_image, :input_names
    ) ON DUPLICATE KEY UPDATE
        published_date = VALUES(published_date),
        indexed_date = VALUES(indexed_date),
        content_image = VALUES(content_image),
        preview_image = VALUES(preview_image),
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
        // Initialize image variables
        $content_image = null;
        $preview_image = null;
        
        $content_image = isset($doc['content']['image']) ? $doc['content']['image'] : null;
        $external_id = isset($doc['author']['external_id']) ? $doc['author']['external_id'] : '';
        $published_date = isset($doc['published_date']) ? $doc['published_date'] : '';

        // Verificar si el registro ya existe
        $checkStmt = $pdo->prepare("SELECT 1 FROM pk_melwater WHERE external_id = :external_id");
        $checkStmt->execute([':external_id' => $external_id]);
        $exists = $checkStmt->fetchColumn();
        
        if ($exists) {
            error_log("Registro con external_id {$external_id} ya existe, actualizando...");
            // Verificar si ya tiene una imagen asociada
            $imgStmt = $pdo->prepare("SELECT content_image, preview_image FROM pk_melwater WHERE external_id = :external_id");
            $imgStmt->execute([':external_id' => $external_id]);
            $existing_images = $imgStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_images) {
                $content_image = $existing_images['content_image'];
                $preview_image = $existing_images['preview_image'];
                error_log("Usando imágenes existentes para {$external_id}:");
                error_log("Content image: " . $content_image);
                error_log("Preview image: " . $preview_image);
            }
        } else {
            error_log("Nuevo registro con external_id {$external_id}, insertando...");
        }
        
        // Procesar la imagen si existe y es necesario
        if ($content_image && $external_id && (!$exists || !$existing_images || !file_exists(__DIR__ . '/' . $content_image) || !file_exists(__DIR__ . '/' . $preview_image))) {
            try {
                // Crear directorios si no existen
                $melwater_dir = __DIR__ . '/images/melwater';
                $previews_dir = $melwater_dir . '/previews';
                
                // Verificar y crear directorios con mejor manejo de errores
                foreach ([$melwater_dir, $previews_dir] as $dir) {
                    if (!file_exists($dir)) {
                        error_log("Intentando crear directorio: " . $dir);
                        if (!@mkdir($dir, 0755, true)) {
                            $error = error_get_last();
                            error_log("Error al crear directorio {$dir}: " . ($error ? $error['message'] : 'Error desconocido'));
                            throw new Exception("No se pudo crear el directorio: " . $dir);
                        }
                        error_log("Directorio creado exitosamente: " . $dir);
                    }
                    
                    // Verificar permisos después de crear
                    if (!is_writable($dir)) {
                        error_log("Error: El directorio {$dir} no tiene permisos de escritura");
                        error_log("Permisos actuales: " . substr(sprintf('%o', fileperms($dir)), -4));
                        throw new Exception("El directorio {$dir} no tiene permisos de escritura");
                    }
                }

                // Generar número aleatorio para el nombre del archivo
                $random_number = mt_rand(1000, 9999);

                // Procesar imagen original con el formato correcto
                $original_filename = $external_id . '_' . $random_number . '_original.webp';
                $original_path = $melwater_dir . '/' . $original_filename;
                
                // Procesar preview con el formato correcto
                $preview_filename = $external_id . '_' . $random_number . '_preview.webp';
                $preview_path = $previews_dir . '/' . $preview_filename;

                // Descargar y procesar la imagen
                error_log("Intentando descargar imagen desde: " . $content_image);
                $image_data = @file_get_contents($content_image);
                if ($image_data === false) {
                    $error = error_get_last();
                    error_log("Error al descargar imagen: " . ($error ? $error['message'] : 'Error desconocido'));
                    throw new Exception("No se pudo descargar la imagen desde: " . $content_image);
                }
                error_log("Imagen descargada exitosamente, tamaño: " . strlen($image_data) . " bytes");

                // Crear archivo temporal
                $temp_file = tempnam(sys_get_temp_dir(), 'melwater_');
                if ($temp_file === false) {
                    throw new Exception("No se pudo crear archivo temporal");
                }
                error_log("Archivo temporal creado: " . $temp_file);

                // Guardar imagen en archivo temporal
                if (@file_put_contents($temp_file, $image_data) === false) {
                    $error = error_get_last();
                    error_log("Error al guardar archivo temporal: " . ($error ? $error['message'] : 'Error desconocido'));
                    throw new Exception("No se pudo guardar el archivo temporal");
                }
                error_log("Imagen guardada en archivo temporal exitosamente");

                error_log("Intentando convertir imagen original a: " . $original_path);
                if (!@convertToWebP($temp_file, $original_path, 90)) {
                    $error = error_get_last();
                    error_log("Error al convertir imagen original: " . ($error ? $error['message'] : 'Error desconocido'));
                    throw new Exception("No se pudo convertir la imagen original para {$external_id}");
                }
                error_log("Imagen original convertida exitosamente");

                error_log("Intentando convertir preview a: " . $preview_path);
                if (!@convertToWebP($temp_file, $preview_path, 25, 320, 480)) {
                    $error = error_get_last();
                    error_log("Error al convertir preview: " . ($error ? $error['message'] : 'Error desconocido'));
                    throw new Exception("No se pudo convertir la imagen preview para {$external_id}");
                }
                error_log("Preview convertido exitosamente");

                // Actualizar las URLs de las imágenes en la base de datos con el formato correcto
                $content_image = 'images/melwater/' . $original_filename;
                $preview_image = 'images/melwater/previews/' . $preview_filename;
                error_log("URL de imagen original actualizada en base de datos: " . $content_image);
                error_log("URL de imagen preview actualizada en base de datos: " . $preview_image);

                // Verificar que los archivos existen y tienen el formato correcto
                if (!file_exists($original_path) || !file_exists($preview_path)) {
                    error_log("Error: Archivos de imagen no creados correctamente para {$external_id}");
                    error_log("Original path: " . $original_path . " (existe: " . (file_exists($original_path) ? 'sí' : 'no') . ")");
                    error_log("Preview path: " . $preview_path . " (existe: " . (file_exists($preview_path) ? 'sí' : 'no') . ")");
                    throw new Exception("Archivos de imagen no creados correctamente");
                }

                // Verificar permisos de archivos
                if (!is_readable($original_path) || !is_readable($preview_path)) {
                    error_log("Error: Los archivos de imagen no tienen permisos de lectura");
                    error_log("Permisos original: " . substr(sprintf('%o', fileperms($original_path)), -4));
                    error_log("Permisos preview: " . substr(sprintf('%o', fileperms($preview_path)), -4));
                    throw new Exception("Los archivos de imagen no tienen permisos de lectura");
                }

                // Verificar tamaños de archivo
                $original_size = filesize($original_path);
                $preview_size = filesize($preview_path);
                error_log("Tamaño archivo original: " . $original_size . " bytes");
                error_log("Tamaño archivo preview: " . $preview_size . " bytes");

                if ($original_size === 0 || $preview_size === 0) {
                    throw new Exception("Los archivos de imagen están vacíos");
                }

                // Verificación final de que las imágenes existen antes de limpiar el temporal
                if (!file_exists($original_path)) {
                    error_log("Error crítico: La imagen original no existe antes de limpiar el temporal");
                    throw new Exception("La imagen original no existe antes de limpiar el temporal");
                }

                if (!file_exists($preview_path)) {
                    error_log("Error crítico: La imagen preview no existe antes de limpiar el temporal");
                    throw new Exception("La imagen preview no existe antes de limpiar el temporal");
                }

                // Verificar que las imágenes existen en la ruta correcta
                $final_original_path = __DIR__ . '/' . $content_image;
                $final_preview_path = __DIR__ . '/' . $preview_image;
                
                if (!file_exists($final_original_path)) {
                    error_log("Error crítico: La imagen original no existe en la ruta final: " . $final_original_path);
                    throw new Exception("La imagen original no existe en la ruta final");
                }

                if (!file_exists($final_preview_path)) {
                    error_log("Error crítico: La imagen preview no existe en la ruta final: " . $final_preview_path);
                    throw new Exception("La imagen preview no existe en la ruta final");
                }

            } catch (Exception $e) {
                error_log("Error procesando imagen para {$external_id}: " . $e->getMessage());
                // NO eliminamos las imágenes finales en caso de error
                // Solo registramos el error
                error_log("Error en el procesamiento de imágenes para {$external_id}: " . $e->getMessage());
                continue;
            }
        } else {
            error_log("No se procesará imagen para {$external_id} - " . 
                     ($exists ? "registro existente con imagen válida y formato correcto" : "sin imagen para procesar"));
            
            // Si no se procesa una nueva imagen, intentar obtener las rutas existentes
            if ($exists) {
                $imgStmt = $pdo->prepare("SELECT content_image, preview_image FROM pk_melwater WHERE external_id = :external_id");
                $imgStmt->execute([':external_id' => $external_id]);
                $existing_images = $imgStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($existing_images) {
                    $content_image = $existing_images['content_image'];
                    $preview_image = $existing_images['preview_image'];
                    error_log("Usando imágenes existentes para {$external_id}:");
                    error_log("Content image: " . $content_image);
                    error_log("Preview image: " . $preview_image);
                }
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

            // Debug logging antes de la inserción
            error_log("Intentando insertar registro con los siguientes datos:");
            error_log("external_id: " . $external_id);
            error_log("published_date: " . $published_date->format('Y-m-d H:i:s'));
            error_log("indexed_date: " . (isset($indexed_date_str) ? $indexed_date_str : 'null'));
            error_log("content_image: " . (isset($content_image) ? $content_image : 'null'));
            error_log("preview_image: " . (isset($preview_image) ? $preview_image : 'null'));
            error_log("input_names: " . $input_names_str);

            // Ejecutar la inserción
            $stmt->execute([
                'external_id' => $external_id,
                'published_date' => $published_date->format('Y-m-d H:i:s'),
                'indexed_date' => $indexed_date_str,
                'content_image' => $content_image,
                'preview_image' => $preview_image,
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
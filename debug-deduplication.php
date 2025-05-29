<?php
/**
 * Debug de Deduplicación - Análisis detallado de duplicados
 * Este archivo permite analizar en detalle qué registros se consideran duplicados
 */

header('Content-Type: application/json');

// Cargar configuración de la base de datos
$cfg = require 'config.php';

try {
    $pdo = new PDO(
        "mysql:host={$cfg['db']['host']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}",
        $cfg['db']['user'],
        $cfg['db']['pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // Obtener los datos de Meltwater
    $stmt = $pdo->query("
        SELECT 
            med.*,
            pk.*,
            'meltwater' as source_type
        FROM pk_melwater pk
        LEFT JOIN medios med ON pk.external_id = med.twitter_id
        WHERE med.visualizar = 1
        ORDER BY med.grupo, med.pais, med.dereach DESC
    ");
    $meltwater_docs = $stmt->fetchAll();

    // Obtener los datos de covers
    $stmt = $pdo->query("
        SELECT 
            c.*,
            med.*,
            'cover' as source_type
        FROM covers c
        LEFT JOIN medios med ON c.source = med.source
        WHERE med.visualizar = 1
        ORDER BY c.scraped_at DESC
    ");
    $covers = $stmt->fetchAll();

    // Obtener los datos de pk_meltwater_resumen
    $stmt = $pdo->query("
        SELECT *, 'resumen' as source_type FROM `pk_meltwater_resumen`
        WHERE visualizar = 1 
    ");
    $pk_meltwater_resumen = $stmt->fetchAll();

    // Función de análisis detallado de duplicados
    function analyzeDeduplication($meltwater_docs, $covers, $pk_meltwater_resumen) {
        $processedIdentifiers = [];
        $analysis = [
            'meltwater' => ['processed' => [], 'duplicates' => []],
            'covers' => ['processed' => [], 'duplicates' => []],
            'resumen' => ['processed' => [], 'duplicates' => []]
        ];

        // Analizar datos de Meltwater
        foreach ($meltwater_docs as $doc) {
            $twitter_id = isset($doc['twitter_id']) ? trim($doc['twitter_id']) : '';
            $external_id = isset($doc['external_id']) ? trim($doc['external_id']) : '';
            
            if (!empty($twitter_id) || !empty($external_id)) {
                $identifiers = [];
                if (!empty($twitter_id)) $identifiers[] = 'twitter_' . $twitter_id;
                if (!empty($external_id)) $identifiers[] = 'external_' . $external_id;
                
                $isDuplicate = false;
                $duplicateOf = '';
                
                foreach ($identifiers as $identifier) {
                    if (isset($processedIdentifiers[$identifier])) {
                        $isDuplicate = true;
                        $duplicateOf = $processedIdentifiers[$identifier];
                        break;
                    }
                }
                
                $docInfo = [
                    'id' => $doc['id'] ?? 'N/A',
                    'twitter_id' => $twitter_id,
                    'external_id' => $external_id,
                    'grupo' => $doc['grupo'] ?? 'N/A',
                    'title' => $doc['title'] ?? 'N/A',
                    'identifiers' => $identifiers
                ];
                
                if ($isDuplicate) {
                    $docInfo['duplicate_of'] = $duplicateOf;
                    $analysis['meltwater']['duplicates'][] = $docInfo;
                } else {
                    $analysis['meltwater']['processed'][] = $docInfo;
                    foreach ($identifiers as $identifier) {
                        $processedIdentifiers[$identifier] = 'meltwater';
                    }
                }
            }
        }

        // Analizar datos de covers
        foreach ($covers as $doc) {
            $source = isset($doc['source']) ? trim($doc['source']) : '';
            $twitter_id = isset($doc['twitter_id']) ? trim($doc['twitter_id']) : '';
            
            if (!empty($source) || !empty($twitter_id)) {
                $identifiers = [];
                if (!empty($twitter_id)) $identifiers[] = 'twitter_' . $twitter_id;
                if (!empty($source)) $identifiers[] = 'source_' . $source;
                
                $isDuplicate = false;
                $duplicateOf = '';
                
                foreach ($identifiers as $identifier) {
                    if (isset($processedIdentifiers[$identifier])) {
                        $isDuplicate = true;
                        $duplicateOf = $processedIdentifiers[$identifier];
                        break;
                    }
                }
                
                $docInfo = [
                    'id' => $doc['id'] ?? 'N/A',
                    'twitter_id' => $twitter_id,
                    'source' => $source,
                    'grupo' => $doc['grupo'] ?? 'N/A',
                    'title' => $doc['title'] ?? 'N/A',
                    'identifiers' => $identifiers
                ];
                
                if ($isDuplicate) {
                    $docInfo['duplicate_of'] = $duplicateOf;
                    $analysis['covers']['duplicates'][] = $docInfo;
                } else {
                    $analysis['covers']['processed'][] = $docInfo;
                    foreach ($identifiers as $identifier) {
                        $processedIdentifiers[$identifier] = 'cover';
                    }
                }
            }
        }

        // Analizar datos de resumen
        foreach ($pk_meltwater_resumen as $doc) {
            $twitter_id = isset($doc['twitter_id']) ? trim($doc['twitter_id']) : '';
            $source = isset($doc['source']) ? trim($doc['source']) : '';
            $doc_id = isset($doc['id']) ? $doc['id'] : '';
            
            $identifiers = [];
            if (!empty($twitter_id)) $identifiers[] = 'twitter_' . $twitter_id;
            if (!empty($source)) $identifiers[] = 'source_' . $source;
            if (empty($identifiers) && !empty($doc_id)) {
                $identifiers[] = 'resumen_id_' . $doc_id;
            }
            
            if (!empty($identifiers)) {
                $isDuplicate = false;
                $duplicateOf = '';
                
                foreach ($identifiers as $identifier) {
                    if (isset($processedIdentifiers[$identifier])) {
                        $isDuplicate = true;
                        $duplicateOf = $processedIdentifiers[$identifier];
                        break;
                    }
                }
                
                $docInfo = [
                    'id' => $doc['id'] ?? 'N/A',
                    'twitter_id' => $twitter_id,
                    'source' => $source,
                    'grupo' => $doc['grupo'] ?? 'N/A',
                    'titulo' => $doc['titulo'] ?? 'N/A',
                    'identifiers' => $identifiers
                ];
                
                if ($isDuplicate) {
                    $docInfo['duplicate_of'] = $duplicateOf;
                    $analysis['resumen']['duplicates'][] = $docInfo;
                } else {
                    $analysis['resumen']['processed'][] = $docInfo;
                    foreach ($identifiers as $identifier) {
                        $processedIdentifiers[$identifier] = 'resumen';
                    }
                }
            }
        }

        return $analysis;
    }

    $detailed_analysis = analyzeDeduplication($meltwater_docs, $covers, $pk_meltwater_resumen);

    // Calcular estadísticas
    $stats = [
        'original_counts' => [
            'meltwater' => count($meltwater_docs),
            'covers' => count($covers),
            'resumen' => count($pk_meltwater_resumen),
            'total' => count($meltwater_docs) + count($covers) + count($pk_meltwater_resumen)
        ],
        'processed_counts' => [
            'meltwater' => count($detailed_analysis['meltwater']['processed']),
            'covers' => count($detailed_analysis['covers']['processed']),
            'resumen' => count($detailed_analysis['resumen']['processed'])
        ],
        'duplicate_counts' => [
            'meltwater' => count($detailed_analysis['meltwater']['duplicates']),
            'covers' => count($detailed_analysis['covers']['duplicates']),
            'resumen' => count($detailed_analysis['resumen']['duplicates']),
            'total' => count($detailed_analysis['meltwater']['duplicates']) + 
                      count($detailed_analysis['covers']['duplicates']) + 
                      count($detailed_analysis['resumen']['duplicates'])
        ]
    ];

    $stats['final_count'] = $stats['processed_counts']['meltwater'] + 
                           $stats['processed_counts']['covers'] + 
                           $stats['processed_counts']['resumen'];

    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'statistics' => $stats,
        'detailed_analysis' => $detailed_analysis
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

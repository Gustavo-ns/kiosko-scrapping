<?php
require_once 'cache_config.php';
require_once 'download_image.php';

// Cargar configuración de la base de datos
$cfg = require 'config.php';

// Función para obtener el hash del contenido actual
function getContentHash($pdo) {
    $stmt = $pdo->query("SELECT MAX(published_date) as last_update FROM pk_melwater");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return md5($result['last_update'] . ASSETS_VERSION);
}

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

    // Generar ETag basado en la última actualización
    $etag = '"' . getContentHash($pdo) . '"';
    
    // Configurar headers de caché y tipo de contenido
    header('Content-Type: text/html; charset=UTF-8');
    setHeadersForContentType('data', true);
    header('ETag: ' . $etag);

    // Verificar si el contenido ha cambiado
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
        http_response_code(304); // Not Modified
        exit;
    }

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


    // Combinar ambos conjuntos de datos
    $documents = array_merge($meltwater_docs, $covers, $pk_meltwater_resumen);

    // Ordenar por fecha de publicación/scraping
    usort($documents, function($a, $b) {
        $date_a = isset($a['published_date']) ? $a['published_date'] : 
                 (isset($a['scraped_at']) ? $a['scraped_at'] : 
                 (isset($a['created_at']) ? $a['created_at'] : null));
        
        $date_b = isset($b['published_date']) ? $b['published_date'] : 
                 (isset($b['scraped_at']) ? $b['scraped_at'] : 
                 (isset($b['created_at']) ? $b['created_at'] : null));
        
        if (!$date_a && !$date_b) return 0;
        if (!$date_a) return 1;
        if (!$date_b) return -1;
        
        return strtotime($date_b) - strtotime($date_a);
    });

    // Obtener grupos únicos con conteo para el selector
    $grupos = $pdo->query("
        SELECT 
            m.grupo,
            COUNT(DISTINCT CASE 
                WHEN EXISTS (
                    SELECT 1 FROM pk_melwater pk WHERE pk.external_id = m.twitter_id
                    UNION ALL
                    SELECT 1 FROM covers c WHERE c.source = m.source
                ) THEN m.id 
            END) as total
        FROM medios m
        WHERE m.visualizar = 1 
        AND m.grupo IS NOT NULL 
        GROUP BY m.grupo
        ORDER BY m.grupo
    ")->fetchAll();

    // Obtener países únicos de covers
    $paises = $pdo->query("
        SELECT DISTINCT country 
        FROM covers 
        ORDER BY country
    ")->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    // En caso de error, no cachear la respuesta
    header('Cache-Control: no-store');
    die("Error de conexión: " . $e->getMessage());
}

// Iniciar buffer de salida
ob_start();
?><!DOCTYPE html>
<html lang="es"> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Portadas de periódicos de América Latina y el Caribe. Selecciona un país para ver las portadas más recientes.">
    <meta name="keywords" content="portadas, periódicos, América Latina, Caribe, noticias, actualidad, prensa, medios de comunicación">
    <meta name="robots" content="index, follow">
    <meta name="theme-color" content="#ffffff">
    <title>Portadas de Periódicos</title>
    <style>
        /* Critical CSS */
        body {
            font-family: 'Bebas Neue', sans-serif;
            background-color: #f4f4f4;
            color: #474747;
            margin: 0;
            padding: 0;
        }
        .controls {
            display: flex;
            padding: 1rem;
            background: #1e1e1e;
            flex-direction: column;
            flex-wrap: wrap;
            align-content: space-around;
            justify-content: center;
            align-items: center;
        }
        .controls label {
            color: #f0f0f0;
        }
        #grupoSelect {
            font-size: 1.2rem;
            padding: 0.5rem 1rem;
            background-color: #222;
            color: #f0f0f0;
            border: 2px solid #444;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.3s;
        }
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            padding: 3rem;
            content-visibility: auto;
            contain-intrinsic-size: 300px;
        }
        .card {
            position: relative;
            overflow: hidden;
            min-height: 500px;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: transform 0.4s ease, box-shadow 0.4s ease;
            cursor: pointer;
            transform-style: preserve-3d;
            will-change: transform;
            content-visibility: auto;
            contain-intrinsic-size: 300px;
            display: grid;
            grid-template-rows: auto 1fr;
        }
        .image-container {
            position: relative;
            width: 100%;
            background: #f0f0f0;
            overflow: hidden;
        }
        .card img {
            position: relative;
            top: 0;
            left: 0;
            width: 100%;
            display: block;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1;
        }
        .card img.loaded {
            opacity: 1;
        }
        .info {
            padding: 1rem;
        }
        .info h3 {
            margin: 0 0 0.5rem;
            font-size: 1.2rem;
            line-height: 1.4;
        }
        .info small {
            display: block;
            color: #666;
            font-size: 0.9rem;
        }
    </style>
    <!-- Favicon básico -->
    <link rel="icon" type="image/x-icon" href="favicon/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">

    <!-- Apple Touch Icon -->
    <link rel="apple-touch-icon" sizes="180x180" href="favicon/favicon-180x180.png">

    <!-- Android -->
    <link rel="icon" type="image/png" sizes="192x192" href="favicon/favicon-192x192.png">

    <!-- PWA y alta resolución -->
    <link rel="icon" type="image/png" sizes="512x512" href="favicon/favicon-512x512.png">

    <link rel="manifest" href="manifest.json">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    </noscript>
    
    <link rel="stylesheet" href="styles.css?v=<?= ASSETS_VERSION ?>" media="print" onload="this.media='all'">

</head>
<body>
    <div class="container">
        <div class="controls">
            <?php 
            // Verificar si hay grupos con contenido
            $hasGroups = false;
            $groupTotals = [];
            
            // Filtrar documentos por fecha
            $now = new DateTime(); // ahora
            $yesterdayEvening = new DateTime('yesterday 16:00'); // ayer a las 16:00
            
            $filtered_documents = array_filter($documents, function($doc) use ($yesterdayEvening, $now) {
                $date_str = null;
                
                if (isset($doc['published_date'])) {
                    $date_str = $doc['published_date'];
                } elseif (isset($doc['scraped_at'])) {
                    $date_str = $doc['scraped_at'];
                } elseif (isset($doc['created_at'])) {
                    $date_str = $doc['created_at'];
                }
                
                if (!$date_str) return false;
                
                try {
                    $doc_date = new DateTime($date_str);
                    return $doc_date >= $yesterdayEvening && $doc_date <= $now;
                } catch (Exception $e) {
                    error_log("Error parsing date: " . $date_str);
                    return false;
                }
            });

            // Calcular totales por grupo después del filtrado
            foreach ($filtered_documents as $doc) {
                $grupo = isset($doc['grupo']) ? $doc['grupo'] : 'otros';
                if (!isset($groupTotals[$grupo])) {
                    $groupTotals[$grupo] = 0;
                }
                $groupTotals[$grupo]++;
                $hasGroups = true;
            }
            
            if ($hasGroups): 
            ?>
                <div class="filters">
                    <div class="filter-group">
                        <label for="grupoSelect">Grupo:</label>
                        <select id="grupoSelect">
                            <option value="">Todos los grupos (<?= count($filtered_documents) ?>)</option>
                            <?php 
                            // Ordenar grupos alfabéticamente
                            ksort($groupTotals);
                            foreach ($groupTotals as $grupo => $total): 
                                if ($total > 0):
                            ?>
                                <option value="<?= htmlspecialchars($grupo) ?>">
                                    <?= htmlspecialchars($grupo) ?> (<?= $total ?>)
                                </option>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>

            <button id="refreshBtn">🔄 Actualizar</button>
        </div>

        <div id="gallery" class="gallery">
            <?php
            if (empty($filtered_documents)) {
                echo '<div style="grid-column: 1/-1; text-align: center; padding: 2em;">
                        <h2>No hay documentos disponibles para el período seleccionado</h2>
                        <p>Últimas 24 horas: ' . $yesterdayEvening->format('d/m/Y H:i') . ' - ' . $now->format('d/m/Y H:i') . '</p>
                      </div>';
            }

            foreach ($filtered_documents as $doc): 
                // Variables comunes
                $source_type = $doc['source_type'];
                
                if ($source_type === 'meltwater') {
                    // Datos de Meltwater
                    $grupo = isset($doc['grupo']) ? htmlspecialchars($doc['grupo']) : '';
                    $url_destino = isset($doc['url_destino']) ? htmlspecialchars($doc['url_destino']) : '#';
                    $content_image = isset($doc['content_image']) ? htmlspecialchars($doc['content_image']) : '';
                    $title = isset($doc['title']) ? htmlspecialchars($doc['title']) : '';
                    $external_id = isset($doc['external_id']) ? htmlspecialchars($doc['external_id']) : '';
                    $published_date = isset($doc['published_date']) ? htmlspecialchars($doc['published_date']) : '';
                    
                    // Procesar imagen
                    $image_paths = '';
                    if ($content_image) {
                        $image_paths = downloadImage($content_image, $external_id);
                    }
                    $display_image = $image_paths ? $image_paths['thumbnail'] : $content_image;
                    $zoom_image = $image_paths ? $image_paths['original'] : $content_image;
                } elseif ($source_type === 'cover') {
                    // Datos de covers
                    $grupo = isset($doc['grupo']) ? htmlspecialchars($doc['grupo']) : '';
                    $url_destino = isset($doc['source']) ? htmlspecialchars($doc['source']) : '#';
                    $content_image = isset($doc['image_url']) ? htmlspecialchars($doc['image_url']) : '';
                    $title = isset($doc['title']) ? htmlspecialchars($doc['title']) : '';
                    $published_date = isset($doc['scraped_at']) ? htmlspecialchars($doc['scraped_at']) : '';
                    
                    $display_image = $content_image;
                    $zoom_image = isset($doc['original_link']) ? $doc['original_link'] : $content_image;
                    $external_id = isset($doc['source']) ? htmlspecialchars($doc['source']) : '';
                } elseif ($source_type === 'resumen') {
                    // Datos de resumen
                    $grupo = isset($doc['grupo']) ? htmlspecialchars($doc['grupo']) : 'otros';
                    $content_image = isset($doc['source']) ? htmlspecialchars($doc['source']) : 'img/resumen-placeholder.jpg';
                    $title = isset($doc['titulo']) ? htmlspecialchars($doc['titulo']) : '(sin título)';
                    $published_date = isset($doc['created_at']) ? date('Y-m-d H:i:s', strtotime($doc['created_at'])) : date('Y-m-d H:i:s');
                    $external_id = isset($doc['twitter_id']) ? htmlspecialchars($doc['twitter_id']) : '';
                    
                    $display_image = $content_image;
                    $zoom_image = $content_image;
                    $url_destino = !empty($external_id) ? 'https://twitter.com/i/status/' . $external_id : '#';
                }

                // Solo mostrar si hay imagen y título
                if (empty($content_image) || empty($title)) continue;

                // Determinar si es una de las primeras 6 imágenes (above the fold)
                static $image_count = 0;
                $image_count++;
                $loading_strategy = $image_count <= 6 ? 'eager' : 'lazy';
            ?>
                <div class="card" 
                     data-source-type="<?= $source_type ?>"
                     data-grupo="<?= $grupo ?>" 
                     data-external-id="<?= $external_id ?>"
                     data-published-date="<?= $published_date ?>">
                    <div class="image-container">
                        <?php if ($content_image): ?>
                            <img loading="<?= $loading_strategy ?>" 
                                 src="<?= $zoom_image ?>?v=<?= ASSETS_VERSION ?>" 
                                 alt="<?= $title ?>" 
                                 class="loaded"
                                 <?php if ($image_count <= 6): ?>
                                 fetchpriority="high"
                                 <?php endif; ?>>
                        <?php endif; ?>
                    </div>
                    <div class="info">
                        <h3><?= $title ?></h3>
                        <?php if ($grupo): ?>
                            <small class="medio-info">
                                <?= $grupo ?>
                            </small>
                        <?php endif; ?>

                        <?php if ($published_date): ?>
                            <small>
                               Publicado: <?= date('d/m/Y H:i', strtotime($published_date)) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="imageModal" class="modal">
        <span class="close">&times;</span>
        <div class="loader" id="modalLoader"></div>
        <img id="modalImage" alt="Imagen en modal" style="display: none;">
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('service-worker.js')
                .then(reg => console.log('SW registrado:', reg.scope))
                .catch(err => console.error('Error SW:', err));
        }

        document.addEventListener('DOMContentLoaded', () => {
            const imageModal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const modalLoader = document.getElementById('modalLoader');
            const closeModal = imageModal.querySelector('.close');
            const grupoSelect = document.getElementById('grupoSelect');
            const gallery = document.getElementById('gallery');

            function showModal(imageUrl) {
                modalImage.style.display = 'none';
                modalLoader.style.display = 'block';
                imageModal.classList.add('show');
                imageModal.style.display = 'flex';
                modalImage.onload = () => {
                    modalLoader.style.display = 'none';
                    modalImage.style.display = 'block';
                };
                modalImage.src = imageUrl;
            }

            closeModal.addEventListener('click', () => {
                imageModal.style.display = 'none';
                modalImage.src = '';
                imageModal.classList.remove('show');
            });

            window.addEventListener('click', e => {
                if (e.target === imageModal) {
                    imageModal.style.display = 'none';
                    modalImage.src = '';
                    imageModal.classList.remove('show');
                }
            });

            document.querySelectorAll('.zoom-icon').forEach(icon => {
                icon.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    const imageUrl = icon.dataset.img;
                    if (imageUrl) showModal(imageUrl);
                });
            });

            const params = new URLSearchParams(window.location.search);
            const initialGrupo = params.get('grupo') || '';

            grupoSelect.value = initialGrupo;

            function updateURL() {
                const url = new URL(window.location);
                const grupo = grupoSelect.value;

                if (grupo) url.searchParams.set('grupo', grupo);
                else url.searchParams.delete('grupo');

                history.replaceState(null, '', url);
            }

            function filterCards() {
                const selectedGrupo = grupoSelect.value;
                
                const cards = gallery.querySelectorAll('.card');
                cards.forEach(card => {
                    const cardGrupo = card.dataset.grupo;
                    const matchesGrupo = !selectedGrupo || cardGrupo === selectedGrupo;

                    if (matchesGrupo) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });

                updateURL();
            }

            grupoSelect.addEventListener('change', filterCards);
            
            if (initialGrupo) {
                filterCards();
            }

            const refreshBtn = document.getElementById('refreshBtn');
            refreshBtn.addEventListener('click', async () => {
                refreshBtn.disabled = true;
                refreshBtn.textContent = '🔄 Actualizando...';
                
                try {
                    const response = await fetch('update_melwater.php');
                    const data = await response.json();
                    
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al actualizar: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('Error al actualizar los datos');
                } finally {
                    refreshBtn.disabled = false;
                    refreshBtn.textContent = '🔄 Actualizar';
                }
            });
        });
    </script>
</body>
</html><?php
// Obtener y enviar el contenido del buffer
echo ob_get_clean();
?>
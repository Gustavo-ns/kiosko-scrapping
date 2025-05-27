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

    // Combinar ambos conjuntos de datos
    $documents = array_merge($meltwater_docs, $covers);

    // Ordenar por fecha de publicación/scraping
    usort($documents, function($a, $b) {
        $date_a = isset($a['published_date']) ? $a['published_date'] : $a['scraped_at'];
        $date_b = isset($b['published_date']) ? $b['published_date'] : $b['scraped_at'];
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
    <style>
        /* Critical CSS - Only essential styles for first render */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Bebas Neue', sans-serif;
            background: #f5f5f5;
        }

        .container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .controls {
            display: flex;
            padding: 1rem;
            background: #1e1e1e;
            color: white;
            position: sticky;
            top: 0;
            z-index: 100;
            flex-direction: column;
            gap: 1rem;
            height: auto;
            min-height: 64px; /* Reservar espacio mínimo */
        }

        .filters {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
            width: 100%;
            min-height: 40px; /* Reservar espacio mínimo */
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            height: 40px; /* Altura fija */
        }

        .filter-group select {
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #444;
            background: #333;
            color: white;
            font-size: 1rem;
            height: 40px; /* Altura fija */
            min-width: 200px; /* Ancho mínimo */
        }

        #refreshBtn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            border: none;
            background: #444;
            color: white;
            cursor: pointer;
            font-size: 1rem;
            height: 40px; /* Altura fija */
            min-width: 120px; /* Ancho mínimo */
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            justify-items: stretch;
            align-content: space-between;
            gap: 1rem;
            padding: 1rem;
            flex: 1;
        }

        .card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            aspect-ratio: 0.65; /* Mantener proporción consistente */
            contain: layout style paint; /* Optimizar rendimiento */
        }

        .image-container {
            position: relative;
            width: 100%;
            aspect-ratio: 0.65; /* Proporción consistente con la tarjeta */
            background: #f0f0f0;
            overflow: hidden;
            contain: layout size style paint; /* Optimizar rendimiento */
        }

        .image-container img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0; /* Iniciar invisible */
            transition: opacity 0.2s ease-in-out;
        }

        .image-container img.loaded {
            opacity: 1;
        }

        /* Placeholder shimmer con dimensiones fijas */
        .image-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, #f0f0f0, #f8f8f8, #f0f0f0);
            background-size: 200% 100%;
            pointer-events: none;
        }

        .info {
            padding: 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-height: 100px; /* Altura mínima para el contenido */
        }

        .info h3 {
            margin: 0;
            font-size: 1.2rem;
            line-height: 1.2;
        }

        .info small {
            display: block;
            line-height: 1.4;
        }

        /* Modal styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal img {
            max-width: 90%;
            max-height: 90vh;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="controls">
            <?php 
            // Verificar si hay grupos con contenido
            $hasGroups = false;
            foreach ($grupos as $grupo) {
                if ($grupo['total'] > 0) {
                    $hasGroups = true;
                    break;
                }
            }
            
            if ($hasGroups): 
            ?>
                <div class="filters">
                    <div class="filter-group">
                        <label for="grupoSelect">Grupo:</label>
                        <select id="grupoSelect">
                            <option value="">Todos los grupos</option>
                            <?php foreach ($grupos as $grupo): ?>
                                <?php if ($grupo['total'] > 0): ?>
                                    <option value="<?= htmlspecialchars($grupo['grupo']) ?>">
                                        <?= htmlspecialchars($grupo['grupo']) ?> (<?= $grupo['total'] ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            <?php endif; ?>

            <button id="refreshBtn">🔄 Actualizar</button>
        </div>

        <div id="gallery" class="gallery">
            <?php foreach ($documents as $doc): 
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
                } else {
                    // Datos de covers
                    $grupo = isset($doc['grupo']) ? htmlspecialchars($doc['grupo']) : '';
                    $url_destino = isset($doc['source']) ? htmlspecialchars($doc['source']) : '#';
                    $content_image = isset($doc['image_url']) ? htmlspecialchars($doc['image_url']) : '';
                    $title = isset($doc['title']) ? htmlspecialchars($doc['title']) : '';
                    $published_date = isset($doc['scraped_at']) ? htmlspecialchars($doc['scraped_at']) : '';
                    
                    $display_image = $content_image;
                    $zoom_image = isset($doc['original_link']) ? $doc['original_link'] : $content_image;
                    $external_id = isset($doc['source']) ? htmlspecialchars($doc['source']) : '';
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
                                 src="<?= $display_image ?>" 
                                 alt="<?= $title ?>" 
                                 width="325" height="500" 
                                 class="loaded"
                                 <?php if ($image_count <= 6): ?>
                                 fetchpriority="high"
                                 <?php endif; ?>>
                            <div class="zoom-icon" 
                                 data-img="<?= $zoom_image ?>" 
                                 title="Ver imagen ampliada">🔍</div>
                        <?php endif; ?>
                    </div>
                    <div class="info">
                        <h3><?= $title ?></h3>
                        <?php if ($grupo): ?>
                            <small class="medio-info">
                                Grupo: <?= $grupo ?>
                            </small>
                        <?php endif; ?>

                        <?php if ($published_date): ?>
                            <small>
                                <?= date('d/m/Y H:i', strtotime($published_date)) ?>
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
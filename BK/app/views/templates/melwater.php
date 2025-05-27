<?php
require_once __DIR__ . '/../../../app/config/cache_config.php';
require_once __DIR__ . '/../../../app/services/ImageService.php';

// Cargar configuración de la base de datos
$cfg = require __DIR__ . '/../../../app/config/config.php';

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
            med.twitter_screen_name,
            med.dereach,
            med.grupo,
            med.pais as pais_medio,
            'meltwater' as source_type
        FROM pk_melwater pk
        LEFT JOIN medios med ON pk.external_id = med.twitter_id
        ORDER BY med.grupo, med.pais, med.dereach DESC
    ");
    $meltwater_docs = $stmt->fetchAll();

    // Obtener los datos de covers
    $stmt = $pdo->query("
        SELECT 
            c.*,
            'cover' as source_type
        FROM covers c 
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

    // Obtener grupos únicos para el selector (solo de Meltwater)
    $grupos = $pdo->query("
        SELECT DISTINCT med.grupo 
        FROM pk_melwater pk 
        LEFT JOIN medios med ON pk.external_id = med.twitter_id 
        WHERE med.grupo IS NOT NULL 
        ORDER BY med.grupo
    ")->fetchAll(PDO::FETCH_COLUMN);

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
    <title>Portadas de Periódicos - Meltwater</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    </noscript>
    
    <link rel="stylesheet" href="/public/assets/css/styles.css?v=<?= ASSETS_VERSION ?>" media="print" onload="this.media='all'">
    <style>
        /* Critical styles only */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Bebas Neue', sans-serif;
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
        }

        .filters {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-group select {
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #444;
            background: #333;
            color: white;
            font-size: 1rem;
        }

        .filter-group label {
            font-size: 1rem;
            white-space: nowrap;
        }

        #refreshBtn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            border: none;
            background: #444;
            color: white;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s;
        }

        #refreshBtn:hover {
            background: #555;
        }

        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1rem;
            padding: 1rem;
        }

        .card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .image-container {
            position: relative;
            aspect-ratio: 325/500;
            background: #f0f0f0;
            overflow: hidden;
        }

        .image-container img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: opacity 0.2s ease-in-out;
        }

        .image-container img:not([src]) {
            opacity: 0;
        }

        .image-container img.loaded {
            opacity: 1;
        }

        /* Placeholder shimmer effect */
        .image-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                #f0f0f0 0%,
                #f8f8f8 50%,
                #f0f0f0 100%
            );
            background-size: 200% 100%;
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }

        .image-container img.loaded + .shimmer {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="controls">
            <div class="filters">
                <div class="filter-group">
                    <label for="grupoSelect">Grupo1:</label>
                    <select id="grupoSelect">
                        <option value="">- Todos los Grupos -</option>
                        <?php foreach ($grupos as $grupo): ?>
                            <option value="<?= htmlspecialchars($grupo) ?>"><?= htmlspecialchars($grupo) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button id="refreshBtn">🔄 Actualizar</button>
        </div>

        <div id="gallery" class="gallery">
            <?php foreach ($documents as $doc): 
                // Variables comunes
                $source_type = $doc['source_type'];
                
                if ($source_type === 'meltwater') {
                    // Datos de Meltwater
                    $content_image = isset($doc['content_image']) ? htmlspecialchars($doc['content_image']) : '';
                    if (empty($content_image)) continue; // Skip if no image
                    
                    $grupo = isset($doc['grupo']) ? htmlspecialchars($doc['grupo']) : '';
                    $url_destino = isset($doc['url_destino']) ? htmlspecialchars($doc['url_destino']) : '#';
                    $author_name = isset($doc['author_name']) ? htmlspecialchars($doc['author_name']) : 'Sin nombre';
                    $external_id = isset($doc['external_id']) ? htmlspecialchars($doc['external_id']) : '';
                    $published_date = isset($doc['published_date']) ? htmlspecialchars($doc['published_date']) : '';
                    $source_id = isset($doc['source_id']) ? htmlspecialchars($doc['source_id']) : '';
                    $social_network = isset($doc['social_network']) ? htmlspecialchars($doc['social_network']) : '';
                    $input_names = isset($doc['input_names']) ? htmlspecialchars($doc['input_names']) : '';
                    $country_name = isset($doc['country_name']) ? htmlspecialchars($doc['country_name']) : '';
                    $twitter_screen_name = isset($doc['twitter_screen_name']) ? htmlspecialchars($doc['twitter_screen_name']) : '';
                    $dereach = isset($doc['dereach']) ? $doc['dereach'] : 0;
                    
                    // Procesar imagen usando ImageService
                    $image_paths = '';
                    if ($content_image) {
                        $image_paths = $imageService->downloadImage($content_image, $external_id);
                    }
                    $display_image = $image_paths ? $image_paths['thumbnail'] : $content_image;
                    $zoom_image = $image_paths ? $image_paths['original'] : $content_image;
                } else {
                    // Datos de covers
                    $content_image = isset($doc['image_url']) ? htmlspecialchars($doc['image_url']) : '';
                    if (empty($content_image)) continue; // Skip if no image
                    
                    $grupo = '';
                    $url_destino = isset($doc['source']) ? htmlspecialchars($doc['source']) : '#';
                    $author_name = isset($doc['title']) ? htmlspecialchars($doc['title']) : 'Sin nombre';
                    $external_id = '';
                    $published_date = isset($doc['scraped_at']) ? htmlspecialchars($doc['scraped_at']) : '';
                    $source_id = '';
                    $social_network = '';
                    $input_names = '';
                    $country_name = isset($doc['country']) ? htmlspecialchars($doc['country']) : '';
                    $twitter_screen_name = '';
                    $dereach = 0;
                    
                    $display_image = $content_image;
                    $zoom_image = isset($doc['original_link']) ? $doc['original_link'] : $content_image;
                }

                // Determinar si es una de las primeras 6 imágenes (above the fold)
                static $image_count = 0;
                $image_count++;
                $loading_strategy = $image_count <= 6 ? 'eager' : 'lazy';
            ?>
                <div class="card" 
                     data-source-type="<?= $source_type ?>"
                     data-grupo="<?= $grupo ?>" 
                     data-country="<?= $country_name ?>"
                     data-external-id="<?= $external_id ?>"
                     data-published-date="<?= $published_date ?>"
                     data-source-id="<?= $source_id ?>"
                     data-social-network="<?= $social_network ?>"
                     data-input-names="<?= $input_names ?>">
                    <a href="<?= $url_destino ?>" target="_blank">
                        <div class="image-container">
                            <?php if ($content_image): ?>
                                <img loading="<?= $loading_strategy ?>" 
                                     src="<?= $display_image ?>" 
                                     alt="<?= $author_name ?>" 
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
                    </a>
                    <div class="info">
                        <h3><?= $twitter_screen_name ?></h3>
                        <?php if ($source_type === 'meltwater' && $twitter_screen_name): ?>
                            <small class="medio-info">
                                @<?= $twitter_screen_name ?>
                                <?php if ($grupo): ?> | Grupo: <?= $grupo ?><?php endif; ?>
                                <?php if ($dereach): ?> | Alcance: <?= number_format($dereach, 0, ',', '.') ?><?php endif; ?>
                            </small><br>
                        <?php endif; ?>

                        <small>
                            <?= $country_name ?> — <?= parse_url($url_destino, PHP_URL_HOST) ?: 'Sin URL' ?>
                        </small><br>

                        <?php if (!empty($doc['content_text'])): ?>
                            <small><em><?= htmlspecialchars($doc['content_text']) ?></em></small>
                        <?php endif; ?>

                        <div class="additional-info">
                            <?php if ($external_id): ?>
                                <small>ID: <?= $external_id ?></small><br>
                            <?php endif; ?>
                            <?php if ($social_network): ?>
                                <small>Red social: <?= $social_network ?></small><br>
                            <?php endif; ?>
                            <?php if ($published_date): ?>
                                <small>Fecha: <?= date('d/m/Y H:i', strtotime($published_date)) ?></small><br>
                            <?php endif; ?>
                            <?php if ($input_names): ?>
                                <small>Keywords: <?= $input_names ?></small>
                            <?php endif; ?>
                        </div>
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

                // Update URL with only grupo parameter
                const url = new URL(window.location);
                if (selectedGrupo) {
                    url.searchParams.set('grupo', selectedGrupo);
                } else {
                    url.searchParams.delete('grupo');
                }
                history.replaceState(null, '', url);
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
                    const response = await fetch('/?action=update');
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    const data = await response.json();
                    
                    if (data.success) {
                        location.reload();
                    } else {
                        console.error('Error de actualización:', data);
                        alert(`Error al actualizar: ${data.message || 'Error desconocido'}`);
                    }
                } catch (error) {
                    console.error('Error detallado:', error);
                    let errorMessage = 'Error al actualizar los datos: ';
                    
                    if (error.name === 'TypeError' && error.message.includes('Failed to fetch')) {
                        errorMessage += 'No se pudo conectar al servidor. Por favor, verifica tu conexión.';
                    } else if (error.message.includes('HTTP error!')) {
                        errorMessage += `Error de servidor (${error.message})`;
                    } else {
                        errorMessage += error.message || 'Error desconocido';
                    }
                    
                    alert(errorMessage);
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
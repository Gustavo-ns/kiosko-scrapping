<?php
  // melwater.php
// URL de la API
$apiUrl = "https://api.meltwater.com/v3/exports/recurring";

// API key (pon la tuya aquí)
$apiKey = "8PMcUPYZ1M954yDpIh6mI8CE61fqwG2LFulSbPGo";

// Inicializar cURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "apikey: $apiKey"
]);

// Ejecutar la solicitud
$response = curl_exec($ch);

// Verificar errores
if (curl_errno($ch)) {
    die("Error de cURL: " . curl_error($ch));
}

curl_close($ch);

// Decodificar JSON
$data = json_decode($response, true);

if (!isset($data['recurring_exports'][0]['data_url'])) {
    die("No se encontró 'data_url'.");
}

// Extraer el enlace real de datos
$dataUrl = $data['recurring_exports'][0]['data_url'];

?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Portadas de periódicos de América Latina y el Caribe. Selecciona un país para ver las portadas más recientes.">
  <meta name="keywords" content="portadas, periódicos, América Latina, Caribe, noticias, actualidad, prensa, medios de comunicación">
  <meta name="author" content="Tu Nombre o el nombre de tu organización">
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
  
  <link rel="stylesheet" href="styles.css" media="print" onload="this.media='all'">
  <style>
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

#countrySelect {
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
  contain-intrinsic-size: 300px; /* tamaño estimado para evitar saltos */
}

.card {
  position: relative;
  overflow: hidden;
  min-height: 500px;
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  overflow: hidden;
  transition: transform 0.4s ease, box-shadow 0.4s ease;
  cursor: pointer;
  transform-style: preserve-3d;
  will-change: transform;
  content-visibility: auto;
  contain-intrinsic-size: 300px; /* tamaño estimado para evitar saltos */
}
  </style>
</head>
<body>

  <div class="container">
    <div class="controls">
      <label for="countrySelect">Selecciona un país:</label>
      <select id="countrySelect">
        <option value="">-- Todos los países --</option>
        <option value="argentina">Argentina</option>
        <option value="bolivia">Bolivia</option>
        <option value="brasil">Brasil</option>
        <option value="chile">Chile</option>
        <option value="colombia">Colombia</option>
        <option value="ecuador">Ecuador</option>
        <option value="usa">Estados Unidos</option>
        <option value="mexico">México</option>
        <option value="panama">Panamá</option>
        <option value="paraguay">Paraguay</option>
        <option value="peru">Perú</option>
        <option value="dominicanRepublic">República Dominicana</option>
        <option value="uruguay">Uruguay</option>
        <option value="venezuela">Venezuela</option>
      </select>
      <button id="refreshBtn">🔄 Actualizar</button>      
    </div>
  </div>
  
  <?php
$response = file_get_contents($dataUrl);
if ($response === FALSE) {
    die("Error al obtener los datos del archivo JSON.");
}

$data = json_decode($response, true);
echo '<div id="gallery" class="gallery">';

if (isset($data['documents'])) {
    foreach ($data['documents'] as $doc) {
        $author_name = $doc['author']['name'] ?? 'N/A';
        $content_image = $doc['content']['image'] ?? null;
        $content_text = $doc['content']['opening_text'] ?? '';
        $country_code = strtolower($doc['location']['country_code'] ?? 'zz');
        $country_name = $country_names[$country_code] ?? ucfirst($country_code);
        $url_destino = $doc['url'] ?? '#';
        $host = parse_url($url_destino, PHP_URL_HOST) ?? 'meltwater.com';

        echo '<div class="card" data-country="' . strtolower($country_code) . '">';
        echo "<a href=\"$url_destino\" target=\"_blank\">";
        echo '<div style="position: relative;">';

        if ($content_image) {
            echo "<img loading=\"lazy\" src=\"$content_image\" alt=\"$author_name\" width=\"325\" height=\"500\" class=\"loaded\">";
            echo "<div class=\"zoom-icon\" data-img=\"$content_image\" title=\"Ver imagen ampliada\">🔍</div>";
        } else {
            echo '<div style="width:325px;height:500px;background:#ccc;display:flex;align-items:center;justify-content:center;">Sin imagen</div>';
        }

        echo '</div></a>';
        echo '<div class="info">';
        echo "<h3>$author_name</h3>";
        echo "<small>$country_name — $host</small><br>";
        if (!empty($content_text)) {
            echo "<small><em>$content_text</em></small>";
        }
        echo '</div></div>';
    }
} else {
    echo "<p>No se encontraron documentos.</p>";
}
echo '</div>';
?>
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
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const imageModal = document.getElementById('imageModal');
  const modalImage = document.getElementById('modalImage');
  const modalLoader = document.getElementById('modalLoader');
  const closeModal = imageModal.querySelector('.close');

  function showModal(imageUrl) {
    modalImage.style.display = 'none';
    modalLoader.style.display = 'block';
    imageModal.classList.add('show'); // 👈 AÑADIR CLASE
    imageModal.style.display = 'flex';
    modalImage.onload = () => {
      modalLoader.style.display = 'none';
      modalImage.style.display = 'block';
    };
    modalImage.src = imageUrl;
  }

  // Cerrar modal
  closeModal.addEventListener('click', () => {
    imageModal.style.display = 'none';
    modalImage.src = '';
    imageModal.classList.remove('show'); // 👈 QUITAR CLASE
  });

  window.addEventListener('click', e => {
    if (e.target === imageModal) {
      imageModal.style.display = 'none';
      modalImage.src = '';
      imageModal.classList.remove('show'); // 👈 QUITAR CLASE
    }
  });

  // Evento para lupa
  document.querySelectorAll('.zoom-icon').forEach(icon => {
    icon.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      const imageUrl = icon.dataset.img;
      if (imageUrl) showModal(imageUrl);
    });
  });
});
</script>


</body>
</html>
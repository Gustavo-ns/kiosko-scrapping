<?php require_once __DIR__ . '/../../../app/config/cache_config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Portadas de periódicos de América Latina y el Caribe. Selecciona un país para ver las portadas más recientes.">
  <meta name="keywords" content="portadas, periódicos, América Latina, Caribe, noticias, actualidad, prensa, medios de comunicación">
  <meta name="robots" content="index, follow">
  <meta name="theme-color" content="#ffffff">
  <title>Portadas de Periódicos - Home</title>
  
  <!-- Favicon básico -->
  <link rel="icon" type="image/x-icon" href="/assets/favicon/favicon.ico">
  <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon/favicon-16x16.png">

  <!-- Apple Touch Icon -->
  <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon/favicon-180x180.png">

  <!-- Android -->
  <link rel="icon" type="image/png" sizes="192x192" href="/assets/favicon/favicon-192x192.png">

  <!-- PWA y alta resolución -->
  <link rel="icon" type="image/png" sizes="512x512" href="/assets/favicon/favicon-512x512.png">

  <link rel="manifest" href="/manifest.json">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" onload="this.onload=null;this.rel='stylesheet'">
  <noscript>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
  </noscript>
  
  <link rel="stylesheet" href="/assets/css/styles.css" media="print" onload="this.media='all'">
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
        <?php foreach ($countries as $code => $name): ?>
          <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($name) ?></option>
        <?php endforeach; ?>
      </select>
      <button id="refreshBtn">🔄 Actualizar</button>      
    </div>
  </div>
  <div id="gallery" class="gallery">
    <div class="card"></div>
    <div class="card"></div>
    <div class="card"></div>
    <div class="card"></div>
    <div class="card"></div>
    <div class="card"></div>
  </div>
  <div id="imageModal" class="modal">
    <span class="close">&times;</span>
    <div class="loader" id="modalLoader"></div>
    <img id="modalImage" alt="Imagen en modal" style="display: none;">
  </div>
  
  <script src="/assets/js/scripts.js" defer></script>
  <script>
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/service-worker.js')
        .then(reg => console.log('SW registrado:', reg.scope))
        .catch(err => console.error('Error SW:', err));
    }
  </script>
</body>
</html> 
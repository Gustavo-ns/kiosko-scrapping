const CACHE_NAME = 'portadas-cache-v2';
const IMAGE_CACHE = 'portadas-images-v1';
const OFFLINE_URL = 'offline.html';

const STATIC_ASSETS = [
  '/',
  'styles.css',
  'scripts.js',
  'favicon/favicon.ico',
  'offline.html',
  'manifest.json'
];

// Instala el Service Worker y guarda en caché los archivos necesarios
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(STATIC_ASSETS))
      .then(() => self.skipWaiting())
  );
});

// Activa el nuevo Service Worker y elimina cachés antiguas
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(
        keys.filter(key => key !== CACHE_NAME)
            .map(key => caches.delete(key))
      )
    ).then(() => self.clients.claim())
  );
});

// Intercepta solicitudes con estrategia optimizada
self.addEventListener('fetch', event => {
    const request = event.request;
  
    // Evitar caché de extensiones y otros esquemas no soportados
    if (!request.url.startsWith('http')) {
      return;
    }

    // Estrategia especial para imágenes
    if (request.destination === 'image') {
      event.respondWith(
        caches.open(IMAGE_CACHE).then(cache => {
          return cache.match(request).then(cached => {
            if (cached) return cached;
            
            return fetch(request).then(response => {
              if (response && response.status === 200) {
                cache.put(request, response.clone());
              }
              return response;
            }).catch(() => {
              // Imagen placeholder en caso de error
              return new Response('<svg xmlns="http://www.w3.org/2000/svg" width="200" height="150"><rect width="100%" height="100%" fill="#f0f0f0"/><text x="50%" y="50%" text-anchor="middle" fill="#999">Imagen no disponible</text></svg>', {
                headers: { 'Content-Type': 'image/svg+xml' }
              });
            });
          });
        })
      );
      return;
    }
  
    // Estrategia para otros recursos
    event.respondWith(
      caches.match(request).then(cached => {
        return cached || fetch(request).then(response => {
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }
  
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(request, responseToCache);
          });
  
          return response;
        }).catch(() => {
          // Retornar página offline para navegación
          if (request.mode === 'navigate') {
            return caches.match(OFFLINE_URL);
          }
        });
      })
    );
  });
  
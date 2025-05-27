const CACHE_NAME = 'portadas-cache-v1';
const OFFLINE_URL = 'offline.html';

const STATIC_ASSETS = [
  '/',
  'styles.css',
  'scripts.js',
  'favicon/favicon.ico',
  'offline.html',
  // Añade más rutas según tu sitio
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

// Intercepta solicitudes
self.addEventListener('fetch', event => {
    const request = event.request;
  
    // Evitar caché de extensiones y otros esquemas no soportados
    if (!request.url.startsWith('http')) {
      return;
    }
  
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
          // Opción: retornar algo en caso de error de red
        });
      })
    );
  });
  
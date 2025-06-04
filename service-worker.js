const CACHE_NAME = 'portadas-cache-v2';
const IMAGE_CACHE = 'portadas-images-v1';
const OFFLINE_URL = 'offline.html';
const CACHE_DURATION = 24 * 60 * 60 * 1000; // 24 horas en milisegundos

const STATIC_ASSETS = [
  '/',
  'styles.css',
  'scripts.js',
  'favicon/favicon.ico',
  'offline.html',
  'manifest.json'
];

// Función para verificar si la caché está expirada
function isCacheExpired(timestamp) {
  return Date.now() - timestamp > CACHE_DURATION;
}

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
        keys.filter(key => key !== CACHE_NAME && key !== IMAGE_CACHE)
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
            if (cached) {
              // Verificar si la imagen en caché está expirada
              const cachedDate = new Date(cached.headers.get('date'));
              if (!isCacheExpired(cachedDate.getTime())) {
                return cached;
              }
            }
            
            return fetch(request).then(response => {
              if (response && response.status === 200) {
                // Agregar timestamp a la respuesta
                const headers = new Headers(response.headers);
                headers.append('sw-cache-timestamp', Date.now().toString());
                const newResponse = new Response(response.body, {
                  status: response.status,
                  statusText: response.statusText,
                  headers: headers
                });
                cache.put(request, newResponse.clone());
                return newResponse;
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
  
    // Estrategia para la página principal
    if (request.url === self.location.origin + '/' || request.url === self.location.origin + '/index.php') {
      event.respondWith(
        fetch(request)
          .then(response => {
            if (!response || response.status !== 200) {
              return caches.match(request);
            }
            return response;
          })
          .catch(() => caches.match(request))
      );
      return;
    }
  
    // Estrategia para otros recursos
    event.respondWith(
      caches.match(request).then(cached => {
        if (cached) {
          // Verificar si el recurso en caché está expirado
          const cachedDate = new Date(cached.headers.get('date'));
          if (!isCacheExpired(cachedDate.getTime())) {
            return cached;
          }
        }

        return fetch(request).then(response => {
          if (!response || response.status !== 200 || response.type !== 'basic') {
            return response;
          }
  
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            // Agregar timestamp a la respuesta
            const headers = new Headers(responseToCache.headers);
            headers.append('sw-cache-timestamp', Date.now().toString());
            const newResponse = new Response(responseToCache.body, {
              status: responseToCache.status,
              statusText: responseToCache.statusText,
              headers: headers
            });
            cache.put(request, newResponse);
          });
  
          return response;
        }).catch(() => {
          // Retornar página offline para navegación
          if (request.mode === 'navigate') {
            return caches.match(OFFLINE_URL);
          }
          return cached;
        });
      })
    );
  });

// Limpiar caché antigua periódicamente
self.addEventListener('periodicsync', event => {
  if (event.tag === 'clean-cache') {
    event.waitUntil(
      caches.keys().then(keys => {
        return Promise.all(
          keys.map(key => {
            return caches.open(key).then(cache => {
              return cache.keys().then(requests => {
                return Promise.all(
                  requests.map(request => {
                    return cache.match(request).then(response => {
                      if (response) {
                        const cachedDate = new Date(response.headers.get('date'));
                        if (isCacheExpired(cachedDate.getTime())) {
                          return cache.delete(request);
                        }
                      }
                    });
                  })
                );
              });
            });
          })
        );
      })
    );
  }
});
  
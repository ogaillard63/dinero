const CACHE_NAME = 'dinero-v1.0.2';
const ASSETS_TO_CACHE = [
    'manifest.json',
    'offline.html',
    'js/pwa-init.js',
    'js/sync.js',
    'icons/icon-192x192.png',
    'icons/icon-512x512.png'
];

// Installation du Service Worker
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                return cache.addAll(ASSETS_TO_CACHE);
            })
    );
});

// Activation et nettoyage des anciens caches
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

// Interception des requêtes réseau
self.addEventListener('fetch', (event) => {
    // Si la requête concerne une URL de l'API, on ne cache pas, on laisse le JS gérer
    if (event.request.url.includes('/api/')) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                // Retourne la réponse en cache si elle existe, sinon fait la requête réseau
                return response || fetch(event.request)
                    .catch(() => {
                        // SI ECHEC (HORS LIGNE)
                        // Si c'est une navigation (page HTML), servire la page offline
                        if (event.request.mode === 'navigate') {
                            return caches.match('offline.html').then(response => {
                                return response || new Response("Mode hors ligne indisponible", { status: 503, headers: { 'Content-Type': 'text/plain' } });
                            });
                        }
                    });
            })
    );
});

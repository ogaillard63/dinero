const CACHE_VERSION = 'dinero-v1.0.0';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const DYNAMIC_CACHE = `${CACHE_VERSION}-dynamic`;
const API_CACHE = `${CACHE_VERSION}-api`;

// Fichiers à mettre en cache immédiatement
const STATIC_ASSETS = [
    '/',
    '/dashboard',
    '/operations',
    '/banks',
    '/css/styles.css',
    '/offline.html',
    'https://cdn.tailwindcss.com',
    'https://cdn.jsdelivr.net/npm/chart.js'
];

// Installation du Service Worker
self.addEventListener('install', (event) => {
    console.log('[SW] Installation...');

    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('[SW] Mise en cache des assets statiques');
                return cache.addAll(STATIC_ASSETS.map(url => new Request(url, {
                    cache: 'reload'
                })));
            })
            .catch((error) => {
                console.error('[SW] Erreur lors de la mise en cache:', error);
            })
            .then(() => self.skipWaiting())
    );
});

// Activation et nettoyage des anciens caches
self.addEventListener('activate', (event) => {
    console.log('[SW] Activation...');

    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((cacheName) => {
                            return cacheName.startsWith('dinero-') &&
                                cacheName !== STATIC_CACHE &&
                                cacheName !== DYNAMIC_CACHE &&
                                cacheName !== API_CACHE;
                        })
                        .map((cacheName) => {
                            console.log('[SW] Suppression ancien cache:', cacheName);
                            return caches.delete(cacheName);
                        })
                );
            })
            .then(() => self.clients.claim())
    );
});

// Stratégies de cache
function isAPIRequest(url) {
    return url.pathname.startsWith('/api/');
}

function isStaticAsset(url) {
    const staticExtensions = ['.css', '.js', '.png', '.jpg', '.jpeg', '.svg', '.woff', '.woff2', '.ttf'];
    return staticExtensions.some(ext => url.pathname.endsWith(ext)) ||
        url.hostname === 'cdn.tailwindcss.com' ||
        url.hostname === 'cdn.jsdelivr.net';
}

// Cache First pour les assets statiques
async function cacheFirst(request) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(request);

    if (cached) {
        return cached;
    }

    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        console.error('[SW] Erreur fetch:', error);
        throw error;
    }
}

// Network First avec fallback sur cache pour les API
async function networkFirst(request) {
    const cache = await caches.open(API_CACHE);

    try {
        const response = await fetch(request);
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    } catch (error) {
        console.log('[SW] Network failed, trying cache');
        const cached = await cache.match(request);
        if (cached) {
            return cached;
        }
        throw error;
    }
}

// Stale While Revalidate pour les pages HTML
async function staleWhileRevalidate(request) {
    const cache = await caches.open(DYNAMIC_CACHE);
    const cached = await cache.match(request);

    const fetchPromise = fetch(request).then((response) => {
        if (response.ok) {
            cache.put(request, response.clone());
        }
        return response;
    });

    return cached || fetchPromise;
}

// Interception des requêtes
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Ignorer les requêtes non-GET
    if (request.method !== 'GET') {
        return;
    }

    // Ignorer les requêtes chrome-extension
    if (url.protocol === 'chrome-extension:') {
        return;
    }

    event.respondWith(
        (async () => {
            try {
                // API requests: Network First
                if (isAPIRequest(url)) {
                    return await networkFirst(request);
                }

                // Static assets: Cache First
                if (isStaticAsset(url)) {
                    return await cacheFirst(request);
                }

                // Pages HTML: Stale While Revalidate
                return await staleWhileRevalidate(request);

            } catch (error) {
                console.error('[SW] Erreur:', error);

                // Fallback sur la page offline pour les pages HTML
                if (request.headers.get('accept').includes('text/html')) {
                    const offlineCache = await caches.open(STATIC_CACHE);
                    const offlinePage = await offlineCache.match('/offline.html');
                    if (offlinePage) {
                        return offlinePage;
                    }
                }

                throw error;
            }
        })()
    );
});

// Background Sync pour la synchronisation des données
self.addEventListener('sync', (event) => {
    console.log('[SW] Background sync:', event.tag);

    if (event.tag === 'sync-data') {
        event.waitUntil(
            fetch('/api/sync')
                .then(response => response.json())
                .then(data => {
                    return self.clients.matchAll().then(clients => {
                        clients.forEach(client => {
                            client.postMessage({
                                type: 'SYNC_COMPLETE',
                                data: data
                            });
                        });
                    });
                })
                .catch(error => {
                    console.error('[SW] Sync failed:', error);
                })
        );
    }
});

// Gestion des messages du client
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data && event.data.type === 'CLEAR_CACHE') {
        event.waitUntil(
            caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => caches.delete(cacheName))
                );
            })
        );
    }
});

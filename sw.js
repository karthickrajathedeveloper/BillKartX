const CACHE_NAME = 'billkartx-cache-v2';
const ASSETS_TO_CACHE = [
    './',
    'index.php',
    'products.php',
    'inventory.php',
    'history.php',
    'reports.php',
    'assets/css/app.css',
    'assets/js/app.js',
    'assets/js/bluetooth-printer.js',
    'assets/js/three-inventory.js',
    'assets/BillKartX Smart Billing Logo.png',
    'assets/RuPay-Inspired K Finance Logo-Favicon.png',
    'assets/vendor/three.min.js',
    'assets/vendor/OrbitControls.js',
    'assets/vendor/jspdf.umd.min.js',
    'assets/vendor/html2canvas.min.js'
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('[ServiceWorker] Caching offline shell assets');
            return cache.addAll(ASSETS_TO_CACHE).catch(err => {
                console.warn('[ServiceWorker] Partial cache install:', err);
            });
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (e) => {
    // Only cache GET requests, bypass API POST / dynamic queries
    if (e.request.method !== 'GET' || e.request.url.includes('api.php')) {
        return;
    }

    e.respondWith(
        fetch(e.request)
            .then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200) {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(e.request, responseClone);
                    });
                }
                return networkResponse;
            })
            .catch(() => {
                return caches.match(e.request);
            })
    );
});


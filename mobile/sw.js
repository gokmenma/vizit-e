const CACHE_NAME = 'vizite-mobile-v1';
const ASSETS = [
    './index.php',
    './assets/css/mobile.css',
    './assets/js/mobile.js',
    'https://cdn.jsdelivr.net/npm/basecoat-css@0.3.11/dist/basecoat.cdn.min.css',
    'https://cdn.jsdelivr.net/npm/@fontsource/geist-sans/index.css',
    'https://unpkg.com/lucide@latest',
    'https://code.jquery.com/jquery-3.7.1.min.js',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11.22.4/dist/sweetalert2.min.css',
    'https://cdn.jsdelivr.net/npm/sweetalert2@11.22.4/dist/sweetalert2.all.min.js'
];

self.addEventListener('install', (e) => {
    e.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS).catch(err => console.warn('PWA Asset caching skipped during install:', err));
        })
    );
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
});

self.addEventListener('fetch', (e) => {
    const url = new URL(e.request.url);
    // Only cache static assets, never PHP routes or JSON APIs
    const isStaticAsset = url.pathname.endsWith('.css') || 
                          url.pathname.endsWith('.js') || 
                          url.pathname.includes('/fonts/') || 
                          url.pathname.endsWith('.svg') ||
                          url.pathname.endsWith('.png') ||
                          url.pathname.endsWith('.ico');

    // Only cache HTTP/HTTPS GET requests to prevent unsupported scheme errors (like chrome-extension)
    const isHttpOrHttps = url.protocol === 'http:' || url.protocol === 'https:';

    if (isStaticAsset && e.request.method === 'GET' && isHttpOrHttps) {
        e.respondWith(
            caches.match(e.request).then((cachedResponse) => {
                return cachedResponse || fetch(e.request).then((networkResponse) => {
                    if (networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(e.request, responseClone);
                        });
                    }
                    return networkResponse;
                }).catch(() => {
                    return caches.match('./index.php');
                });
            })
        );
    } else {
        e.respondWith(fetch(e.request));
    }
});

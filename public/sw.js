// Docs/Spec.md §4.6 / §10 Ops: cache static assets + the last visited page,
// so a dropped connection shows the last-known page instead of a browser
// error, and a truly never-cached page shows a friendly offline message
// with a retry button instead. Docs/plan.md Task 5.3.
const STATIC_CACHE = 'ngafe-static-v1';
const PAGE_CACHE = 'ngafe-pages-v1';
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil((async () => {
        const cache = await caches.open(STATIC_CACHE);
        await cache.addAll([OFFLINE_URL, '/manifest.webmanifest']);
    })());
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil((async () => {
        const keys = await caches.keys();
        await Promise.all(
            keys.filter((key) => ![STATIC_CACHE, PAGE_CACHE].includes(key)).map((key) => caches.delete(key)),
        );
        await self.clients.claim();
    })());
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    if (request.mode === 'navigate') {
        event.respondWith(handleNavigate(request));
        return;
    }

    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(request));
    }
});

function isStaticAsset(url) {
    return url.pathname.startsWith('/build/')
        || url.pathname.startsWith('/icons/')
        || url.pathname.startsWith('/fonts/');
}

async function handleNavigate(request) {
    const pageCache = await caches.open(PAGE_CACHE);
    try {
        const response = await fetch(request);
        pageCache.put(request, response.clone());

        return response;
    } catch {
        const cached = await pageCache.match(request);
        if (cached) return cached;

        const offline = await caches.match(OFFLINE_URL);

        return offline || Response.error();
    }
}

async function cacheFirst(request) {
    const cache = await caches.open(STATIC_CACHE);
    const cached = await cache.match(request);
    if (cached) return cached;

    try {
        const response = await fetch(request);
        cache.put(request, response.clone());

        return response;
    } catch {
        return Response.error();
    }
}

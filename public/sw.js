const CACHE_NAME = 'meash-pwa-v8';

// Q3: precache ONLY what the public page needs on first visit.
// Removed: /app, /telegram-miniapp, /vendor/chart.umd.js, leaflet files,
// /vendor/lucide.min.js (Q2 sprite is inline in the page) and all 20 font
// files. Fonts and other assets are cached at runtime on first use
// (cache-first in handleStaticAsset below) instead of being downloaded
// up front. The staff app loads its own libraries when staff open it;
// they enter the same runtime cache. Old precache was ~1.8 MB; this
// list is ~0.23 MB of static files plus the '/' page itself.
const PRECACHE_ASSETS = [
  '/',
  '/offline.html',
  '/manifest.json',
  '/css/fonts.css',
  '/css/meash.css',
  '/js/meash-i18n.js',
  '/js/meash-offline.js',
  '/logo.jpg',
  '/assets/icon-192.png',
  '/assets/icon-512.png',
];

// Install: Cache each asset one-by-one and log any failure
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(async (cache) => {
      console.log('[ServiceWorker] Starting pre-cache of', PRECACHE_ASSETS.length, 'assets');
      for (const asset of PRECACHE_ASSETS) {
        try {
          const response = await fetch(asset, { cache: 'no-cache' });
          if (response.ok) {
            await cache.put(asset, response);
          } else {
            console.warn('[ServiceWorker] Failed to pre-cache (HTTP ' + response.status + '):', asset);
          }
        } catch (err) {
          console.warn('[ServiceWorker] Network error pre-caching asset:', asset, err.message);
        }
      }
      console.log('[ServiceWorker] Pre-cache completed');
    })
  );
  self.skipWaiting();
});

// Activate: Delete old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            console.log('[ServiceWorker] Deleting old cache:', key);
            return caches.delete(key);
          }
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch: Handle navigation vs static assets, ignore /api/
self.addEventListener('fetch', (event) => {
  const req = event.request;
  const url = new URL(req.url);

  // Never touch /api/ or non-GET requests
  if (req.method !== 'GET' || url.pathname.startsWith('/api/')) {
    return;
  }

  // 1. Navigation requests (Page loading)
  if (req.mode === 'navigate') {
    event.respondWith(handleNavigation(req, url));
    return;
  }

  // 2. Static Assets (CSS, JS, Fonts, Images): Cache First with background revalidation
  event.respondWith(handleStaticAsset(req));
});

// Navigation handler: Network first with 8s timeout -> Cache -> /offline.html
async function handleNavigation(request, url) {
  try {
    // 8 second network timeout
    const networkPromise = fetch(request);
    const timeoutPromise = new Promise((_, reject) => {
      setTimeout(() => reject(new Error('Network timeout')), 8000);
    });

    const response = await Promise.race([networkPromise, timeoutPromise]);
    if (response && response.ok) {
      // Update cache in background
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, response.clone()).catch(() => {});
      return response;
    }
  } catch (err) {
    // Network failed or timed out, fall back to cache
  }

  const cache = await caches.open(CACHE_NAME);

  // If path starts with /app, return cached /app shell
  if (url.pathname.startsWith('/app')) {
    const cachedApp = await cache.match('/app');
    if (cachedApp) return cachedApp;
  }

  // If path is /telegram-miniapp, return cached miniapp
  if (url.pathname.startsWith('/telegram-miniapp')) {
    const cachedTma = await cache.match('/telegram-miniapp');
    if (cachedTma) return cachedTma;
  }

  // Try exact match or root /
  const directMatch = await cache.match(request);
  if (directMatch) return directMatch;

  const rootMatch = await cache.match('/');
  if (rootMatch) return rootMatch;

  // Final fallback to offline.html
  const offlinePage = await cache.match('/offline.html');
  if (offlinePage) return offlinePage;

  return new Response('Offline - please reconnect to internet', {
    status: 503,
    statusText: 'Service Unavailable',
    headers: { 'Content-Type': 'text/plain; charset=utf-8' }
  });
}

// Static asset handler: Network-first for JavaScript, Cache-first with background revalidation for other assets
async function handleStaticAsset(request) {
  const url = new URL(request.url);

  // For JS files, ALWAYS try network first so bug fixes and logic updates arrive instantly
  if (url.pathname.endsWith('.js') || url.pathname.includes('/js/')) {
    try {
      const netRes = await fetch(request);
      if (netRes && netRes.ok) {
        const cache = await caches.open(CACHE_NAME);
        cache.put(request, netRes.clone()).catch(() => {});
        return netRes;
      }
    } catch (e) {
      // Fallback to cache if network is down
    }
  }

  const cache = await caches.open(CACHE_NAME);
  const cachedResponse = await cache.match(request);

  if (cachedResponse) {
    // Revalidate in background
    fetch(request)
      .then((networkResponse) => {
        if (networkResponse && networkResponse.ok) {
          cache.put(request, networkResponse);
        }
      })
      .catch(() => {});
    return cachedResponse;
  }

  // Not in cache, fetch from network
  try {
    const networkResponse = await fetch(request);
    if (networkResponse && networkResponse.ok) {
      cache.put(request, networkResponse.clone()).catch(() => {});
    }
    return networkResponse;
  } catch (err) {
    // Missing asset fallback
    return new Response('', { status: 404, statusText: 'Not Found' });
  }
}

// Support Skip Waiting via message
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

// Push notifications
self.addEventListener('push', (event) => {
  let payload = {
    title: 'ሜሽ የፅዳት አገልግሎት | Meash Cleaning',
    body: 'አዲስ ማሳወቂያ ደርሶዎታል!',
    url: '/app'
  };
  if (event.data) {
    try {
      payload = event.data.json();
    } catch (e) {
      payload.body = event.data.text();
    }
  }

  const options = {
    body: payload.body,
    icon: '/assets/icon-192.png',
    badge: '/assets/icon-192.png',
    vibrate: [200, 100, 200],
    data: {
      url: payload.url || '/app'
    }
  };

  event.waitUntil(
    self.registration.showNotification(payload.title, options)
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const urlToOpen = event.notification.data?.url || '/app';
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
      for (let client of windowClients) {
        if (client.url.includes('/app') && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(urlToOpen);
      }
    })
  );
});

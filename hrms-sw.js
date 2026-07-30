/**
 * BES HRMS Service Worker
 * Provides offline support, caching, and push notifications
 */

const CACHE_VERSION = 'v1.0.2';
const STATIC_CACHE = `hrms-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `hrms-dynamic-${CACHE_VERSION}`;
const DATA_CACHE = `hrms-data-${CACHE_VERSION}`;

// Static assets to cache on install
const STATIC_ASSETS = [
    '/hrms/home/',
    '/hrms/attendance/',
    '/hrms/salary/',
    '/hrms/leave/',
    '/hrms/profile/',
    '/hrms/reports/',
    '/hrms/documents/',
    '/xsite/mod/hrms/manifest.json',
    '/xsite/mod/hrms/icons/icon-192x192.png',
    '/xsite/mod/hrms/icons/icon-512x512.png',
    '/lib/js/jquery-3.3.1.min.js',
    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap'
];

// API endpoints to cache responses
const API_ENDPOINTS = [
    '/xsite/mod/hrms/x-hrms.inc.php'
];

// Offline fallback page
const OFFLINE_PAGE = '/hrms/offline/';

/**
 * Install Event - Cache static assets
 */
self.addEventListener('install', event => {
    console.log('[SW] Installing Service Worker...');

    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => {
                console.log('[SW] Caching static assets');
                // Cache what we can, don't fail on errors
                return Promise.allSettled(
                    STATIC_ASSETS.map(url =>
                        cache.add(url).catch(err => {
                            console.log('[SW] Failed to cache:', url, err);
                        })
                    )
                );
            })
            .then(() => {
                console.log('[SW] Static assets cached');
                return self.skipWaiting();
            })
    );
});

/**
 * Activate Event - Clean up old caches
 */
self.addEventListener('activate', event => {
    console.log('[SW] Activating Service Worker...');

    event.waitUntil(
        caches.keys()
            .then(cacheNames => {
                return Promise.all(
                    cacheNames
                        .filter(name => {
                            return name.startsWith('hrms-') &&
                                   name !== STATIC_CACHE &&
                                   name !== DYNAMIC_CACHE &&
                                   name !== DATA_CACHE;
                        })
                        .map(name => {
                            console.log('[SW] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => {
                console.log('[SW] Service Worker activated');
                return self.clients.claim();
            })
    );
});

/**
 * Fetch Event - Handle requests with appropriate caching strategy
 */
self.addEventListener('fetch', event => {
    const url = new URL(event.request.url);

    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip Chrome extension requests
    if (url.protocol === 'chrome-extension:') {
        return;
    }

    // Handle API requests (Network First, then Cache)
    if (isAPIRequest(url)) {
        event.respondWith(networkFirst(event.request, DATA_CACHE));
        return;
    }

    // Handle page requests (Network First with offline fallback)
    if (isPageRequest(event.request)) {
        event.respondWith(networkFirstWithOfflineFallback(event.request));
        return;
    }

    // Handle static assets (Cache First, then Network)
    if (isStaticAsset(url)) {
        event.respondWith(cacheFirst(event.request, STATIC_CACHE));
        return;
    }

    // Default: Network First with Dynamic Cache
    event.respondWith(networkFirst(event.request, DYNAMIC_CACHE));
});

/**
 * Check if request is for an API endpoint
 */
function isAPIRequest(url) {
    return url.pathname.includes('x-hrms.inc.php') ||
           url.pathname.includes('/api/');
}

/**
 * Check if request is for a page
 */
function isPageRequest(request) {
    return request.mode === 'navigate' ||
           request.headers.get('accept')?.includes('text/html');
}

/**
 * Check if request is for a static asset
 */
function isStaticAsset(url) {
    const staticExtensions = ['.js', '.css', '.png', '.jpg', '.jpeg', '.gif', '.svg', '.woff', '.woff2', '.ttf'];
    return staticExtensions.some(ext => url.pathname.endsWith(ext));
}

/**
 * Cache First Strategy
 * Try cache first, fallback to network
 */
async function cacheFirst(request, cacheName) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }

    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        console.log('[SW] Cache first failed:', request.url, error);
        return new Response('Offline', { status: 503, statusText: 'Service Unavailable' });
    }
}

/**
 * Network First Strategy
 * Try network first, fallback to cache
 */
async function networkFirst(request, cacheName) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        console.log('[SW] Network first - falling back to cache:', request.url);
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        throw error;
    }
}

/**
 * Network First with Offline Fallback for Pages
 */
async function networkFirstWithOfflineFallback(request) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        console.log('[SW] Page request failed, trying cache:', request.url);

        // Try to get cached version
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }

        // Return offline page if available
        const offlinePage = await caches.match(OFFLINE_PAGE);
        if (offlinePage) {
            return offlinePage;
        }

        // Generate offline response
        return new Response(getOfflineHTML(), {
            status: 503,
            headers: { 'Content-Type': 'text/html' }
        });
    }
}

/**
 * Generate offline HTML page
 */
function getOfflineHTML() {
    return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - BES HRMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f9fafb;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .offline-container {
            text-align: center;
            max-width: 400px;
        }
        .offline-icon {
            width: 80px;
            height: 80px;
            background: #fee2e2;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
        }
        .offline-icon svg {
            width: 40px;
            height: 40px;
            color: #ef4444;
        }
        h1 {
            font-size: 24px;
            color: #111827;
            margin-bottom: 8px;
        }
        p {
            color: #6b7280;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        .retry-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
        }
        .retry-btn:hover {
            background: #1d4ed8;
        }
        .cached-data {
            margin-top: 32px;
            padding: 16px;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        .cached-data h3 {
            font-size: 14px;
            color: #374151;
            margin-bottom: 8px;
        }
        .cached-data p {
            font-size: 13px;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="offline-container">
        <div class="offline-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="1" y1="1" x2="23" y2="23"/>
                <path d="M16.72 11.06A10.94 10.94 0 0 1 19 12.55"/>
                <path d="M5 12.55a10.94 10.94 0 0 1 5.17-2.39"/>
                <path d="M10.71 5.05A16 16 0 0 1 22.58 9"/>
                <path d="M1.42 9a15.91 15.91 0 0 1 4.7-2.88"/>
                <path d="M8.53 16.11a6 6 0 0 1 6.95 0"/>
                <line x1="12" y1="20" x2="12.01" y2="20"/>
            </svg>
        </div>
        <h1>You're Offline</h1>
        <p>Please check your internet connection and try again. Some features may be available from cached data.</p>
        <button class="retry-btn" onclick="window.location.reload()">Try Again</button>

        <div class="cached-data">
            <h3>Cached Data Available</h3>
            <p>Your recent attendance and leave data may be available offline.</p>
        </div>
    </div>
</body>
</html>`;
}

/**
 * Push Notification Event
 */
self.addEventListener('push', event => {
    console.log('[SW] Push notification received');

    let data = {
        title: 'BES HRMS',
        body: 'You have a new notification',
        icon: '/xsite/mod/hrms/icons/icon-192x192.png',
        badge: '/xsite/mod/hrms/icons/icon-72x72.png',
        tag: 'hrms-notification',
        data: {}
    };

    if (event.data) {
        try {
            const payload = event.data.json();
            data = { ...data, ...payload };
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon,
        badge: data.badge,
        tag: data.tag,
        data: data.data,
        vibrate: [100, 50, 100],
        actions: data.actions || [
            { action: 'open', title: 'Open' },
            { action: 'dismiss', title: 'Dismiss' }
        ],
        requireInteraction: data.requireInteraction || false
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

/**
 * Notification Click Event
 */
self.addEventListener('notificationclick', event => {
    console.log('[SW] Notification clicked:', event.action);

    event.notification.close();

    if (event.action === 'dismiss') {
        return;
    }

    // Default action - open the app
    const urlToOpen = event.notification.data?.url || '/hrms/home/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then(windowClients => {
                // Check if app is already open
                for (const client of windowClients) {
                    if (client.url.includes('/hrms/') && 'focus' in client) {
                        client.navigate(urlToOpen);
                        return client.focus();
                    }
                }
                // Open new window
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

/**
 * Background Sync Event
 * Handles offline data submission when connection is restored
 */
self.addEventListener('sync', event => {
    console.log('[SW] Sync event:', event.tag);

    if (event.tag === 'hrms-offline-actions') {
        event.waitUntil(syncOfflineActions());
    }
});

/**
 * Sync offline actions (leave applications, etc.)
 */
async function syncOfflineActions() {
    try {
        // Get pending actions from IndexedDB
        const db = await openDatabase();
        const actions = await getPendingActions(db);

        for (const action of actions) {
            try {
                const response = await fetch(action.url, {
                    method: action.method,
                    headers: action.headers,
                    body: action.body
                });

                if (response.ok) {
                    await removePendingAction(db, action.id);
                    console.log('[SW] Synced action:', action.id);
                }
            } catch (error) {
                console.log('[SW] Failed to sync action:', action.id, error);
            }
        }
    } catch (error) {
        console.log('[SW] Sync failed:', error);
    }
}

/**
 * IndexedDB helpers for offline data
 */
const DB_NAME = 'hrms-offline';
const DB_VERSION = 1;

function openDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);

        request.onupgradeneeded = event => {
            const db = event.target.result;

            // Store for offline actions
            if (!db.objectStoreNames.contains('pending-actions')) {
                db.createObjectStore('pending-actions', { keyPath: 'id', autoIncrement: true });
            }

            // Store for cached data
            if (!db.objectStoreNames.contains('cached-data')) {
                const store = db.createObjectStore('cached-data', { keyPath: 'key' });
                store.createIndex('timestamp', 'timestamp');
            }
        };
    });
}

function getPendingActions(db) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(['pending-actions'], 'readonly');
        const store = transaction.objectStore('pending-actions');
        const request = store.getAll();

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve(request.result);
    });
}

function removePendingAction(db, id) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction(['pending-actions'], 'readwrite');
        const store = transaction.objectStore('pending-actions');
        const request = store.delete(id);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => resolve();
    });
}

/**
 * Message Event - Communication with main app
 */
self.addEventListener('message', event => {
    console.log('[SW] Message received:', event.data);

    if (event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data.type === 'CACHE_URLS') {
        event.waitUntil(
            caches.open(DYNAMIC_CACHE).then(cache => {
                return cache.addAll(event.data.urls);
            })
        );
    }

    if (event.data.type === 'CLEAR_CACHE') {
        event.waitUntil(
            caches.keys().then(names => {
                return Promise.all(names.map(name => caches.delete(name)));
            })
        );
    }
});

console.log('[SW] Service Worker loaded');

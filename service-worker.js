const CACHE_NAME = 'static-cache-v1'; // UPDATE WHEN STATIC FILES ARE UPDATED!
const DATA_CACHE_NAME = 'data-cache-v1';

const FILES_TO_CACHE = [
  '/cookies.js',
  '/common.css',
  '/service-worker.js',
  '/manifest.json',
  '/install.js',
  'https://fonts.googleapis.com/icon?family=Material+Icons',
  'https://fonts.googleapis.com/css?family=Roboto:400,500',

  '/index.html',
  '/index.css',
  '/index.js',
  '/login.html',
  '/login.css',
  '/login.js',
  '/tutorial.html',
  '/tutorial.css',
  '/tutorial.js',
  '/offline.html',
  '/offline.js',
  '/logo.png',
  '/logo.svg'
];

self.addEventListener('install', (evt) => {
  console.log('[ServiceWorker] Install');

  evt.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[ServiceWorker] Pre-caching offline page');
      return cache.addAll(FILES_TO_CACHE);
    })
  );

  self.skipWaiting();
});

self.addEventListener('activate', (evt) => {
  console.log('[ServiceWorker] Activate');
  
  evt.waitUntil(
    caches.keys().then((keyList) => {
      return Promise.all(keyList.map((key) => {
        if (key !== CACHE_NAME) {
          console.log('[ServiceWorker] Removing old cache', key);
          return caches.delete(key);
        }
      }));
    })
  );

  self.clients.claim();
});

// Listens for fetches
self.addEventListener('fetch', (evt) => {
  console.log('[ServiceWorker] Fetch', evt.request.url);

  // If it's not a page navigation it may be an API call
  if (evt.request.mode !== 'navigate') {
    // API request
    if (evt.request.url.includes('/api/')) {
      console.log('[Service Worker] Fetch (data)', evt.request.url);
      evt.respondWith(
        caches.open(DATA_CACHE_NAME).then((cache) => {
          return fetch(evt.request).then((response) => {
            // If the response was good, clone it and store it in the cache.
            if (response.status === 200) {
              cache.put(evt.request.url, response.clone());
            }
            return response;
          }).catch((err) => {
            // Network request failed, try to get it from the cache.
            return cache.match(evt.request);
          });
        }));
      return;
    }

    // If it's a resource request
    evt.respondWith(
      caches.open(CACHE_NAME).then((cache) => {
        return cache.match(evt.request).then((response) => {
          return response || fetch(evt.request);
        });
      })
    );
  }
  else {
    // Pages
    evt.respondWith(
      fetch(evt.request).catch(() => {
        return caches.open(CACHE_NAME).then((cache) => {
          return cache.match('offline.html');
        });
      })
    );
  }

});
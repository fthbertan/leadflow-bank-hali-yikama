// LeadFlow — Service Worker
var CACHE_NAME = 'leadflow-cache-v1';
var urlsToCache = [
  '/',
  '/css/style.css',
  '/js/main.js',
  '/favicon.svg',
  '/404.html'
];

// Install — önbelleğe al
self.addEventListener('install', function(event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function(cache) {
      return cache.addAll(urlsToCache);
    })
  );
  self.skipWaiting();
});

// Activate — eski önbellekleri temizle
self.addEventListener('activate', function(event) {
  event.waitUntil(
    caches.keys().then(function(cacheNames) {
      return Promise.all(
        cacheNames.filter(function(name) {
          return name !== CACHE_NAME;
        }).map(function(name) {
          return caches.delete(name);
        })
      );
    })
  );
  self.clients.claim();
});

// Fetch — Network first, cache fallback
self.addEventListener('fetch', function(event) {
  // API isteklerini önbelleğe alma
  if (event.request.url.indexOf('/api/') !== -1) return;

  event.respondWith(
    fetch(event.request).then(function(response) {
      // Başarılı yanıtı önbelleğe kaydet
      if (response && response.status === 200 && response.type === 'basic') {
        var responseToCache = response.clone();
        caches.open(CACHE_NAME).then(function(cache) {
          cache.put(event.request, responseToCache);
        });
      }
      return response;
    }).catch(function() {
      // Ağ hatası — önbellekten sun
      return caches.match(event.request).then(function(response) {
        return response || caches.match('/404.html');
      });
    })
  );
});

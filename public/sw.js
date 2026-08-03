const CACHE_NAME = "club-v7";

const STATIC_PAGES = ["/", "/offline.html"];

self.addEventListener("install", event => {
    event.waitUntil(caches.open(CACHE_NAME)
        .then(async cache => {
            let assets = [];

            try {
                const response = await fetch("/pwa-assets.json");

                if (response.ok) {
                    const data = await response.json();
                    assets = data.assets ?? [];
                }
            } catch (error) {
                console.log("PWA assets load failed");
            }

            await cache.addAll([...STATIC_PAGES, ...assets]);
        }));

    self.skipWaiting();
});

self.addEventListener("activate", event => {
    event.waitUntil(caches.keys()
        .then(keys => {
            return Promise.all(keys
                .filter(key => key !== CACHE_NAME)
                .map(key => caches.delete(key)));
        })
        .then(() => clients.claim()));
});

self.addEventListener("fetch", event => {
    if (event.request.method !== "GET") {
        return;
    }

    const url = new URL(event.request.url);

    if (url.protocol !== "http:" && url.protocol !== "https:") {
        return;
    }

    if (url.pathname.startsWith("/livewire") || url.pathname.startsWith("/_boost")) {
        return;
    }

    if (event.request.mode === "navigate") {
        event.respondWith(fetch(event.request)
            .then(response => {
                const clone = response.clone();

                caches.open(CACHE_NAME)
                    .then(cache => {
                        cache.put(event.request, clone);
                    });

                return response;
            })
            .catch(() => {
                return caches.match(event.request)
                    .then(cached => {
                        return cached || caches.match("/offline.html");
                    });
            }));

        return;
    }

    event.respondWith(caches.match(event.request)
        .then(cached => {
            if (cached) {
                return cached;
            }

            return fetch(event.request)
                .then(response => {

                    if (!response.ok) {
                        return response;
                    }

                    const clone = response.clone();

                    caches.open(CACHE_NAME)
                        .then(cache => {

                            if (event.request.url.startsWith("http://") || event.request.url.startsWith("https://")) {
                                cache.put(event.request, clone);
                            }

                        });

                    return response;
                });
        }));
});

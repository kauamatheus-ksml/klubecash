// === SERVICE WORKER - KLUBE CASH PWA ===
// Versão do cache - alterar para forçar atualização
const CACHE_VERSION = 'v1.2.0';
const STATIC_CACHE = `klube-cash-static-${CACHE_VERSION}`;
const DYNAMIC_CACHE = `klube-cash-dynamic-${CACHE_VERSION}`;
const OFFLINE_CACHE = `klube-cash-offline-${CACHE_VERSION}`;

// === ARQUIVOS PARA CACHE ESTÁTICO ===
const STATIC_FILES = [
    // Páginas principais
    '/',
    '/cliente/dashboard',
    '/cliente/extrato',
    '/cliente/lojas-parceiras',
    '/cliente/perfil',
    '/offline.html',
    
    // CSS
    '/assets/css/main.css',
    '/assets/css/client.css',
    '/assets/css/pwa.css',
    '/assets/css/mobile-first.css',
    '/assets/css/animations.css',
    '/assets/css/responsive.css',
    
    // JavaScript
    '/assets/js/main.js',
    '/assets/js/client.js',
    '/assets/js/pwa-main.js',
    '/assets/js/ui-interactions.js',
    '/assets/js/charts.js',
    
    // Imagens essenciais
    '/assets/images/logo.png',
    '/assets/icons/icon-192x192.png',
    '/assets/icons/icon-512x512.png',
    '/assets/images/empty-state.png',
    '/assets/images/offline-icon.png',
    
    // Fontes
    'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap',
    
    // Manifest
    '/pwa/manifest.json'
];

// === RECURSOS DINÂMICOS (APIs) ===
const DYNAMIC_URLS = [
    '/api/client/dashboard',
    '/api/client/transactions',
    '/api/client/stores',
    '/api/client/profile',
    '/api/client/notifications'
];

// === PÁGINAS OFFLINE ===
const OFFLINE_FALLBACKS = {
    '/cliente/dashboard': '/offline.html',
    '/cliente/extrato': '/offline.html',
    '/cliente/lojas-parceiras': '/offline.html',
    '/cliente/perfil': '/offline.html'
};

// === EVENTO INSTALL ===
self.addEventListener('install', (event) => {
    console.log('🔧 Service Worker: Instalando...');
    
    event.waitUntil(
        Promise.all([
            // Cache dos arquivos estáticos
            caches.open(STATIC_CACHE)
                .then(cache => {
                    console.log('📦 Cacheando arquivos estáticos...');
                    return cache.addAll(STATIC_FILES);
                }),
            
            // Cache da página offline
            caches.open(OFFLINE_CACHE)
                .then(cache => {
                    console.log('📴 Cacheando página offline...');
                    return cache.add('/pwa/offline.html');
                })
        ])
        .then(() => {
            console.log('✅ Service Worker: Instalação concluída');
            // Força a ativação imediata
            self.skipWaiting();
        })
        .catch(error => {
            console.error('❌ Erro na instalação:', error);
        })
    );
});

// === EVENTO ACTIVATE ===
self.addEventListener('activate', (event) => {
    console.log('🔄 Service Worker: Ativando...');
    
    event.waitUntil(
        Promise.all([
            // Limpar caches antigos
            cleanupOldCaches(),
            
            // Assumir controle imediato
            self.clients.claim()
        ])
        .then(() => {
            console.log('✅ Service Worker: Ativação concluída');
        })
    );
});

// === FUNÇÃO PARA LIMPAR CACHES ANTIGOS ===
async function cleanupOldCaches() {
    const cacheNames = await caches.keys();
    const currentCaches = [STATIC_CACHE, DYNAMIC_CACHE, OFFLINE_CACHE];
    
    return Promise.all(
        cacheNames.map(cacheName => {
            if (!currentCaches.includes(cacheName)) {
                console.log('🗑️ Removendo cache antigo:', cacheName);
                return caches.delete(cacheName);
            }
        })
    );
}

// === EVENTO FETCH - INTERCEPTAÇÃO DE REQUISIÇÕES ===
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);
    
    // Ignorar requisições não-HTTP
    if (!request.url.startsWith('http')) return;
    
    // Estratégia baseada no tipo de recurso
    if (isStaticResource(request)) {
        // CACHE FIRST para recursos estáticos
        event.respondWith(cacheFirstStrategy(request));
    } else if (isAPIRequest(request)) {
        // NETWORK FIRST para APIs
        event.respondWith(networkFirstStrategy(request));
    } else if (isPageRequest(request)) {
        // STALE WHILE REVALIDATE para páginas
        event.respondWith(staleWhileRevalidateStrategy(request));
    } else {
        // NETWORK ONLY para outros recursos
        event.respondWith(fetch(request));
    }
});

// === VERIFICAÇÕES DE TIPO DE RECURSO ===
function isStaticResource(request) {
    const url = request.url;
    return url.includes('/assets/') || 
           url.includes('/pwa/') ||
           url.includes('fonts.googleapis.com') ||
           url.includes('.css') ||
           url.includes('.js') ||
           url.includes('.png') ||
           url.includes('.jpg') ||
           url.includes('.svg') ||
           url.includes('.ico');
}

function isAPIRequest(request) {
    return request.url.includes('/api/');
}

function isPageRequest(request) {
    return request.mode === 'navigate' || 
           (request.method === 'GET' && 
            request.headers.get('accept').includes('text/html'));
}

// === ESTRATÉGIA: CACHE FIRST ===
async function cacheFirstStrategy(request) {
    try {
        // Buscar no cache primeiro
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Se não estiver no cache, buscar na rede
        const networkResponse = await fetch(request);
        
        // Cachear a resposta se for bem-sucedida
        if (networkResponse.ok) {
            const cache = await caches.open(STATIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
        
    } catch (error) {
        console.log('❌ Cache First falhou para:', request.url);
        
        // Fallback para recursos críticos
        if (request.url.includes('.css')) {
            return new Response('/* CSS offline fallback */', {
                headers: { 'Content-Type': 'text/css' }
            });
        }
        
        throw error;
    }
}

// === ESTRATÉGIA: NETWORK FIRST ===
async function networkFirstStrategy(request) {
    try {
        // Tentar rede primeiro
        const networkResponse = await fetch(request);
        
        // Cachear resposta se for bem-sucedida
        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
        
    } catch (error) {
        console.log('🌐 Network falhou, buscando no cache:', request.url);
        
        // Fallback para cache
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        
        // Fallback para dados offline
        return createOfflineAPIResponse(request);
    }
}

// === ESTRATÉGIA: STALE WHILE REVALIDATE ===
async function staleWhileRevalidateStrategy(request) {
    const cache = await caches.open(DYNAMIC_CACHE);
    const cachedResponse = await cache.match(request);
    
    // Sempre tentar buscar versão atualizada em background
    const fetchPromise = fetch(request)
        .then(response => {
            if (response.ok) {
                cache.put(request, response.clone());
            }
            return response;
        })
        .catch(() => {
            console.log('🔄 Revalidação falhou para:', request.url);
        });
    
    // Retornar cache se disponível, senão aguardar rede
    if (cachedResponse) {
        fetchPromise; // Executa em background
        return cachedResponse;
    } else {
        try {
            return await fetchPromise;
        } catch (error) {
            // Fallback para página offline
            return caches.match('/pwa/offline.html');
        }
    }
}

// === RESPOSTA OFFLINE PARA APIs ===
function createOfflineAPIResponse(request) {
    const url = new URL(request.url);
    
    // Dados mock para diferentes endpoints
    const offlineData = {
        '/api/client/dashboard': {
            saldo_disponivel: 0,
            saldo_pendente: 0,
            transacoes_recentes: [],
            message: 'Dados offline - conecte-se para atualizar'
        },
        '/api/client/transactions': {
            transactions: [],
            total: 0,
            message: 'Dados offline - conecte-se para ver transações'
        },
        '/api/client/stores': {
            stores: [],
            message: 'Dados offline - conecte-se para ver lojas'
        }
    };
    
    const data = offlineData[url.pathname] || { 
        message: 'Dados indisponíveis offline' 
    };
    
    return new Response(JSON.stringify(data), {
        status: 200,
        headers: {
            'Content-Type': 'application/json',
            'X-Offline': 'true'
        }
    });
}

// === PUSH NOTIFICATIONS ===
self.addEventListener('push', (event) => {
    console.log('📱 Push notification recebida');
    
    let data = {};
    if (event.data) {
        data = event.data.json();
    }
    
    const options = {
        title: data.title || 'Klube Cash',
        body: data.body || 'Você tem uma nova notificação',
        icon: '/assets/icons/icon-192x192.png',
        badge: '/assets/icons/badge-72x72.png',
        tag: data.tag || 'klube-cash-notification',
        data: data.data || {},
        actions: [
            {
                action: 'view',
                title: 'Ver'
            },
            {
                action: 'dismiss',
                title: 'Dispensar'
            }
        ],
        requireInteraction: true,
        vibrate: [200, 100, 200]
    };
    
    event.waitUntil(
        self.registration.showNotification(options.title, options)
    );
});

// === CLIQUE EM NOTIFICAÇÃO ===
self.addEventListener('notificationclick', (event) => {
    console.log('👆 Notificação clicada:', event.action);
    
    event.notification.close();
    
    if (event.action === 'view') {
        // Abrir página específica baseada nos dados
        const urlToOpen = event.notification.data.url || '/cliente/dashboard';
        
        event.waitUntil(
            clients.matchAll().then(clientList => {
                // Se já existe uma aba aberta, focar nela
                for (let client of clientList) {
                    if (client.url.includes(urlToOpen) && 'focus' in client) {
                        return client.focus();
                    }
                }
                
                // Senão, abrir nova aba
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
        );
    }
});

// === BACKGROUND SYNC ===
self.addEventListener('sync', (event) => {
    console.log('🔄 Background sync:', event.tag);
    
    if (event.tag === 'background-sync-transactions') {
        event.waitUntil(syncPendingData());
    }
});

// === SINCRONIZAÇÃO DE DADOS PENDENTES ===
async function syncPendingData() {
    try {
        // Buscar dados pendentes do IndexedDB
        const pendingData = await getPendingData();
        
        if (pendingData.length > 0) {
            console.log('📤 Sincronizando dados pendentes:', pendingData.length);
            
            for (let data of pendingData) {
                try {
                    await fetch('/api/sync', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    });
                    
                    // Remover do IndexedDB após sucesso
                    await removePendingData(data.id);
                    
                } catch (error) {
                    console.log('❌ Falha ao sincronizar item:', data.id);
                }
            }
        }
        
    } catch (error) {
        console.error('❌ Erro na sincronização:', error);
    }
}

// === HELPERS PARA INDEXEDDB ===
async function getPendingData() {
    // Implementar lógica de IndexedDB
    return [];
}

async function removePendingData(id) {
    // Implementar lógica de IndexedDB
}

// === COMPARTILHAMENTO DE ARQUIVOS ===
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);
    
    // Interceptar compartilhamento Web Share Target
    if (url.pathname === '/share-target' && event.request.method === 'POST') {
        event.respondWith(handleSharedContent(event.request));
    }
});

async function handleSharedContent(request) {
    const formData = await request.formData();
    
    // Processar conteúdo compartilhado
    const title = formData.get('title');
    const text = formData.get('text');
    const url = formData.get('url');
    
    console.log('📤 Conteúdo compartilhado:', { title, text, url });
    
    // Redirecionar para página apropriada
    return Response.redirect('/cliente/dashboard?shared=true', 302);
}

// === ATUALIZAÇÃO AUTOMÁTICA ===
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        console.log('🔄 Aplicando atualização...');
        self.skipWaiting();
    }
});

// === LOG INICIAL ===
console.log('🚀 Service Worker Klube Cash iniciado!');
console.log('📦 Cache version:', CACHE_VERSION);
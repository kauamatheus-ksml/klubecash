/**
 * Klube Cash PWA - Testes Completos
 * Sistema de testes para Progressive Web App
 * Versão: 2.1.0
 * Data: 2025
 */

// === CONFIGURAÇÕES DE TESTE ===
const TEST_CONFIG = {
    timeout: 10000, // 10 segundos
    baseUrl: window.location.origin,
    serviceWorkerUrl: '/pwa/sw.js',
    manifestUrl: '/pwa/manifest.json',
    debug: true,
    runOnLoad: true
};

// === VARIÁVEIS GLOBAIS ===
let testResults = {
    passed: 0,
    failed: 0,
    total: 0,
    details: []
};

// === UTILITÁRIOS DE TESTE ===
class PWATestSuite {
    constructor() {
        this.results = [];
        this.startTime = performance.now();
    }

    log(message, type = 'info') {
        if (!TEST_CONFIG.debug) return;
        
        const timestamp = new Date().toLocaleTimeString();
        const prefix = '🧪 PWA Test';
        
        switch(type) {
            case 'error':
                console.error(`${prefix} [${timestamp}] ❌`, message);
                break;
            case 'warn':
                console.warn(`${prefix} [${timestamp}] ⚠️`, message);
                break;
            case 'success':
                console.log(`${prefix} [${timestamp}] ✅`, message);
                break;
            default:
                console.log(`${prefix} [${timestamp}] ℹ️`, message);
        }
    }

    assert(condition, description, details = null) {
        testResults.total++;
        
        if (condition) {
            testResults.passed++;
            this.log(`✅ PASS: ${description}`, 'success');
        } else {
            testResults.failed++;
            this.log(`❌ FAIL: ${description}`, 'error');
            if (details) this.log(`   Detalhes: ${details}`, 'error');
        }
        
        testResults.details.push({
            passed: condition,
            description,
            details,
            timestamp: new Date().toISOString()
        });
        
        return condition;
    }

    async runTest(testName, testFunction) {
        this.log(`\n🔍 Executando teste: ${testName}`);
        try {
            await testFunction();
            this.log(`✅ Teste completado: ${testName}`, 'success');
        } catch (error) {
            this.log(`❌ Erro no teste ${testName}: ${error.message}`, 'error');
            this.assert(false, `Execução do teste ${testName}`, error.message);
        }
    }

    generateReport() {
        const endTime = performance.now();
        const duration = Math.round(endTime - this.startTime);
        
        const report = {
            summary: {
                total: testResults.total,
                passed: testResults.passed,
                failed: testResults.failed,
                successRate: Math.round((testResults.passed / testResults.total) * 100),
                duration: `${duration}ms`
            },
            details: testResults.details,
            timestamp: new Date().toISOString()
        };
        
        this.log('\n📊 RELATÓRIO FINAL DOS TESTES:', 'info');
        this.log(`Total: ${report.summary.total}`, 'info');
        this.log(`Passou: ${report.summary.passed}`, 'success');
        this.log(`Falhou: ${report.summary.failed}`, 'error');
        this.log(`Taxa de sucesso: ${report.summary.successRate}%`, 'info');
        this.log(`Duração: ${report.summary.duration}`, 'info');
        
        return report;
    }
}

// === TESTES DE SERVICE WORKER ===
class ServiceWorkerTests {
    constructor(testSuite) {
        this.testSuite = testSuite;
    }

    async runAll() {
        await this.testSuite.runTest('Service Worker Support', () => this.testSupport());
        await this.testSuite.runTest('Service Worker Registration', () => this.testRegistration());
        await this.testSuite.runTest('Service Worker Install', () => this.testInstallation());
        await this.testSuite.runTest('Service Worker Activation', () => this.testActivation());
        await this.testSuite.runTest('Service Worker Update', () => this.testUpdate());
        await this.testSuite.runTest('Service Worker Messaging', () => this.testMessaging());
    }

    testSupport() {
        this.testSuite.assert(
            'serviceWorker' in navigator,
            'Navigator suporta Service Worker'
        );

        this.testSuite.assert(
            'PushManager' in window,
            'Browser suporta Push Notifications'
        );

        this.testSuite.assert(
            'Notification' in window,
            'Browser suporta Notifications API'
        );

        this.testSuite.assert(
            'fetch' in window,
            'Browser suporta Fetch API'
        );

        this.testSuite.assert(
            'Cache' in window && 'caches' in window,
            'Browser suporta Cache API'
        );
    }

    async testRegistration() {
        if (!('serviceWorker' in navigator)) {
            this.testSuite.assert(false, 'Service Worker não suportado');
            return;
        }

        try {
            const registration = await navigator.serviceWorker.register(
                TEST_CONFIG.serviceWorkerUrl,
                { scope: '/' }
            );

            this.testSuite.assert(
                registration instanceof ServiceWorkerRegistration,
                'Service Worker registrado com sucesso'
            );

            this.testSuite.assert(
                registration.scope.includes(window.location.origin),
                'Escopo do Service Worker está correto',
                `Escopo: ${registration.scope}`
            );

        } catch (error) {
            this.testSuite.assert(
                false,
                'Falha no registro do Service Worker',
                error.message
            );
        }
    }

    async testInstallation() {
        if (!('serviceWorker' in navigator)) return;

        return new Promise((resolve) => {
            navigator.serviceWorker.ready.then((registration) => {
                this.testSuite.assert(
                    registration.installing || registration.waiting || registration.active,
                    'Service Worker instalado ou ativo'
                );

                if (registration.active) {
                    this.testSuite.assert(
                        registration.active.state === 'activated',
                        'Service Worker está ativo'
                    );
                }

                resolve();
            }).catch((error) => {
                this.testSuite.assert(
                    false,
                    'Erro na verificação de instalação',
                    error.message
                );
                resolve();
            });
        });
    }

    async testActivation() {
        if (!('serviceWorker' in navigator)) return;

        const registration = await navigator.serviceWorker.getRegistration();
        
        if (registration && registration.active) {
            this.testSuite.assert(
                registration.active.state === 'activated',
                'Service Worker está no estado ativado'
            );
        } else {
            this.testSuite.assert(
                false,
                'Service Worker não está ativo'
            );
        }
    }

    async testUpdate() {
        if (!('serviceWorker' in navigator)) return;

        try {
            const registration = await navigator.serviceWorker.getRegistration();
            
            if (registration) {
                await registration.update();
                this.testSuite.assert(
                    true,
                    'Verificação de atualização executada com sucesso'
                );
            } else {
                this.testSuite.assert(
                    false,
                    'Não foi possível verificar atualizações - Service Worker não registrado'
                );
            }
        } catch (error) {
            this.testSuite.assert(
                false,
                'Erro na verificação de atualização',
                error.message
            );
        }
    }

    async testMessaging() {
        if (!('serviceWorker' in navigator)) return;

        return new Promise((resolve) => {
            const messageChannel = new MessageChannel();
            const timeout = setTimeout(() => {
                this.testSuite.assert(
                    false,
                    'Timeout na comunicação com Service Worker'
                );
                resolve();
            }, 5000);

            messageChannel.port1.onmessage = (event) => {
                clearTimeout(timeout);
                this.testSuite.assert(
                    event.data && event.data.type === 'TEST_RESPONSE',
                    'Comunicação com Service Worker funcionando'
                );
                resolve();
            };

            if (navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage(
                    { type: 'TEST_MESSAGE', test: true },
                    [messageChannel.port2]
                );
            } else {
                clearTimeout(timeout);
                this.testSuite.assert(
                    false,
                    'Service Worker controller não disponível'
                );
                resolve();
            }
        });
    }
}

// === TESTES DE MANIFEST ===
class ManifestTests {
    constructor(testSuite) {
        this.testSuite = testSuite;
    }

    async runAll() {
        await this.testSuite.runTest('Manifest Link', () => this.testManifestLink());
        await this.testSuite.runTest('Manifest Content', () => this.testManifestContent());
        await this.testSuite.runTest('Manifest Icons', () => this.testManifestIcons());
        await this.testSuite.runTest('PWA Criteria', () => this.testPWACriteria());
    }

    testManifestLink() {
        const manifestLink = document.querySelector('link[rel="manifest"]');
        
        this.testSuite.assert(
            manifestLink !== null,
            'Tag link para manifest existe'
        );

        if (manifestLink) {
            this.testSuite.assert(
                manifestLink.href.includes('manifest.json'),
                'Link do manifest aponta para arquivo .json'
            );
        }
    }

    async testManifestContent() {
        try {
            const response = await fetch(TEST_CONFIG.manifestUrl);
            const manifest = await response.json();

            this.testSuite.assert(
                response.ok,
                'Manifest.json carregado com sucesso'
            );

            // Verificar campos obrigatórios
            this.testSuite.assert(
                manifest.name && manifest.name.length > 0,
                'Manifest possui nome',
                `Nome: ${manifest.name}`
            );

            this.testSuite.assert(
                manifest.short_name && manifest.short_name.length > 0,
                'Manifest possui nome curto',
                `Nome curto: ${manifest.short_name}`
            );

            this.testSuite.assert(
                manifest.start_url && manifest.start_url.length > 0,
                'Manifest possui start_url',
                `Start URL: ${manifest.start_url}`
            );

            this.testSuite.assert(
                manifest.display && ['standalone', 'fullscreen', 'minimal-ui'].includes(manifest.display),
                'Manifest possui display mode válido',
                `Display: ${manifest.display}`
            );

            this.testSuite.assert(
                manifest.background_color && manifest.background_color.match(/^#[0-9A-Fa-f]{6}$/),
                'Manifest possui background_color válida',
                `Background: ${manifest.background_color}`
            );

            this.testSuite.assert(
                manifest.theme_color && manifest.theme_color.match(/^#[0-9A-Fa-f]{6}$/),
                'Manifest possui theme_color válida',
                `Theme: ${manifest.theme_color}`
            );

            return manifest;

        } catch (error) {
            this.testSuite.assert(
                false,
                'Erro ao carregar manifest.json',
                error.message
            );
            return null;
        }
    }

    async testManifestIcons() {
        try {
            const response = await fetch(TEST_CONFIG.manifestUrl);
            const manifest = await response.json();

            this.testSuite.assert(
                manifest.icons && Array.isArray(manifest.icons),
                'Manifest possui array de ícones'
            );

            if (manifest.icons) {
                this.testSuite.assert(
                    manifest.icons.length > 0,
                    'Manifest possui pelo menos um ícone'
                );

                // Verificar se há ícone 192x192
                const icon192 = manifest.icons.find(icon => 
                    icon.sizes && icon.sizes.includes('192x192')
                );
                this.testSuite.assert(
                    icon192 !== undefined,
                    'Manifest possui ícone 192x192'
                );

                // Verificar se há ícone 512x512
                const icon512 = manifest.icons.find(icon => 
                    icon.sizes && icon.sizes.includes('512x512')
                );
                this.testSuite.assert(
                    icon512 !== undefined,
                    'Manifest possui ícone 512x512'
                );

                // Testar se os ícones existem
                for (let icon of manifest.icons.slice(0, 3)) { // Testar apenas os 3 primeiros
                    try {
                        const iconResponse = await fetch(icon.src);
                        this.testSuite.assert(
                            iconResponse.ok,
                            `Ícone ${icon.sizes} existe e é acessível`,
                            `URL: ${icon.src}`
                        );
                    } catch (error) {
                        this.testSuite.assert(
                            false,
                            `Erro ao carregar ícone ${icon.sizes}`,
                            error.message
                        );
                    }
                }
            }

        } catch (error) {
            this.testSuite.assert(
                false,
                'Erro na verificação de ícones',
                error.message
            );
        }
    }

    async testPWACriteria() {
        // Verificar HTTPS
        this.testSuite.assert(
            location.protocol === 'https:' || location.hostname === 'localhost',
            'Site servido via HTTPS ou localhost'
        );

        // Verificar viewport meta tag
        const viewport = document.querySelector('meta[name="viewport"]');
        this.testSuite.assert(
            viewport !== null,
            'Meta tag viewport presente'
        );

        // Verificar se é responsivo
        const isResponsive = viewport && viewport.content.includes('width=device-width');
        this.testSuite.assert(
            isResponsive,
            'Site configurado como responsivo'
        );
    }
}

// === TESTES DE PERFORMANCE ===
class PerformanceTests {
    constructor(testSuite) {
        this.testSuite = testSuite;
    }

    async runAll() {
        await this.testSuite.runTest('Page Load Performance', () => this.testPageLoad());
        await this.testSuite.runTest('Resource Load Times', () => this.testResourceLoading());
        await this.testSuite.runTest('Cache Performance', () => this.testCachePerformance());
        await this.testSuite.runTest('Network Conditions', () => this.testNetworkConditions());
    }

    testPageLoad() {
        const navigation = performance.getEntriesByType('navigation')[0];
        
        if (navigation) {
            const loadTime = navigation.loadEventEnd - navigation.fetchStart;
            const domContentLoaded = navigation.domContentLoadedEventEnd - navigation.fetchStart;
            
            this.testSuite.assert(
                loadTime < 3000, // 3 segundos
                'Página carrega em menos de 3 segundos',
                `Tempo de carregamento: ${Math.round(loadTime)}ms`
            );

            this.testSuite.assert(
                domContentLoaded < 1500, // 1.5 segundos
                'DOM carrega em menos de 1.5 segundos',
                `DOM ready: ${Math.round(domContentLoaded)}ms`
            );
        } else {
            this.testSuite.assert(
                false,
                'Dados de navegação não disponíveis'
            );
        }
    }

    testResourceLoading() {
        const resources = performance.getEntriesByType('resource');
        
        this.testSuite.assert(
            resources.length > 0,
            'Recursos carregados detectados'
        );

        // Verificar recursos críticos
        const css = resources.filter(r => r.name.includes('.css'));
        const js = resources.filter(r => r.name.includes('.js'));
        
        css.forEach(resource => {
            const loadTime = resource.responseEnd - resource.fetchStart;
            this.testSuite.assert(
                loadTime < 1000,
                `CSS carrega rapidamente: ${resource.name.split('/').pop()}`,
                `Tempo: ${Math.round(loadTime)}ms`
            );
        });

        js.forEach(resource => {
            const loadTime = resource.responseEnd - resource.fetchStart;
            this.testSuite.assert(
                loadTime < 2000,
                `JavaScript carrega rapidamente: ${resource.name.split('/').pop()}`,
                `Tempo: ${Math.round(loadTime)}ms`
            );
        });
    }

    async testCachePerformance() {
        if (!('caches' in window)) {
            this.testSuite.assert(
                false,
                'Cache API não suportada'
            );
            return;
        }

        try {
            const cacheNames = await caches.keys();
            
            this.testSuite.assert(
                cacheNames.length > 0,
                'Cache do Service Worker criado',
                `Caches encontrados: ${cacheNames.length}`
            );

            // Testar acesso a um cache específico
            if (cacheNames.length > 0) {
                const cache = await caches.open(cacheNames[0]);
                const keys = await cache.keys();
                
                this.testSuite.assert(
                    keys.length > 0,
                    'Cache contém recursos',
                    `Recursos em cache: ${keys.length}`
                );
            }

        } catch (error) {
            this.testSuite.assert(
                false,
                'Erro ao verificar cache',
                error.message
            );
        }
    }

    testNetworkConditions() {
        // Verificar Connection API se disponível
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        
        if (connection) {
            this.testSuite.assert(
                connection.effectiveType !== 'slow-2g',
                'Conexão não é 2G lenta',
                `Tipo de conexão: ${connection.effectiveType}`
            );

            this.testSuite.assert(
                connection.downlink > 0.5,
                'Velocidade de download adequada',
                `Download: ${connection.downlink} Mbps`
            );
        } else {
            this.testSuite.assert(
                true,
                'Connection API não disponível (não é erro)'
            );
        }

        // Verificar se está online
        this.testSuite.assert(
            navigator.onLine,
            'Dispositivo está online'
        );
    }
}

// === TESTES DE FUNCIONALIDADE PWA ===
class PWAFunctionalityTests {
    constructor(testSuite) {
        this.testSuite = testSuite;
    }

    async runAll() {
        await this.testSuite.runTest('Install Prompt', () => this.testInstallPrompt());
        await this.testSuite.runTest('Offline Functionality', () => this.testOfflineMode());
        await this.testSuite.runTest('Background Sync', () => this.testBackgroundSync());
        await this.testSuite.runTest('Push Notifications', () => this.testPushNotifications());
    }

    async testInstallPrompt() {
        // Verificar se o evento beforeinstallprompt foi capturado
        this.testSuite.assert(
            window.deferredPrompt !== undefined || 
            window.matchMedia('(display-mode: standalone)').matches,
            'PWA é instalável ou já está instalado'
        );

        // Verificar se está rodando como PWA instalado
        const isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
                           window.navigator.standalone ||
                           document.referrer.includes('android-app://');

        if (isStandalone) {
            this.testSuite.assert(
                true,
                'PWA está rodando no modo standalone'
            );
        }
    }

    async testOfflineMode() {
        try {
            // Simular requisição offline usando cache
            const response = await fetch('/pwa/offline.html', {
                cache: 'force-cache'
            });

            this.testSuite.assert(
                response.ok,
                'Página offline disponível no cache'
            );

        } catch (error) {
            this.testSuite.assert(
                false,
                'Página offline não está em cache',
                error.message
            );
        }
    }

    async testBackgroundSync() {
        if (!('serviceWorker' in navigator)) return;

        try {
            const registration = await navigator.serviceWorker.ready;
            
            if ('sync' in window.ServiceWorkerRegistration.prototype) {
                this.testSuite.assert(
                    true,
                    'Background Sync API suportada'
                );

                // Tentar registrar um evento de sync
                await registration.sync.register('test-sync');
                this.testSuite.assert(
                    true,
                    'Evento de Background Sync registrado'
                );

            } else {
                this.testSuite.assert(
                    false,
                    'Background Sync API não suportada'
                );
            }

        } catch (error) {
            this.testSuite.assert(
                false,
                'Erro no teste de Background Sync',
                error.message
            );
        }
    }

    async testPushNotifications() {
        if (!('Notification' in window)) {
            this.testSuite.assert(
                false,
                'Notification API não suportada'
            );
            return;
        }

        this.testSuite.assert(
            'Notification' in window,
            'Notification API suportada'
        );

        this.testSuite.assert(
            'PushManager' in window,
            'Push Manager API suportada'
        );

        // Verificar permissão atual
        const permission = Notification.permission;
        this.testSuite.assert(
            ['granted', 'denied', 'default'].includes(permission),
            'Status de permissão de notificação válido',
            `Permissão: ${permission}`
        );
    }
}

// === EXECUTOR PRINCIPAL DOS TESTES ===
class PWATestRunner {
    constructor() {
        this.testSuite = new PWATestSuite();
        this.serviceWorkerTests = new ServiceWorkerTests(this.testSuite);
        this.manifestTests = new ManifestTests(this.testSuite);
        this.performanceTests = new PerformanceTests(this.testSuite);
        this.functionalityTests = new PWAFunctionalityTests(this.testSuite);
    }

    async runAllTests() {
        this.testSuite.log('🚀 Iniciando bateria completa de testes PWA', 'info');
        
        try {
            await this.serviceWorkerTests.runAll();
            await this.manifestTests.runAll();
            await this.performanceTests.runAll();
            await this.functionalityTests.runAll();
            
            const report = this.testSuite.generateReport();
            
            // Exibir resultado na interface se houver elemento
            this.displayResults(report);
            
            return report;
            
        } catch (error) {
            this.testSuite.log(`Erro crítico durante os testes: ${error.message}`, 'error');
            throw error;
        }
    }

    displayResults(report) {
        const resultElement = document.getElementById('pwa-test-results');
        if (!resultElement) return;

        const successRate = report.summary.successRate;
        const statusClass = successRate >= 90 ? 'success' : successRate >= 70 ? 'warning' : 'error';
        
        resultElement.innerHTML = `
            <div class="test-summary ${statusClass}">
                <h3>Resultados dos Testes PWA</h3>
                <div class="test-stats">
                    <div class="stat">
                        <span class="label">Total:</span>
                        <span class="value">${report.summary.total}</span>
                    </div>
                    <div class="stat">
                        <span class="label">Passou:</span>
                        <span class="value success">${report.summary.passed}</span>
                    </div>
                    <div class="stat">
                        <span class="label">Falhou:</span>
                        <span class="value error">${report.summary.failed}</span>
                    </div>
                    <div class="stat">
                        <span class="label">Taxa de Sucesso:</span>
                        <span class="value">${report.summary.successRate}%</span>
                    </div>
                    <div class="stat">
                        <span class="label">Duração:</span>
                        <span class="value">${report.summary.duration}</span>
                    </div>
                </div>
            </div>
            <div class="test-details">
                <h4>Detalhes dos Testes</h4>
                <ul class="test-list">
                    ${report.details.map(test => `
                        <li class="test-item ${test.passed ? 'passed' : 'failed'}">
                            <span class="test-status">${test.passed ? '✅' : '❌'}</span>
                            <span class="test-description">${test.description}</span>
                            ${test.details ? `<span class="test-details-text">${test.details}</span>` : ''}
                        </li>
                    `).join('')}
                </ul>
            </div>
        `;
    }
}

// === INICIALIZAÇÃO DOS TESTES ===
let pwaTestRunner;

function initializePWATests() {
    pwaTestRunner = new PWATestRunner();
    
    // Executar automaticamente se configurado
    if (TEST_CONFIG.runOnLoad) {
        window.addEventListener('load', () => {
            setTimeout(() => {
                runPWATests();
            }, 2000); // Aguardar 2 segundos para garantir que tudo carregou
        });
    }
}

// Função global para executar testes manualmente
window.runPWATests = async function() {
    if (!pwaTestRunner) {
        pwaTestRunner = new PWATestRunner();
    }
    
    try {
        const report = await pwaTestRunner.runAllTests();
        return report;
    } catch (error) {
        console.error('Erro ao executar testes PWA:', error);
        return null;
    }
};

// Função para executar teste específico
window.runSpecificPWATest = async function(testType) {
    if (!pwaTestRunner) {
        pwaTestRunner = new PWATestRunner();
    }
    
    switch(testType) {
        case 'serviceworker':
            await pwaTestRunner.serviceWorkerTests.runAll();
            break;
        case 'manifest':
            await pwaTestRunner.manifestTests.runAll();
            break;
        case 'performance':
            await pwaTestRunner.performanceTests.runAll();
            break;
        case 'functionality':
            await pwaTestRunner.functionalityTests.runAll();
            break;
        default:
            console.error('Tipo de teste inválido. Use: serviceworker, manifest, performance, functionality');
    }
};

// Inicializar testes quando o script carregar
initializePWATests();

// Exportar para uso em módulos se necessário
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        PWATestRunner,
        runPWATests: window.runPWATests,
        runSpecificPWATest: window.runSpecificPWATest
    };
}
<?php
/**
 * KLUBE CASH - FRONT CONTROLLER PWA
 * Sistema de roteamento inteligente com detecção mobile
 * Redirecionamentos automáticos e suporte offline
 * 
 * @version 2.0
 * @author Klube Cash Development Team
 */

// === CONFIGURAÇÕES INICIAIS ===
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Headers de segurança básicos
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');

// === INCLUDES NECESSÁRIOS ===
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/utils/Security.php';
require_once __DIR__ . '/utils/Logger.php';
require_once __DIR__ . '/controllers/AuthController.php';

/**
 * Classe para detecção de dispositivos móveis
 */
class MobileDetector {
    private $userAgent;
    private $httpHeaders;
    
    public function __construct() {
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $this->httpHeaders = $this->getHttpHeaders();
    }
    
    /**
     * Detecta se o dispositivo é móvel
     */
    public function isMobile() {
        return $this->isTablet() || $this->isPhone();
    }
    
    /**
     * Detecta se é um tablet
     */
    public function isTablet() {
        $tabletRegex = '/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i';
        return preg_match($tabletRegex, $this->userAgent);
    }
    
    /**
     * Detecta se é um smartphone
     */
    public function isPhone() {
        $phoneRegex = '/(up\.browser|up\.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i';
        $mobileRegex = '/mobile/i';
        
        return preg_match($phoneRegex, $this->userAgent) || 
               preg_match($mobileRegex, $this->userAgent);
    }
    
    /**
     * Detecta se é iOS
     */
    public function isiOS() {
        return preg_match('/(iphone|ipod|ipad)/i', $this->userAgent);
    }
    
    /**
     * Detecta se é Android
     */
    public function isAndroid() {
        return preg_match('/android/i', $this->userAgent);
    }
    
    /**
     * Detecta se suporta PWA
     */
    public function supportsPWA() {
        // iOS 11.3+ e Chrome Android suportam PWA
        if ($this->isiOS()) {
            preg_match('/OS (\d+)_(\d+)/', $this->userAgent, $matches);
            if (isset($matches[1]) && $matches[1] >= 11) {
                return isset($matches[2]) && $matches[2] >= 3;
            }
            return false;
        }
        
        if ($this->isAndroid()) {
            return preg_match('/Chrome\/(\d+)/', $this->userAgent, $matches) && 
                   isset($matches[1]) && $matches[1] >= 57;
        }
        
        // Desktop Chrome também suporta
        return preg_match('/Chrome\/(\d+)/', $this->userAgent, $matches) && 
               isset($matches[1]) && $matches[1] >= 70;
    }
    
    /**
     * Detecta se está rodando como PWA
     */
    public function isRunningAsPWA() {
        return isset($_GET['standalone']) || 
               isset($_GET['utm_source']) && $_GET['utm_source'] === 'pwa' ||
               (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                $_SERVER['HTTP_X_REQUESTED_WITH'] === 'PWA');
    }
    
    /**
     * Obtém informações do dispositivo
     */
    public function getDeviceInfo() {
        return [
            'isMobile' => $this->isMobile(),
            'isTablet' => $this->isTablet(),
            'isPhone' => $this->isPhone(),
            'isiOS' => $this->isiOS(),
            'isAndroid' => $this->isAndroid(),
            'supportsPWA' => $this->supportsPWA(),
            'isRunningAsPWA' => $this->isRunningAsPWA(),
            'userAgent' => $this->userAgent,
            'viewport' => $this->getViewportSize()
        ];
    }
    
    /**
     * Obtém tamanho estimado do viewport
     */
    private function getViewportSize() {
        if ($this->isPhone()) {
            return ['width' => 375, 'height' => 667]; // iPhone-like
        } elseif ($this->isTablet()) {
            return ['width' => 768, 'height' => 1024]; // iPad-like
        }
        return ['width' => 1920, 'height' => 1080]; // Desktop
    }
    
    /**
     * Obtém headers HTTP
     */
    private function getHttpHeaders() {
        return getallheaders() ?: [];
    }
}

/**
 * Classe principal de roteamento PWA
 */
class PWARouter {
    private $mobileDetector;
    private $authController;
    private $currentUser;
    private $requestUri;
    private $requestMethod;
    private $routes;
    private $pwaPaths;
    
    public function __construct() {
        $this->mobileDetector = new MobileDetector();
        $this->authController = new AuthController();
        $this->currentUser = $this->authController->getCurrentUser();
        $this->requestUri = $this->parseRequestUri();
        $this->requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        $this->initializeRoutes();
        $this->initializePWAPaths();
    }
    
    /**
     * Parseia a URI da requisição
     */
    private function parseRequestUri() {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        return $uri === '' ? '/' : $uri;
    }
    
    /**
     * Inicializa as rotas do sistema
     */
    private function initializeRoutes() {
        $this->routes = [
            // === ROTAS PÚBLICAS ===
            '/' => ['file' => 'views/public/index.php', 'auth' => false],
            '/sobre' => ['file' => 'views/public/about.php', 'auth' => false],
            '/contato' => ['file' => 'views/public/contact.php', 'auth' => false],
            '/como-funciona' => ['file' => 'views/public/how-it-works.php', 'auth' => false],
            '/lojas-parceiras' => ['file' => 'views/public/partner-stores.php', 'auth' => false],
            '/seja-parceiro' => ['file' => 'views/public/become-partner.php', 'auth' => false],
            
            // === ROTAS DE AUTENTICAÇÃO ===
            '/login' => ['file' => 'views/auth/login.php', 'auth' => false],
            '/registro' => ['file' => 'views/auth/register.php', 'auth' => false],
            '/recuperar-senha' => ['file' => 'views/auth/recover-password.php', 'auth' => false],
            '/logout' => ['action' => 'logout', 'auth' => true],
            
            // === ROTAS DO CLIENTE ===
            '/cliente' => ['file' => 'views/client/dashboard.php', 'auth' => true, 'role' => 'cliente'],
            '/cliente/dashboard' => ['file' => 'views/client/dashboard.php', 'auth' => true, 'role' => 'cliente'],
            '/cliente/extrato' => ['file' => 'views/client/statement.php', 'auth' => true, 'role' => 'cliente'],
            '/cliente/cashback' => ['file' => 'views/client/cashback-history.php', 'auth' => true, 'role' => 'cliente'],
            '/cliente/lojas' => ['file' => 'views/client/partner-stores.php', 'auth' => true, 'role' => 'cliente'],
            '/cliente/perfil' => ['file' => 'views/client/profile.php', 'auth' => true, 'role' => 'cliente'],
            
            // === ROTAS ADMINISTRATIVAS ===
            '/admin' => ['file' => 'views/admin/dashboard.php', 'auth' => true, 'role' => 'admin'],
            '/admin/dashboard' => ['file' => 'views/admin/dashboard.php', 'auth' => true, 'role' => 'admin'],
            '/admin/usuarios' => ['file' => 'views/admin/users.php', 'auth' => true, 'role' => 'admin'],
            '/admin/lojas' => ['file' => 'views/admin/stores.php', 'auth' => true, 'role' => 'admin'],
            '/admin/transacoes' => ['file' => 'views/admin/transactions.php', 'auth' => true, 'role' => 'admin'],
            '/admin/configuracoes' => ['file' => 'views/admin/settings.php', 'auth' => true, 'role' => 'admin'],
            
            // === ROTAS DA LOJA ===
            '/loja' => ['file' => 'views/store/dashboard.php', 'auth' => true, 'role' => 'loja'],
            '/loja/dashboard' => ['file' => 'views/store/dashboard.php', 'auth' => true, 'role' => 'loja'],
            '/loja/registrar-transacao' => ['file' => 'views/store/register-transaction.php', 'auth' => true, 'role' => 'loja'],
            '/loja/comissoes' => ['file' => 'views/store/pending-commissions.php', 'auth' => true, 'role' => 'loja'],
            '/loja/perfil' => ['file' => 'views/store/profile.php', 'auth' => true, 'role' => 'loja'],
            
            // === ROTAS PWA ESPECÍFICAS ===
            '/manifest.json' => ['action' => 'manifest'],
            '/sw.js' => ['file' => 'pwa/sw.js', 'headers' => ['Content-Type: application/javascript']],
            '/offline' => ['file' => 'pwa/offline.html'],
            
            // === ROTAS API ===
            '/api' => ['action' => 'api_handler']
        ];
    }
    
    /**
     * Inicializa caminhos PWA (versões mobile)
     */
    private function initializePWAPaths() {
        $this->pwaPaths = [
            '/cliente/dashboard' => 'views/client/dashboard-pwa.php',
            '/cliente/extrato' => 'views/client/statement-pwa.php',
            '/cliente/cashback' => 'views/client/cashback-history-pwa.php',
            '/cliente/lojas' => 'views/client/partner-stores-pwa.php',
            '/cliente/perfil' => 'views/client/profile-pwa.php'
        ];
    }
    
    /**
     * Método principal de roteamento
     */
    public function route() {
        try {
            // Log da requisição
            $this->logRequest();
            
            // Verificar manutenção
            if ($this->isMaintenanceMode()) {
                $this->showMaintenancePage();
                return;
            }
            
            // Aplicar headers PWA se necessário
            if ($this->shouldUsePWA()) {
                $this->setPWAHeaders();
            }
            
            // Tratamento especial para manifest e service worker
            if ($this->handleSpecialRoutes()) {
                return;
            }
            
            // Verificar se a rota existe
            $route = $this->matchRoute();
            
            if (!$route) {
                $this->handle404();
                return;
            }
            
            // Verificar autenticação
            if (!$this->checkAuthentication($route)) {
                $this->redirectToLogin();
                return;
            }
            
            // Verificar permissões
            if (!$this->checkPermissions($route)) {
                $this->handle403();
                return;
            }
            
            // Decidir se usa versão PWA
            $finalFile = $this->decidePWAVersion($route);
            
            // Executar a rota
            $this->executeRoute($route, $finalFile);
            
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    /**
     * Verifica se deve usar PWA
     */
    private function shouldUsePWA() {
        $deviceInfo = $this->mobileDetector->getDeviceInfo();
        
        return ($deviceInfo['isMobile'] && $deviceInfo['supportsPWA']) ||
               $deviceInfo['isRunningAsPWA'] ||
               (isset($_GET['pwa']) && $_GET['pwa'] === '1');
    }
    
    /**
     * Define headers específicos para PWA
     */
    private function setPWAHeaders() {
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-PWA-Mode: 1');
        
        // Headers para instalação PWA
        if ($this->mobileDetector->isRunningAsPWA()) {
            header('X-PWA-Running: 1');
            header('X-Frame-Options: ALLOWALL');
        }
        
        // Headers para Service Worker
        header('Service-Worker-Allowed: /');
        
        // Meta tags dinâmicas para PWA
        $this->setPWAMetaTags();
    }
    
    /**
     * Define meta tags dinâmicas para PWA
     */
    private function setPWAMetaTags() {
        $deviceInfo = $this->mobileDetector->getDeviceInfo();
        
        // Viewport otimizado por dispositivo
        if ($deviceInfo['isPhone']) {
            header('X-Viewport: width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
        } elseif ($deviceInfo['isTablet']) {
            header('X-Viewport: width=device-width, initial-scale=1.0, maximum-scale=1.2');
        }
        
        // Theme color baseado no dispositivo
        if ($deviceInfo['isiOS']) {
            header('X-Apple-Mobile-Web-App-Capable: yes');
            header('X-Apple-Mobile-Web-App-Status-Bar-Style: default');
            header('X-Apple-Mobile-Web-App-Title: Klube Cash');
        }
        
        if ($deviceInfo['isAndroid']) {
            header('X-Mobile-Web-App-Capable: yes');
            header('X-Theme-Color: #FF7A00');
        }
    }
    
    /**
     * Trata rotas especiais (manifest, service worker)
     */
    private function handleSpecialRoutes() {
        switch ($this->requestUri) {
            case '/manifest.json':
                $this->serveManifest();
                return true;
                
            case '/sw.js':
                $this->serveServiceWorker();
                return true;
                
            case '/offline':
                $this->serveOfflinePage();
                return true;
                
            default:
                return false;
        }
    }
    
    /**
     * Serve o manifest.json
     */
    private function serveManifest() {
        header('Content-Type: application/manifest+json');
        header('Cache-Control: public, max-age=86400'); // 24 horas
        
        $manifestPath = __DIR__ . '/pwa/manifest.json';
        
        if (file_exists($manifestPath)) {
            readfile($manifestPath);
        } else {
            // Gerar manifest dinâmico
            $manifest = $this->generateDynamicManifest();
            echo json_encode($manifest, JSON_PRETTY_PRINT);
        }
    }
    
    /**
     * Gera manifest dinâmico
     */
    private function generateDynamicManifest() {
        return [
            "name" => "Klube Cash - Cashback Inteligente",
            "short_name" => "Klube Cash",
            "description" => "Transforme suas compras em dinheiro de volta",
            "start_url" => "/",
            "display" => "standalone",
            "orientation" => "portrait",
            "theme_color" => "#FF7A00",
            "background_color" => "#FFFFFF",
            "scope" => "/",
            "lang" => "pt-BR",
            "icons" => [
                [
                    "src" => "/assets/icons/icon-72x72.png",
                    "sizes" => "72x72",
                    "type" => "image/png",
                    "purpose" => "any maskable"
                ],
                [
                    "src" => "/assets/icons/icon-192x192.png",
                    "sizes" => "192x192",
                    "type" => "image/png",
                    "purpose" => "any maskable"
                ],
                [
                    "src" => "/assets/icons/icon-512x512.png",
                    "sizes" => "512x512",
                    "type" => "image/png",
                    "purpose" => "any maskable"
                ]
            ],
            "categories" => ["finance", "business", "shopping"],
            "shortcuts" => [
                [
                    "name" => "Dashboard",
                    "short_name" => "Dashboard",
                    "description" => "Acesso rápido ao painel principal",
                    "url" => "/cliente/dashboard?utm_source=pwa_shortcut",
                    "icons" => [
                        [
                            "src" => "/assets/icons/dashboard-icon.png",
                            "sizes" => "96x96"
                        ]
                    ]
                ],
                [
                    "name" => "Cashback",
                    "short_name" => "Cashback",
                    "description" => "Ver histórico de cashback",
                    "url" => "/cliente/cashback?utm_source=pwa_shortcut",
                    "icons" => [
                        [
                            "src" => "/assets/icons/cashback-icon.png",
                            "sizes" => "96x96"
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Serve o service worker
     */
    private function serveServiceWorker() {
        header('Content-Type: application/javascript');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Service-Worker-Allowed: /');
        
        $swPath = __DIR__ . '/pwa/sw.js';
        
        if (file_exists($swPath)) {
            readfile($swPath);
        } else {
            // Gerar service worker básico
            echo $this->generateBasicServiceWorker();
        }
    }
    
    /**
     * Gera service worker básico
     */
    private function generateBasicServiceWorker() {
        return "
const CACHE_NAME = 'klube-cash-v1';
const urlsToCache = [
    '/',
    '/offline',
    '/assets/css/main.css',
    '/assets/js/main.js',
    '/assets/icons/icon-192x192.png'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(urlsToCache))
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request)
            .then((response) => {
                return response || fetch(event.request);
            })
            .catch(() => {
                return caches.match('/offline');
            })
    );
});
        ";
    }
    
    /**
     * Serve a página offline
     */
    private function serveOfflinePage() {
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: public, max-age=3600'); // 1 hora
        
        $offlinePath = __DIR__ . '/pwa/offline.html';
        
        if (file_exists($offlinePath)) {
            readfile($offlinePath);
        } else {
            echo $this->generateOfflinePage();
        }
    }
    
    /**
     * Gera página offline básica
     */
    private function generateOfflinePage() {
        return '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klube Cash - Modo Offline</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
        .offline-container { max-width: 400px; margin: 0 auto; }
        .logo { width: 120px; margin-bottom: 30px; }
        h1 { color: #FF7A00; margin-bottom: 20px; }
        p { color: #666; line-height: 1.6; }
        .retry-btn { background: #FF7A00; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="offline-container">
        <img src="/assets/images/logo.png" alt="Klube Cash" class="logo">
        <h1>Você está offline</h1>
        <p>Parece que você perdeu a conexão com a internet. Algumas funcionalidades podem estar limitadas.</p>
        <p>Seus dados serão sincronizados automaticamente quando a conexão for restaurada.</p>
        <button class="retry-btn" onclick="window.location.reload()">Tentar Novamente</button>
    </div>
</body>
</html>';
    }
    
    /**
     * Busca por uma rota correspondente
     */
    private function matchRoute() {
        // Busca exata
        if (isset($this->routes[$this->requestUri])) {
            return $this->routes[$this->requestUri];
        }
        
        // Busca por patterns dinâmicos
        foreach ($this->routes as $pattern => $route) {
            if ($this->matchPattern($pattern, $this->requestUri)) {
                return $route;
            }
        }
        
        return null;
    }
    
    /**
     * Verifica se o pattern corresponde à URI
     */
    private function matchPattern($pattern, $uri) {
        // Converter pattern para regex
        $regex = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        return preg_match($regex, $uri);
    }
    
    /**
     * Verifica autenticação
     */
    private function checkAuthentication($route) {
        if (!isset($route['auth']) || !$route['auth']) {
            return true;
        }
        
        return $this->currentUser !== null;
    }
    
    /**
     * Verifica permissões de role
     */
    private function checkPermissions($route) {
        if (!isset($route['role'])) {
            return true;
        }
        
        if (!$this->currentUser) {
            return false;
        }
        
        return $this->currentUser['tipo'] === $route['role'];
    }
    
    /**
     * Decide se usa versão PWA
     */
    private function decidePWAVersion($route) {
        if (!$this->shouldUsePWA()) {
            return $route['file'] ?? null;
        }
        
        // Verificar se existe versão PWA
        if (isset($this->pwaPaths[$this->requestUri])) {
            $pwaFile = $this->pwaPaths[$this->requestUri];
            if (file_exists(__DIR__ . '/' . $pwaFile)) {
                return $pwaFile;
            }
        }
        
        return $route['file'] ?? null;
    }
    
    /**
     * Executa a rota
     */
    private function executeRoute($route, $file) {
        // Executar ação se especificada
        if (isset($route['action'])) {
            $this->executeAction($route['action']);
            return;
        }
        
        // Definir headers customizados
        if (isset($route['headers'])) {
            foreach ($route['headers'] as $header) {
                header($header);
            }
        }
        
        // Incluir arquivo
        if ($file && file_exists(__DIR__ . '/' . $file)) {
            // Disponibilizar variáveis para a view
            $deviceInfo = $this->mobileDetector->getDeviceInfo();
            $currentUser = $this->currentUser;
            $isPWA = $this->shouldUsePWA();
            
            include __DIR__ . '/' . $file;
        } else {
            $this->handle404();
        }
    }
    
    /**
     * Executa ações especiais
     */
    private function executeAction($action) {
        switch ($action) {
            case 'logout':
                $this->authController->logout();
                $this->redirect('/login?logout=1');
                break;
                
            case 'api_handler':
                $this->handleAPI();
                break;
                
            default:
                $this->handle404();
        }
    }
    
    /**
     * Trata requisições de API
     */
    private function handleAPI() {
        $apiPath = str_replace('/api', '', $this->requestUri);
        $apiFile = __DIR__ . '/api' . $apiPath . '.php';
        
        if (file_exists($apiFile)) {
            include $apiFile;
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'API endpoint not found']);
        }
    }
    
    /**
     * Redireciona para login
     */
    private function redirectToLogin() {
        $redirectUrl = urlencode($this->requestUri);
        $loginUrl = '/login';
        
        if ($this->shouldUsePWA()) {
            $loginUrl .= '?pwa=1';
        }
        
        if ($redirectUrl !== '/') {
            $loginUrl .= (strpos($loginUrl, '?') ? '&' : '?') . 'redirect=' . $redirectUrl;
        }
        
        $this->redirect($loginUrl);
    }
    
    /**
     * Redireciona
     */
    private function redirect($url, $code = 302) {
        header("Location: $url", true, $code);
        exit;
    }
    
    /**
     * Verifica modo de manutenção
     */
    private function isMaintenanceMode() {
        return file_exists(__DIR__ . '/maintenance.flag') && 
               !$this->isAdminUser();
    }
    
    /**
     * Verifica se é usuário admin
     */
    private function isAdminUser() {
        return $this->currentUser && $this->currentUser['tipo'] === 'admin';
    }
    
    /**
     * Mostra página de manutenção
     */
    private function showMaintenancePage() {
        http_response_code(503);
        header('Content-Type: text/html; charset=UTF-8');
        include __DIR__ . '/views/errors/maintenance.php';
    }
    
    /**
     * Trata erro 404
     */
    private function handle404() {
        http_response_code(404);
        
        if ($this->shouldUsePWA()) {
            include __DIR__ . '/views/errors/404-pwa.php';
        } else {
            include __DIR__ . '/views/errors/404.php';
        }
    }
    
    /**
     * Trata erro 403
     */
    private function handle403() {
        http_response_code(403);
        include __DIR__ . '/views/errors/403.php';
    }
    
    /**
     * Trata exceções
     */
    private function handleException($exception) {
        http_response_code(500);
        
        // Log do erro
        error_log("PWA Router Exception: " . $exception->getMessage());
        
        if (ENVIRONMENT === 'development') {
            echo '<pre>' . $exception->getTraceAsString() . '</pre>';
        } else {
            include __DIR__ . '/views/errors/500.php';
        }
    }
    
    /**
     * Log da requisição
     */
    private function logRequest() {
        if (!LOG_REQUESTS) return;
        
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $this->requestMethod,
            'uri' => $this->requestUri,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'direct',
            'is_mobile' => $this->mobileDetector->isMobile(),
            'supports_pwa' => $this->mobileDetector->supportsPWA(),
            'user_id' => $this->currentUser['id'] ?? 'anonymous'
        ];
        
        $logLine = json_encode($logData) . PHP_EOL;
        file_put_contents(__DIR__ . '/logs/requests.log', $logLine, FILE_APPEND | LOCK_EX);
    }
}

// === INICIALIZAÇÃO DO SISTEMA ===
try {
    // Verificar se o sistema está instalado
    if (!file_exists(__DIR__ . '/config/installed.flag')) {
        header('Location: /install.php');
        exit;
    }
    
    // Verificar HTTPS em produção
    if (ENVIRONMENT === 'production' && 
        (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on')) {
        $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header("Location: $redirectURL", true, 301);
        exit;
    }
    
    // Inicializar e executar roteador
    $router = new PWARouter();
    $router->route();
    
} catch (Exception $e) {
    // Log crítico do erro
    error_log("Critical PWA Error: " . $e->getMessage());
    
    // Página de erro genérica
    http_response_code(500);
    echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klube Cash - Erro Interno</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; background: #f5f5f5; }
        .error-container { background: white; padding: 40px; border-radius: 8px; max-width: 500px; margin: 0 auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #e74c3c; margin-bottom: 20px; }
        p { color: #666; line-height: 1.6; margin-bottom: 20px; }
        .btn { background: #FF7A00; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Oops! Algo deu errado</h1>
        <p>Encontramos um problema temporário. Nossa equipe já foi notificada e está trabalhando para resolver.</p>
        <p>Tente novamente em alguns minutos.</p>
        <a href="/" class="btn">Voltar ao Início</a>
    </div>
</body>
</html>';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isLoggedIn ? "Bem-vindo ao Klube Cash, " . htmlspecialchars($userName) : "Klube Cash - Transforme suas Compras em Dinheiro de Volta"; ?></title>
    
    <!-- Meta tags otimizadas -->
    <meta name="description" content="Klube Cash - O programa de cashback mais inteligente do Brasil. Receba dinheiro de volta em todas as suas compras. Cadastre-se grátis e comece a economizar hoje mesmo!">
    <meta name="keywords" content="cashback, dinheiro de volta, economia, programa de fidelidade, compras online, desconto, lojas parceiras">
    <meta name="author" content="Klube Cash">
    <meta name="robots" content="index, follow">
    
    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="assets/images/icons/KlubeCashLOGO.ico">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS INLINE SIMPLIFICADO -->
    <style>
        /* === RESET E BASE === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }

        /* === HEADER === */
        .modern-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid #e0e0e0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            height: 100%;
        }

        .main-navigation {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 100%;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            text-decoration: none;
        }

        .logo-image {
            height: 40px;
            width: auto;
        }

        .desktop-menu {
            display: flex;
            list-style: none;
            gap: 30px;
        }

        .nav-link {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #FF7A00;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* User Menu */
        .user-menu {
            position: relative;
        }

        .user-button {
            display: flex;
            align-items: center;
            gap: 10px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #FF7A00, #FF9A40);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }

        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            width: 200px;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 8px;
            display: none;
        }

        .user-dropdown.show {
            display: block;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            text-decoration: none;
            color: #333;
            border-radius: 6px;
            transition: background 0.2s ease;
        }

        .dropdown-item:hover {
            background: #f5f5f5;
        }

        /* Mobile Menu */
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
        }

        .hamburger-line {
            width: 25px;
            height: 3px;
            background: #333;
            margin: 3px 0;
            transition: 0.3s;
        }

        .mobile-menu {
            display: none;
            position: fixed;
            top: 80px;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e0e0e0;
            padding: 20px;
        }

        .mobile-menu.show {
            display: block;
        }

        .mobile-nav-list {
            list-style: none;
        }

        .mobile-nav-list li {
            margin: 15px 0;
        }

        .mobile-nav-link {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            font-size: 18px;
        }

        /* === BOTÕES === */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #FF7A00, #FF9A40);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 122, 0, 0.3);
        }

        .btn-ghost {
            background: transparent;
            color: #333;
            border: 2px solid #e0e0e0;
        }

        .btn-ghost:hover {
            border-color: #FF7A00;
            color: #FF7A00;
        }

        /* === LAYOUT PRINCIPAL === */
        .main-content {
            padding-top: 80px;
        }

        .section {
            padding: 80px 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* === HERO === */
        .hero {
            background: linear-gradient(135deg, #FF7A00 0%, #FF9A40 50%, #FFB366 100%);
            color: white;
            text-align: center;
            padding: 120px 0;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 40px;
            opacity: 0.95;
        }

        .hero-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 60px;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            max-width: 600px;
            margin: 0 auto;
            padding-top: 40px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #FFD700;
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* === SEÇÕES === */
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-badge {
            display: inline-block;
            padding: 8px 16px;
            background: rgba(255, 122, 0, 0.1);
            color: #FF7A00;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            color: #333;
        }

        .section-description {
            font-size: 1.1rem;
            color: #666;
            max-width: 600px;
            margin: 0 auto;
        }

        /* === GRID === */
        .grid {
            display: grid;
            gap: 30px;
        }

        .grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        .grid-4 {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }

        /* === CARDS === */
        .card {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: #FF7A00;
        }

        .card-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 122, 0, 0.1);
            color: #FF7A00;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
        }

        .card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #333;
        }

        .card p {
            color: #666;
            line-height: 1.6;
        }

        /* === LOJAS PARCEIRAS === */
        .partner-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .partner-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .partner-logo {
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
        }

        .store-logo-image {
            max-width: 70px;
            max-height: 70px;
            border-radius: 8px;
            object-fit: contain;
        }

        .store-logo-fallback {
            width: 70px;
            height: 70px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 800;
            color: white;
            margin: 0 auto;
        }

        .partner-info h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }

        .partner-category {
            display: inline-block;
            padding: 4px 12px;
            background: #f5f5f5;
            color: #666;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-bottom: 10px;
        }

        .partner-cashback {
            color: #FF7A00;
            font-weight: 700;
        }

        /* === CTA === */
        .cta {
            background: linear-gradient(135deg, #1a1a1a, #333);
            color: white;
            text-align: center;
            padding: 100px 0;
        }

        .cta h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 20px;
        }

        .cta p {
            font-size: 1.2rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }

        /* === FOOTER === */
        .footer {
            background: #1a1a1a;
            color: white;
            padding: 60px 0 20px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer h4 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: #FF7A00;
        }

        .footer ul {
            list-style: none;
        }

        .footer ul li {
            margin-bottom: 10px;
        }

        .footer a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: #FF7A00;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #333;
            color: #999;
        }

        /* === RESPONSIVO === */
        @media (max-width: 768px) {
            .desktop-menu {
                display: none;
            }

            .mobile-menu-toggle {
                display: flex;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .hero-actions {
                flex-direction: column;
                align-items: center;
            }

            .hero-stats {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .section-title {
                font-size: 2rem;
            }

            .btn {
                width: 100%;
                max-width: 300px;
            }
        }

        /* === UTILITÁRIOS === */
        .text-center { text-align: center; }
        .mb-0 { margin-bottom: 0; }
        .mb-20 { margin-bottom: 20px; }
        .mt-20 { margin-top: 20px; }

        /* === ANIMAÇÕES SIMPLES === */
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .bg-light {
            background: #f8f9fa;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="modern-header" id="mainHeader">
        <div class="header-container">
            <nav class="main-navigation">
                <!-- Logo -->
                <a href="<?php echo SITE_URL; ?>" class="brand-logo">
                    <img src="assets/images/logolaranja.png" alt="Klube Cash" class="logo-image">
                </a>
                
                <!-- Menu Desktop -->
                <ul class="desktop-menu">
                    <li><a href="#como-funciona" class="nav-link">Como Funciona</a></li>
                    <li><a href="#vantagens" class="nav-link">Vantagens</a></li>
                    <li><a href="#parceiros" class="nav-link">Parceiros</a></li>
                    <li><a href="#sobre" class="nav-link">Sobre</a></li>
                </ul>
                
                <!-- Ações do Header -->
                <div class="header-actions">
                    <?php if ($isLoggedIn): ?>
                        <div class="user-menu">
                            <button class="user-button" id="userMenuBtn">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                                </div>
                                <span><?php echo htmlspecialchars($userName); ?></span>
                            </button>
                            
                            <div class="user-dropdown" id="userDropdown">
                                <a href="<?php echo htmlspecialchars($dashboardURL); ?>" class="dropdown-item">
                                    Meu Painel
                                </a>
                                <a href="<?php echo SITE_URL; ?>/controllers/AuthController.php?action=logout" class="dropdown-item">
                                    Sair
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo LOGIN_URL; ?>" class="btn btn-ghost">Entrar</a>
                        
                    <?php endif; ?>
                </div>
                
                <!-- Botão Mobile -->
                <button class="mobile-menu-toggle" id="mobileMenuBtn">
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                    <span class="hamburger-line"></span>
                </button>
            </nav>
        </div>
        
        <!-- Menu Mobile -->
        <div class="mobile-menu" id="mobileMenu">
            <ul class="mobile-nav-list">
                <li><a href="#como-funciona" class="mobile-nav-link">Como Funciona</a></li>
                <li><a href="#vantagens" class="mobile-nav-link">Vantagens</a></li>
                <li><a href="#parceiros" class="mobile-nav-link">Parceiros</a></li>
                <li><a href="#sobre" class="mobile-nav-link">Sobre</a></li>
            </ul>
            
            <?php if (!$isLoggedIn): ?>
                <div style="margin-top: 20px;">
                    <a href="<?php echo LOGIN_URL; ?>" class="btn btn-ghost" style="margin-bottom: 10px;">Entrar</a>
                    <a href="<?php echo REGISTER_URL; ?>" class="btn btn-primary">Cadastrar Grátis</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <main class="main-content">
        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <?php if ($isLoggedIn): ?>
                    <h1>Olá, <?php echo htmlspecialchars($userName); ?>! 👋</h1>
                    <p>Continue economizando com inteligência. Explore suas oportunidades de cashback.</p>
                    <div class="hero-actions">
                        <a href="<?php echo htmlspecialchars($dashboardURL); ?>" class="btn btn-primary">
                            Acessar Minha Conta
                        </a>
                        <a href="#parceiros" class="btn btn-ghost">Ver Lojas Parceiras</a>
                    </div>
                <?php else: ?>
                    <h1>Transforme suas compras em dinheiro de volta</h1>
                    <p>O programa de cashback mais inteligente do Brasil. Cadastre-se gratuitamente e comece a receber dinheiro de volta em todas as suas compras.</p>
                    <div class="hero-actions">
                        <a href="<?php echo REGISTER_URL; ?>" class="btn btn-primary">
                            Começar Agora - É Grátis
                        </a>
                        <a href="#como-funciona" class="btn btn-ghost">Como Funciona?</a>
                    </div>
                <?php endif; ?>
                
                
            </div>
        </section>

        <!-- Como Funciona -->
        <section id="como-funciona" class="section">
            <div class="container">
                <div class="section-header">
                    <span class="section-badge">Processo Simples</span>
                    <h2 class="section-title">Como a Klube Cash Funciona?</h2>
                    <p class="section-description">
                        Três passos simples para começar a receber dinheiro de volta em todas as suas compras.
                    </p>
                </div>
                
                <div class="grid grid-3">
                    <div class="card fade-in">
                        <div class="card-icon">1</div>
                        <h3>Cadastre-se Gratuitamente</h3>
                        <p>Crie sua conta em menos de 2 minutos. É 100% gratuito e você não paga nada para participar do programa.</p>
                    </div>
                    
                    <div class="card fade-in">
                        <div class="card-icon">2</div>
                        <h3>Compre e Se Identifique</h3>
                        <p>Faça suas compras normalmente nas lojas parceiras e se identifique como membro Klube Cash no momento da compra.</p>
                    </div>
                    
                    <div class="card fade-in">
                        <div class="card-icon">3</div>
                        <h3>Receba Seu Cashback</h3>
                        <p>Uma porcentagem do valor das suas compras volta para sua conta Klube Cash. É crédito real que você pode usar!</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Vantagens -->
        <section id="vantagens" class="section bg-light">
            <div class="container">
                <div class="section-header">
                    <span class="section-badge">Por Que Escolher?</span>
                    <h2 class="section-title">Vantagens Exclusivas do Klube Cash</h2>
                    <p class="section-description">
                        Descubra porque somos a escolha número 1 de quem quer economizar de verdade
                    </p>
                </div>
                
                <div class="grid grid-3">
                    <div class="card fade-in">
                        <div class="card-icon">💰</div>
                        <h3>Cashback Real</h3>
                        <p>Crédito real que você terá na sua conta, não pontos que expiram ou vales que complicam sua vida.</p>
                    </div>
                    
                    <div class="card fade-in">
                        <div class="card-icon">🔒</div>
                        <h3>100% Seguro</h3>
                        <p>Plataforma criptografada e dados protegidos. Sua segurança é nossa prioridade máxima, e conformidade com a LGPD.</p>
                    </div>
                    
                    <div class="card fade-in">
                        <div class="card-icon">⚡</div>
                        <h3>Instantâneo</h3>
                        <p>Cashback processado rapidamente. Você vê o retorno do seu crédito em tempo real.</p>
                    </div>
                    
                    <div class="card fade-in">
                        <div class="card-icon">🛠️</div>
                        <h3>Suporte 24/7</h3>
                        <p>Equipe especializada sempre pronta para ajudar você com qualquer dúvida ou problema.</p>
                    </div>
                    
                    <div class="card fade-in">
                        <div class="card-icon">❤️</div>
                        <h3>Pagou, usou</h3>
                        <p>Use quando quiser, como quiser. Sem contratos longos ou obrigações chatas.</p>
                    </div>
                    
                    <div class="card fade-in">
                        <div class="card-icon">🏪</div>
                        <h3>Diversas Categorias em Expansão</h3>
                        <p>A cada dia, mais lojas estão chegando para ampliar suas escolhas.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Lojas Parceiras -->
        <section id="parceiros" class="section">
            <div class="container">
                <div class="section-header">
                    <span class="section-badge">Nossos Parceiros</span>
                    <h2 class="section-title">Onde Você Pode Usar o Klube Cash</h2>
                    <p class="section-description">
                        Descubra algumas das incríveis lojas parceiras onde você pode ganhar cashback
                    </p>
                </div>
                
                <?php if (!empty($partnerStores)): ?>
                    <div class="grid grid-4">
                        <?php foreach ($partnerStores as $store): ?>
                            <div class="partner-item fade-in">
                                <div class="partner-logo">
                                    <?php echo renderStoreLogo($store); ?>
                                </div>
                                <div class="partner-info">
                                    <h4><?php echo htmlspecialchars($store['nome_fantasia']); ?></h4>
                                    <?php if (!empty($store['categoria'])): ?>
                                        <span class="partner-category"><?php echo htmlspecialchars($store['categoria']); ?></span>
                                    <?php endif; ?>
                                    <!--<div class="partner-cashback">
                                        Cashback: <?php echo number_format($store['porcentagem_cashback'] ?? 5, 1); ?>%
                                    </div>-->
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-center mt-20">
                        
                        <a href="<?php echo STORE_REGISTER_URL; ?>" class="btn btn-primary">Quero Ser Parceiro</a>
                    </div>
                <?php else: ?>
                    <div class="text-center">
                        <h3>Em Breve: Lojas Incríveis!</h3>
                        <p>Estamos fechando parcerias com as melhores lojas para você.</p>
                        <a href="<?php echo STORE_REGISTER_URL; ?>" class="btn btn-primary">Seja o Primeiro Parceiro</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- CTA -->
        <section class="cta">
            <div class="container">
                <h2>Pronto para Começar a economizar Dinheiro?</h2>
                <p>Junte-se a milhares de brasileiros que já descobriram o segredo de transformar gastos em ganhos.</p>
                <a href="<?php echo REGISTER_URL; ?>" class="btn btn-primary">
                    Quero Meu Cashback Agora!
                </a>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <h4>Klube Cash</h4>
                    <p>Transformando suas compras em oportunidades de economia. O programa de cashback mais inteligente e confiável do Brasil.</p>
                </div>
                
                <div>
                    <h4>Links Rápidos</h4>
                    <ul>
                        <li><a href="#como-funciona">Como Funciona</a></li>
                        <li><a href="#vantagens">Vantagens</a></li>
                        <li><a href="#parceiros">Lojas Parceiras</a></li>
                        <li><a href="<?php echo STORE_REGISTER_URL; ?>">Seja Parceiro</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4>Legal</h4>
                    <ul>
                        <li><a href="#">Termos de Uso</a></li>
                        <li><a href="#">Política de Privacidade</a></li>
                        <li><a href="#">Política de Cookies</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4>Contato</h4>
                    <ul>
                        <li><a href="mailto:contato@klubecash.com">contato@klubecash.com</a></li>
                        <li><a href="tel:+5534999999999">(34) 9999-9999</a></li>
                        <li>Patos de Minas, MG</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Klube Cash. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript SIMPLIFICADO -->
    <script>
        // === FUNCIONALIDADES BÁSICAS ===
        document.addEventListener('DOMContentLoaded', function() {
            initMobileMenu();
            initUserMenu();
            initSmoothScroll();
        });

        // Menu Mobile
        function initMobileMenu() {
            const menuToggle = document.getElementById('mobileMenuBtn');
            const mobileMenu = document.getElementById('mobileMenu');
            
            if (!menuToggle || !mobileMenu) return;
            
            let isOpen = false;
            
            menuToggle.addEventListener('click', function() {
                isOpen = !isOpen;
                mobileMenu.classList.toggle('show', isOpen);
                
                // Animar hamburger
                const lines = menuToggle.querySelectorAll('.hamburger-line');
                if (isOpen) {
                    lines[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                    lines[1].style.opacity = '0';
                    lines[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
                } else {
                    lines[0].style.transform = '';
                    lines[1].style.opacity = '';
                    lines[2].style.transform = '';
                }
            });
            
            // Fechar ao clicar em links
            const mobileLinks = document.querySelectorAll('.mobile-nav-link');
            mobileLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (isOpen) {
                        menuToggle.click();
                    }
                });
            });
        }

        // Menu do Usuário
        function initUserMenu() {
            const userButton = document.getElementById('userMenuBtn');
            const userDropdown = document.getElementById('userDropdown');
            
            if (!userButton || !userDropdown) return;
            
            userButton.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('show');
            });
            
            // Fechar ao clicar fora
            document.addEventListener('click', function() {
                userDropdown.classList.remove('show');
            });
        }

        // Scroll Suave
        function initSmoothScroll() {
            const links = document.querySelectorAll('a[href^="#"]');
            
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        const headerHeight = 80;
                        const targetPosition = targetElement.offsetTop - headerHeight;
                        
                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });
        }

        // Animações simples on scroll
        function animateOnScroll() {
            const elements = document.querySelectorAll('.fade-in');
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, { threshold: 0.1 });
            
            elements.forEach(function(element) {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(element);
            });
        }

        // Inicializar animações
        if ('IntersectionObserver' in window) {
            animateOnScroll();
        }

        console.log('✅ Klube Cash carregado com sucesso!');
    </script>
</body>
</html>
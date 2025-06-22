<?php
/**
 * pwa-routes.php
 * Klube Cash - Sistema de Cashback
 * 
 * Sistema de roteamento específico para Progressive Web App (PWA)
 * Inclui rotas específicas, fallbacks offline e deep linking
 * 
 * @author Klube Cash Team
 * @version 2.0
 * @since 2024
 */

// Incluir dependências
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../utils/PWAUtils.php';

class PWARouter {
    
    private $routes = [];
    private $fallbacks = [];
    private $currentRoute = '';
    private $isOffline = false;
    private $deviceType = '';
    
    public function __construct() {
        $this->deviceType = PWAUtils::detectDevice();
        $this->isOffline = $this->checkOfflineMode();
        $this->currentRoute = $this->getCurrentRoute();
        $this->initializeRoutes();
        $this->initializeFallbacks();
    }
    
    /**
     * Inicializa todas as rotas PWA
     */
    private function initializeRoutes() {
        
        // === ROTAS DE AUTENTICAÇÃO ===
        $this->addRoute('GET', '', [$this, 'handleHome']);
        $this->addRoute('GET', '/', [$this, 'handleHome']);
        $this->addRoute('GET', '/login', [$this, 'handleLogin']);
        $this->addRoute('GET', '/register', [$this, 'handleRegister']);
        $this->addRoute('GET', '/recover-password', [$this, 'handleRecoverPassword']);
        
        // === ROTAS DO CLIENTE PWA ===
        $this->addRoute('GET', '/client/dashboard', [$this, 'handleClientDashboard']);
        $this->addRoute('GET', '/client/dashboard-pwa', [$this, 'handleClientDashboardPWA']);
        $this->addRoute('GET', '/client/statement', [$this, 'handleClientStatement']);
        $this->addRoute('GET', '/client/statement-pwa', [$this, 'handleClientStatementPWA']);
        $this->addRoute('GET', '/client/partner-stores', [$this, 'handleClientStores']);
        $this->addRoute('GET', '/client/partner-stores-pwa', [$this, 'handleClientStoresPWA']);
        $this->addRoute('GET', '/client/profile', [$this, 'handleClientProfile']);
        $this->addRoute('GET', '/client/profile-pwa', [$this, 'handleClientProfilePWA']);
        $this->addRoute('GET', '/client/cashback-history', [$this, 'handleCashbackHistory']);
        
        // === ROTAS DE TRANSAÇÕES ===
        $this->addRoute('GET', '/transaction/([0-9]+)', [$this, 'handleTransactionDetails']);
        $this->addRoute('GET', '/cashback/([0-9]+)', [$this, 'handleCashbackDetails']);
        
        // === ROTAS DE LOJAS ===
        $this->addRoute('GET', '/store/([0-9]+)', [$this, 'handleStoreDetails']);
        $this->addRoute('GET', '/stores/category/([a-zA-Z0-9-]+)', [$this, 'handleStoreCategory']);
        
        // === ROTAS DE NOTIFICAÇÕES ===
        $this->addRoute('GET', '/notifications', [$this, 'handleNotifications']);
        $this->addRoute('GET', '/notification/([0-9]+)', [$this, 'handleNotificationDetails']);
        
        // === ROTAS DE COMPARTILHAMENTO (Deep Links) ===
        $this->addRoute('GET', '/share/store/([0-9]+)', [$this, 'handleShareStore']);
        $this->addRoute('GET', '/share/cashback/([0-9]+)', [$this, 'handleShareCashback']);
        $this->addRoute('GET', '/invite/([a-zA-Z0-9]+)', [$this, 'handleInviteCode']);
        
        // === ROTAS PWA ESPECÍFICAS ===
        $this->addRoute('GET', '/pwa/install', [$this, 'handlePWAInstall']);
        $this->addRoute('GET', '/pwa/offline', [$this, 'handleOfflinePage']);
        $this->addRoute('GET', '/pwa/sync', [$this, 'handleOfflineSync']);
        
        // === ROTAS DE API PWA ===
        $this->addRoute('POST', '/api/pwa/sync', [$this, 'handleAPISync']);
        $this->addRoute('POST', '/api/pwa/register-subscription', [$this, 'handleRegisterSubscription']);
        $this->addRoute('POST', '/api/pwa/unregister-subscription', [$this, 'handleUnregisterSubscription']);
        $this->addRoute('GET', '/api/pwa/config', [$this, 'handlePWAConfig']);
        
        // === ROTAS DE ADMINISTRAÇÃO PWA ===
        $this->addRoute('GET', '/admin/dashboard-pwa', [$this, 'handleAdminDashboardPWA']);
        
        // === ROTAS DE LOJA PWA ===
        $this->addRoute('GET', '/store/dashboard-pwa', [$this, 'handleStoreDashboardPWA']);
    }
    
    /**
     * Inicializa fallbacks para modo offline
     */
    private function initializeFallbacks() {
        
        // Fallbacks para páginas principais
        $this->addFallback('/client/*', '/pwa/offline.html');
        $this->addFallback('/admin/*', '/pwa/offline.html');
        $this->addFallback('/store/*', '/pwa/offline.html');
        
        // Fallbacks para API
        $this->addFallback('/api/*', [$this, 'handleOfflineAPI']);
        
        // Fallback geral
        $this->addFallback('*', '/pwa/offline.html');
    }
    
    /**
     * Adiciona uma nova rota
     */
    private function addRoute($method, $pattern, $handler) {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }
    
    /**
     * Adiciona um fallback offline
     */
    private function addFallback($pattern, $handler) {
        $this->fallbacks[] = [
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }
    
    /**
     * Obtém a rota atual
     */
    private function getCurrentRoute() {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return rtrim($path, '/');
    }
    
    /**
     * Verifica se está em modo offline
     */
    private function checkOfflineMode() {
        // Verificar se o request veio do service worker
        return isset($_SERVER['HTTP_SERVICE_WORKER']) || 
               isset($_SERVER['HTTP_X_OFFLINE_REQUEST']);
    }
    
    /**
     * Processa a rota atual
     */
    public function route() {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = $this->currentRoute;
        
        PWAUtils::log("Roteamento PWA", [
            'method' => $method,
            'path' => $path,
            'device' => $this->deviceType,
            'offline' => $this->isOffline
        ]);
        
        // Se está offline, usar fallbacks
        if ($this->isOffline) {
            return $this->handleOfflineRoute($path);
        }
        
        // Processar rotas normais
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            
            $pattern = $route['pattern'];
            $regex = $this->patternToRegex($pattern);
            
            if (preg_match($regex, $path, $matches)) {
                array_shift($matches); // Remove a primeira match (string completa)
                return $this->executeHandler($route['handler'], $matches);
            }
        }
        
        // Rota não encontrada
        return $this->handle404();
    }
    
    /**
     * Converte padrão de rota para regex
     */
    private function patternToRegex($pattern) {
        if ($pattern === '' || $pattern === '/') {
            return '/^\/$/';
        }
        
        $pattern = str_replace('/', '\/', $pattern);
        $pattern = preg_replace('/\(([^)]+)\)/', '($1)', $pattern);
        return '/^' . $pattern . '$/';
    }
    
    /**
     * Executa o handler da rota
     */
    private function executeHandler($handler, $params = []) {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }
        
        if (is_string($handler)) {
            return $this->includeFile($handler);
        }
        
        return false;
    }
    
    /**
     * Manipula rotas offline
     */
    private function handleOfflineRoute($path) {
        foreach ($this->fallbacks as $fallback) {
            $pattern = str_replace('*', '.*', $fallback['pattern']);
            $regex = '/^' . str_replace('/', '\/', $pattern) . '$/';
            
            if (preg_match($regex, $path)) {
                return $this->executeHandler($fallback['handler']);
            }
        }
        
        return $this->handleOfflinePage();
    }
    
    // === HANDLERS DAS ROTAS ===
    
    /**
     * Handler para página inicial
     */
    public function handleHome() {
        // Detectar se usuário está logado
        if ($this->isUserLoggedIn()) {
            $userType = $this->getUserType();
            
            switch ($userType) {
                case 'client':
                    return $this->redirectToPWA('/client/dashboard-pwa');
                case 'admin':
                    return $this->redirectToPWA('/admin/dashboard-pwa');
                case 'store':
                    return $this->redirectToPWA('/store/dashboard-pwa');
            }
        }
        
        // Redirecionar para login PWA se mobile
        if (PWAUtils::isTouchDevice()) {
            return $this->includeFile('/views/auth/login-pwa.php');
        }
        
        return $this->includeFile('/views/public/index.php');
    }
    
    /**
     * Handler para login
     */
    public function handleLogin() {
        if (PWAUtils::isTouchDevice()) {
            return $this->includeFile('/views/auth/login-pwa.php');
        }
        return $this->includeFile('/views/auth/login.php');
    }
    
    /**
     * Handler para registro
     */
    public function handleRegister() {
        if (PWAUtils::isTouchDevice()) {
            return $this->includeFile('/views/auth/register-pwa.php');
        }
        return $this->includeFile('/views/auth/register.php');
    }
    
    /**
     * Handler para recuperação de senha
     */
    public function handleRecoverPassword() {
        if (PWAUtils::isTouchDevice()) {
            return $this->includeFile('/views/auth/recover-password-pwa.php');
        }
        return $this->includeFile('/views/auth/recover-password.php');
    }
    
    /**
     * Handler para dashboard do cliente
     */
    public function handleClientDashboard() {
        if (!$this->isUserLoggedIn() || $this->getUserType() !== 'client') {
            return $this->redirectToLogin();
        }
        
        // Redirecionar para versão PWA se mobile
        if (PWAUtils::isTouchDevice()) {
            return $this->redirectToPWA('/client/dashboard-pwa');
        }
        
        return $this->includeFile('/views/client/dashboard.php');
    }
    
    /**
     * Handler para dashboard PWA do cliente
     */
    public function handleClientDashboardPWA() {
        if (!$this->isUserLoggedIn() || $this->getUserType() !== 'client') {
            return $this->redirectToLogin();
        }
        
        return $this->includeFile('/views/client/dashboard-pwa.php');
    }
    
    /**
     * Handler para extrato do cliente
     */
    public function handleClientStatement() {
        if (!$this->isUserLoggedIn() || $this->getUserType() !== 'client') {
            return $this->redirectToLogin();
        }
        
        if (PWAUtils::isTouchDevice()) {
            return $this->redirectToPWA('/client/statement-pwa');
        }
        
        return $this->includeFile('/views/client/statement.php');
    }
    
    /**
     * Handler para extrato PWA do cliente
     */
    public function handleClientStatementPWA() {
        if (!$this->isUserLoggedIn() || $this->getUserType() !== 'client') {
            return $this->redirectToLogin();
        }
        
        return $this->includeFile('/views/client/statement-pwa.php');
    }
    
    /**
     * Handler para lojas parceiras
     */
    public function handleClientStores() {
        if (PWAUtils::isTouchDevice()) {
            return $this->redirectToPWA('/client/partner-stores-pwa');
        }
        
        return $this->includeFile('/views/client/partner-stores.php');
    }
    
    /**
     * Handler para lojas parceiras PWA
     */
    public function handleClientStoresPWA() {
        return $this->includeFile('/views/client/partner-stores-pwa.php');
    }
    
    /**
     * Handler para perfil do cliente
     */
    public function handleClientProfile() {
        if (!$this->isUserLoggedIn() || $this->getUserType() !== 'client') {
            return $this->redirectToLogin();
        }
        
        if (PWAUtils::isTouchDevice()) {
            return $this->redirectToPWA('/client/profile-pwa');
        }
        
        return $this->includeFile('/views/client/profile.php');
    }
    
    /**
     * Handler para perfil PWA do cliente
     */
    public function handleClientProfilePWA() {
        if (!$this->isUserLoggedIn() || $this->getUserType() !== 'client') {
            return $this->redirectToLogin();
        }
        
        return $this->includeFile('/views/client/profile-pwa.php');
    }
    
    /**
     * Handler para histórico de cashback
     */
    public function handleCashbackHistory() {
        if (!$this->isUserLoggedIn() || $this->getUserType() !== 'client') {
            return $this->redirectToLogin();
        }
        
        return $this->includeFile('/views/client/cashback-history.php');
    }
    
    /**
     * Handler para detalhes de transação
     */
    public function handleTransactionDetails($transactionId) {
        if (!$this->isUserLoggedIn()) {
            return $this->redirectToLogin();
        }
        
        // Validar acesso à transação
        if (!$this->canAccessTransaction($transactionId)) {
            return $this->handle403();
        }
        
        $_GET['transaction_id'] = $transactionId;
        
        if (PWAUtils::isTouchDevice()) {
            return $this->includeFile('/views/client/transaction-details-pwa.php');
        }
        
        return $this->includeFile('/views/client/transaction-details.php');
    }
    
    /**
     * Handler para detalhes de cashback
     */
    public function handleCashbackDetails($cashbackId) {
        if (!$this->isUserLoggedIn()) {
            return $this->redirectToLogin();
        }
        
        $_GET['cashback_id'] = $cashbackId;
        
        if (PWAUtils::isTouchDevice()) {
            return $this->includeFile('/views/client/cashback-details-pwa.php');
        }
        
        return $this->includeFile('/views/client/cashback-details.php');
    }
    
    /**
     * Handler para detalhes de loja
     */
    public function handleStoreDetails($storeId) {
        $_GET['store_id'] = $storeId;
        
        if (PWAUtils::isTouchDevice()) {
            return $this->includeFile('/views/public/store-details-pwa.php');
        }
        
        return $this->includeFile('/views/public/store-details.php');
    }
    
    /**
     * Handler para categoria de lojas
     */
    public function handleStoreCategory($category) {
        $_GET['category'] = $category;
        
        if (PWAUtils::isTouchDevice()) {
            return $this->includeFile('/views/public/store-category-pwa.php');
        }
        
        return $this->includeFile('/views/public/store-category.php');
    }
    
    /**
     * Handler para notificações
     */
    public function handleNotifications() {
        if (!$this->isUserLoggedIn()) {
            return $this->redirectToLogin();
        }
        
        if (PWAUtils::isTouchDevice()) {
            return $this->includeFile('/views/client/notifications-pwa.php');
        }
        
        return $this->includeFile('/views/client/notifications.php');
    }
    
    /**
     * Handler para detalhes de notificação
     */
    public function handleNotificationDetails($notificationId) {
        if (!$this->isUserLoggedIn()) {
            return $this->redirectToLogin();
        }
        
        $_GET['notification_id'] = $notificationId;
        return $this->includeFile('/views/client/notification-details.php');
    }
    
    /**
     * Handler para compartilhamento de loja
     */
    public function handleShareStore($storeId) {
        // Redirect para página da loja com parâmetros de compartilhamento
        $_GET['shared'] = 'true';
        $_GET['source'] = 'share';
        return $this->handleStoreDetails($storeId);
    }
    
    /**
     * Handler para compartilhamento de cashback
     */
    public function handleShareCashback($cashbackId) {
        $_GET['shared'] = 'true';
        $_GET['source'] = 'share';
        return $this->handleCashbackDetails($cashbackId);
    }
    
    /**
     * Handler para código de convite
     */
    public function handleInviteCode($inviteCode) {
        $_GET['invite_code'] = $inviteCode;
        
        if (PWAUtils::isTouchDevice()) {
            return $this->includeFile('/views/auth/register-pwa.php');
        }
        
        return $this->includeFile('/views/auth/register.php');
    }
    
    /**
     * Handler para instalação PWA
     */
    public function handlePWAInstall() {
        return $this->includeFile('/views/pwa/install.php');
    }
    
    /**
     * Handler para página offline
     */
    public function handleOfflinePage() {
        header('Content-Type: text/html; charset=UTF-8');
        return $this->includeFile('/pwa/offline.html');
    }
    
    /**
     * Handler para sincronização offline
     */
    public function handleOfflineSync() {
        if (!$this->isUserLoggedIn()) {
            return $this->sendJSONResponse(['error' => 'Unauthorized'], 401);
        }
        
        return $this->includeFile('/api/pwa/sync.php');
    }
    
    /**
     * Handler para API de sincronização
     */
    public function handleAPISync() {
        header('Content-Type: application/json');
        return $this->includeFile('/api/pwa/sync.php');
    }
    
    /**
     * Handler para registro de subscription push
     */
    public function handleRegisterSubscription() {
        header('Content-Type: application/json');
        return $this->includeFile('/api/pwa/notifications.php');
    }
    
    /**
     * Handler para cancelamento de subscription push
     */
    public function handleUnregisterSubscription() {
        header('Content-Type: application/json');
        return $this->includeFile('/api/pwa/notifications.php');
    }
    
    /**
     * Handler para configuração PWA
     */
    public function handlePWAConfig() {
        header('Content-Type: application/json');
        
        $config = PWAUtils::getJavaScriptConfig();
        echo $config;
        exit;
    }
    
    /**
     * Handler para dashboard admin PWA
     */
    public function handleAdminDashboardPWA() {
        if (!$this->isUserLoggedIn() || $this->getUserType() !== 'admin') {
            return $this->redirectToLogin();
        }
        
        return $this->includeFile('/views/admin/dashboard-pwa.php');
    }
    
    /**
     * Handler para dashboard loja PWA
     */
    public function handleStoreDashboardPWA() {
        if (!$this->isUserLoggedIn() || $this->getUserType() !== 'store') {
            return $this->redirectToLogin();
        }
        
        return $this->includeFile('/views/store/dashboard-pwa.php');
    }
    
    /**
     * Handler para API offline
     */
    public function handleOfflineAPI() {
        header('Content-Type: application/json');
        
        $response = [
            'status' => false,
            'message' => 'Você está offline. Dados serão sincronizados quando a conexão for restaurada.',
            'offline' => true,
            'retry_after' => 30
        ];
        
        echo json_encode($response);
        exit;
    }
    
    // === MÉTODOS AUXILIARES ===
    
    /**
     * Verifica se usuário está logado
     */
    private function isUserLoggedIn() {
        session_start();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Obtém tipo do usuário
     */
    private function getUserType() {
        session_start();
        return $_SESSION['user_type'] ?? null;
    }
    
    /**
     * Verifica se pode acessar transação
     */
    private function canAccessTransaction($transactionId) {
        // Implementar lógica de verificação de acesso
        session_start();
        $userId = $_SESSION['user_id'] ?? null;
        $userType = $_SESSION['user_type'] ?? null;
        
        // Admin pode ver todas
        if ($userType === 'admin') return true;
        
        // Implementar verificação específica baseada no tipo
        return true; // Placeholder
    }
    
    /**
     * Redireciona para login
     */
    private function redirectToLogin() {
        $loginUrl = PWAUtils::isTouchDevice() ? '/login' : '/login';
        return $this->redirect($loginUrl);
    }
    
    /**
     * Redireciona para versão PWA
     */
    private function redirectToPWA($path) {
        return $this->redirect($path);
    }
    
    /**
     * Executa redirecionamento
     */
    private function redirect($path) {
        $url = SITE_URL . $path;
        header("Location: $url");
        exit;
    }
    
    /**
     * Inclui arquivo de view
     */
    private function includeFile($filePath) {
        $fullPath = ROOT_DIR . $filePath;
        
        if (file_exists($fullPath)) {
            include $fullPath;
            return true;
        }
        
        return $this->handle404();
    }
    
    /**
     * Handler para 404
     */
    private function handle404() {
        http_response_code(404);
        
        if (PWAUtils::isTouchDevice()) {
            return $this->includeFile('/views/errors/404-pwa.php');
        }
        
        return $this->includeFile('/views/errors/404.php');
    }
    
    /**
     * Handler para 403
     */
    private function handle403() {
        http_response_code(403);
        
        if (PWAUtils::isTouchDevice()) {
            return $this->includeFile('/views/errors/403-pwa.php');
        }
        
        return $this->includeFile('/views/errors/403.php');
    }
    
    /**
     * Envia resposta JSON
     */
    private function sendJSONResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

// === EXECUÇÃO DO ROTEAMENTO ===

// Verificar se deve processar rota PWA
if (php_sapi_name() !== 'cli') {
    $router = new PWARouter();
    $router->route();
}

// === FUNÇÕES HELPER GLOBAIS ===

/**
 * Gera URL para rota PWA
 */
function pwa_route($route, $params = []) {
    $url = SITE_URL . '/' . ltrim($route, '/');
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}

/**
 * Gera URL de compartilhamento
 */
function share_url($type, $id, $extra = []) {
    $baseUrl = SITE_URL . "/share/{$type}/{$id}";
    
    if (!empty($extra)) {
        $baseUrl .= '?' . http_build_query($extra);
    }
    
    return $baseUrl;
}

/**
 * Gera URL de convite
 */
function invite_url($inviteCode) {
    return SITE_URL . "/invite/{$inviteCode}";
}

/**
 * Verifica se é rota PWA
 */
function is_pwa_route($route = null) {
    if ($route === null) {
        $route = $_SERVER['REQUEST_URI'] ?? '/';
    }
    
    return strpos($route, '-pwa') !== false || 
           strpos($route, '/pwa/') !== false ||
           PWAUtils::isTouchDevice();
}

/**
 * Redireciona para versão PWA da rota atual
 */
function redirect_to_pwa($currentRoute = null) {
    if ($currentRoute === null) {
        $currentRoute = $_SERVER['REQUEST_URI'] ?? '/';
    }
    
    // Converter rota normal para PWA
    $pwaRoute = str_replace(['/client/', '/admin/', '/store/'], 
                           ['/client/', '/admin/', '/store/'], 
                           $currentRoute);
    
    if (strpos($pwaRoute, '-pwa') === false) {
        $pwaRoute = str_replace('.php', '-pwa.php', $pwaRoute);
    }
    
    header("Location: " . SITE_URL . $pwaRoute);
    exit;
}
?>
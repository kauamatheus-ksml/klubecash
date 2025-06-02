<?php
/**
 * Constantes do sistema - Klube Cash v2.0
 * Configurações otimizadas para performance e SEO
 */

// === INFORMAÇÕES DO SISTEMA ===
define('SYSTEM_NAME', 'Klube Cash');
define('SYSTEM_VERSION', '2.0.0');
define('SITE_URL', 'https://klubecash.com');
define('ADMIN_EMAIL', 'contato@klubecash.com');

// === CORES DO TEMA ===
define('PRIMARY_COLOR', '#FF7A00');
define('SECONDARY_COLOR', '#1A1A1A');
define('SUCCESS_COLOR', '#10B981');
define('WARNING_COLOR', '#F59E0B');
define('DANGER_COLOR', '#EF4444');
define('INFO_COLOR', '#3B82F6');

// === DIRETÓRIOS ===
define('ROOT_DIR', dirname(__DIR__));
define('VIEWS_DIR', ROOT_DIR . '/views');
define('UPLOADS_DIR', ROOT_DIR . '/uploads');
define('LOGS_DIR', ROOT_DIR . '/logs');
define('ASSETS_DIR', ROOT_DIR . '/assets');

// === CONFIGURAÇÕES DE CASHBACK ===
define('DEFAULT_CASHBACK_TOTAL', 10.00);
define('DEFAULT_CASHBACK_CLIENT', 5.00);
define('DEFAULT_CASHBACK_ADMIN', 5.00);
define('DEFAULT_CASHBACK_STORE', 0.00);

// === STATUS ===
define('TRANSACTION_PENDING', 'pendente');
define('TRANSACTION_APPROVED', 'aprovado');
define('TRANSACTION_CANCELED', 'cancelado');
define('TRANSACTION_PAYMENT_PENDING', 'pagamento_pendente');

define('USER_ACTIVE', 'ativo');
define('USER_INACTIVE', 'inativo');
define('USER_BLOCKED', 'bloqueado');

define('USER_TYPE_CLIENT', 'cliente');
define('USER_TYPE_ADMIN', 'admin');
define('USER_TYPE_STORE', 'loja');

define('STORE_PENDING', 'pendente');
define('STORE_APPROVED', 'aprovado');
define('STORE_REJECTED', 'rejeitado');

// === CONFIGURAÇÕES DE SEGURANÇA ===
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_LIFETIME', 86400);
define('TOKEN_EXPIRATION', 7200);
// === OPENPIX CONFIGURAÇÕES ===
define('OPENPIX_API_KEY', 'Q2xpZW50X0lkXzg1YjYzYTI0LTJlOGEtNGMyYi04ZDNjLTQ2YWQyYzIyNGQ0ODpDbGllbnRfU2VjcmV0X1dSbjQxVEZ4QlZ1Zk9tTk9WN2UyNHIxMkNnQkhvZEUyRHhmcjg2bW91TzQ9'); // Chave da OpenPix
define('OPENPIX_WEBHOOK_URL', SITE_URL . '/api/openpix?action=webhook');

// === URLs OPENPIX ===
define('OPENPIX_CREATE_CHARGE_URL', SITE_URL . '/api/openpix?action=create_charge');
define('OPENPIX_CHECK_STATUS_URL', SITE_URL . '/api/openpix?action=status');
// === PAGINAÇÃO ===
define('ITEMS_PER_PAGE', 10);

// === LIMITES ===
define('MIN_TRANSACTION_VALUE', 5.00);
define('MIN_WITHDRAWAL_VALUE', 20.00);

// === URLs PRINCIPAIS ===
define('LOGIN_URL', SITE_URL . '/login');
define('REGISTER_URL', SITE_URL . '/registro');
define('RECOVER_PASSWORD_URL', SITE_URL . '/recuperar-senha');

// === URLs DO CLIENTE ===
define('CLIENT_DASHBOARD_URL', SITE_URL . '/cliente/dashboard');
define('CLIENT_STATEMENT_URL', SITE_URL . '/cliente/extrato');
define('CLIENT_STORES_URL', SITE_URL . '/cliente/lojas-parceiras');
define('CLIENT_PROFILE_URL', SITE_URL . '/cliente/perfil');
define('CLIENT_BALANCE_URL', SITE_URL . '/cliente/saldo');
define('CLIENT_ACTIONS_URL', SITE_URL . '/cliente/actions');

// === URLs DO ADMIN ===
define('ADMIN_DASHBOARD_URL', SITE_URL . '/admin/dashboard');
define('ADMIN_USERS_URL', SITE_URL . '/admin/usuarios');
define('ADMIN_STORES_URL', SITE_URL . '/admin/lojas');
define('ADMIN_TRANSACTIONS_URL', SITE_URL . '/admin/transacoes');
define('ADMIN_SETTINGS_URL', SITE_URL . '/admin/configuracoes');
define('ADMIN_TRANSACTION_DETAILS_URL', SITE_URL . '/admin/transacao');
define('ADMIN_REPORTS_URL', SITE_URL . '/admin/relatorios');
define('ADMIN_COMMISSIONS_URL', SITE_URL . '/admin/comissoes');
define('ADMIN_PAYMENTS_URL', SITE_URL . '/admin/pagamentos');
define('ADMIN_BALANCE_URL', SITE_URL . '/admin/saldo');

// === URLs DA LOJA ===
define('STORE_REGISTER_URL', SITE_URL . '/lojas/cadastro');
define('STORE_DASHBOARD_URL', SITE_URL . '/store/dashboard');
define('STORE_TRANSACTIONS_URL', SITE_URL . '/store/transacoes');
define('STORE_PENDING_TRANSACTIONS_URL', SITE_URL . '/store/transacoes-pendentes');
define('STORE_REGISTER_TRANSACTION_URL', SITE_URL . '/store/registrar-transacao');
define('STORE_BATCH_UPLOAD_URL', SITE_URL . '/store/upload-lote');
define('STORE_PAYMENT_URL', SITE_URL . '/store/pagamento');
define('STORE_PAYMENT_HISTORY_URL', SITE_URL . '/store/historico-pagamentos');
define('STORE_PROFILE_URL', SITE_URL . '/store/perfil');

// === CONFIGURAÇÕES DE ASSETS ===
define('ASSETS_VERSION', '2.0.0'); // Para cache busting
define('CDN_URL', SITE_URL); // Para futuros CDNs
define('CSS_URL', SITE_URL . '/assets/css');
define('JS_URL', SITE_URL . '/assets/js');
define('IMG_URL', SITE_URL . '/assets/images');

// === GOOGLE OAUTH ===
define('GOOGLE_CLIENT_ID', '662122339659-cj38e31a45cghrmnt4qq9slkroqh24n4s.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', 'https://klubecash.com/auth/google/callback');

define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USER_INFO_URL', 'https://www.googleapis.com/oauth2/v2/userinfo');
define('GOOGLE_PEOPLE_API_URL', 'https://people.googleapis.com/v1/people/me');

define('GOOGLE_AUTH_ENDPOINT', SITE_URL . '/auth/google/auth');
define('GOOGLE_CALLBACK_ENDPOINT', SITE_URL . '/auth/google/callback');

// === CONFIGURAÇÕES DE EMAIL ===
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', 'smtp.hostinger.com');
    define('SMTP_PORT', 465);
    define('SMTP_USERNAME', 'klubecash@klubecash.com');
    define('SMTP_PASSWORD', 'Aaku_2004@');
    define('SMTP_FROM_EMAIL', 'noreply@klubecash.com');
    define('SMTP_FROM_NAME', 'Klube Cash');
    define('SMTP_ENCRYPTION', 'ssl');
}

// === AMBIENTE ===
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'production');
    define('LOG_LEVEL', 'INFO');
}

// === EXPORTAÇÕES ===
define('EXPORTS_DIR', ROOT_DIR . '/exports');

// === CONFIGURAÇÕES DE SESSÃO ===
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 0);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1); // HTTPS obrigatório
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}
// === EMAIL MARKETING (ADICIONE ao final do seu constants.php) ===
define('ADMIN_EMAIL_MARKETING_URL', SITE_URL . '/admin/email-marketing');
define('ADMIN_EMAIL_TEMPLATES_URL', SITE_URL . '/admin/email-templates');
define('ADMIN_EMAIL_CAMPAIGNS_URL', SITE_URL . '/admin/email-campanhas');

// Configurações de envio em lote
define('EMAIL_BATCH_SIZE', 50); // Quantos emails enviar por vez
define('EMAIL_SEND_DELAY', 100000); // Pausa entre emails (microssegundos)
define('EMAIL_MAX_RETRIES', 3); // Tentativas máximas para emails falhados
// === PERFORMANCE CONFIGS ===
define('CACHE_DURATION', 3600); // 1 hora
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// === SEO E META ===
define('DEFAULT_META_TITLE', 'Klube Cash - Transforme suas Compras em Dinheiro de Volta');
define('DEFAULT_META_DESCRIPTION', 'O programa de cashback mais inteligente do Brasil. Receba dinheiro de volta em todas as suas compras. Cadastre-se grátis!');
define('DEFAULT_META_KEYWORDS', 'cashback, dinheiro de volta, economia, programa de fidelidade, compras online, desconto, lojas parceiras');

// === URLs ADICIONAIS ===
if (!defined('CLIENT_BALANCE_DETAILS_URL')) {
    define('CLIENT_BALANCE_DETAILS_URL', SITE_URL . '/cliente/saldo/detalhes');
}

if (!defined('CLIENT_DASHBOARD_API_URL')) {
    define('CLIENT_DASHBOARD_API_URL', SITE_URL . '/api/client/dashboard');
}

// === FUNÇÕES HELPER ===
function asset($path, $versioned = true) {
    $url = SITE_URL . '/assets/' . ltrim($path, '/');
    return $versioned ? $url . '?v=' . ASSETS_VERSION : $url;
}

function route($name, $params = []) {
    $routes = [
        'home' => SITE_URL,
        'login' => LOGIN_URL,
        'register' => REGISTER_URL,
        'client.dashboard' => CLIENT_DASHBOARD_URL,
        'admin.dashboard' => ADMIN_DASHBOARD_URL,
        'store.dashboard' => STORE_DASHBOARD_URL,
    ];
    
    $url = $routes[$name] ?? SITE_URL;
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    return $url;
}

function cdn($path) {
    return CDN_URL . '/' . ltrim($path, '/');
}

// === VALIDAÇÕES ===
function is_production() {
    return ENVIRONMENT === 'production';
}

function is_development() {
    return ENVIRONMENT === 'development';
}

function get_asset_url($file) {
    $hash = is_production() ? md5_file(ROOT_DIR . '/assets/' . $file) : time();
    return asset($file) . '?v=' . substr($hash, 0, 8);
}

?>
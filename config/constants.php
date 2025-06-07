<?php
/**
 * Configurações Globais do Klube Cash
 * Versão: 3.1.1 - OpenPix Integrado (Corrigido)
 */

// === INFORMAÇÕES DO SISTEMA ===
define('SYSTEM_NAME', 'Klube Cash');
define('SYSTEM_VERSION', '3.1.1');
define('SYSTEM_DESCRIPTION', 'Sistema de Cashback com PIX Automático');
define('SITE_URL', 'https://klubecash.com');
define('DOMAIN_NAME', 'klubecash.com');
define('ADMIN_EMAIL', 'contato@klubecash.com');

// === CONFIGURAÇÕES DE AMBIENTE ===
define('ENVIRONMENT', 'production');
define('DEBUG_MODE', false);
define('LOG_ERRORS', true);
define('LOG_WEBHOOK_CALLS', true);

// === BANCO DE DADOS ===
define('DB_HOST', 'localhost');
define('DB_NAME', 'u383946504_klubecash');
define('DB_USER', 'u383946504_klube');
define('DB_PASS', 'Klube@2024#Cash');
define('DB_CHARSET', 'utf8mb4');

// === CONFIGURAÇÕES OPENPIX (PRIORITÁRIO) ===
define('OPENPIX_API_KEY', 'Q2xpZW50X0lkXzIzOTVjN2E0LWM0N2ItNGZhMi1iMTU3LTc1NmZkOGY1MjNiNTpDbGllbnRfU2VjcmV0X2Q0N2E0YzIyLTczYWYtNGU0MC1iNmU3LTU0YmQyZjQ2OWY3Mw==');
define('OPENPIX_BASE_URL', 'https://api.openpix.com.br/api/v1');
define('OPENPIX_WEBHOOK_URL', SITE_URL . '/api/openpix?action=webhook');
define('OPENPIX_ENVIRONMENT', 'production');
define('OPENPIX_TIMEOUT', 30);
define('OPENPIX_USER_AGENT', 'KlubeCash/3.1 (OpenPix Integration)');
define('OPENPIX_DEBUG', false);
define('OPENPIX_ENABLE_SSL_VERIFICATION', true);
define('OPENPIX_MAX_RETRIES', 3);
define('OPENPIX_RETRY_DELAY', 1000);
define('LOG_OPENPIX_REQUESTS', true);

// === URLS OPENPIX ===
define('OPENPIX_CREATE_CHARGE_URL', SITE_URL . '/api/openpix.php?action=create_charge');
define('OPENPIX_CHECK_STATUS_URL', SITE_URL . '/api/openpix.php?action=status');
define('OPENPIX_TEST_URL', SITE_URL . '/api/openpix.php?action=test');

// === CONFIGURAÇÕES DE CASHBACK ===
define('DEFAULT_CASHBACK_TOTAL', 10.00);
define('DEFAULT_CASHBACK_CLIENT', 5.00);
define('DEFAULT_CASHBACK_ADMIN', 5.00);
define('DEFAULT_CASHBACK_STORE', 0.00);
define('CASHBACK_PORCENTAGEM_CLIENTE', 5.00);
define('CASHBACK_PORCENTAGEM_ADMIN', 5.00);
define('CASHBACK_PORCENTAGEM_LOJA', 0.00);
define('COMISSAO_TOTAL_LOJA', 10.00);

// === CONFIGURAÇÕES DE EMAIL ===
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_USERNAME', 'klubecash@klubecash.com');
define('SMTP_PASSWORD', 'Aaku_2004@');
define('SMTP_FROM_EMAIL', 'klubecash@klubecash.com');
define('SMTP_FROM_NAME', 'Klube Cash');
define('SMTP_ENCRYPTION', 'ssl');

// === CONFIGURAÇÕES MERCADO PAGO (BACKUP) ===
define('MP_PUBLIC_KEY', 'APP_USR-60bd9502-2ea5-46c8-80b5-765f10277949');
define('MP_ACCESS_TOKEN', 'APP_USR-8622491157025652-060223-01208b007f3c9b708958e846841e0a63-2320640278');
define('MP_WEBHOOK_URL', SITE_URL . '/api/mercadopago-webhook');
define('MP_WEBHOOK_SECRET', '21c03ffb0010adca8e57a0b9fcf30855191d44008baa16b757d9104ed5bfce5b');
define('MP_ENVIRONMENT', 'production');
define('MP_PLATFORM_ID', 'mp-ecom');
define('MP_CORPORATION_ID', 'klubecash');
define('MP_INTEGRATION_TYPE', 'direct');
define('MP_MAX_RETRIES', 3);
define('MP_TIMEOUT', 30);
define('MP_USER_AGENT', 'KlubeCash/2.1 (Mercado Pago Integration Optimized)');
define('MP_CREATE_PAYMENT_URL', SITE_URL . '/api/mercadopago?action=create_payment');
define('MP_CHECK_STATUS_URL', SITE_URL . '/api/mercadopago?action=status');
define('MP_BASE_URL', 'https://api.mercadopago.com');
define('MP_ENABLE_DEVICE_ID', true);
define('MP_ENABLE_FRAUD_PREVENTION', true);
define('MP_REQUIRE_PAYER_INFO', true);
define('MP_ENABLE_ADDRESS_VALIDATION', true);
define('MP_ENABLE_PHONE_VALIDATION', true);
define('MP_SDK_VERSION', 'v2');
define('MP_SDK_URL', 'https://sdk.mercadopago.com/js/v2');
define('MP_FRONTEND_SDK_ENABLED', true);
define('MP_BACKEND_SDK_ENABLED', true);
define('MP_PCI_COMPLIANCE_MODE', true);

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
define('EXPORTS_DIR', ROOT_DIR . '/exports');

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
define('CPF_REQUIRED', true);
define('JWT_SECRET_KEY', 'KlubeCash2024#JWT#Secret#Key#Ultra#Secure');
define('ENCRYPTION_KEY', 'KlubeCash2024#Encryption#Key#256bits');
define('SESSION_TIMEOUT', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_BLOCK_TIME', 900);

// === MÉTODOS DE PAGAMENTO ===
define('PAYMENT_METHOD_PIX', 'pix');
define('PAYMENT_METHOD_PIX_MP', 'pix_mercadopago');
define('PAYMENT_METHOD_TRANSFER', 'transferencia');
define('PAYMENT_METHOD_BOLETO', 'boleto');
define('PAYMENT_METHOD_CARD', 'cartao');
define('PAYMENT_METHOD_OTHER', 'outro');

// === PAGINAÇÃO E LIMITES ===
define('ITEMS_PER_PAGE', 10);
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
define('STORE_PAYMENT_PIX_URL', SITE_URL . '/store/pagamento-pix');

// === CONTROLADORES ===
define('STORE_CONTROLLER_URL', SITE_URL . '/controllers/StoreController.php');
define('TRANSACTION_CONTROLLER_URL', SITE_URL . '/controllers/TransactionController.php');
define('STORE_ACTIONS_URL', SITE_URL . '/controllers/store_actions.php');

// === CONFIGURAÇÕES DE ASSETS ===
define('ASSETS_VERSION', '3.1.1');
define('CDN_URL', SITE_URL);
define('CSS_URL', SITE_URL . '/assets/css');
define('JS_URL', SITE_URL . '/assets/js');
define('IMG_URL', SITE_URL . '/assets/images');
define('CSS_VERSION', '3.1.1');
define('JS_VERSION', '3.1.1');

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

// === CONFIGURAÇÕES DE 2FA ===
define('TWO_FA_VERIFY_URL', SITE_URL . '/verificar-2fa');
define('TWO_FA_CODE_LENGTH', 6);
define('TWO_FA_DEFAULT_EXPIRATION', 5);
define('TWO_FA_MAX_ATTEMPTS', 3);
define('TWO_FA_BLOCK_DURATION', 15);

// === EMAIL MARKETING ===
define('ADMIN_EMAIL_MARKETING_URL', SITE_URL . '/admin/email-marketing');
define('ADMIN_EMAIL_TEMPLATES_URL', SITE_URL . '/admin/email-templates');
define('ADMIN_EMAIL_CAMPAIGNS_URL', SITE_URL . '/admin/email-campanhas');
define('EMAIL_BATCH_SIZE', 50);
define('EMAIL_SEND_DELAY', 100000);
define('EMAIL_MAX_RETRIES', 3);

// === CONFIGURAÇÕES DE UPLOAD ===
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// === CONFIGURAÇÕES DE LOGS ===
define('LOG_PATH', __DIR__ . '/../logs/');
define('LOG_MAX_SIZE', 10 * 1024 * 1024);
define('LOG_RETENTION_DAYS', 30);
define('LOG_MP_REQUESTS', true);
define('LOG_MP_RESPONSES', true);
define('LOG_WEBHOOK_EVENTS', true);
define('LOG_QUALITY_METRICS', true);

// === PERFORMANCE ===
define('CACHE_DURATION', 3600);

// === SEO E META ===
define('DEFAULT_META_TITLE', 'Klube Cash - Transforme suas Compras em Dinheiro de Volta');
define('DEFAULT_META_DESCRIPTION', 'O programa de cashback mais inteligente do Brasil. Receba dinheiro de volta em todas as suas compras. Cadastre-se grátis!');
define('DEFAULT_META_KEYWORDS', 'cashback, dinheiro de volta, economia, programa de fidelidade, compras online, desconto, lojas parceiras');

// === CERTIFICADOS E SEGURANÇA ===
define('SSL_ENABLED', true);
define('TLS_VERSION', '1.2+');
define('PCI_DSS_COMPLIANT', true);
define('HTTPS_ONLY', true);

// === DEVICE ID CONFIGURATION ===
define('DEVICE_ID_PREFIX', 'klube_web_');
define('DEVICE_ID_ALGORITHM', 'enhanced');
define('DEVICE_ID_STORAGE', 'multi');

// === MENSAGENS DE VALIDAÇÃO ===
define('MSG_CPF_REQUIRED', 'CPF é obrigatório para completar seu perfil');
define('MSG_CPF_INVALID', 'CPF informado é inválido');
define('MSG_CPF_EXISTS', 'Este CPF já está cadastrado no sistema');

// === STATUS CODES ===
define('STATUS_SUCCESS', 200);
define('STATUS_CREATED', 201);
define('STATUS_BAD_REQUEST', 400);
define('STATUS_UNAUTHORIZED', 401);
define('STATUS_FORBIDDEN', 403);
define('STATUS_NOT_FOUND', 404);
define('STATUS_INTERNAL_ERROR', 500);

// === TIMEZONE ===
date_default_timezone_set('America/Sao_Paulo');

// === CONFIGURAÇÕES DE SESSÃO SEGURA ===
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 0);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    session_start();
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

function mp_log($message, $data = null) {
    if (LOG_MP_REQUESTS) {
        $logMessage = "[MP] " . $message;
        if ($data) {
            $logMessage .= " - Data: " . json_encode($data);
        }
        error_log($logMessage);
    }
}

function mp_is_enabled() {
    return defined('MP_ACCESS_TOKEN') && !empty(MP_ACCESS_TOKEN);
}

function mp_get_device_id() {
    if (!MP_ENABLE_DEVICE_ID) return null;
    
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $timestamp = time();
    
    return 'device_' . md5($userAgent . $ip . $timestamp);
}

function is_ssl_enabled() {
    return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
}

function get_tls_version() {
    return $_SERVER['SSL_PROTOCOL'] ?? 'unknown';
}

function validate_pci_compliance() {
    return is_ssl_enabled() && 
           (strpos(get_tls_version(), '1.2') !== false || 
            strpos(get_tls_version(), '1.3') !== false);
}
?>
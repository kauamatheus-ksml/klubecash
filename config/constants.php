<?php
/**
 * Configurações Globais do Klube Cash
 * Versão: 3.1 - OpenPix Integrado - Warning Corrigido
 */

// === INFORMAÇÕES DO SISTEMA ===
define('SYSTEM_NAME', 'Klube Cash');
define('SYSTEM_VERSION', '3.1.1');
define('SYSTEM_DESCRIPTION', 'Sistema de Cashback com PIX Automático');

// === CONFIGURAÇÕES DE AMBIENTE ===
define('ENVIRONMENT', 'production');
define('DEBUG_MODE', false);
define('LOG_ERRORS', true);
define('LOG_WEBHOOK_CALLS', true);

// === CONFIGURAÇÕES DE DOMÍNIO ===
define('SITE_URL', 'https://klubecash.com');
define('DOMAIN_NAME', 'klubecash.com');

// === CONFIGURAÇÕES DE BANCO DE DADOS ===
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

// === URLS OPENPIX ===
define('OPENPIX_CREATE_CHARGE_URL', SITE_URL . '/api/openpix.php?action=create_charge');
define('OPENPIX_CHECK_STATUS_URL', SITE_URL . '/api/openpix.php?action=status');
define('OPENPIX_TEST_URL', SITE_URL . '/api/openpix.php?action=test');

// === CONFIGURAÇÕES DE CASHBACK ===
define('CASHBACK_PORCENTAGEM_CLIENTE', 5.00);
define('CASHBACK_PORCENTAGEM_ADMIN', 5.00);
define('CASHBACK_PORCENTAGEM_LOJA', 0.00);
define('COMISSAO_TOTAL_LOJA', 10.00);

// === CONFIGURAÇÕES DE EMAIL ===
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'no-reply@klubecash.com');
define('SMTP_PASSWORD', 'Klube@2024#Email');
define('SMTP_ENCRYPTION', 'tls');
define('FROM_EMAIL', 'no-reply@klubecash.com');
define('FROM_NAME', 'Klube Cash');

// === CONFIGURAÇÕES MERCADO PAGO (BACKUP) ===
define('MP_PUBLIC_KEY', 'APP_USR-d87f9024-0bd9-47bc-853e-b89cd9d01abe');
define('MP_ACCESS_TOKEN', 'APP_USR-4899000077896259-060423-5df6ad11e3ea6e0e1ff9e8b14dbc3b1a-1881167082');
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

// === CONFIGURAÇÕES DE EMAIL (CONDICIONAL) ===
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', 'smtp.hostinger.com');
    define('SMTP_PORT', 465);
    define('SMTP_USERNAME', 'klubecash@klubecash.com');
    define('SMTP_PASSWORD', 'Aaku_2004@');
    define('SMTP_FROM_EMAIL', 'noreply@klubecash.com');
    define('SMTP_FROM_NAME', 'Klube Cash');
    define('SMTP_ENCRYPTION', 'ssl');
}

// === AMBIENTE (CONDICIONAL) ===
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'production');
    define('LOG_LEVEL', 'INFO');
}

// === DIRETÓRIOS (SEM DUPLICAÇÃO) ===
define('ROOT_DIR', __DIR__ . '/../');
define('UPLOADS_DIR', ROOT_DIR . 'uploads/');
define('EXPORTS_DIR', ROOT_DIR . 'exports/');

// === CONFIGURAÇÕES DE SEGURANÇA ===
define('JWT_SECRET_KEY', 'KlubeCash2024#JWT#Secret#Key#Ultra#Secure');
define('ENCRYPTION_KEY', 'KlubeCash2024#Encryption#Key#256bits');
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_TIMEOUT', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_BLOCK_TIME', 900);

// === CONFIGURAÇÕES DE UPLOAD ===
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// === CONFIGURAÇÕES DE LOGS ===
define('LOG_PATH', __DIR__ . '/../logs/');
define('LOG_MAX_SIZE', 10 * 1024 * 1024);
define('LOG_RETENTION_DAYS', 30);

// === TIMEZONE ===
date_default_timezone_set('America/Sao_Paulo');

// === VERSÕES DE ASSETS ===
define('CSS_VERSION', '3.1.1');
define('JS_VERSION', '3.1.1');

// === STATUS CODES CUSTOMIZADOS ===
define('STATUS_SUCCESS', 200);
define('STATUS_CREATED', 201);
define('STATUS_BAD_REQUEST', 400);
define('STATUS_UNAUTHORIZED', 401);
define('STATUS_FORBIDDEN', 403);
define('STATUS_NOT_FOUND', 404);
define('STATUS_INTERNAL_ERROR', 500);
?>
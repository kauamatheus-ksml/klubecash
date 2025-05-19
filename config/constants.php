<?php
/**
 * Constantes do sistema
 * Klube Cash - Sistema de Cashback
 */
define('PRIMARY_COLOR', '#FF7A00');
define('SECONDARY_COLOR', '#2c3e50');
define('SUCCESS_COLOR', '#28a745');
define('WARNING_COLOR', '#ffc107');
define('DANGER_COLOR', '#dc3545');
define('INFO_COLOR', '#17a2b8');

// Informações básicas do sistema
define('SYSTEM_NAME', 'Klube Cash');
define('SYSTEM_VERSION', '2.0.0');
define('SITE_URL', 'https://klubecash.com');
define('ADMIN_EMAIL', 'contato@klubecash.com');

// Diretórios
define('ROOT_DIR', dirname(__DIR__));
define('VIEWS_DIR', ROOT_DIR . '/views');
define('UPLOADS_DIR', ROOT_DIR . '/uploads');
define('LOGS_DIR', ROOT_DIR . '/logs');

// Configurações de cashback padrão (em porcentagem)
define('DEFAULT_CASHBACK_TOTAL', 5.00);  // 5% de cashback total
define('DEFAULT_CASHBACK_CLIENT', 3.00); // 3% para o cliente
define('DEFAULT_CASHBACK_ADMIN', 1.00);  // 1% para o administrador
define('DEFAULT_CASHBACK_STORE', 1.00);  // 1% para a loja

// Status de transação
define('TRANSACTION_PENDING', 'pendente');
define('TRANSACTION_APPROVED', 'aprovado');
define('TRANSACTION_CANCELED', 'cancelado');
define('TRANSACTION_PAYMENT_PENDING', 'pagamento_pendente');

// Status de usuário
define('USER_ACTIVE', 'ativo');
define('USER_INACTIVE', 'inativo');
define('USER_BLOCKED', 'bloqueado');

// Tipos de usuário
define('USER_TYPE_CLIENT', 'cliente');
define('USER_TYPE_ADMIN', 'admin');
define('USER_TYPE_STORE', 'loja');

// Status de loja
define('STORE_PENDING', 'pendente');
define('STORE_APPROVED', 'aprovado');
define('STORE_REJECTED', 'rejeitado');

// Configurações de segurança
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_LIFETIME', 86400); // 24 horas em segundos
define('TOKEN_EXPIRATION', 7200);  // 2 horas em segundos

// Configurações de paginação
define('ITEMS_PER_PAGE', 10);

// Limites de valor
define('MIN_TRANSACTION_VALUE', 5.00);  // Valor mínimo de transação: R$ 5,00
define('MIN_WITHDRAWAL_VALUE', 20.00);  // Valor mínimo para saque: R$ 20,00

// Caminhos de URL
define('LOGIN_URL', SITE_URL . '/login');
define('REGISTER_URL', SITE_URL . '/registro');
define('RECOVER_PASSWORD_URL', SITE_URL . '/recuperar-senha');
define('CLIENT_DASHBOARD_URL', SITE_URL . '/cliente/dashboard');
define('ADMIN_DASHBOARD_URL', SITE_URL . '/admin/dashboard');

// URLs adicionais para cliente
define('CLIENT_STATEMENT_URL', SITE_URL . '/cliente/extrato');
define('CLIENT_STORES_URL', SITE_URL . '/cliente/lojas-parceiras');
define('CLIENT_PROFILE_URL', SITE_URL . '/cliente/perfil');
// URLs do cliente
define('CLIENT_BALANCE_URL', SITE_URL . '/cliente/saldo');
// URLs de ações
define('CLIENT_ACTIONS_URL', SITE_URL . '/cliente/actions');

// URLs adicionais para admin
define('ADMIN_USERS_URL', SITE_URL . '/admin/usuarios');
define('ADMIN_STORES_URL', SITE_URL . '/admin/lojas');
define('ADMIN_TRANSACTIONS_URL', SITE_URL . '/admin/transacoes');
define('ADMIN_SETTINGS_URL', SITE_URL . '/admin/configuracoes');
define('ADMIN_TRANSACTION_DETAILS_URL', SITE_URL . '/admin/transacao');
define('ADMIN_REPORTS_URL', SITE_URL . '/admin/relatorios');
define('ADMIN_COMMISSIONS_URL', SITE_URL . '/admin/comissoes');
define('ADMIN_PAYMENTS_URL', SITE_URL . '/admin/pagamentos');

// URLs de loja
define('STORE_REGISTER_URL', SITE_URL . '/lojas/cadastro');
define('STORE_DASHBOARD_URL', SITE_URL . '/store/dashboard');
define('STORE_TRANSACTIONS_URL', SITE_URL . '/store/transacoes');
define('STORE_PENDING_TRANSACTIONS_URL', SITE_URL . '/store/transacoes-pendentes');
define('STORE_REGISTER_TRANSACTION_URL', SITE_URL . '/store/registrar-transacao');
define('STORE_BATCH_UPLOAD_URL', SITE_URL . '/store/upload-lote');
define('STORE_PAYMENT_URL', SITE_URL . '/store/pagamento');
define('STORE_PAYMENT_HISTORY_URL', SITE_URL . '/store/historico-pagamentos');
define('STORE_PROFILE_URL', SITE_URL . '/store/perfil');

// Diretório para exportação
define('EXPORTS_DIR', ROOT_DIR . '/exports');

// Configurações de email (adicionar se não existirem)
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', 'smtp.hostinger.com');
    define('SMTP_PORT', 465);
    define('SMTP_USERNAME', 'klubecash@klubecash.com');
    define('SMTP_PASSWORD', 'Aaku_2004@');
    define('SMTP_FROM_EMAIL', 'noreply@klubecash.com');
    define('SMTP_FROM_NAME', 'Klube Cash');
    define('SMTP_ENCRYPTION', 'ssl');
}

// Log level
if (!defined('LOG_LEVEL')) {
    define('LOG_LEVEL', 'INFO');
}

// Environment
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'production');
}



// Configurações de sessão
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 0);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Mude para 1 se usar HTTPS
    session_start();
}
?>


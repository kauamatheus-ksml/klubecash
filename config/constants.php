<?php
/**
 * Constantes do sistema
 * Klube Cash - Sistema de Cashback
 */

// Informações básicas do sistema
define('SYSTEM_NAME', 'Klube Cash');
define('SYSTEM_VERSION', '1.0.0');
define('SITE_URL', 'https://klubecash.com');
define('ADMIN_EMAIL', 'admin@klubecash.com');

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

// URLs adicionais para admin
define('ADMIN_USERS_URL', SITE_URL . '/admin/usuarios');
define('ADMIN_STORES_URL', SITE_URL . '/admin/lojas');
define('ADMIN_TRANSACTIONS_URL', SITE_URL . '/admin/transacoes');
define('ADMIN_SETTINGS_URL', SITE_URL . '/admin/configuracoes');

// URLs de loja
define('STORE_REGISTER_URL', SITE_URL . '/lojas/cadastro');
// Adicionar estas constantes em constants.php


// Adicionar após as definições de URLs de admin existentes
define('ADMIN_TRANSACTION_DETAILS_URL', SITE_URL . '/admin/transacao');


// Adicionar nas URLs para admin
define('ADMIN_REPORTS_URL', SITE_URL . '/admin/relatorios');

// URL para o dashboard da loja
define('STORE_DASHBOARD_URL', SITE_URL . '/store/dashboard');

// URLs adicionais para loja
define('STORE_TRANSACTIONS_URL', SITE_URL . '/store/transacoes');
define('STORE_PENDING_TRANSACTIONS_URL', SITE_URL . '/store/transacoes-pendentes');
define('STORE_REGISTER_TRANSACTION_URL', SITE_URL . '/store/registrar-transacao');
define('STORE_BATCH_UPLOAD_URL', SITE_URL . '/store/upload-lote');
define('STORE_PAYMENT_URL', SITE_URL . '/store/pagamento');
define('STORE_PAYMENT_HISTORY_URL', SITE_URL . '/store/historico-pagamentos');

// URLs adicionais para admin
define('ADMIN_COMMISSIONS_URL', SITE_URL . '/admin/comissoes');
define('ADMIN_PAYMENTS_URL', SITE_URL . '/admin/pagamentos');


// Status adicionais de transação
define('TRANSACTION_PAYMENT_PENDING', 'pagamento_pendente');  // Quando pagamento foi registrado mas ainda não aprovado

// Diretório para exportação
define('EXPORTS_DIR', ROOT_DIR . '/exports');
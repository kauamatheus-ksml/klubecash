<?php
// Adicionar no início do constants.php
if (!defined('ROOT_DIR')) {
    define('ROOT_DIR', dirname(__DIR__));
}

// Configurações de erro para produção
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'production');
}

if (ENVIRONMENT === 'production') {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_DIR . '/logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Verificar se diretório de logs existe
if (!is_dir(ROOT_DIR . '/logs')) {
    mkdir(ROOT_DIR . '/logs', 0755, true);
}

// Restante do arquivo constants.php continua igual...
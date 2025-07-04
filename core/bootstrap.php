<?php
// public_html/core/bootstrap.php - Arquivo de inicialização centralizado

// ===============================================================
// 1. DEFINIÇÃO DE ROOT_PATH (robusta, direto aqui)
// Isso garante que ROOT_PATH será definido, independentemente de onde bootstrap.php seja incluído.
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/'); // Define ROOT_PATH como public_html/
    // O dirname(__DIR__) de 'public_html/core/bootstrap.php' é 'public_html/core'
    // Adicionando '/../' ou ajustando: dirname(dirname(__FILE__)) . '/' seria 'public_html/'
    // Ou, para ser SUPER SEGURO, use o caminho absoluto que você já obteve:
    // define('ROOT_PATH', '/home/u383946504/domains/klubecash.com/public_html/');
    // Vamos usar o dirname(dirname(__FILE__)) para que seja relativo e mais portátil.
    define('ROOT_PATH_AUTO', dirname(dirname(__FILE__)) . '/');
}

// Agora que ROOT_PATH_AUTO está definido, podemos usá-lo
if (!defined('ROOT_PATH')) { // Se ainda não definido por um método mais prioritário
    define('ROOT_PATH', ROOT_PATH_AUTO);
}
// ===============================================================

// Ativar exibição de erros PHP e relatório completo para depuração (REMOVA EM PRODUÇÃO!)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar captura de saída em buffer para evitar problemas com headers e para logar tudo
// Se o ob_start() for chamado aqui, não precisará ser chamado nos scripts da API.
// ob_start(); // Pode causar problemas se APIs já chamam. Melhor deixar em cada API.

// --- Função de Log Personalizado ---
function api_log($message) {
    // Loga em um arquivo acessível via ROOT_PATH
    $log_dir = ROOT_PATH . 'api2/logs/'; // Defina o diretório de logs explicitamente
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true); // Cria o diretório se não existir
    }
    $log_file = $log_dir . 'api_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}
api_log("BOOTSTRAP: Script de inicialização carregado. ROOT_PATH: " . ROOT_PATH);


// ===============================================================
// 2. INCLUIR TODAS AS DEPENDÊNCIAS ESSENCIAIS APENAS UMA VEZ
// ===============================================================

// Inclui constants.php (agora que ROOT_PATH está definido)
// É importante que constants.php use 'if (!defined())' para cada constante para evitar warnings.
require_once ROOT_PATH . 'config/constants.php';
api_log("BOOTSTRAP: config/constants.php incluído.");

// Inclui database.php
require_once ROOT_PATH . 'config/database.php';
api_log("BOOTSTRAP: config/database.php incluído.");

// Inclui utilitários e controladores (APENAS AQUI)
require_once ROOT_PATH . 'controllers/AuthController.php';
api_log("BOOTSTRAP: controllers/AuthController.php incluído.");

require_once ROOT_PATH . 'utils/Email.php';
api_log("BOOTSTRAP: utils/Email.php incluído.");

require_once ROOT_PATH . 'utils/Validator.php'; // Se Validator.php for usado
api_log("BOOTSTRAP: utils/Validator.php incluído.");

api_log("BOOTSTRAP: Todas as dependências principais carregadas.");

// Você pode adicionar qualquer outra configuração global aqui.
?>
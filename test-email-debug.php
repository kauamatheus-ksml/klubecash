<?php
// test-email-debug.php - PÁGINA TEMPORÁRIA PARA DIAGNÓSTICO

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico do Sistema de Email</h1>";

// Teste 1: Verificar se as constantes estão definidas
echo "<h2>📋 1. Constantes de Email:</h2>";
require_once __DIR__ . '/config/constants.php';

$constants = [
    'SMTP_HOST' => defined('SMTP_HOST') ? SMTP_HOST : 'NÃO DEFINIDO',
    'SMTP_PORT' => defined('SMTP_PORT') ? SMTP_PORT : 'NÃO DEFINIDO',
    'SMTP_USERNAME' => defined('SMTP_USERNAME') ? SMTP_USERNAME : 'NÃO DEFINIDO',
    'SMTP_PASSWORD' => defined('SMTP_PASSWORD') ? '***OCULTO***' : 'NÃO DEFINIDO',
    'SMTP_FROM_EMAIL' => defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'NÃO DEFINIDO',
    'SMTP_FROM_NAME' => defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'NÃO DEFINIDO',
    'SMTP_ENCRYPTION' => defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'NÃO DEFINIDO',
];

foreach ($constants as $name => $value) {
    echo "<p><strong>$name:</strong> $value</p>";
}

// Teste 2: Verificar PHPMailer
echo "<h2>📦 2. Verificação do PHPMailer:</h2>";

// Verificar Composer
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<p>✅ Composer autoload encontrado</p>";
    require_once __DIR__ . '/vendor/autoload.php';
    
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "<p>✅ PHPMailer carregado via Composer</p>";
        $phpmailer_loaded = true;
    } else {
        echo "<p>❌ PHPMailer NÃO encontrado via Composer</p>";
        $phpmailer_loaded = false;
    }
} else {
    echo "<p>❌ Composer não encontrado</p>";
    $phpmailer_loaded = false;
}

// Verificar carregamento manual
if (!$phpmailer_loaded) {
    $manual_paths = [
        __DIR__ . '/libs/PHPMailer/src/PHPMailer.php',
        __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php'
    ];
    
    foreach ($manual_paths as $path) {
        if (file_exists($path)) {
            echo "<p>✅ PHPMailer encontrado em: $path</p>";
            $phpmailer_loaded = true;
            break;
        } else {
            echo "<p>❌ PHPMailer NÃO encontrado em: $path</p>";
        }
    }
}

// Teste 3: Testar carregamento da classe Email
echo "<h2>🔧 3. Teste da Classe Email:</h2>";

try {
    require_once __DIR__ . '/utils/Email.php';
    echo "<p>✅ Classe Email carregada</p>";
    
    // Testar método de conexão
    echo "<p>📡 Testando conexão SMTP...</p>";
    $result = Email::testConnection();
    
    echo "<pre>" . print_r($result, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro ao carregar classe Email: " . $e->getMessage() . "</p>";
    echo "<p>📍 Arquivo: " . $e->getFile() . "</p>";
    echo "<p>📍 Linha: " . $e->getLine() . "</p>";
}

echo "<h2>🎯 4. Solução:</h2>";
if (!$phpmailer_loaded) {
    echo "<p>❌ <strong>PROBLEMA:</strong> PHPMailer não está instalado corretamente</p>";
    echo "<p>📋 <strong>EXECUTAR:</strong> <code>composer require phpmailer/phpmailer</code></p>";
} else {
    echo "<p>✅ PHPMailer OK - Verificar configurações SMTP</p>";
}
?>
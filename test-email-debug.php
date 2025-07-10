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


echo "<h2>🧪 5. Teste Detalhado de Envio:</h2>";

try {
    // Habilitar logs detalhados
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    echo "<p>📧 Iniciando teste detalhado...</p>";
    
    $testEmail = 'seuemail@gmail.com'; // SUBSTITUA pelo seu email
    $testToken = 'test_token_' . time();
    
    // Teste 1: Chamar método diretamente
    echo "<p>🔧 Teste 1: Chamando sendPasswordRecovery diretamente...</p>";
    
    $result = Email::sendPasswordRecovery($testEmail, 'Usuário Teste', $testToken);
    
    echo "<p>Resultado sendPasswordRecovery: " . ($result ? "✅ TRUE" : "❌ FALSE") . "</p>";
    
    // Teste 2: Chamar método send() diretamente
    echo "<p>🔧 Teste 2: Chamando método send() diretamente...</p>";
    
    $simpleMessage = "<h2>Teste direto</h2><p>Este é um teste do método send().</p>";
    $directResult = Email::send($testEmail, 'Teste Direto - Klube Cash', $simpleMessage, 'Teste');
    
    echo "<p>Resultado send direto: " . ($directResult ? "✅ TRUE" : "❌ FALSE") . "</p>";
    
    // Teste 3: Capturar logs em tempo real
    echo "<p>🔧 Teste 3: Capturando erros em tempo real...</p>";
    
    // Criar handler de erro personalizado
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        echo "<p>⚠️ <strong>ERRO PHP:</strong> $errstr em $errfile linha $errline</p>";
    });
    
    // Tentar novamente com captura de erros
    ob_start();
    $finalResult = Email::send($testEmail, 'Teste Final - Klube Cash', $simpleMessage, 'Teste Final');
    $output = ob_get_clean();
    
    echo "<p>Resultado final: " . ($finalResult ? "✅ TRUE" : "❌ FALSE") . "</p>";
    
    if (!empty($output)) {
        echo "<p>📋 <strong>Output capturado:</strong></p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ <strong>EXCEÇÃO CAPTURADA:</strong> " . $e->getMessage() . "</p>";
    echo "<p>📍 <strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p>📍 <strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "<p>📋 <strong>Stack Trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

// Verificar logs de erro
echo "<h2>📋 7. Últimos Logs de Erro:</h2>";
$errorLogFile = '/home/u383946504/domains/klubecash.com/public_html/error_log';

if (file_exists($errorLogFile)) {
    $logContent = file_get_contents($errorLogFile);
    $logLines = explode("\n", $logContent);
    $recentLines = array_slice($logLines, -20); // Últimas 20 linhas
    
    echo "<p>📄 <strong>Últimas 20 linhas do error_log:</strong></p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow-y: scroll;'>";
    foreach ($recentLines as $line) {
        if (!empty(trim($line))) {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p>❌ Arquivo error_log não encontrado</p>";
}

// Teste adicional: Verificar se as funções mail() funcionam
echo "<h2>🔧 8. Teste da Função mail() Nativa do PHP:</h2>";

$nativeResult = mail($testEmail, 'Teste Nativo PHP', 'Este é um teste da função mail() nativa do PHP.', 'From: noreply@klubecash.com');

echo "<p>Resultado mail() nativo: " . ($nativeResult ? "✅ TRUE" : "❌ FALSE") . "</p>";
?>
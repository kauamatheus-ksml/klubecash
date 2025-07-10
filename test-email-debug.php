<?php
// test-email-debug.php - DIAGNÓSTICO ESPECÍFICO DO PHPMAILER

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnóstico Específico do PHPMailer</h1>";

require_once __DIR__ . '/config/constants.php';

// Carregamento direto do PHPMailer
require_once __DIR__ . '/libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/libs/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "<h2>🧪 Teste Direto do PHPMailer (SEM classe Email):</h2>";

try {
    $testEmail = 'kauamatheus920@gmail.com'; // SUBSTITUA pelo seu email
    
    echo "<p>📧 Criando instância do PHPMailer...</p>";
    
    $mail = new PHPMailer(true); // true = exceções habilitadas
    
    echo "<p>✅ PHPMailer instanciado</p>";
    
    // Configurações SMTP (exatamente como funcionou no teste de conexão)
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'klubecash@klubecash.com';
    $mail->Password = 'Aaku_2004@';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    
    // Configurações SSL
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    
    // HABILITANDO DEBUG COMPLETO
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = function($str, $level) {
        echo "<div style='background: #f0f0f0; padding: 5px; margin: 2px; border-left: 3px solid #007cba;'>";
        echo "<strong>Debug Level $level:</strong> " . htmlspecialchars($str);
        echo "</div>";
    };
    
    $mail->Timeout = 30;
    
    echo "<p>✅ Configurações SMTP definidas</p>";
    
    // Remetente e destinatário
    $mail->setFrom('noreply@klubecash.com', 'Klube Cash');
    $mail->addReplyTo('noreply@klubecash.com', 'Klube Cash');
    $mail->addAddress($testEmail, 'Usuário Teste');
    
    echo "<p>✅ Remetente e destinatário definidos</p>";
    
    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = 'Teste Direto PHPMailer - Klube Cash';
    $mail->Body = '
    <h2>Teste Direto do PHPMailer</h2>
    <p>Este email foi enviado diretamente pelo PHPMailer, sem usar a classe Email.</p>
    <p>Se você recebeu este email, o PHPMailer está funcionando corretamente.</p>
    <p>Data/Hora: ' . date('d/m/Y H:i:s') . '</p>
    ';
    $mail->AltBody = 'Teste Direto do PHPMailer - Klube Cash';
    
    echo "<p>✅ Conteúdo do email definido</p>";
    
    echo "<p>🚀 <strong>ENVIANDO EMAIL...</strong></p>";
    echo "<div style='background: #f9f9f9; border: 1px solid #ddd; padding: 10px; margin: 10px 0;'>";
    
    // Tentar enviar
    $result = $mail->send();
    
    echo "</div>";
    
    if ($result) {
        echo "<p>✅ <strong>EMAIL ENVIADO COM SUCESSO!</strong></p>";
        echo "<p>📬 Verifique a caixa de entrada (e spam) do email: $testEmail</p>";
    } else {
        echo "<p>❌ <strong>FALHA AO ENVIAR EMAIL</strong></p>";
        echo "<p>🔍 <strong>ErrorInfo:</strong> " . $mail->ErrorInfo . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ <strong>EXCEÇÃO DO PHPMAILER:</strong></p>";
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0;'>";
    echo "<p><strong>Mensagem:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Código:</strong> " . $e->getCode() . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
} catch (Throwable $e) {
    echo "<p>❌ <strong>ERRO FATAL:</strong></p>";
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 10px; margin: 10px 0;'>";
    echo "<p><strong>Mensagem:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "<h2>📋 Informações do Servidor:</h2>";
echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
echo "<p><strong>OpenSSL:</strong> " . (extension_loaded('openssl') ? '✅ Carregado' : '❌ Não carregado') . "</p>";
echo "<p><strong>cURL:</strong> " . (extension_loaded('curl') ? '✅ Carregado' : '❌ Não carregado') . "</p>";
echo "<p><strong>allow_url_fopen:</strong> " . (ini_get('allow_url_fopen') ? '✅ Habilitado' : '❌ Desabilitado') . "</p>";

echo "<h2>🔧 Próximos Passos:</h2>";
echo "<p>Se este teste FALHAR, o problema é específico do PHPMailer.</p>";
echo "<p>Se este teste FUNCIONAR, o problema está na classe Email.</p>";
?>
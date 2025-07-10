<?php
// teste-email-final.php - TESTE FINAL

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/libs/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

try {
    $mail = new PHPMailer(true);
    
    // Configurações SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'klubecash@klubecash.com';
    $mail->Password = 'Aaku_2004@';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    
    // CORREÇÃO: Usar o email autenticado como remetente
    $mail->setFrom('klubecash@klubecash.com', 'Klube Cash');
    $mail->addReplyTo('klubecash@klubecash.com', 'Klube Cash');
    $mail->addAddress('kauamatheus920@gmail.com', 'Teste');
    
    // Conteúdo
    $mail->isHTML(true);
    $mail->Subject = 'Teste CORRIGIDO - Klube Cash';
    $mail->Body = '
    <h2>🎉 Teste Corrigido!</h2>
    <p>Este email foi enviado com o remetente correto: <strong>klubecash@klubecash.com</strong></p>
    <p>Se você recebeu este email, o problema está resolvido!</p>
    <p>Data/Hora: ' . date('d/m/Y H:i:s') . '</p>
    ';
    
    $result = $mail->send();
    
    if ($result) {
        echo "✅ EMAIL ENVIADO COM SUCESSO!<br>";
        echo "📬 Verifique sua caixa de entrada (e spam)";
    } else {
        echo "❌ Falha: " . $mail->ErrorInfo;
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
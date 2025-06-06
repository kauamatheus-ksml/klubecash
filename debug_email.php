<?php
// debug_email.php - VERSÃO CORRIGIDA
session_start();

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'config/email.php';
require_once 'utils/Email.php';
require_once 'controllers/AuthController.php';

// Verificar se é admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('<h1>Acesso negado</h1><p>Faça login como administrador.</p>');
}

echo "<h2>🔧 Debug do Sistema de Email</h2>";

// Teste 1: Verificar configurações
echo "<h3>1. Configurações SMTP:</h3>";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><td><strong>Host:</strong></td><td>" . SMTP_HOST . "</td></tr>";
echo "<tr><td><strong>Port:</strong></td><td>" . SMTP_PORT . "</td></tr>";
echo "<tr><td><strong>Username:</strong></td><td>" . SMTP_USERNAME . "</td></tr>";
echo "<tr><td><strong>From Email:</strong></td><td>" . SMTP_FROM_EMAIL . "</td></tr>";
echo "<tr><td><strong>Encryption:</strong></td><td>" . SMTP_ENCRYPTION . "</td></tr>";
echo "</table>";

// Teste 2: Teste de conexão
echo "<h3>2. Teste de Conexão SMTP:</h3>";
$connectionTest = Email::testEmailConnection();
echo "<div style='padding: 10px; border: 1px solid " . ($connectionTest['status'] ? 'green' : 'red') . "; background: " . ($connectionTest['status'] ? '#e8f5e8' : '#ffe8e8') . ";'>";
echo "<strong>Status:</strong> " . ($connectionTest['status'] ? '✅ OK' : '❌ ERRO') . "<br>";
echo "<strong>Mensagem:</strong> " . htmlspecialchars($connectionTest['message']) . "<br>";
if (isset($connectionTest['debug']) && !empty($connectionTest['debug'])) {
    echo "<details><summary>Debug Info (clique para expandir)</summary><pre>" . htmlspecialchars($connectionTest['debug']) . "</pre></details>";
}
echo "</div>";

// Teste 3: Teste de envio simples
if (isset($_GET['test_simple'])) {
    echo "<h3>3. Teste de Email Simples:</h3>";
    $result = AuthController::sendTestEmail();
    echo "<div style='padding: 10px; border: 1px solid " . ($result['status'] ? 'green' : 'red') . "; background: " . ($result['status'] ? '#e8f5e8' : '#ffe8e8') . ";'>";
    echo "<strong>Status:</strong> " . ($result['status'] ? '✅ ENVIADO' : '❌ FALHA') . "<br>";
    echo "<strong>Mensagem:</strong> " . htmlspecialchars($result['message']) . "<br>";
    echo "</div>";
}

// Teste 4: Teste de email 2FA
if (isset($_GET['test_2fa'])) {
    echo "<h3>4. Teste de Email 2FA:</h3>";
    $result = AuthController::test2FAEmail();
    echo "<div style='padding: 10px; border: 1px solid " . ($result['status'] ? 'green' : 'red') . "; background: " . ($result['status'] ? '#e8f5e8' : '#ffe8e8') . ";'>";
    echo "<strong>Status:</strong> " . ($result['status'] ? '✅ ENVIADO' : '❌ FALHA') . "<br>";
    echo "<strong>Mensagem:</strong> " . htmlspecialchars($result['message']) . "<br>";
    echo "</div>";
}

// Botões de teste
echo "<h3>Executar Testes:</h3>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='?test_simple=1' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; margin-right: 10px; border-radius: 5px;'>🧪 Testar Email Simples</a>";
echo "<a href='?test_2fa=1' style='background: #ff7a00; color: white; padding: 10px 15px; text-decoration: none; margin-right: 10px; border-radius: 5px;'>🔐 Testar Email 2FA</a>";
echo "</div>";

// Informações da sessão
echo "<h3>5. Informações da Sessão:</h3>";
echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><td><strong>User ID:</strong></td><td>" . ($_SESSION['user_id'] ?? 'N/A') . "</td></tr>";
echo "<tr><td><strong>User Name:</strong></td><td>" . ($_SESSION['user_name'] ?? 'N/A') . "</td></tr>";
echo "<tr><td><strong>User Email:</strong></td><td>" . ($_SESSION['user_email'] ?? 'N/A') . "</td></tr>";
echo "<tr><td><strong>User Type:</strong></td><td>" . ($_SESSION['user_type'] ?? 'N/A') . "</td></tr>";
echo "</table>";

echo "<div style='margin-top: 30px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6;'>";
echo "<h4>📋 Próximos Passos:</h4>";
echo "<ol>";
echo "<li>Se a conexão SMTP está OK, teste o envio de email simples</li>";
echo "<li>Se o email simples funcionar, teste o email 2FA</li>";
echo "<li>Verifique sua caixa de spam se não receber os emails</li>";
echo "<li>Volte para as configurações e teste o botão lá</li>";
echo "</ol>";
echo "</div>";

echo "<br><a href='views/admin/settings.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>⬅️ Voltar para Configurações</a>";
?>
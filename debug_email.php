<?php
// debug_email.php - ARQUIVO TEMPORÁRIO PARA DEBUG
session_start();

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'config/email.php';
require_once 'controllers/AuthController.php';

// Verificar se é admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('Acesso negado');
}

echo "<h2>Debug do Sistema de Email</h2>";

// Teste 1: Verificar configurações
echo "<h3>1. Configurações SMTP:</h3>";
echo "Host: " . SMTP_HOST . "<br>";
echo "Port: " . SMTP_PORT . "<br>";
echo "Username: " . SMTP_USERNAME . "<br>";
echo "From Email: " . SMTP_FROM_EMAIL . "<br>";

// Teste 2: Teste de conexão
echo "<h3>2. Teste de Conexão:</h3>";
$connectionTest = Email::testEmailConnection();
echo "Status: " . ($connectionTest['status'] ? 'OK' : 'ERRO') . "<br>";
echo "Mensagem: " . $connectionTest['message'] . "<br>";

// Teste 3: Teste de envio
if (isset($_GET['test_send'])) {
    echo "<h3>3. Teste de Envio:</h3>";
    $result = AuthController::test2FAEmail();
    echo "Status: " . ($result['status'] ? 'OK' : 'ERRO') . "<br>";
    echo "Mensagem: " . $result['message'] . "<br>";
}

echo "<br><a href='?test_send=1'>Executar Teste de Envio</a>";
echo "<br><a href='views/admin/settings.php'>Voltar para Configurações</a>";
?>
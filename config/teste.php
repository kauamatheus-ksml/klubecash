<?php
// Como teste.php está na mesma pasta que email.php, use caminho relativo correto
require_once __DIR__ . '/email.php';
echo '<h1>Teste de Configuração de Email</h1>';
// Test SMTP connection
$result = Email::testEmailConnection();
echo '<h2>Resultado do Teste de Conexão SMTP</h2>';
echo '<pre>';
print_r($result);
echo '</pre>';
// Informações para debug
echo '<h2>Informações do Servidor</h2>';
echo '<pre>';
echo 'PHP Version: ' . phpversion() . "\n";
echo 'Server Name: ' . $_SERVER['SERVER_NAME'] . "\n";
echo 'Server Address: ' . $_SERVER['SERVER_ADDR'] . "\n";
echo 'Document Root: ' . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo 'Script Filename: ' . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo '</pre>';
// Teste de envio se a conexão foi bem-sucedida
if ($result['status']) {
    echo '<h2>Teste de Envio de Email</h2>';
    echo '<p>Tentando enviar email de teste...</p>';
   
    // Email de teste - substitua pelo seu email para testar
    $testEmail = 'kauamatheus920@gmail.com'; // Altere para seu email
   
    // Verificar logs de erro anteriores
    $logFile = ini_get('error_log');
    echo '<p>Arquivo de log: ' . ($logFile ? htmlspecialchars($logFile) : 'Não definido') . '</p>';
   
    // Forçar limpeza de qualquer instância anterior
    gc_collect_cycles();
   
    // Habilitar exibição de erros para debugging
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
   
    // Teste com uma configuração mais simples
    try {
        // Mensagem básica para teste
        $subject = 'Teste de Email - Klube Cash';
        $message = '<p>Este é um email de teste do sistema Klube Cash.</p><p>Horário: ' . date('Y-m-d H:i:s') . '</p>';
       
        echo '<p>Tentando enviar para: ' . htmlspecialchars($testEmail) . '</p>';
       
        $sent = Email::send(
            $testEmail,
            $subject,
            $message,
            'Usuário de Teste'
        );
       
        if ($sent) {
            echo '<p style="color: green; font-weight: bold;">Email enviado com sucesso para ' . htmlspecialchars($testEmail) . '!</p>';
        } else {
            echo '<p style="color: red; font-weight: bold;">Falha ao enviar email.</p>';
           
            // Verificar logs para mais detalhes
            echo '<p>Verifique o arquivo de log para mais detalhes.</p>';
           
            // Verificar se há mensagens de erro registradas
            $errorLogContent = '';
            if (file_exists($logFile)) {
                $errorLogContent = implode('', array_slice(file($logFile), -20));
                echo '<h3>Últimas linhas do log de erros:</h3>';
                echo '<pre>' . htmlspecialchars($errorLogContent) . '</pre>';
            }
        }
    } catch (Exception $e) {
        echo '<p style="color: red; font-weight: bold;">Exceção durante o envio: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
} else {
    echo '<p style="color: red; font-weight: bold;">Não foi possível testar o envio de email porque a conexão SMTP falhou.</p>';
}
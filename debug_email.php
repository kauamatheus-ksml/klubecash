<?php
// debug_email.php - VERSÃO APRIMORADA
session_start();

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'config/email.php';
require_once 'utils/Email.php';
require_once 'controllers/AuthController.php';

// Verificar se é admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('<h1>❌ Acesso negado</h1><p>Faça login como administrador.</p>');
}

echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; }
    .card { border: 1px solid #ddd; border-radius: 8px; margin: 15px 0; padding: 20px; }
    .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
    .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
    .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
    .btn { padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; }
    .btn-primary { background: #007bff; color: white; }
    .btn-success { background: #28a745; color: white; }
    .btn-warning { background: #ffc107; color: black; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    td, th { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>";

echo "<h1>🔧 Debug Avançado do Sistema de Email</h1>";

// Teste 1: Verificar configurações
echo "<div class='card info'>";
echo "<h2>1. ⚙️ Configurações SMTP</h2>";
echo "<table>";
echo "<tr><th>Configuração</th><th>Valor</th><th>Status</th></tr>";
echo "<tr><td>Host</td><td>" . SMTP_HOST . "</td><td>✅</td></tr>";
echo "<tr><td>Port</td><td>" . SMTP_PORT . "</td><td>✅</td></tr>";
echo "<tr><td>Username</td><td>" . SMTP_USERNAME . "</td><td>✅</td></tr>";
echo "<tr><td>From Email</td><td>" . SMTP_FROM_EMAIL . "</td><td>✅</td></tr>";
echo "<tr><td>Encryption</td><td>" . SMTP_ENCRYPTION . "</td><td>✅</td></tr>";
echo "</table>";
echo "</div>";

// Teste 2: Verificar arquivos
echo "<div class='card info'>";
echo "<h2>2. 📁 Verificação de Arquivos</h2>";
$files = [
    'Email.php' => 'utils/Email.php',
    'AuthController.php' => 'controllers/AuthController.php',
    'PHPMailer' => 'libs/PHPMailer/src/PHPMailer.php'
];

echo "<table>";
echo "<tr><th>Arquivo</th><th>Caminho</th><th>Status</th></tr>";
foreach ($files as $name => $path) {
    $exists = file_exists($path);
    echo "<tr><td>{$name}</td><td>{$path}</td><td>" . ($exists ? '✅ Existe' : '❌ Não encontrado') . "</td></tr>";
}
echo "</table>";
echo "</div>";

// Teste 3: Teste de conexão
echo "<div class='card'>";
echo "<h2>3. 🔗 Teste de Conexão SMTP</h2>";
try {
    $connectionTest = Email::testEmailConnection();
    $class = $connectionTest['status'] ? 'success' : 'error';
    echo "<div class='card {$class}'>";
    echo "<strong>Status:</strong> " . ($connectionTest['status'] ? '✅ SUCESSO' : '❌ FALHA') . "<br>";
    echo "<strong>Mensagem:</strong> " . htmlspecialchars($connectionTest['message']) . "<br>";
    if (isset($connectionTest['debug'])) {
        echo "<details><summary>📋 Debug Info</summary><pre>" . htmlspecialchars($connectionTest['debug']) . "</pre></details>";
    }
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='card error'>";
    echo "<strong>❌ ERRO:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
echo "</div>";

// Teste 4: Teste via API
echo "<div class='card'>";
echo "<h2>4. 🌐 Teste via API</h2>";
echo "<button class='btn btn-primary' onclick='testAPI(\"test_connection\")'>Testar Conexão via API</button>";
echo "<button class='btn btn-success' onclick='testAPI(\"send_simple\")'>Enviar Email Simples via API</button>";
echo "<button class='btn btn-warning' onclick='testAPI(\"send_2fa\")'>Enviar 2FA via API</button>";
echo "<div id='apiResults'></div>";
echo "</div>";

// Informações da sessão
echo "<div class='card info'>";
echo "<h2>5. 👤 Informações da Sessão</h2>";
echo "<table>";
echo "<tr><td><strong>User ID:</strong></td><td>" . ($_SESSION['user_id'] ?? 'N/A') . "</td></tr>";
echo "<tr><td><strong>User Name:</strong></td><td>" . ($_SESSION['user_name'] ?? 'N/A') . "</td></tr>";
echo "<tr><td><strong>User Email:</strong></td><td>" . ($_SESSION['user_email'] ?? 'N/A') . "</td></tr>";
echo "<tr><td><strong>User Type:</strong></td><td>" . ($_SESSION['user_type'] ?? 'N/A') . "</td></tr>";
echo "</table>";
echo "</div>";

echo "<div class='card info'>";
echo "<h2>📋 Próximos Passos</h2>";
echo "<ol>";
echo "<li>✅ Se a conexão SMTP está OK, teste os emails via API</li>";
echo "<li>📧 Verifique sua caixa de spam se não receber os emails</li>";
echo "<li>🔙 Volte para as configurações e teste os botões lá</li>";
echo "<li>📞 Se ainda houver problemas, verifique os logs do servidor</li>";
echo "</ol>";
echo "</div>";

echo "<br><a href='views/admin/settings.php' class='btn btn-success'>⬅️ Voltar para Configurações</a>";
?>

<script>
async function testAPI(action) {
    const resultsDiv = document.getElementById('apiResults');
    resultsDiv.innerHTML = '<p>🔄 Processando...</p>';
    
    try {
        const response = await fetch('/api/email-test.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=${encodeURIComponent(action)}`
        });
        
        const data = await response.json();
        
        const statusClass = data.status ? 'success' : 'error';
        const statusIcon = data.status ? '✅' : '❌';
        
        resultsDiv.innerHTML = `
            <div class="card ${statusClass}">
                <h3>${statusIcon} Resultado do Teste: ${action}</h3>
                <p><strong>Status:</strong> ${data.status ? 'SUCESSO' : 'FALHA'}</p>
                <p><strong>Mensagem:</strong> ${data.message}</p>
                <p><strong>Timestamp:</strong> ${data.timestamp}</p>
                ${data.data ? `<pre>Dados: ${JSON.stringify(data.data, null, 2)}</pre>` : ''}
            </div>
        `;
        
    } catch (error) {
        resultsDiv.innerHTML = `
            <div class="card error">
                <h3>❌ Erro na Requisição</h3>
                <p><strong>Erro:</strong> ${error.message}</p>
            </div>
        `;
    }
}
</script>
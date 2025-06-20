<?php
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'utils/WhatsAppBot.php';

echo "<h2>🔗 Teste de Integração Completa - WhatsApp + Transações</h2>";

// Primeiro, verificar se temos usuários de teste
try {
    $db = Database::getConnection();
    
    // Buscar um usuário com telefone para teste
    $userStmt = $db->prepare("
        SELECT id, nome, telefone, email 
        FROM usuarios 
        WHERE telefone IS NOT NULL AND telefone != '' 
        AND tipo = 'cliente'
        LIMIT 1
    ");
    $userStmt->execute();
    $testUser = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testUser) {
        echo "<p style='color: orange;'>⚠️ Nenhum usuário com telefone encontrado para teste</p>";
        echo "<p>Para testar a integração completa, adicione um telefone no cadastro de um usuário cliente.</p>";
        exit;
    }
    
    echo "<h3>👤 Usuário de Teste Encontrado:</h3>";
    echo "<p><strong>Nome:</strong> {$testUser['nome']}</p>";
    echo "<p><strong>Telefone:</strong> {$testUser['telefone']}</p>";
    echo "<p><strong>Email:</strong> {$testUser['email']}</p>";
    
    // Buscar uma loja para o teste
    $storeStmt = $db->prepare("
        SELECT id, nome_fantasia 
        FROM lojas 
        WHERE status = 'aprovado' 
        LIMIT 1
    ");
    $storeStmt->execute();
    $testStore = $storeStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testStore) {
        echo "<p style='color: orange;'>⚠️ Nenhuma loja aprovada encontrada para teste</p>";
        exit;
    }
    
    echo "<h3>🏪 Loja de Teste:</h3>";
    echo "<p><strong>Nome:</strong> {$testStore['nome_fantasia']}</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao buscar dados de teste: " . $e->getMessage() . "</p>";
    exit;
}

// Testar envio de notificação de nova transação
if (isset($_POST['test_new_transaction'])) {
    echo "<h3>📤 Testando Notificação de Nova Transação:</h3>";
    
    $transactionData = [
        'valor_cashback' => 5.50,
        'valor_usado' => 2.30,
        'nome_loja' => $testStore['nome_fantasia']
    ];
    
    $result = WhatsAppBot::sendNewTransactionNotification($testUser['telefone'], $transactionData);
    
    echo "<div style='background: " . ($result['success'] ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Resultado:</strong><br>";
    echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    echo "</div>";
}

// Testar envio de notificação de cashback liberado
if (isset($_POST['test_cashback_released'])) {
    echo "<h3>🎉 Testando Notificação de Cashback Liberado:</h3>";
    
    $transactionData = [
        'valor_cashback' => 8.75,
        'nome_loja' => $testStore['nome_fantasia']
    ];
    
    $result = WhatsAppBot::sendCashbackReleasedNotification($testUser['telefone'], $transactionData);
    
    echo "<div style='background: " . ($result['success'] ? '#d4edda' : '#f8d7da') . "; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>Resultado:</strong><br>";
    echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    echo "</div>";
}
?>

<h3>🧪 Testes Disponíveis:</h3>

<form method="post" style="margin: 20px 0;">
    <button type="submit" name="test_new_transaction" value="1" 
            style="background: #17a2b8; color: white; padding: 10px 20px; border: none; border-radius: 5px; margin: 5px; cursor: pointer;">
        📝 Testar Nova Transação
    </button>
</form>

<form method="post" style="margin: 20px 0;">
    <button type="submit" name="test_cashback_released" value="1" 
            style="background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; margin: 5px; cursor: pointer;">
        🎉 Testar Cashback Liberado
    </button>
</form>

<h3>📋 Status do Sistema:</h3>
<?php
$status = WhatsAppBot::getDetailedStatus();
echo "<pre>" . json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
?>

<hr>
<p><strong>Próximos Passos:</strong></p>
<ol>
    <li>Teste primeiro o status do WhatsApp em: <a href="test-whatsapp.php">test-whatsapp.php</a></li>
    <li>Execute os testes acima para validar as notificações</li>
    <li>Verifique os logs do servidor para confirmar que as mensagens estão sendo registradas</li>
    <li>Quando estiver satisfeito, integre com o sistema real de transações</li>
</ol>
<?php
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/TransactionController.php';
require_once 'utils/WhatsAppBot.php';

echo "<h2>🎯 Teste de Integração Completa - Simulação de Transação Real</h2>";

if (isset($_POST['simulate_full_process'])) {
    echo "<h3>🔄 Simulando Processo Completo de Transação...</h3>";
    
    try {
        $db = Database::getConnection();
        
        // Buscar usuário e loja de teste
        $userStmt = $db->prepare("
            SELECT id, nome, telefone 
            FROM usuarios 
            WHERE telefone IS NOT NULL AND telefone != '' AND tipo = 'cliente'
            LIMIT 1
        ");
        $userStmt->execute();
        $testUser = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        $storeStmt = $db->prepare("
            SELECT id, nome_fantasia 
            FROM lojas 
            WHERE status = 'aprovado' 
            LIMIT 1
        ");
        $storeStmt->execute();
        $testStore = $storeStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$testUser || !$testStore) {
            throw new Exception("Dados de teste não encontrados");
        }
        
        echo "<p><strong>📱 Simulando para:</strong> {$testUser['nome']} ({$testUser['telefone']})</p>";
        echo "<p><strong>🏪 Loja:</strong> {$testStore['nome_fantasia']}</p>";
        
        // Simular criação de nova transação
        echo "<h4>📝 1. Simulando Nova Transação...</h4>";
        $newTransactionData = [
            'valor_cashback' => 12.50,
            'valor_usado' => 5.00,
            'nome_loja' => $testStore['nome_fantasia']
        ];
        
        $result1 = WhatsAppBot::sendNewTransactionNotification($testUser['telefone'], $newTransactionData);
        echo "<div style='background: " . ($result1['success'] ? '#d4edda' : '#f8d7da') . "; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Resultado Nova Transação:</strong><br>";
        echo "<pre>" . json_encode($result1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        echo "</div>";
        
        // Simular liberação de cashback
        echo "<h4>🎉 2. Simulando Liberação de Cashback...</h4>";
        $releasedData = [
            'valor_cashback' => 12.50,
            'nome_loja' => $testStore['nome_fantasia']
        ];
        
        $result2 = WhatsAppBot::sendCashbackReleasedNotification($testUser['telefone'], $releasedData);
        echo "<div style='background: " . ($result2['success'] ? '#d4edda' : '#f8d7da') . "; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>Resultado Cashback Liberado:</strong><br>";
        echo "<pre>" . json_encode($result2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
        echo "</div>";
        
        echo "<h4>📊 Resumo da Simulação:</h4>";
        echo "<p>✅ Cliente simulado: {$testUser['nome']}</p>";
        echo "<p>✅ Telefone utilizado: {$testUser['telefone']}</p>";
        echo "<p>✅ Loja simulada: {$testStore['nome_fantasia']}</p>";
        echo "<p>✅ Nova transação: " . ($result1['success'] ? 'Enviada' : 'Falhou') . "</p>";
        echo "<p>✅ Cashback liberado: " . ($result2['success'] ? 'Enviado' : 'Falhou') . "</p>";
        
        if ($result1['success'] && $result2['success']) {
            echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h4 style='color: #0c5460;'>🎊 Parabéns! Integração Completa Funcionando!</h4>";
            echo "<p>O sistema está pronto para notificar automaticamente os clientes via WhatsApp sempre que:</p>";
            echo "<ul>";
            echo "<li>Uma nova transação for registrada por uma loja</li>";
            echo "<li>Um pagamento for aprovado e o cashback liberado</li>";
            echo "</ul>";
            echo "<p><strong>Próximo passo:</strong> As modificações nos controllers farão com que isso aconteça automaticamente no sistema real!</p>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro na simulação: " . $e->getMessage() . "</p>";
    }
}
?>

<form method="post" style="margin: 20px 0;">
    <button type="submit" name="simulate_full_process" value="1" 
            style="background: #007bff; color: white; padding: 15px 30px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
        🚀 Simular Processo Completo de Transação
    </button>
</form>

<h3>📋 Status Atual do Sistema:</h3>
<?php
$status = WhatsAppBot::getDetailedStatus();
echo "<pre>" . json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
?>

<hr>
<p><strong>Observação:</strong> Este teste simula exatamente o que acontecerá quando você integrar o código com os controllers reais. As mensagens serão registradas nos logs do servidor (modo simulação) até que você configure a API real do WhatsApp Business.</p>
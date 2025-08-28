<?php
// Teste simples e direto da funcionalidade MVP
session_start();
require_once 'config/database.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/TransactionController.php';

echo "<h2>🚀 Teste MVP Simplificado</h2>";

try {
    $db = Database::getConnection();
    
    // Buscar loja MVP
    $storeQuery = "
        SELECT l.*, u.id as user_id, u.email, u.mvp
        FROM lojas l 
        JOIN usuarios u ON l.usuario_id = u.id 
        WHERE l.status = 'aprovado' AND u.mvp = 'sim'
        LIMIT 1
    ";
    $storeStmt = $db->prepare($storeQuery);
    $storeStmt->execute();
    $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$store) {
        echo "<p style='color: red;'>❌ Nenhuma loja MVP encontrada!</p>";
        exit;
    }
    
    // Simular sessão de loja
    $_SESSION['user_id'] = $store['user_id'];
    $_SESSION['user_type'] = 'loja';
    $_SESSION['user_email'] = $store['email'];
    $_SESSION['store_id'] = $store['id'];
    
    // Buscar cliente
    $clientQuery = "SELECT id, nome, email FROM usuarios WHERE tipo = 'cliente' AND status = 'ativo' LIMIT 1";
    $clientStmt = $db->prepare($clientQuery);
    $clientStmt->execute();
    $client = $clientStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        echo "<p style='color: red;'>❌ Nenhum cliente encontrado!</p>";
        exit;
    }
    
    echo "<p>🏪 Loja MVP: {$store['nome_fantasia']} (ID: {$store['id']})</p>";
    echo "<p>👤 Cliente: {$client['nome']} (ID: {$client['id']})</p>";
    
    // Dados simples da transação
    $transactionData = [
        'loja_id' => $store['id'],
        'usuario_id' => $client['id'],
        'valor_total' => 25.00,
        'codigo_transacao' => 'MVP_SIMPLE_' . time(),
        'descricao' => 'Teste MVP Simples - ' . date('Y-m-d H:i:s')
    ];
    
    echo "<h3>📋 Dados da transação:</h3>";
    echo "<pre>" . print_r($transactionData, true) . "</pre>";
    
    // Executar transação
    echo "<h3>⚡ Executando transação...</h3>";
    $result = TransactionController::registerTransaction($transactionData);
    
    echo "<h3>📊 Resultado:</h3>";
    if ($result['status']) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
        echo "<h4>🎉 SUCESSO!</h4>";
        echo "<p><strong>Transaction ID:</strong> {$result['data']['transaction_id']}</p>";
        echo "<p><strong>Valor:</strong> R$ " . number_format($result['data']['valor_original'], 2, ',', '.') . "</p>";
        echo "<p><strong>Cashback:</strong> R$ " . number_format($result['data']['valor_cashback'], 2, ',', '.') . "</p>";
        echo "<p><strong>Status MVP:</strong> " . ($result['data']['is_mvp'] ? '🏆 SIM' : '❌ NÃO') . "</p>";
        echo "<p><strong>Status Transação:</strong> {$result['data']['status_transacao']}</p>";
        echo "<p><strong>Cashback Creditado:</strong> " . ($result['data']['cashback_creditado'] ? '✅ SIM' : '❌ NÃO') . "</p>";
        echo "<p><strong>Mensagem:</strong> {$result['message']}</p>";
        echo "</div>";
        
        // Verificar se realmente foi creditado o cashback
        if ($result['data']['is_mvp'] && $result['data']['cashback_creditado']) {
            require_once 'models/CashbackBalance.php';
            $balanceModel = new CashbackBalance();
            $saldo = $balanceModel->getStoreBalance($client['id'], $store['id']);
            
            echo "<h4>💰 Saldo atual do cliente na loja:</h4>";
            echo "<p><strong>Saldo:</strong> R$ " . number_format($saldo, 2, ',', '.') . "</p>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
        echo "<h4>❌ ERRO!</h4>";
        echo "<p><strong>Mensagem:</strong> {$result['message']}</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
    echo "<h4>❌ EXCEÇÃO</h4>";
    echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . " (linha " . $e->getLine() . ")</p>";
    echo "</div>";
}
?>

<style>
pre { background: #f8f8f8; padding: 10px; border-radius: 3px; overflow-x: auto; }
div { margin: 1rem 0; }
</style>
<?php
// Simular exatamente como a página web chama a função
session_start();

// Carregar arquivos na mesma ordem da página
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/StoreController.php';
require_once 'controllers/TransactionController.php';
require_once 'controllers/CommissionController.php';
require_once 'utils/StoreHelper.php';

echo "<h2>🌐 Simulação da Página Web</h2>";

try {
    // Simular sessão exatamente como a página faz
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
    
    // Configurar sessão EXATAMENTE como a página faz
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
    
    // Simular dados EXATAMENTE como a página envia
    $transactionData = [
        'usuario_id' => $client['id'],
        'loja_id' => $store['id'],
        'valor_total' => 30.00,
        'codigo_transacao' => 'WEB_SIM_' . time(),
        'descricao' => 'Teste simulação web - ' . date('Y-m-d H:i:s'),
        'data_transacao' => date('Y-m-d H:i:s'),
        'usar_saldo' => false,
        'valor_saldo_usado' => 0
    ];
    
    echo "<h3>📋 Dados da transação (como página web):</h3>";
    echo "<pre>" . print_r($transactionData, true) . "</pre>";
    
    // Habilitar todos os logs
    error_log("WEB_SIMULATION: Iniciando teste de simulação web");
    error_log("WEB_SIMULATION: Dados: " . print_r($transactionData, true));
    
    echo "<h3>⚡ Chamando TransactionController::registerTransaction...</h3>";
    
    // Chamar EXATAMENTE como a página faz
    $result = TransactionController::registerTransaction($transactionData);
    
    error_log("WEB_SIMULATION: Resultado: " . print_r($result, true));
    
    echo "<h3>📊 Resultado:</h3>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    if ($result['status']) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
        echo "<h4>🎉 SUCESSO!</h4>";
        echo "<p>Transaction ID: {$result['data']['transaction_id']}</p>";
        echo "<p>É MVP: " . ($result['data']['is_mvp'] ? '🏆 SIM' : '❌ NÃO') . "</p>";
        echo "<p>Status: {$result['data']['status_transacao']}</p>";
        echo "<p>Cashback Creditado: " . ($result['data']['cashback_creditado'] ? '✅ SIM' : '❌ NÃO') . "</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
        echo "<h4>❌ ERRO!</h4>";
        echo "<p>Mensagem: {$result['message']}</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
    echo "<h4>❌ EXCEÇÃO</h4>";
    echo "<p>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . " (linha " . $e->getLine() . ")</p>";
    echo "</div>";
}
?>

<style>
pre { background: #f8f8f8; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>
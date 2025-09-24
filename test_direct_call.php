<?php
// Teste direto da função registerTransaction com logs detalhados
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once 'config/database.php';
require_once 'controllers/AuthController.php';

echo "<h2>🎯 Teste de Chamada Direta</h2>";

try {
    $db = Database::getConnection();
    
    // Simular sessão de loja MVP
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
    
    // Configurar sessão
    $_SESSION['user_id'] = $store['user_id'];
    $_SESSION['user_type'] = 'loja';
    $_SESSION['user_email'] = $store['email'];
    $_SESSION['store_id'] = $store['id'];
    
    // Buscar cliente
    $clientQuery = "SELECT id, nome, email FROM usuarios WHERE tipo = 'cliente' AND status = 'ativo' LIMIT 1";
    $clientStmt = $db->prepare($clientQuery);
    $clientStmt->execute();
    $client = $clientStmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>✅ Loja: {$store['nome_fantasia']} (MVP: {$store['mvp']})</p>";
    echo "<p>✅ Cliente: {$client['nome']}</p>";
    echo "<p>✅ Sessão configurada</p>";
    
    // Testar autenticação
    echo "<h3>🔐 Testando autenticação:</h3>";
    if (AuthController::isAuthenticated()) {
        echo "<p>✅ Autenticado</p>";
    } else {
        echo "<p style='color: red;'>❌ NÃO autenticado</p>";
    }
    
    if (AuthController::isStore()) {
        echo "<p>✅ É loja</p>";
    } else {
        echo "<p style='color: red;'>❌ NÃO é loja</p>";
    }
    
    // Dados da transação
    $data = [
        'loja_id' => $store['id'],
        'usuario_id' => $client['id'],
        'valor_total' => 10.00, // Valor bem baixo para testar
        'codigo_transacao' => 'DIRECT_' . time()
    ];
    
    echo "<h3>📝 Dados da transação:</h3>";
    echo "<pre>" . print_r($data, true) . "</pre>";
    
    // ANTES de chamar registerTransaction, vamos chamar a classe manualmente
    require_once 'controllers/TransactionController.php';
    
    echo "<h3>🚀 Chamando registerTransaction...</h3>";
    
    // Capturar toda saída e logs
    ob_start();
    $result = TransactionController::registerTransaction($data);
    $captured_output = ob_get_clean();
    
    if ($captured_output) {
        echo "<h4>📤 Output capturado:</h4>";
        echo "<pre>" . htmlspecialchars($captured_output) . "</pre>";
    }
    
    echo "<h3>📊 Resultado final:</h3>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    if ($result['status']) {
        echo "<p style='color: green;'>🎉 SUCESSO!</p>";
    } else {
        echo "<p style='color: red;'>❌ ERRO: {$result['message']}</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "<h4>❌ EXCEÇÃO CAPTURADA</h4>";
    echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . " (linha " . $e->getLine() . ")</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>

<style>
pre { background: #f8f8f8; padding: 8px; border-radius: 3px; overflow-x: auto; font-size: 12px; }
</style>
<?php
// Teste direto simulando exatamente como a página chama
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/StoreController.php';
require_once 'utils/StoreHelper.php';

echo "<h2>🎯 Teste Direto da Página</h2>";

try {
    // Setup da sessão exatamente como a página
    $db = Database::getConnection();
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
    
    $clientQuery = "SELECT id, nome, email FROM usuarios WHERE tipo = 'cliente' AND status = 'ativo' LIMIT 1";
    $clientStmt = $db->prepare($clientQuery);
    $clientStmt->execute();
    $client = $clientStmt->fetch(PDO::FETCH_ASSOC);
    
    // Configurar sessão
    $_SESSION['user_id'] = $store['user_id'];
    $_SESSION['user_type'] = 'loja';
    $_SESSION['user_email'] = $store['email'];
    $_SESSION['store_id'] = $store['id'];
    
    echo "<p>✅ Sessão configurada</p>";
    echo "<p>🏪 Loja: {$store['nome_fantasia']} (MVP: {$store['mvp']})</p>";
    echo "<p>👤 Cliente: {$client['nome']}</p>";
    
    // Dados da transação EXATAMENTE como a página envia
    $transactionData = [
        'usuario_id' => $client['id'],
        'loja_id' => $store['id'],
        'valor_total' => 20.00,
        'codigo_transacao' => 'PAGE_DIRECT_' . time(),
        'descricao' => 'Teste direto página - ' . date('Y-m-d H:i:s'),
        'data_transacao' => date('Y-m-d H:i:s'),
        'usar_saldo' => false,
        'valor_saldo_usado' => 0
    ];
    
    echo "<h3>📋 Dados da transação:</h3>";
    echo "<pre>" . print_r($transactionData, true) . "</pre>";
    
    // Vamos criar uma versão super simplificada da função registerTransaction
    // que ignora todas as integrações problemáticas
    require_once 'controllers/TransactionController.php';
    
    echo "<h3>🚀 Chamando função...</h3>";
    
    // Primeiro vamos verificar se o problema está na própria chamada da função
    if (method_exists('TransactionController', 'registerTransaction')) {
        echo "<p>✅ Método existe</p>";
        
        // Vamos tentar capturar QUALQUER tipo de erro
        set_error_handler(function($severity, $message, $file, $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        
        try {
            ob_start();
            $result = TransactionController::registerTransactionFixed($transactionData);
            $output = ob_get_clean();
            
            if ($output) {
                echo "<h4>💭 Output capturado:</h4>";
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
            }
            
            echo "<h4>📊 Resultado:</h4>";
            echo "<pre>" . print_r($result, true) . "</pre>";
            
            if (isset($result['status']) && $result['status'] === true) {
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
                echo "<h4>🎉 SUCESSO!</h4>";
                echo "<p>Transaction ID: {$result['data']['transaction_id']}</p>";
                echo "<p>MVP: " . ($result['data']['is_mvp'] ? '🏆 SIM' : '❌ NÃO') . "</p>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
                echo "<h4>❌ ERRO!</h4>";
                echo "<p>Mensagem: " . ($result['message'] ?? 'Sem mensagem') . "</p>";
                echo "<p>Status: " . print_r($result['status'] ?? 'undefined', true) . "</p>";
                echo "</div>";
            }
            
        } catch (Throwable $e) {
            ob_end_clean();
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
            echo "<h4>❌ EXCEÇÃO CAPTURADA!</h4>";
            echo "<p><strong>Tipo:</strong> " . get_class($e) . "</p>";
            echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>Arquivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        }
        
        restore_error_handler();
        
    } else {
        echo "<p style='color: red;'>❌ Método registerTransaction não existe!</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ ERRO GERAL</h4>";
    echo "<p>Mensagem: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
pre { background: #f8f8f8; padding: 10px; border-radius: 3px; overflow-x: auto; font-size: 12px; }
</style>
<?php
// Teste de transação com autenticação simulada
session_start();

require_once 'config/database.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/TransactionController.php';

echo "<h2>🔐 Teste de Transação com Autenticação</h2>";

try {
    // 1. Simular sessão de loja autenticada
    echo "<h3>1. Configurando sessão de loja...</h3>";
    
    $db = Database::getConnection();
    
    // Buscar uma loja MVP para teste
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
    
    echo "<p>🏪 Loja selecionada: {$store['nome_fantasia']} (ID: {$store['id']})</p>";
    echo "<p>👤 Usuário: {$store['email']}</p>";
    echo "<p>🏆 MVP: {$store['mvp']}</p>";
    
    // Simular sessão de loja
    $_SESSION['user_id'] = $store['user_id'];
    $_SESSION['user_type'] = 'loja';
    $_SESSION['user_email'] = $store['email'];
    $_SESSION['store_id'] = $store['id'];
    
    echo "<p>✅ Sessão configurada</p>";
    
    // 2. Verificar se AuthController reconhece a autenticação
    echo "<h3>2. Verificando AuthController...</h3>";
    
    if (AuthController::isAuthenticated()) {
        echo "<p>✅ Usuário autenticado</p>";
    } else {
        echo "<p style='color: red;'>❌ Usuário NÃO autenticado</p>";
    }
    
    if (AuthController::isStore()) {
        echo "<p>✅ Reconhecido como loja</p>";
    } else {
        echo "<p style='color: red;'>❌ NÃO reconhecido como loja</p>";
    }
    
    // 3. Buscar um cliente para a transação
    echo "<h3>3. Buscando cliente...</h3>";
    
    $clientQuery = "SELECT id, nome, email FROM usuarios WHERE tipo = 'cliente' AND status = 'ativo' LIMIT 1";
    $clientStmt = $db->prepare($clientQuery);
    $clientStmt->execute();
    $client = $clientStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$client) {
        // Criar cliente de teste se não existir
        echo "<p>⚠️ Criando cliente de teste...</p>";
        $createClientQuery = "
            INSERT INTO usuarios (nome, email, tipo, status, data_criacao) 
            VALUES ('Cliente Teste MVP', 'cliente.mvp.teste@exemplo.com', 'cliente', 'ativo', NOW())
        ";
        $db->prepare($createClientQuery)->execute();
        $client = [
            'id' => $db->lastInsertId(),
            'nome' => 'Cliente Teste MVP',
            'email' => 'cliente.mvp.teste@exemplo.com'
        ];
    }
    
    echo "<p>👤 Cliente: {$client['nome']} (ID: {$client['id']})</p>";
    
    // 4. Dados da transação
    $transactionData = [
        'loja_id' => $store['id'],
        'usuario_id' => $client['id'],
        'valor_total' => 100.00,
        'codigo_transacao' => 'MVP_TEST_' . time(),
        'descricao' => 'Teste MVP - ' . date('Y-m-d H:i:s'),
        'data_transacao' => date('Y-m-d H:i:s')
    ];
    
    echo "<h3>4. Registrando transação...</h3>";
    echo "<p>📝 Dados:</p>";
    echo "<pre>" . print_r($transactionData, true) . "</pre>";
    
    // 5. Executar transação
    $result = TransactionController::registerTransaction($transactionData);
    
    echo "<h3>5. Resultado:</h3>";
    
    if ($result['status']) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
        echo "<h4>🎉 SUCESSO!</h4>";
        echo "<p><strong>Transaction ID:</strong> {$result['data']['transaction_id']}</p>";
        echo "<p><strong>Valor Original:</strong> R$ " . number_format($result['data']['valor_original'], 2, ',', '.') . "</p>";
        echo "<p><strong>Valor Cashback:</strong> R$ " . number_format($result['data']['valor_cashback'], 2, ',', '.') . "</p>";
        echo "<p><strong>É MVP:</strong> " . ($result['data']['is_mvp'] ? '🏆 SIM' : '❌ NÃO') . "</p>";
        echo "<p><strong>Status:</strong> {$result['data']['status_transacao']}</p>";
        echo "<p><strong>Cashback Creditado:</strong> " . ($result['data']['cashback_creditado'] ? '✅ SIM' : '❌ NÃO') . "</p>";
        echo "</div>";
        
        echo "<h4>💬 Mensagem do sistema:</h4>";
        echo "<p><em>" . htmlspecialchars($result['message']) . "</em></p>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
        echo "<h4>❌ ERRO!</h4>";
        echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($result['message']) . "</p>";
        echo "</div>";
    }
    
    // 6. Verificar transação no banco
    echo "<h3>6. Verificando no banco de dados...</h3>";
    
    if (isset($result['data']['transaction_id'])) {
        $transactionId = $result['data']['transaction_id'];
        
        $checkQuery = "
            SELECT t.*, u.nome as cliente_nome 
            FROM transacoes_cashback t 
            JOIN usuarios u ON t.usuario_id = u.id 
            WHERE t.id = :transaction_id
        ";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':transaction_id', $transactionId);
        $checkStmt->execute();
        $transaction = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaction) {
            echo "<p>✅ Transação encontrada no banco:</p>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Campo</th><th>Valor</th></tr>";
            foreach ($transaction as $key => $value) {
                echo "<tr><td>{$key}</td><td>" . htmlspecialchars($value) . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>❌ Transação não encontrada no banco!</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
    echo "<h4>❌ EXCEÇÃO</h4>";
    echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . " (linha " . $e->getLine() . ")</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
?>

<style>
table { border-collapse: collapse; margin: 1rem 0; }
th, td { padding: 8px 12px; text-align: left; border: 1px solid #ddd; }
th { background: #f5f5f5; }
pre { background: #f8f8f8; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>
<?php
// Debug específico para erro de transação
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'controllers/TransactionController.php';

echo "<h2>🔍 Debug do Erro de Transação</h2>";

try {
    // Simular dados de uma transação real
    $testData = [
        'loja_id' => 34, // Loja MVP encontrada
        'usuario_id' => 1, // Assumindo que existe um usuário cliente
        'valor_total' => 50.00,
        'codigo_transacao' => 'DEBUG_' . time(),
        'descricao' => 'Teste de debug - ' . date('Y-m-d H:i:s')
    ];

    echo "<h3>1. Dados de teste:</h3>";
    echo "<pre>" . print_r($testData, true) . "</pre>";

    echo "<h3>2. Verificando se usuário cliente existe:</h3>";
    $db = Database::getConnection();
    
    $userQuery = "SELECT id, nome, email, tipo FROM usuarios WHERE tipo = 'cliente' AND status = 'ativo' LIMIT 1";
    $userStmt = $db->prepare($userQuery);
    $userStmt->execute();
    $cliente = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cliente) {
        echo "<p>✅ Cliente encontrado: {$cliente['nome']} ({$cliente['email']})</p>";
        $testData['usuario_id'] = $cliente['id'];
    } else {
        echo "<p style='color: red;'>❌ PROBLEMA: Nenhum cliente ativo encontrado!</p>";
        
        // Tentar criar um cliente de teste
        echo "<h4>Criando cliente de teste...</h4>";
        $createClientQuery = "
            INSERT INTO usuarios (nome, email, tipo, status, data_criacao) 
            VALUES ('Cliente Teste', 'cliente.teste@exemplo.com', 'cliente', 'ativo', NOW())
        ";
        
        try {
            $db->prepare($createClientQuery)->execute();
            $testData['usuario_id'] = $db->lastInsertId();
            echo "<p>✅ Cliente de teste criado com ID: {$testData['usuario_id']}</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erro ao criar cliente: " . $e->getMessage() . "</p>";
        }
    }

    echo "<h3>3. Verificando loja MVP:</h3>";
    $storeQuery = "
        SELECT l.*, COALESCE(u.mvp, 'nao') as store_mvp 
        FROM lojas l 
        JOIN usuarios u ON l.usuario_id = u.id 
        WHERE l.id = :loja_id
    ";
    $storeStmt = $db->prepare($storeQuery);
    $storeStmt->bindParam(':loja_id', $testData['loja_id']);
    $storeStmt->execute();
    $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($store) {
        echo "<p>✅ Loja encontrada: {$store['nome_fantasia']}</p>";
        echo "<p>🏆 Status MVP: {$store['store_mvp']}</p>";
        echo "<p>📊 Status: {$store['status']}</p>";
    } else {
        echo "<p style='color: red;'>❌ Loja não encontrada!</p>";
    }

    echo "<h3>4. Testando TransactionController::registerTransaction():</h3>";
    
    // Capturar todos os erros
    ob_start();
    $result = TransactionController::registerTransaction($testData);
    $output = ob_get_clean();
    
    if ($output) {
        echo "<h4>Output capturado:</h4>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }

    echo "<h4>Resultado:</h4>";
    echo "<pre>" . print_r($result, true) . "</pre>";

    if (!$result['status']) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
        echo "<h4>❌ ERRO ENCONTRADO</h4>";
        echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($result['message']) . "</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;'>";
        echo "<h4>✅ SUCESSO!</h4>";
        echo "<p>Transação registrada com ID: {$result['data']['transaction_id']}</p>";
        echo "<p>Status MVP: " . ($result['data']['is_mvp'] ? 'SIM' : 'NÃO') . "</p>";
        echo "</div>";
    }

    echo "<h3>5. Verificando logs de erro:</h3>";
    
    // Capturar últimos logs
    $errorLog = error_get_last();
    if ($errorLog) {
        echo "<h4>Último erro PHP:</h4>";
        echo "<pre>" . print_r($errorLog, true) . "</pre>";
    } else {
        echo "<p>✅ Nenhum erro PHP registrado</p>";
    }

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;'>";
    echo "<h4>❌ EXCEÇÃO CAPTURADA</h4>";
    echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . " (linha " . $e->getLine() . ")</p>";
    echo "<h4>Stack Trace:</h4>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "<h3>6. Verificações adicionais:</h3>";

// Verificar constantes
$constants = ['TRANSACTION_PENDING', 'TRANSACTION_APPROVED', 'STORE_APPROVED', 'USER_TYPE_CLIENT', 'USER_ACTIVE'];
foreach ($constants as $const) {
    if (defined($const)) {
        echo "<p>✅ {$const} = " . constant($const) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ {$const} não definida</p>";
    }
}

// Verificar se modelo CashbackBalance existe
if (class_exists('CashbackBalance')) {
    echo "<p>✅ Classe CashbackBalance carregada</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Classe CashbackBalance não carregada</p>";
}

?>

<style>
pre { background: #f8f8f8; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>
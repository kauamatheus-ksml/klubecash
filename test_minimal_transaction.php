<?php
// Teste com função mínima de transação
session_start();

require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

echo "<h2>🧪 Teste Transação Mínima</h2>";

// Função simplificada para testar
function registerTransactionMinimal($data) {
    try {
        echo "<p>✅ 1. Função iniciada</p>";
        
        // Validar dados obrigatórios
        $requiredFields = ['loja_id', 'usuario_id', 'valor_total', 'codigo_transacao'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                echo "<p style='color: red;'>❌ Campo {$field} faltante</p>";
                return ['status' => false, 'message' => 'Campo faltante: ' . $field];
            }
        }
        echo "<p>✅ 2. Campos validados</p>";
        
        // Verificar autenticação
        if (!AuthController::isAuthenticated()) {
            echo "<p style='color: red;'>❌ Não autenticado</p>";
            return ['status' => false, 'message' => 'Usuário não autenticado.'];
        }
        echo "<p>✅ 3. Usuário autenticado</p>";
        
        if (!AuthController::isStore() && !AuthController::isAdmin()) {
            echo "<p style='color: red;'>❌ Tipo de usuário incorreto</p>";
            return ['status' => false, 'message' => 'Apenas lojas podem registrar.'];
        }
        echo "<p>✅ 4. Tipo de usuário OK</p>";
        
        $db = Database::getConnection();
        echo "<p>✅ 5. Conexão com banco OK</p>";
        
        // Verificar cliente existe
        $userStmt = $db->prepare("SELECT id, nome FROM usuarios WHERE id = :id AND tipo = :tipo AND status = :status");
        $userStmt->bindParam(':id', $data['usuario_id']);
        $userStmt->bindParam(':tipo', USER_TYPE_CLIENT);
        $userStmt->bindParam(':status', USER_ACTIVE);
        $userStmt->execute();
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo "<p style='color: red;'>❌ Cliente não encontrado</p>";
            return ['status' => false, 'message' => 'Cliente não encontrado.'];
        }
        echo "<p>✅ 6. Cliente validado: {$user['nome']}</p>";
        
        // Verificar loja existe
        $storeStmt = $db->prepare("SELECT id, nome_fantasia FROM lojas WHERE id = :id AND status = :status");
        $storeStmt->bindParam(':id', $data['loja_id']);
        $storeStmt->bindParam(':status', STORE_APPROVED);
        $storeStmt->execute();
        $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$store) {
            echo "<p style='color: red;'>❌ Loja não encontrada</p>";
            return ['status' => false, 'message' => 'Loja não encontrada.'];
        }
        echo "<p>✅ 7. Loja validada: {$store['nome_fantasia']}</p>";
        
        // Início da transação do banco
        $db->beginTransaction();
        echo "<p>✅ 8. Transação iniciada</p>";
        
        try {
            // Inserir transação simples
            $stmt = $db->prepare("
                INSERT INTO transacoes_cashback (
                    usuario_id, loja_id, valor_total, valor_cashback,
                    valor_cliente, valor_admin, valor_loja, codigo_transacao, 
                    data_transacao, status, descricao
                ) VALUES (
                    :usuario_id, :loja_id, :valor_total, :valor_cashback,
                    :valor_cliente, :valor_admin, :valor_loja, :codigo_transacao, 
                    :data_transacao, :status, :descricao
                )
            ");
            
            $valorCashback = ($data['valor_total'] * 10) / 100;
            $valorCliente = ($data['valor_total'] * 5) / 100;
            $valorAdmin = ($data['valor_total'] * 5) / 100;
            $valorLoja = 0.00;
            $status = TRANSACTION_PENDING;
            $descricao = 'Teste minimal - ' . date('Y-m-d H:i:s');
            $dataTransacao = date('Y-m-d H:i:s');
            
            $stmt->bindParam(':usuario_id', $data['usuario_id']);
            $stmt->bindParam(':loja_id', $data['loja_id']);
            $stmt->bindParam(':valor_total', $data['valor_total']);
            $stmt->bindParam(':valor_cashback', $valorCashback);
            $stmt->bindParam(':valor_cliente', $valorCliente);
            $stmt->bindParam(':valor_admin', $valorAdmin);
            $stmt->bindParam(':valor_loja', $valorLoja);
            $stmt->bindParam(':codigo_transacao', $data['codigo_transacao']);
            $stmt->bindParam(':data_transacao', $dataTransacao);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':descricao', $descricao);
            
            $result = $stmt->execute();
            
            if ($result) {
                $transactionId = $db->lastInsertId();
                echo "<p>✅ 9. Transação inserida: ID {$transactionId}</p>";
                
                // Commit
                $db->commit();
                echo "<p>✅ 10. Transação commitada</p>";
                
                return [
                    'status' => true,
                    'message' => 'Transação registrada com sucesso!',
                    'data' => [
                        'transaction_id' => $transactionId,
                        'valor_total' => $data['valor_total'],
                        'valor_cashback' => $valorCliente
                    ]
                ];
            } else {
                echo "<p style='color: red;'>❌ Falha no INSERT</p>";
                $db->rollBack();
                return ['status' => false, 'message' => 'Falha ao inserir no banco'];
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Erro no try interno: " . $e->getMessage() . "</p>";
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return ['status' => false, 'message' => 'Erro na transação: ' . $e->getMessage()];
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro geral: " . $e->getMessage() . "</p>";
        return ['status' => false, 'message' => 'Erro geral: ' . $e->getMessage()];
    }
}

try {
    // Simular sessão
    $db = Database::getConnection();
    $storeQuery = "SELECT l.*, u.id as user_id, u.email FROM lojas l JOIN usuarios u ON l.usuario_id = u.id WHERE l.status = 'aprovado' LIMIT 1";
    $storeStmt = $db->prepare($storeQuery);
    $storeStmt->execute();
    $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
    
    $clientQuery = "SELECT id, nome FROM usuarios WHERE tipo = 'cliente' AND status = 'ativo' LIMIT 1";
    $clientStmt = $db->prepare($clientQuery);
    $clientStmt->execute();
    $client = $clientStmt->fetch(PDO::FETCH_ASSOC);
    
    $_SESSION['user_id'] = $store['user_id'];
    $_SESSION['user_type'] = 'loja';
    $_SESSION['user_email'] = $store['email'];
    $_SESSION['store_id'] = $store['id'];
    
    echo "<p>🏪 Loja: {$store['nome_fantasia']}</p>";
    echo "<p>👤 Cliente: {$client['nome']}</p>";
    
    $data = [
        'loja_id' => $store['id'],
        'usuario_id' => $client['id'],
        'valor_total' => 15.00,
        'codigo_transacao' => 'MINIMAL_' . time()
    ];
    
    echo "<h3>📋 Dados:</h3>";
    echo "<pre>" . print_r($data, true) . "</pre>";
    
    echo "<h3>🚀 Executando função minimal...</h3>";
    $result = registerTransactionMinimal($data);
    
    echo "<h3>📊 Resultado:</h3>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    if ($result['status']) {
        echo "<p style='color: green;'>🎉 SUCESSO!</p>";
    } else {
        echo "<p style='color: red;'>❌ ERRO: {$result['message']}</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ EXCEÇÃO: " . $e->getMessage() . "</p>";
}
?>

<style>
pre { background: #f8f8f8; padding: 8px; border-radius: 3px; overflow-x: auto; }
</style>
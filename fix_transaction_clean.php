<?php
// Versão limpa da função registerTransaction apenas com o core MVP
session_start();
require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'controllers/AuthController.php';

class TransactionControllerClean {
    public static function registerTransactionClean($data) {
        try {
            // 1. Validar campos
            $requiredFields = ['loja_id', 'usuario_id', 'valor_total', 'codigo_transacao'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    return ['status' => false, 'message' => 'Campo faltante: ' . $field];
                }
            }
            
            // 2. Verificar autenticação
            if (!AuthController::isAuthenticated()) {
                return ['status' => false, 'message' => 'Usuário não autenticado'];
            }
            
            if (!AuthController::isStore() && !AuthController::isAdmin()) {
                return ['status' => false, 'message' => 'Apenas lojas podem registrar'];
            }
            
            $db = Database::getConnection();
            
            // 3. Verificar cliente
            $userStmt = $db->prepare("SELECT id, nome FROM usuarios WHERE id = ? AND tipo = ? AND status = ?");
            $userStmt->execute([$data['usuario_id'], USER_TYPE_CLIENT, USER_ACTIVE]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return ['status' => false, 'message' => 'Cliente não encontrado'];
            }
            
            // 4. Verificar loja e MVP
            $storeStmt = $db->prepare("
                SELECT l.*, COALESCE(u.mvp, 'nao') as store_mvp 
                FROM lojas l 
                JOIN usuarios u ON l.usuario_id = u.id 
                WHERE l.id = ? AND l.status = ?
            ");
            $storeStmt->execute([$data['loja_id'], STORE_APPROVED]);
            $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$store) {
                return ['status' => false, 'message' => 'Loja não encontrada'];
            }
            
            $isStoreMvp = ($store['store_mvp'] === 'sim');
            
            // 5. Calcular valores
            $valorCashbackTotal = ($data['valor_total'] * 10) / 100;
            $valorCashbackCliente = ($data['valor_total'] * 5) / 100;
            $valorCashbackAdmin = ($data['valor_total'] * 5) / 100;
            $valorLoja = 0.00;
            
            // 6. Definir status
            $transactionStatus = $isStoreMvp ? TRANSACTION_APPROVED : TRANSACTION_PENDING;
            
            // 7. Inserir no banco
            $db->beginTransaction();
            
            $insertStmt = $db->prepare("
                INSERT INTO transacoes_cashback (
                    usuario_id, loja_id, valor_total, valor_cashback,
                    valor_cliente, valor_admin, valor_loja, codigo_transacao,
                    data_transacao, status, descricao
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $dataTransacao = $data['data_transacao'] ?? date('Y-m-d H:i:s');
            $descricao = $data['descricao'] ?? 'Transação limpa - ' . date('Y-m-d H:i:s');
            
            $result = $insertStmt->execute([
                $data['usuario_id'],
                $data['loja_id'],
                $data['valor_total'],
                $valorCashbackTotal,
                $valorCashbackCliente,
                $valorCashbackAdmin,
                $valorLoja,
                $data['codigo_transacao'],
                $dataTransacao,
                $transactionStatus,
                $descricao
            ]);
            
            if (!$result) {
                $db->rollBack();
                return ['status' => false, 'message' => 'Falha ao inserir no banco'];
            }
            
            $transactionId = $db->lastInsertId();
            
            // 8. Commit
            $db->commit();
            
            // 9. Se MVP, creditar cashback
            if ($isStoreMvp && $valorCashbackCliente > 0) {
                require_once 'models/CashbackBalance.php';
                $balanceModel = new CashbackBalance();
                $descricaoCashback = "Cashback MVP instantâneo - Código: " . $data['codigo_transacao'];
                $creditResult = $balanceModel->addBalance(
                    $data['usuario_id'],
                    $data['loja_id'],
                    $valorCashbackCliente,
                    $descricaoCashback,
                    $transactionId
                );
            }
            
            // 10. Retornar sucesso
            $successMessage = $isStoreMvp ? 
                '🎉 Transação MVP aprovada instantaneamente! Cashback creditado automaticamente.' :
                'Transação registrada com sucesso!';
            
            return [
                'status' => true,
                'message' => $successMessage,
                'data' => [
                    'transaction_id' => $transactionId,
                    'valor_original' => $data['valor_total'],
                    'valor_cashback' => $valorCashbackCliente,
                    'is_mvp' => $isStoreMvp,
                    'status_transacao' => $transactionStatus,
                    'cashback_creditado' => ($isStoreMvp && isset($creditResult) && $creditResult)
                ]
            ];
            
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            return ['status' => false, 'message' => 'Erro limpo: ' . $e->getMessage()];
        }
    }
}

echo "<h2>🧹 Teste com Função Limpa</h2>";

try {
    // Setup
    $db = Database::getConnection();
    $storeQuery = "SELECT l.*, u.id as user_id, u.email, u.mvp FROM lojas l JOIN usuarios u ON l.usuario_id = u.id WHERE l.status = 'aprovado' AND u.mvp = 'sim' LIMIT 1";
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
    
    echo "<p>🏪 Loja: {$store['nome_fantasia']} (MVP: {$store['mvp']})</p>";
    echo "<p>👤 Cliente: {$client['nome']}</p>";
    
    $data = [
        'usuario_id' => $client['id'],
        'loja_id' => $store['id'],
        'valor_total' => 25.00,
        'codigo_transacao' => 'CLEAN_' . time(),
        'descricao' => 'Teste função limpa - ' . date('Y-m-d H:i:s'),
        'data_transacao' => date('Y-m-d H:i:s')
    ];
    
    echo "<h3>📋 Dados:</h3>";
    echo "<pre>" . print_r($data, true) . "</pre>";
    
    echo "<h3>🚀 Executando função limpa...</h3>";
    $result = TransactionControllerClean::registerTransactionClean($data);
    
    echo "<h3>📊 Resultado:</h3>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    if ($result['status']) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
        echo "<h4>🎉 SUCESSO COM FUNÇÃO LIMPA!</h4>";
        echo "<p>Transaction ID: {$result['data']['transaction_id']}</p>";
        echo "<p>MVP: " . ($result['data']['is_mvp'] ? '🏆 SIM' : '❌ NÃO') . "</p>";
        echo "<p>Status: {$result['data']['status_transacao']}</p>";
        echo "<p>Cashback Creditado: " . ($result['data']['cashback_creditado'] ? '✅ SIM' : '❌ NÃO') . "</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<h4>❌ ERRO!</h4>";
        echo "<p>Mensagem: {$result['message']}</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
?>

<style>
pre { background: #f8f8f8; padding: 10px; border-radius: 3px; overflow-x: auto; }
</style>
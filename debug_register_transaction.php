<?php
// debug_register_transaction.php - Debug específico do registerTransaction

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/TransactionController.php';
require_once 'models/CashbackBalance.php';

// Simular autenticação de loja
session_start();
$_SESSION['user_id'] = 1; // ID de um usuário loja
$_SESSION['user_type'] = 'loja';

echo "<pre>";
echo "=== DEBUG REGISTER TRANSACTION ===\n\n";

try {
    // Testar cada validação
    echo "1. Testando validações básicas...\n";
    
    $transactionData = [
        'usuario_id' => 9,
        'loja_id' => 13,
        'valor_total' => 100.00,
        'codigo_transacao' => 'DEBUG_' . time(),
        'usar_saldo' => true,
        'valor_saldo_usado' => 30.00
    ];
    
    // Testar autenticação
    if (!AuthController::isAuthenticated()) {
        echo "ERRO: Não autenticado\n";
        exit;
    }
    echo "✓ Autenticação OK\n";
    
    // Testar permissão
    if (!AuthController::isStore() && !AuthController::isAdmin()) {
        echo "ERRO: Sem permissão\n";
        exit;
    }
    echo "✓ Permissão OK\n";
    
    // Testar conexão com banco
    $db = Database::getConnection();
    if (!$db) {
        echo "ERRO: Conexão com banco falhou\n";
        exit;
    }
    echo "✓ Conexão com banco OK\n";
    
    echo "\n2. Testando consultas...\n";
    
    // Testar consulta do cliente
    $userStmt = $db->prepare("SELECT id, nome, email FROM usuarios WHERE id = ? AND tipo = ? AND status = ?");
    $userStmt->execute([$transactionData['usuario_id'], USER_TYPE_CLIENT, USER_ACTIVE]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "ERRO: Cliente não encontrado\n";
        exit;
    }
    echo "✓ Cliente encontrado: {$user['nome']}\n";
    
    // Testar consulta da loja
    $storeStmt = $db->prepare("SELECT * FROM lojas WHERE id = ? AND status = ?");
    $storeStmt->execute([$transactionData['loja_id'], STORE_APPROVED]);
    $store = $storeStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$store) {
        echo "ERRO: Loja não encontrada\n";
        exit;
    }
    echo "✓ Loja encontrada: {$store['nome_fantasia']}\n";
    
    // Testar saldo
    $balanceModel = new CashbackBalance();
    $saldoDisponivel = $balanceModel->getStoreBalance($transactionData['usuario_id'], $transactionData['loja_id']);
    echo "✓ Saldo disponível: R$ " . number_format($saldoDisponivel, 2, ',', '.') . "\n";
    
    if ($saldoDisponivel < $transactionData['valor_saldo_usado']) {
        echo "ERRO: Saldo insuficiente\n";
        exit;
    }
    
    echo "\n3. Testando configurações de cashback...\n";
    
    // Testar configurações
    $configStmt = $db->prepare("SELECT * FROM configuracoes_cashback ORDER BY id DESC LIMIT 1");
    $configStmt->execute();
    $config = $configStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($config) {
        echo "✓ Configurações encontradas\n";
    } else {
        echo "⚠️  Nenhuma configuração encontrada, usando padrão\n";
    }
    
    echo "\n4. Testando cálculos...\n";
    
    $valorEfetivamentePago = $transactionData['valor_total'] - $transactionData['valor_saldo_usado'];
    echo "✓ Valor efetivamente pago: R$ " . number_format($valorEfetivamentePago, 2, ',', '.') . "\n";
    
    $porcentagemCliente = $config['porcentagem_cliente'] ?? 5.00;
    $valorCashbackCliente = ($valorEfetivamentePago * $porcentagemCliente) / 100;
    echo "✓ Cashback cliente: R$ " . number_format($valorCashbackCliente, 2, ',', '.') . "\n";
    
    echo "\n5. Testando inserção na tabela transacoes_cashback...\n";
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("
            INSERT INTO transacoes_cashback (
                usuario_id, loja_id, valor_total, valor_cashback,
                valor_cliente, valor_admin, valor_loja, codigo_transacao, 
                data_transacao, status, descricao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $dataTransacao = date('Y-m-d H:i:s');
        $result = $stmt->execute([
            $transactionData['usuario_id'],
            $transactionData['loja_id'],
            $transactionData['valor_total'],
            $valorCashbackCliente * 2, // Total = cliente + admin
            $valorCashbackCliente,
            $valorCashbackCliente,
            0,
            $transactionData['codigo_transacao'],
            $dataTransacao,
            'pendente',
            'Teste debug'
        ]);
        
        if ($result) {
            $transactionId = $db->lastInsertId();
            echo "✓ Transação inserida - ID: {$transactionId}\n";
            
            echo "\n6. Testando débito de saldo...\n";
            
            $descricaoUso = "Teste debug - Transação #{$transactionId}";
            $debitResult = $balanceModel->useBalance(
                $transactionData['usuario_id'], 
                $transactionData['loja_id'], 
                $transactionData['valor_saldo_usado'], 
                $descricaoUso, 
                $transactionId
            );
            
            if ($debitResult) {
                echo "✓ Saldo debitado com sucesso\n";
                
                // Verificar saldo final
                $saldoFinal = $balanceModel->getStoreBalance($transactionData['usuario_id'], $transactionData['loja_id']);
                echo "✓ Saldo final: R$ " . number_format($saldoFinal, 2, ',', '.') . "\n";
                
                $db->commit();
                echo "\n✓ TESTE COMPLETO - TUDO FUNCIONOU!\n";
                
            } else {
                echo "✗ ERRO ao debitar saldo\n";
                $db->rollBack();
            }
            
        } else {
            echo "✗ ERRO na inserção da transação\n";
            $errorInfo = $stmt->errorInfo();
            echo "Erro SQL: " . print_r($errorInfo, true) . "\n";
            $db->rollBack();
        }
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo "✗ EXCEÇÃO: " . $e->getMessage() . "\n";
        echo "Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
} catch (Exception $e) {
    echo "ERRO GERAL: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>
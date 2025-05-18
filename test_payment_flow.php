<?php
// test_payment_flow.php - ATUALIZAR
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/TransactionController.php';

// Simular login de admin
session_start();
$_SESSION['user_id'] = 11; // ID do admin
$_SESSION['user_type'] = 'admin';

echo "<h3>Teste do fluxo de pagamento:</h3>";

try {
    $db = Database::getConnection();
    
    // Criar transação
    $stmt = $db->prepare("
        INSERT INTO transacoes_cashback 
        (usuario_id, loja_id, valor_total, valor_cashback, valor_cliente, valor_admin, valor_loja, status) 
        VALUES (9, 9, 100.00, 10.00, 5.00, 5.00, 0.00, 'pendente')
    ");
    $stmt->execute();
    $transId = $db->lastInsertId();
    echo "✅ Transação criada: ID $transId<br>";
    
    // Testar pagamento
    $paymentData = [
        'loja_id' => 9,
        'transacoes' => [$transId],
        'valor_total' => 10.00,
        'metodo_pagamento' => 'teste_completo'
    ];
    
    
    $result = TransactionController::registerPayment($paymentData);
    echo "✅ Pagamento: " . ($result['status'] ? 'SUCESSO' : 'ERRO - ' . $result['message']) . "<br>";
    
    if ($result['status']) {
        $paymentId = $result['data']['payment_id'];
        echo "✅ Payment ID: $paymentId<br>";
        
        // Testar aprovação
        $approval = TransactionController::approvePayment($paymentId, 'Teste completo');
        echo "✅ Aprovação: " . ($approval['status'] ? 'SUCESSO' : 'ERRO - ' . $approval['message']) . "<br>";
        
        if ($approval['status']) {
            // Verificar se transação foi atualizada
            $check = $db->prepare("SELECT status FROM transacoes_cashback WHERE id = ?");
            $check->execute([$transId]);
            $status = $check->fetchColumn();
            echo "✅ Status final da transação: $status<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage();
}
?>
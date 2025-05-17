<?php
// test_payment_flow.php - CRIAR TEMPORARIAMENTE
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/TransactionController.php';

try {
    echo "<h3>Teste do fluxo de pagamento:</h3>";
    
    // 1. Criar transação de teste
    $db = Database::getConnection();
    
    $stmt = $db->prepare("
        INSERT INTO transacoes_cashback 
        (usuario_id, loja_id, valor_total, valor_cashback, valor_cliente, valor_admin, valor_loja, status) 
        VALUES (9, 9, 100.00, 10.00, 5.00, 5.00, 0.00, 'pendente')
    ");
    $stmt->execute();
    $transId = $db->lastInsertId();
    echo "Transação criada: ID $transId<br>";
    
    // 2. Testar pagamento
    $paymentData = [
        'loja_id' => 9,
        'transacoes' => [$transId],
        'valor_total' => 10.00,
        'metodo_pagamento' => 'teste_flow'
    ];
    
    $result = TransactionController::registerPayment($paymentData);
    echo "Resultado do pagamento: " . print_r($result, true) . "<br>";
    
    if ($result['status']) {
        $paymentId = $result['data']['payment_id'];
        
        // 3. Verificar associação
        $check = $db->prepare("SELECT * FROM pagamentos_transacoes WHERE pagamento_id = ?");
        $check->execute([$paymentId]);
        $associations = $check->fetchAll(PDO::FETCH_ASSOC);
        echo "Associações criadas: " . print_r($associations, true) . "<br>";
        
        // 4. Testar aprovação
        $approval = TransactionController::approvePayment($paymentId, 'Teste de aprovação');
        echo "Resultado da aprovação: " . print_r($approval, true) . "<br>";
    }
    
} catch (Exception $e) {
    echo "Erro no teste: " . $e->getMessage();
}
?>
<?php
// fix-approved-payments.php
// Script para corrigir pagamentos que foram aprovados mas não processados

require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/TransactionController.php';
require_once 'utils/MercadoPagoClient.php';

$db = Database::getConnection();

// Buscar pagamentos PIX que estão pendentes mas podem ter sido aprovados
$stmt = $db->prepare("
    SELECT * FROM pagamentos_comissao 
    WHERE metodo_pagamento = 'pix_mercadopago' 
    AND status IN ('pix_aguardando', 'pendente') 
    AND mp_payment_id IS NOT NULL
    ORDER BY data_registro DESC
");
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "🔍 Verificando " . count($payments) . " pagamentos...\n\n";

$mpClient = new MercadoPagoClient();
$processedCount = 0;

foreach ($payments as $payment) {
    echo "📋 Verificando pagamento ID: {$payment['id']} (MP: {$payment['mp_payment_id']})\n";
    
    // Consultar status no Mercado Pago
    $statusResponse = $mpClient->getPaymentStatus($payment['mp_payment_id']);
    
    if ($statusResponse['status'] && isset($statusResponse['data']['status'])) {
        $mpStatus = $statusResponse['data']['status'];
        echo "   Status no MP: {$mpStatus}\n";
        
        if ($mpStatus === 'approved') {
            echo "   ✅ Pagamento aprovado no MP, processando...\n";
            
            $result = TransactionController::approvePaymentAutomatically(
                $payment['id'], 
                'Pagamento PIX aprovado - Processado via script de correção'
            );
            
            if ($result['status']) {
                echo "   ✅ Pagamento processado com sucesso!\n";
                echo "   💰 Cashback liberado: R$ " . number_format($result['data']['cashback_liberado'], 2, ',', '.') . "\n";
                echo "   📊 Transações aprovadas: " . $result['data']['transacoes_aprovadas'] . "\n";
                $processedCount++;
                
                // Atualizar status do MP no banco
                $updateStmt = $db->prepare("
                    UPDATE pagamentos_comissao 
                    SET mp_status = 'approved', pix_paid_at = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->execute([$payment['id']]);
                
            } else {
                echo "   ❌ Erro ao processar: " . $result['message'] . "\n";
            }
        } else {
            echo "   ⏳ Status: {$mpStatus} (não aprovado)\n";
        }
    } else {
        echo "   ❌ Erro ao consultar MP: " . ($statusResponse['message'] ?? 'Erro desconhecido') . "\n";
    }
    
    echo "\n";
}

echo "🎉 Script finalizado!\n";
echo "📊 Pagamentos processados: {$processedCount}\n";
?>
<?php
/**
 * CRIAR NOVA TRANSAÇÃO PARA TESTAR INTEGRAÇÃO COMPLETA
 */

require_once __DIR__ . '/config/database.php';

echo "=== CRIANDO NOVA TRANSAÇÃO PARA TESTE ===\n";

try {
    $db = Database::getConnection();

    // Usar dados da Cecilia que sabemos que tem telefone
    $usuarioId = 162; // Cecilia 3
    $lojaId = 59; // Sync Holding

    echo "👤 Criando transação para usuário ID: {$usuarioId}\n";
    echo "🏪 Loja ID: {$lojaId}\n";

    // Criar transação de teste
    $stmt = $db->prepare("
        INSERT INTO transacoes_cashback
        (usuario_id, loja_id, valor_total, valor_cashback, valor_cliente, valor_admin, valor_loja, status, data_transacao)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $valorTotal = 250.00;
    $valorCashback = 12.50;
    $valorCliente = 11.25;
    $valorAdmin = 1.25;
    $valorLoja = 0.00;
    $status = 'pendente';

    $stmt->execute([
        $usuarioId,
        $lojaId,
        $valorTotal,
        $valorCashback,
        $valorCliente,
        $valorAdmin,
        $valorLoja,
        $status
    ]);

    $transactionId = $db->lastInsertId();

    echo "✅ Transação criada com sucesso!\n";
    echo "🆔 ID da transação: {$transactionId}\n";
    echo "💰 Valor: R$ " . number_format($valorTotal, 2, ',', '.') . "\n";
    echo "🎁 Cashback: R$ " . number_format($valorCliente, 2, ',', '.') . "\n";
    echo "📊 Status: {$status}\n";

    echo "\n⚠️  IMPORTANTE: Esta transação foi criada diretamente no banco.\n";
    echo "Para testar a integração completa, seria necessário criar via TransactionController.\n";
    echo "Mas agora você pode monitorar se novas transações via sistema acionam o UltraDirectNotifier.\n";

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "\n=== TRANSAÇÃO CRIADA ===\n";
?>
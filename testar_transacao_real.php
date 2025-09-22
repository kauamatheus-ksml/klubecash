<?php
/**
 * TESTAR NOTIFICAÇÃO COM TRANSAÇÃO REAL
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/UltraDirectNotifier.php';

echo "=== TESTANDO NOTIFICAÇÃO COM TRANSAÇÃO REAL ===\n";

try {
    $db = Database::getConnection();

    // Usar a transação mais recente (ID 574)
    $transactionId = 574;

    echo "🔍 Buscando dados da transação {$transactionId}...\n";

    // Buscar dados completos da transação
    $stmt = $db->prepare("
        SELECT t.*, u.nome as cliente_nome, u.telefone as cliente_telefone, l.nome_fantasia as loja_nome
        FROM transacoes_cashback t
        LEFT JOIN usuarios u ON t.usuario_id = u.id
        LEFT JOIN lojas l ON t.loja_id = l.id
        WHERE t.id = ?
    ");
    $stmt->execute([$transactionId]);
    $transactionData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transactionData) {
        echo "❌ Transação não encontrada\n";
        exit;
    }

    echo "📋 Dados da transação:\n";
    echo "   👤 Cliente: {$transactionData['cliente_nome']}\n";
    echo "   📱 Telefone: " . ($transactionData['cliente_telefone'] ?? 'NÃO CADASTRADO') . "\n";
    echo "   🏪 Loja: {$transactionData['loja_nome']}\n";
    echo "   💰 Valor: R$ " . number_format($transactionData['valor_total'], 2, ',', '.') . "\n";
    echo "   🎁 Cashback: R$ " . number_format($transactionData['valor_cliente'], 2, ',', '.') . "\n";
    echo "   📊 Status: {$transactionData['status']}\n";

    if (empty($transactionData['cliente_telefone'])) {
        echo "\n❌ Cliente não tem telefone cadastrado. Impossível enviar notificação.\n";
        exit;
    }

    echo "\n🚀 Testando UltraDirectNotifier...\n";

    $notifier = new UltraDirectNotifier();
    $result = $notifier->notifyTransaction($transactionData);

    echo "\n📋 Resultado do envio:\n";
    print_r($result);

    if ($result['success']) {
        echo "\n✅ NOTIFICAÇÃO ENVIADA COM SUCESSO!\n";
        echo "⏱️  Tempo: {$result['time_ms']}ms\n";
    } else {
        echo "\n❌ FALHA NA NOTIFICAÇÃO!\n";
        echo "🚫 Erro: {$result['error']}\n";
    }

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>
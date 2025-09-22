<?php
/**
 * SIMULAR TRANSAÇÃO REAL PARA TESTAR NOTIFICAÇÃO
 */

require_once __DIR__ . '/config/database.php';

echo "=== SIMULANDO TRANSAÇÃO REAL ===\n";

try {
    $db = Database::getConnection();

    // Buscar um usuário existente para testar
    $stmt = $db->query("SELECT id, nome, telefone FROM usuarios WHERE telefone IS NOT NULL AND telefone != '' LIMIT 1");
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo "❌ Nenhum usuário com telefone encontrado\n";
        exit;
    }

    echo "👤 Usuário encontrado: {$usuario['nome']} - {$usuario['telefone']}\n";

    // Buscar uma loja
    $stmt = $db->query("SELECT id, nome_fantasia FROM lojas LIMIT 1");
    $loja = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$loja) {
        echo "❌ Nenhuma loja encontrada\n";
        exit;
    }

    echo "🏪 Loja encontrada: {$loja['nome_fantasia']}\n";

    // Criar transação de teste
    $stmt = $db->prepare("
        INSERT INTO transacoes_cashback
        (usuario_id, loja_id, valor_total, percentual_cashback, valor_cliente, status, data_transacao, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $valorTotal = 100.00;
    $percentual = 5.0;
    $valorCliente = 5.00;
    $status = 'pendente';

    $stmt->execute([
        $usuario['id'],
        $loja['id'],
        $valorTotal,
        $percentual,
        $valorCliente,
        $status
    ]);

    $transactionId = $db->lastInsertId();

    echo "💰 Transação criada com ID: {$transactionId}\n";
    echo "📱 Valor: R$ " . number_format($valorTotal, 2, ',', '.') . "\n";
    echo "🎁 Cashback: R$ " . number_format($valorCliente, 2, ',', '.') . "\n";

    // Agora vamos simular o processo de notificação que aconteceria no TransactionController
    echo "\n🔔 Simulando processo de notificação...\n";

    // Incluir o UltraDirectNotifier
    require_once __DIR__ . '/classes/UltraDirectNotifier.php';

    if (class_exists('UltraDirectNotifier')) {
        echo "✅ UltraDirectNotifier encontrado\n";

        $notifier = new UltraDirectNotifier();

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

        if ($transactionData && !empty($transactionData['cliente_telefone'])) {
            echo "📞 Enviando notificação para: {$transactionData['cliente_telefone']}\n";

            $result = $notifier->notifyTransaction($transactionData);

            echo "📋 Resultado:\n";
            print_r($result);

            if ($result['success']) {
                echo "✅ NOTIFICAÇÃO ENVIADA COM SUCESSO!\n";
            } else {
                echo "❌ FALHA NA NOTIFICAÇÃO: " . $result['error'] . "\n";
            }
        } else {
            echo "❌ Dados insuficientes para notificação\n";
        }
    } else {
        echo "❌ UltraDirectNotifier não encontrado\n";
    }

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DA SIMULAÇÃO ===\n";
?>
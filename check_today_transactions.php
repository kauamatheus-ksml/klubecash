<?php
require_once __DIR__ . '/config/database.php';

$db = Database::getConnection();

echo "=== TRANSAÇÕES DE HOJE ===\n\n";

$stmt = $db->prepare("
    SELECT
        t.id,
        t.usuario_id,
        t.loja_id,
        t.valor_total,
        t.status,
        t.data_transacao,
        u.nome as usuario_nome,
        u.telefone,
        l.nome_fantasia as loja_nome
    FROM transacoes_cashback t
    LEFT JOIN usuarios u ON t.usuario_id = u.id
    LEFT JOIN lojas l ON t.loja_id = l.id
    WHERE DATE(t.data_transacao) = CURDATE()
    ORDER BY t.id DESC
    LIMIT 10
");

$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($transactions)) {
    echo "❌ Nenhuma transação criada hoje\n";
} else {
    echo "✅ Transações encontradas hoje:\n\n";
    foreach ($transactions as $t) {
        echo "ID: {$t['id']} | ";
        echo "Usuário: {$t['usuario_nome']} ({$t['telefone']}) | ";
        echo "Loja: {$t['loja_nome']} | ";
        echo "Valor: R$ " . number_format($t['valor_total'], 2, ',', '.') . " | ";
        echo "Status: {$t['status']} | ";
        echo "Data: {$t['data_transacao']}\n";
    }
}

echo "\n=== LOGS WHATSAPP DE HOJE ===\n\n";

$stmt = $db->prepare("
    SELECT id, type, phone, success, created_at, additional_data
    FROM whatsapp_logs
    WHERE DATE(created_at) = CURDATE()
    ORDER BY id DESC
    LIMIT 10
");

$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($logs)) {
    echo "❌ Nenhum log WhatsApp de hoje\n";
} else {
    echo "✅ Logs WhatsApp de hoje:\n\n";
    foreach ($logs as $log) {
        echo "ID: {$log['id']} | ";
        echo "Tipo: {$log['type']} | ";
        echo "Telefone: {$log['phone']} | ";
        echo "Sucesso: " . ($log['success'] ? 'SIM' : 'NÃO') . " | ";
        echo "Data: {$log['created_at']}";

        if ($log['additional_data']) {
            $data = json_decode($log['additional_data'], true);
            if (isset($data['transaction_id'])) {
                echo " | Transação: {$data['transaction_id']}";
            }
        }
        echo "\n";
    }
}
?>
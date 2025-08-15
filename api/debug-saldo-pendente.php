<?php
// api/debug-saldo-pendente.php
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

echo "<h1>🔍 Debug - Saldo Pendente por Loja</h1>";

$userId = 9; // ID do Kaua

try {
    $db = Database::getConnection();
    
    // Mostrar todas as transações pendentes
    echo "<h3>📋 Todas as Transações Pendentes do Usuário {$userId}:</h3>";
    
    $stmt = $db->prepare("
        SELECT t.id, t.loja_id, l.nome_fantasia, t.valor_cliente, t.valor_cashback, t.status, t.data_transacao
        FROM transacoes_cashback t
        LEFT JOIN lojas l ON t.loja_id = l.id
        WHERE t.usuario_id = :user_id 
        ORDER BY t.data_transacao DESC
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Loja ID</th><th>Nome Loja</th><th>Valor Cliente</th><th>Status</th><th>Data</th></tr>";
    
    while ($trans = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $style = $trans['status'] === 'pendente' ? 'background: #fff3cd;' : '';
        echo "<tr style='{$style}'>";
        echo "<td>{$trans['id']}</td>";
        echo "<td>{$trans['loja_id']}</td>";
        echo "<td>{$trans['nome_fantasia']}</td>";
        echo "<td>R$ " . number_format($trans['valor_cliente'], 2, ',', '.') . "</td>";
        echo "<td><strong>{$trans['status']}</strong></td>";
        echo "<td>{$trans['data_transacao']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Mostrar saldos disponíveis
    echo "<h3>💰 Saldos Disponíveis por Loja:</h3>";
    
    require_once __DIR__ . '/../models/CashbackBalance.php';
    $balanceModel = new CashbackBalance();
    $saldos = $balanceModel->getAllUserBalances($userId);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Loja ID</th><th>Nome</th><th>Saldo Disponível</th></tr>";
    
    foreach ($saldos as $saldo) {
        echo "<tr>";
        echo "<td>{$saldo['loja_id']}</td>";
        echo "<td>{$saldo['nome_fantasia']}</td>";
        echo "<td>R$ " . number_format($saldo['saldo_disponivel'], 2, ',', '.') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>ERRO: " . $e->getMessage() . "</div>";
}
?>
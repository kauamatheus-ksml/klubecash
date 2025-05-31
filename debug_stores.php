<?php
// debug_stores.php - Para testar os dados diretamente

require_once 'config/database.php';
require_once 'config/constants.php';

try {
    $db = Database::getConnection();
    
    echo "<h2>Verificação de Dados das Lojas</h2>";
    
    // 1. Verificar lojas
    echo "<h3>1. Lojas cadastradas:</h3>";
    $lojas = $db->query("SELECT id, nome_fantasia, status FROM lojas ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($lojas as $loja) {
        echo "ID: {$loja['id']} - {$loja['nome_fantasia']} - Status: {$loja['status']}<br>";
    }
    
    // 2. Verificar saldos
    echo "<h3>2. Saldos por loja:</h3>";
    $saldos = $db->query("
        SELECT 
            cs.loja_id,
            l.nome_fantasia,
            cs.usuario_id,
            cs.saldo_disponivel,
            cs.total_creditado,
            cs.total_usado
        FROM cashback_saldos cs
        JOIN lojas l ON cs.loja_id = l.id
        WHERE cs.saldo_disponivel > 0
        ORDER BY cs.loja_id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($saldos)) {
        echo "Nenhum saldo encontrado!<br>";
    } else {
        foreach ($saldos as $saldo) {
            echo "Loja: {$saldo['nome_fantasia']} (ID: {$saldo['loja_id']}) - Cliente: {$saldo['usuario_id']} - Saldo: R$ {$saldo['saldo_disponivel']}<br>";
        }
    }
    
    // 3. Verificar transações
    echo "<h3>3. Transações com uso de saldo:</h3>";
    $transacoes = $db->query("
        SELECT 
            tc.id,
            tc.loja_id,
            l.nome_fantasia,
            tc.valor_total,
            tsu.valor_usado,
            tc.status
        FROM transacoes_cashback tc
        JOIN lojas l ON tc.loja_id = l.id
        LEFT JOIN transacoes_saldo_usado tsu ON tc.id = tsu.transacao_id
        WHERE tc.status = 'aprovado'
        ORDER BY tc.loja_id, tc.id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($transacoes as $trans) {
        echo "Loja: {$trans['nome_fantasia']} - Transação: {$trans['id']} - Valor: R$ {$trans['valor_total']} - Saldo usado: R$ " . ($trans['valor_usado'] ?? '0,00') . "<br>";
    }
    
    // 4. Query final como no sistema
    echo "<h3>4. Query completa (como no sistema):</h3>";
    $resultado = $db->query("
        SELECT 
            l.id,
            l.nome_fantasia,
            l.status,
            -- Saldo dos clientes
            COALESCE(saldo_info.clientes_com_saldo, 0) as clientes_com_saldo,
            COALESCE(saldo_info.total_saldo_clientes, 0) as total_saldo_clientes,
            -- Transações
            COALESCE(trans_info.total_transacoes, 0) as total_transacoes,
            COALESCE(trans_info.transacoes_com_saldo, 0) as transacoes_com_saldo
        FROM lojas l
        LEFT JOIN (
            SELECT 
                loja_id,
                COUNT(DISTINCT usuario_id) as clientes_com_saldo,
                SUM(saldo_disponivel) as total_saldo_clientes
            FROM cashback_saldos 
            WHERE saldo_disponivel > 0
            GROUP BY loja_id
        ) saldo_info ON l.id = saldo_info.loja_id
        LEFT JOIN (
            SELECT 
                tc.loja_id,
                COUNT(DISTINCT tc.id) as total_transacoes,
                COUNT(DISTINCT CASE WHEN tsu.valor_usado > 0 THEN tc.id END) as transacoes_com_saldo
            FROM transacoes_cashback tc
            LEFT JOIN transacoes_saldo_usado tsu ON tc.id = tsu.transacao_id
            WHERE tc.status = 'aprovado'
            GROUP BY tc.loja_id
        ) trans_info ON l.id = trans_info.loja_id
        ORDER BY l.id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Status</th><th>Clientes c/ Saldo</th><th>Total Saldo</th><th>Total Trans</th><th>Trans c/ Saldo</th></tr>";
    foreach ($resultado as $row) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['nome_fantasia']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['clientes_com_saldo']}</td>";
        echo "<td>R$ " . number_format($row['total_saldo_clientes'], 2, ',', '.') . "</td>";
        echo "<td>{$row['total_transacoes']}</td>";
        echo "<td>{$row['transacoes_com_saldo']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
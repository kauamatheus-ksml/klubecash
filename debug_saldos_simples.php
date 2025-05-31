<?php
// debug_saldos_simples.php

session_start();
require_once 'config/database.php';

echo "<h2>🔍 Debug Saldos - Klube Cash</h2>";

try {
    $db = Database::getConnection();
    
    // 1. Verificar cashback_saldos
    echo "<h3>1. Dados da tabela cashback_saldos:</h3>";
    $saldos = $db->query("
        SELECT 
            cs.*,
            l.nome_fantasia 
        FROM cashback_saldos cs 
        JOIN lojas l ON cs.loja_id = l.id 
        ORDER BY cs.loja_id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($saldos)) {
        echo "❌ <strong>PROBLEMA ENCONTRADO:</strong> Tabela cashback_saldos está vazia!<br>";
        echo "Isso explica por que não aparecem saldos na tela.<br><br>";
    } else {
        echo "✅ Encontrados " . count($saldos) . " registros:<br><br>";
        foreach ($saldos as $saldo) {
            echo "🏪 <strong>{$saldo['nome_fantasia']}</strong> (Loja ID: {$saldo['loja_id']}, Usuario ID: {$saldo['usuario_id']})<br>";
            echo "&nbsp;&nbsp;💰 Saldo disponível: <strong>R$ " . number_format($saldo['saldo_disponivel'], 2, ',', '.') . "</strong><br>";
            echo "&nbsp;&nbsp;📈 Total creditado: R$ " . number_format($saldo['total_creditado'], 2, ',', '.') . "<br>";
            echo "&nbsp;&nbsp;📉 Total usado: R$ " . number_format($saldo['total_usado'], 2, ',', '.') . "<br><br>";
        }
    }
    
    // 2. Verificar transações aprovadas
    echo "<h3>2. Transações aprovadas:</h3>";
    $transacoes = $db->query("
        SELECT COUNT(*) as total, SUM(valor_cliente) as total_cashback 
        FROM transacoes_cashback 
        WHERE status = 'aprovado'
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "📊 Total de transações aprovadas: <strong>{$transacoes['total']}</strong><br>";
    echo "💰 Total cashback gerado: <strong>R$ " . number_format($transacoes['total_cashback'], 2, ',', '.') . "</strong><br><br>";
    
    // 3. Testar a query da tela de lojas
    echo "<h3>3. Testando query da tela de lojas:</h3>";
    $resultado = $db->query("
        SELECT 
            l.id,
            l.nome_fantasia,
            COALESCE(saldo_info.clientes_com_saldo, 0) as clientes_com_saldo,
            COALESCE(saldo_info.total_saldo_clientes, 0) as total_saldo_clientes
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
        ORDER BY l.id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Nome da Loja</th><th>Clientes com Saldo</th><th>Total Saldo</th></tr>";
    foreach ($resultado as $row) {
        $cor = $row['total_saldo_clientes'] > 0 ? 'style="background: #e8f5e9;"' : '';
        echo "<tr $cor>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['nome_fantasia']}</td>";
        echo "<td>{$row['clientes_com_saldo']}</td>";
        echo "<td>R$ " . number_format($row['total_saldo_clientes'], 2, ',', '.') . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // 4. Verificar se existe saldo > 0
    echo "<h3>4. Saldos ativos (> 0):</h3>";
    $saldosAtivos = $db->query("
        SELECT COUNT(*) as total, SUM(saldo_disponivel) as soma
        FROM cashback_saldos 
        WHERE saldo_disponivel > 0
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "📊 Registros com saldo > 0: <strong>{$saldosAtivos['total']}</strong><br>";
    echo "💰 Soma total dos saldos ativos: <strong>R$ " . number_format($saldosAtivos['soma'], 2, ',', '.') . "</strong><br>";
    
    // 5. Diagnóstico
    echo "<hr><h3>🔧 Diagnóstico:</h3>";
    if (empty($saldos)) {
        echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; color: #c62828;'>";
        echo "<strong>❌ PROBLEMA IDENTIFICADO:</strong><br>";
        echo "A tabela <code>cashback_saldos</code> está vazia. Isso significa que:<br>";
        echo "1. As transações não estão criando saldos para os clientes<br>";
        echo "2. Ou o processo de aprovação de transações não está funcionando<br>";
        echo "3. Ou há um problema na criação de registros de saldo<br>";
        echo "</div>";
    } else if ($saldosAtivos['total'] == 0) {
        echo "<div style='background: #fff3e0; padding: 15px; border-radius: 5px; color: #f57c00;'>";
        echo "<strong>⚠️ PROBLEMA:</strong><br>";
        echo "Existem registros na tabela cashback_saldos, mas todos têm saldo_disponivel = 0<br>";
        echo "Isso pode indicar que todos os saldos foram usados ou há um problema no cálculo<br>";
        echo "</div>";
    } else {
        echo "<div style='background: #e8f5e9; padding: 15px; border-radius: 5px; color: #2e7d32;'>";
        echo "<strong>✅ DADOS OK:</strong><br>";
        echo "Os dados estão corretos no banco. Se a tela não mostra, o problema está na query ou na renderização.<br>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border-radius: 5px; color: #c62828;'>";
    echo "<strong>❌ Erro:</strong> " . $e->getMessage();
    echo "</div>";
}
?>

<br><br>
<a href="views/admin/stores.php" style="background: #FF7A00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">← Voltar para Gestão de Lojas</a>
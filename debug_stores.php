<?php
// debug_saldos.php - Debug específico para saldos e transações

session_start();
require_once 'config/database.php';
require_once 'config/constants.php';
require_once 'controllers/AuthController.php';

// Verificar autenticação
if (!AuthController::isAuthenticated() || !AuthController::isAdmin()) {
    die('Acesso negado');
}

try {
    $db = Database::getConnection();
    
    echo "<h2>🔍 Debug Detalhado - Saldos e Transações</h2>";
    
    // 1. Verificar tabela cashback_saldos
    echo "<h3>1. 📊 Tabela cashback_saldos:</h3>";
    $saldos = $db->query("
        SELECT 
            cs.id,
            cs.usuario_id,
            cs.loja_id,
            l.nome_fantasia,
            cs.saldo_disponivel,
            cs.total_creditado,
            cs.total_usado,
            cs.data_criacao,
            cs.ultima_atualizacao
        FROM cashback_saldos cs
        JOIN lojas l ON cs.loja_id = l.id
        ORDER BY cs.loja_id, cs.usuario_id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($saldos)) {
        echo "❌ <strong>PROBLEMA:</strong> Nenhum registro encontrado na tabela cashback_saldos!<br><br>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Usuário</th><th>Loja</th><th>Nome Loja</th><th>Saldo Disponível</th><th>Total Creditado</th><th>Total Usado</th><th>Criação</th></tr>";
        
        foreach ($saldos as $saldo) {
            $cor = $saldo['saldo_disponivel'] > 0 ? 'style="background: #e8f5e9;"' : '';
            echo "<tr $cor>";
            echo "<td>{$saldo['id']}</td>";
            echo "<td>{$saldo['usuario_id']}</td>";
            echo "<td>{$saldo['loja_id']}</td>";
            echo "<td>{$saldo['nome_fantasia']}</td>";
            echo "<td><strong>R$ " . number_format($saldo['saldo_disponivel'], 2, ',', '.') . "</strong></td>";
            echo "<td>R$ " . number_format($saldo['total_creditado'], 2, ',', '.') . "</td>";
            echo "<td>R$ " . number_format($saldo['total_usado'], 2, ',', '.') . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($saldo['data_criacao'])) . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        // Resumo por loja
        echo "<h4>📈 Resumo por Loja:</h4>";
        $resumo = $db->query("
            SELECT 
                cs.loja_id,
                l.nome_fantasia,
                COUNT(DISTINCT cs.usuario_id) as clientes_com_saldo,
                SUM(cs.saldo_disponivel) as total_saldo_loja,
                COUNT(DISTINCT CASE WHEN cs.saldo_disponivel > 0 THEN cs.usuario_id END) as clientes_saldo_ativo
            FROM cashback_saldos cs
            JOIN lojas l ON cs.loja_id = l.id
            GROUP BY cs.loja_id, l.nome_fantasia
            ORDER BY cs.loja_id
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($resumo as $r) {
            echo "🏪 <strong>{$r['nome_fantasia']}</strong> (ID: {$r['loja_id']})<br>";
            echo "&nbsp;&nbsp;- Total clientes: {$r['clientes_com_saldo']}<br>";
            echo "&nbsp;&nbsp;- Clientes com saldo ativo: {$r['clientes_saldo_ativo']}<br>";
            echo "&nbsp;&nbsp;- Saldo total: R$ " . number_format($r['total_saldo_loja'], 2, ',', '.') . "<br><br>";
        }
    }
    
    // 2. Verificar tabela transacoes_cashback
    echo "<h3>2. 💳 Tabela transacoes_cashback:</h3>";
    $transacoes = $db->query("
        SELECT 
            tc.id,
            tc.usuario_id,
            tc.loja_id,
            l.nome_fantasia,
            tc.valor_total,
            tc.valor_cashback,
            tc.valor_cliente,
            tc.status,
            tc.data_transacao
        FROM transacoes_cashback tc
        JOIN lojas l ON tc.loja_id = l.id
        ORDER BY tc.loja_id, tc.data_transacao DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($transacoes)) {
        echo "❌ <strong>PROBLEMA:</strong> Nenhuma transação encontrada!<br><br>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Usuário</th><th>Loja</th><th>Valor Total</th><th>Cashback</th><th>Para Cliente</th><th>Status</th><th>Data</th></tr>";
        
        foreach ($transacoes as $trans) {
            $cor = $trans['status'] == 'aprovado' ? 'style="background: #e8f5e9;"' : 'style="background: #fff3cd;"';
            echo "<tr $cor>";
            echo "<td>{$trans['id']}</td>";
            echo "<td>{$trans['usuario_id']}</td>";
            echo "<td>{$trans['loja_id']} - {$trans['nome_fantasia']}</td>";
            echo "<td>R$ " . number_format($trans['valor_total'], 2, ',', '.') . "</td>";
            echo "<td>R$ " . number_format($trans['valor_cashback'], 2, ',', '.') . "</td>";
            echo "<td>R$ " . number_format($trans['valor_cliente'], 2, ',', '.') . "</td>";
            echo "<td><strong>{$trans['status']}</strong></td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($trans['data_transacao'])) . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
    // 3. Verificar tabela transacoes_saldo_usado
    echo "<h3>3. 💸 Tabela transacoes_saldo_usado:</h3>";
    $saldoUsado = $db->query("
        SELECT 
            tsu.id,
            tsu.transacao_id,
            tsu.usuario_id,
            tsu.loja_id,
            l.nome_fantasia,
            tsu.valor_usado,
            tsu.data_uso,
            tc.valor_total as valor_transacao
        FROM transacoes_saldo_usado tsu
        JOIN lojas l ON tsu.loja_id = l.id
        JOIN transacoes_cashback tc ON tsu.transacao_id = tc.id
        ORDER BY tsu.loja_id, tsu.data_uso DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($saldoUsado)) {
        echo "ℹ️ Nenhum uso de saldo registrado ainda.<br><br>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>ID</th><th>Transação</th><th>Usuário</th><th>Loja</th><th>Valor Usado</th><th>Valor Total</th><th>Data</th></tr>";
        
        foreach ($saldoUsado as $uso) {
            echo "<tr>";
            echo "<td>{$uso['id']}</td>";
            echo "<td>{$uso['transacao_id']}</td>";
            echo "<td>{$uso['usuario_id']}</td>";
            echo "<td>{$uso['loja_id']} - {$uso['nome_fantasia']}</td>";
            echo "<td><strong>R$ " . number_format($uso['valor_usado'], 2, ',', '.') . "</strong></td>";
            echo "<td>R$ " . number_format($uso['valor_transacao'], 2, ',', '.') . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($uso['data_uso'])) . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
    // 4. Testar a query exata do sistema
    echo "<h3>4. 🧪 Testando Query do Sistema (manageStoresWithBalance):</h3>";
    
    $queryTest = "
        SELECT 
            l.id,
            l.nome_fantasia,
            l.status,
            l.categoria,
            -- Saldo dos clientes
            COALESCE(saldo_info.clientes_com_saldo, 0) as clientes_com_saldo,
            COALESCE(saldo_info.total_saldo_clientes, 0) as total_saldo_clientes,
            -- Transações
            COALESCE(trans_info.total_transacoes, 0) as total_transacoes,
            COALESCE(trans_info.transacoes_com_saldo, 0) as transacoes_com_saldo,
            COALESCE(trans_info.total_saldo_usado, 0) as total_saldo_usado
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
                COUNT(DISTINCT CASE WHEN tsu.valor_usado > 0 THEN tc.id END) as transacoes_com_saldo,
                COALESCE(SUM(tsu.valor_usado), 0) as total_saldo_usado
            FROM transacoes_cashback tc
            LEFT JOIN transacoes_saldo_usado tsu ON tc.id = tsu.transacao_id
            WHERE tc.status = 'aprovado'
            GROUP BY tc.loja_id
        ) trans_info ON l.id = trans_info.loja_id
        ORDER BY l.id
    ";
    
    $resultado = $db->query($queryTest)->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>ID</th><th>Nome</th><th>Status</th><th>Categoria</th><th>Clientes c/ Saldo</th><th>Total Saldo</th><th>Total Trans</th><th>Trans c/ Saldo</th><th>Saldo Usado</th></tr>";
    
    foreach ($resultado as $row) {
        $corLinha = $row['total_saldo_clientes'] > 0 ? 'style="background: #e8f5e9;"' : '';
        echo "<tr $corLinha>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['nome_fantasia']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>{$row['categoria']}</td>";
        echo "<td><strong>{$row['clientes_com_saldo']}</strong></td>";
        echo "<td><strong>R$ " . number_format($row['total_saldo_clientes'], 2, ',', '.') . "</strong></td>";
        echo "<td>{$row['total_transacoes']}</td>";
        echo "<td>{$row['transacoes_com_saldo']}</td>";
        echo "<td>R$ " . number_format($row['total_saldo_usado'], 2, ',', '.') . "</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // 5. Estatísticas gerais
    echo "<h3>5. 📈 Estatísticas Gerais:</h3>";
    $stats = $db->query("
        SELECT 
            COUNT(DISTINCT l.id) as total_lojas,
            COUNT(DISTINCT CASE WHEN cs.saldo_disponivel > 0 THEN cs.loja_id END) as lojas_com_saldo,
            COALESCE(SUM(cs.saldo_disponivel), 0) as total_saldo_acumulado,
            COALESCE(SUM(tsu.valor_usado), 0) as total_saldo_usado,
            COUNT(DISTINCT CASE WHEN l.status = 'aprovado' THEN l.id END) as lojas_aprovadas,
            COUNT(DISTINCT CASE WHEN l.status = 'pendente' THEN l.id END) as lojas_pendentes
        FROM lojas l
        LEFT JOIN cashback_saldos cs ON l.id = cs.loja_id
        LEFT JOIN transacoes_cashback tc ON l.id = tc.loja_id AND tc.status = 'aprovado'
        LEFT JOIN transacoes_saldo_usado tsu ON tc.id = tsu.transacao_id
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
    echo "<strong>📊 Resumo Geral:</strong><br>";
    echo "• Total de lojas: <strong>{$stats['total_lojas']}</strong><br>";
    echo "• Lojas com saldo ativo: <strong>{$stats['lojas_com_saldo']}</strong><br>";
    echo "• Total saldo acumulado: <strong>R$ " . number_format($stats['total_saldo_acumulado'], 2, ',', '.') . "</strong><br>";
    echo "• Total saldo usado: <strong>R$ " . number_format($stats['total_saldo_usado'], 2, ',', '.') . "</strong><br>";
    echo "• Lojas aprovadas: <strong>{$stats['lojas_aprovadas']}</strong><br>";
    echo "• Lojas pendentes: <strong>{$stats['lojas_pendentes']}</strong><br>";
    echo "</div>";
    
    echo "<br><hr><br>";
    echo "<h3>🔧 Próximos Passos:</h3>";
    echo "<p>1. Se a tabela <code>cashback_saldos</code> estiver vazia, precisamos verificar por que os saldos não estão sendo criados.</p>";
    echo "<p>2. Se houver dados, mas a query não estiver retornando, temos um problema na query SQL.</p>";
    echo "<p>3. Se tudo estiver correto aqui, o problema está na renderização da tela.</p>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; color: #c62828; padding: 15px; border-radius: 5px;'>";
    echo "<strong>❌ Erro:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>Arquivo:</strong> " . $e->getFile() . "<br>";
    echo "<strong>Linha:</strong> " . $e->getLine();
    echo "</div>";
}
?>

<br><br>
<a href="views/admin/stores.php" style="background: #FF7A00; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">← Voltar para Gestão de Lojas</a>
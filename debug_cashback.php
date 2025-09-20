<?php
// debug_cashback.php - Script para verificar e corrigir problemas de cashback

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/models/CashbackBalance.php';

echo "<h1>Debug Sistema de Cashback</h1>\n";

try {
    $db = Database::getConnection();
    $balanceModel = new CashbackBalance();

    echo "<h2>1. Verificando transações aprovadas sem cashback creditado</h2>\n";

    // Buscar transações aprovadas que ainda não têm cashback creditado
    $stmt = $db->prepare("
        SELECT
            tc.*,
            u.nome as cliente_nome,
            l.nome_fantasia as loja_nome,
            COALESCE(cs.saldo_disponivel, 0) as saldo_atual
        FROM transacoes_cashback tc
        JOIN usuarios u ON tc.usuario_id = u.id
        JOIN lojas l ON tc.loja_id = l.id
        LEFT JOIN cashback_saldos cs ON tc.usuario_id = cs.usuario_id AND tc.loja_id = cs.loja_id
        WHERE tc.status = 'aprovado'
        AND tc.valor_cliente > 0
        AND NOT EXISTS (
            SELECT 1 FROM cashback_movimentacoes cm
            WHERE cm.transacao_origem_id = tc.id
            AND cm.tipo_operacao = 'credito'
        )
        ORDER BY tc.data_transacao DESC
        LIMIT 20
    ");
    $stmt->execute();
    $transactionsPendingCashback = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>Encontradas " . count($transactionsPendingCashback) . " transações aprovadas sem cashback creditado:</p>\n";

    if (!empty($transactionsPendingCashback)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr>
                <th>ID</th>
                <th>Cliente</th>
                <th>Loja</th>
                <th>Valor Total</th>
                <th>Cashback Cliente</th>
                <th>Data</th>
                <th>Saldo Atual</th>
                <th>Ação</th>
              </tr>\n";

        foreach ($transactionsPendingCashback as $transaction) {
            echo "<tr>";
            echo "<td>{$transaction['id']}</td>";
            echo "<td>{$transaction['cliente_nome']}</td>";
            echo "<td>{$transaction['loja_nome']}</td>";
            echo "<td>R$ " . number_format($transaction['valor_total'], 2, ',', '.') . "</td>";
            echo "<td>R$ " . number_format($transaction['valor_cliente'], 2, ',', '.') . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($transaction['data_transacao'])) . "</td>";
            echo "<td>R$ " . number_format($transaction['saldo_atual'], 2, ',', '.') . "</td>";
            echo "<td>";

            // Tentar creditar o cashback
            $description = "Cashback creditado via correção automática - Transação #{$transaction['id']}";
            $creditResult = $balanceModel->addBalance(
                $transaction['usuario_id'],
                $transaction['loja_id'],
                $transaction['valor_cliente'],
                $description,
                $transaction['id']
            );

            if ($creditResult) {
                echo "<span style='color: green;'>✅ Creditado</span>";
            } else {
                echo "<span style='color: red;'>❌ Erro</span>";
            }

            echo "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }

    echo "<h2>2. Verificando histórico recente de cashback</h2>\n";

    // Buscar movimentações recentes
    $stmt = $db->prepare("
        SELECT
            cm.*,
            u.nome as cliente_nome,
            l.nome_fantasia as loja_nome
        FROM cashback_movimentacoes cm
        JOIN usuarios u ON cm.usuario_id = u.id
        JOIN lojas l ON cm.loja_id = l.id
        WHERE cm.data_operacao >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY cm.data_operacao DESC
        LIMIT 20
    ");
    $stmt->execute();
    $recentMovements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>Últimas " . count($recentMovements) . " movimentações de cashback (7 dias):</p>\n";

    if (!empty($recentMovements)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr>
                <th>Data</th>
                <th>Cliente</th>
                <th>Loja</th>
                <th>Operação</th>
                <th>Valor</th>
                <th>Saldo Anterior</th>
                <th>Saldo Atual</th>
                <th>Descrição</th>
              </tr>\n";

        foreach ($recentMovements as $movement) {
            echo "<tr>";
            echo "<td>" . date('d/m/Y H:i', strtotime($movement['data_operacao'])) . "</td>";
            echo "<td>{$movement['cliente_nome']}</td>";
            echo "<td>{$movement['loja_nome']}</td>";
            echo "<td>{$movement['tipo_operacao']}</td>";
            echo "<td>R$ " . number_format($movement['valor'], 2, ',', '.') . "</td>";
            echo "<td>R$ " . number_format($movement['saldo_anterior'], 2, ',', '.') . "</td>";
            echo "<td>R$ " . number_format($movement['saldo_atual'], 2, ',', '.') . "</td>";
            echo "<td>{$movement['descricao']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }

    echo "<h2>3. Verificando saldos atuais dos clientes</h2>\n";

    // Buscar saldos atuais
    $stmt = $db->prepare("
        SELECT
            cs.*,
            u.nome as cliente_nome,
            l.nome_fantasia as loja_nome
        FROM cashback_saldos cs
        JOIN usuarios u ON cs.usuario_id = u.id
        JOIN lojas l ON cs.loja_id = l.id
        WHERE cs.saldo_disponivel > 0
        ORDER BY cs.saldo_disponivel DESC
        LIMIT 20
    ");
    $stmt->execute();
    $balances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>Top " . count($balances) . " saldos de cashback:</p>\n";

    if (!empty($balances)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr>
                <th>Cliente</th>
                <th>Loja</th>
                <th>Saldo Disponível</th>
                <th>Total Creditado</th>
                <th>Total Usado</th>
                <th>Última Atualização</th>
              </tr>\n";

        foreach ($balances as $balance) {
            echo "<tr>";
            echo "<td>{$balance['cliente_nome']}</td>";
            echo "<td>{$balance['loja_nome']}</td>";
            echo "<td>R$ " . number_format($balance['saldo_disponivel'], 2, ',', '.') . "</td>";
            echo "<td>R$ " . number_format($balance['total_creditado'], 2, ',', '.') . "</td>";
            echo "<td>R$ " . number_format($balance['total_usado'] ?? 0, 2, ',', '.') . "</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($balance['ultima_atualizacao'])) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }

    echo "<h2>4. Verificando pagamentos pendentes do Mercado Pago</h2>\n";

    // Buscar pagamentos MP pendentes
    $stmt = $db->prepare("
        SELECT
            pc.*,
            l.nome_fantasia as loja_nome,
            COUNT(pt.transacao_id) as qtd_transacoes
        FROM pagamentos_comissao pc
        JOIN lojas l ON pc.loja_id = l.id
        LEFT JOIN pagamentos_transacoes pt ON pc.id = pt.pagamento_id
        WHERE pc.status IN ('pendente', 'pix_aguardando')
        AND pc.mp_payment_id IS NOT NULL
        GROUP BY pc.id
        ORDER BY pc.data_registro DESC
        LIMIT 10
    ");
    $stmt->execute();
    $pendingPayments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>Pagamentos MP pendentes: " . count($pendingPayments) . "</p>\n";

    if (!empty($pendingPayments)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr>
                <th>ID</th>
                <th>Loja</th>
                <th>Valor</th>
                <th>Status</th>
                <th>MP Payment ID</th>
                <th>Transações</th>
                <th>Data</th>
              </tr>\n";

        foreach ($pendingPayments as $payment) {
            echo "<tr>";
            echo "<td>{$payment['id']}</td>";
            echo "<td>{$payment['loja_nome']}</td>";
            echo "<td>R$ " . number_format($payment['valor_total'], 2, ',', '.') . "</td>";
            echo "<td>{$payment['status']}</td>";
            echo "<td>{$payment['mp_payment_id']}</td>";
            echo "<td>{$payment['qtd_transacoes']}</td>";
            echo "<td>" . date('d/m/Y H:i', strtotime($payment['data_registro'])) . "</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }

    echo "<h2>5. Resumo do Sistema</h2>\n";

    // Estatísticas gerais
    $stats = [];

    $stmt = $db->query("SELECT COUNT(*) as total FROM transacoes_cashback WHERE status = 'aprovado'");
    $stats['transacoes_aprovadas'] = $stmt->fetch()['total'];

    $stmt = $db->query("SELECT COUNT(*) as total FROM transacoes_cashback WHERE status = 'pendente'");
    $stats['transacoes_pendentes'] = $stmt->fetch()['total'];

    $stmt = $db->query("SELECT COUNT(*) as total FROM cashback_saldos WHERE saldo_disponivel > 0");
    $stats['usuarios_com_saldo'] = $stmt->fetch()['total'];

    $stmt = $db->query("SELECT SUM(saldo_disponivel) as total FROM cashback_saldos");
    $stats['total_saldo_sistema'] = $stmt->fetch()['total'] ?? 0;

    $stmt = $db->query("SELECT COUNT(*) as total FROM cashback_movimentacoes WHERE tipo_operacao = 'credito' AND data_operacao >= CURDATE()");
    $stats['creditos_hoje'] = $stmt->fetch()['total'];

    echo "<ul>";
    echo "<li>Transações aprovadas: {$stats['transacoes_aprovadas']}</li>";
    echo "<li>Transações pendentes: {$stats['transacoes_pendentes']}</li>";
    echo "<li>Usuários com saldo: {$stats['usuarios_com_saldo']}</li>";
    echo "<li>Total em saldo no sistema: R$ " . number_format($stats['total_saldo_sistema'], 2, ',', '.') . "</li>";
    echo "<li>Créditos realizados hoje: {$stats['creditos_hoje']}</li>";
    echo "</ul>";

    echo "<h3>Status: <span style='color: green;'>Sistema funcionando</span></h3>";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>Erro: " . $e->getMessage() . "</h3>";
    error_log("DEBUG CASHBACK ERROR: " . $e->getMessage());
}
?>
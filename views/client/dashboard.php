<?php
// views/client/dashboard.php
$activeMenu = 'painel'; // Define o menu ativo para a navbar

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/ClientController.php';
require_once '../../controllers/AuthController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_CLIENT) {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Cliente';

// Inicializar variáveis para evitar erros de undefined
$dashboardData = ['saldo_total' => 0, 'transacoes_recentes' => [], 'estatisticas' => [], 'lojas_favoritas' => [], 'notificacoes' => []];
$saldoTotalDisponivel = 0;
$saldosPorLoja = [];
$saldosPendentes = [];
$totalSaldoPendente = 0;
$movimentacoesRecentes = [];
$estatisticasUso = ['total_creditado' => 0, 'total_usado' => 0, 'total_estornado' => 0, 'total_usos' => 0];
$lojasMaisUsadas = [];
$transacoesRecentesComSaldo = []; // Renomeado para clareza
$dadosMensaisParaGrafico = ['labels' => [], 'creditos' => [], 'usos' => []]; // Para o gráfico
$hasError = false;
$errorMessage = '';

try {
    $db = Database::getConnection();
    $dashboardResult = ClientController::getDashboardData($userId);

    if (!$dashboardResult['status']) {
        $hasError = true;
        $errorMessage = $dashboardResult['message'];
    } else {
        $dashboardData = $dashboardResult['data'];
    }

    require_once '../../models/CashbackBalance.php';
    $balanceModel = new CashbackBalance();
    $saldoTotalDisponivel = $balanceModel->getTotalBalance($userId);
    $saldosPorLoja = $balanceModel->getAllUserBalances($userId);

    $saldoPendenteQuery = "
        SELECT SUM(t.valor_cliente) as total_pendente
        FROM transacoes_cashback t
        WHERE t.usuario_id = :user_id AND t.status = 'pendente'";
    $saldoPendenteStmt = $db->prepare($saldoPendenteQuery);
    $saldoPendenteStmt->bindParam(':user_id', $userId);
    $saldoPendenteStmt->execute();
    $totalSaldoPendenteFetch = $saldoPendenteStmt->fetch(PDO::FETCH_ASSOC);
    $totalSaldoPendente = $totalSaldoPendenteFetch['total_pendente'] ?? 0;


    $movimentacoesQuery = "
        SELECT cm.*, l.nome_fantasia as loja_nome
        FROM cashback_movimentacoes cm
        JOIN lojas l ON cm.loja_id = l.id
        WHERE cm.usuario_id = :user_id
        ORDER BY cm.data_operacao DESC LIMIT 5"; // Ajustado para 5, como no HTML anterior
    $movimentacoesStmt = $db->prepare($movimentacoesQuery);
    $movimentacoesStmt->bindParam(':user_id', $userId);
    $movimentacoesStmt->execute();
    $movimentacoesRecentes = $movimentacoesStmt->fetchAll(PDO::FETCH_ASSOC);

    $usoSaldoQuery = "
        SELECT
            SUM(CASE WHEN tipo_operacao = 'credito' THEN valor ELSE 0 END) as total_creditado,
            SUM(CASE WHEN tipo_operacao = 'uso' THEN valor ELSE 0 END) as total_usado,
            COUNT(CASE WHEN tipo_operacao = 'uso' THEN 1 END) as total_usos
        FROM cashback_movimentacoes
        WHERE usuario_id = :user_id";
    $usoSaldoStmt = $db->prepare($usoSaldoQuery);
    $usoSaldoStmt->bindParam(':user_id', $userId);
    $usoSaldoStmt->execute();
    $estatisticasUso = $usoSaldoStmt->fetch(PDO::FETCH_ASSOC) ?: $estatisticasUso;


    // Transações Recentes (com info de saldo usado)
    $transacoesRecentesQuery = "
        SELECT t.*, l.nome_fantasia as loja_nome,
               COALESCE((SELECT SUM(cm.valor) FROM cashback_movimentacoes cm WHERE cm.transacao_uso_id = t.id AND cm.tipo_operacao = 'uso'), 0) as saldo_usado
        FROM transacoes_cashback t
        JOIN lojas l ON t.loja_id = l.id
        WHERE t.usuario_id = :user_id
        ORDER BY t.data_transacao DESC LIMIT 5";
    $transacoesRecentesStmt = $db->prepare($transacoesRecentesQuery);
    $transacoesRecentesStmt->bindParam(':user_id', $userId);
    $transacoesRecentesStmt->execute();
    $transacoesRecentesComSaldo = $transacoesRecentesStmt->fetchAll(PDO::FETCH_ASSOC);


    // Dados para o gráfico (últimos 6 meses)
    $dadosMensaisQuery = "
        SELECT DATE_FORMAT(data_operacao, '%Y-%m') as mes,
               SUM(CASE WHEN tipo_operacao = 'credito' THEN valor ELSE 0 END) as creditos,
               SUM(CASE WHEN tipo_operacao = 'uso' THEN valor ELSE 0 END) as usos
        FROM cashback_movimentacoes
        WHERE usuario_id = :user_id AND data_operacao >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(data_operacao, '%Y-%m')
        ORDER BY mes ASC";
    $dadosMensaisStmt = $db->prepare($dadosMensaisQuery);
    $dadosMensaisStmt->bindParam(':user_id', $userId);
    $dadosMensaisStmt->execute();
    $rawDadosMensais = $dadosMensaisStmt->fetchAll(PDO::FETCH_ASSOC);

    $monthNames = ['01'=>'Jan','02'=>'Fev','03'=>'Mar','04'=>'Abr','05'=>'Mai','06'=>'Jun','07'=>'Jul','08'=>'Ago','09'=>'Set','10'=>'Out','11'=>'Nov','12'=>'Dez'];
    foreach ($rawDadosMensais as $data) {
        $parts = explode('-', $data['mes']);
        $dadosMensaisParaGrafico['labels'][] = $monthNames[$parts[1]] . '/' . substr($parts[0], -2);
        $dadosMensaisParaGrafico['creditos'][] = (float)$data['creditos'];
        $dadosMensaisParaGrafico['usos'][] = (float)$data['usos'];
    }


} catch (Exception $e) {
    error_log('Erro ao carregar dashboard do cliente: ' . $e->getMessage());
    $hasError = true;
    $errorMessage = 'Erro ao carregar dados do dashboard.';
}

function formatCurrency($value) {
    return 'R$ ' . number_format($value ?: 0, 2, ',', '.');
}
function formatDate($date) {
    return $date ? date('d/m/Y', strtotime($date)) : 'N/A';
}
function formatDateTime($dateTime) {
    return $dateTime ? date('d/m/Y H:i', strtotime($dateTime)) : 'N/A';
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Klube Cash</title>
    <link rel="shortcut icon" type="image/x-icon" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="../../assets/css/views/client/dashboard-modern.css"> </head>
<body>
    <?php include_once '../components/navbar.php'; ?>

    <div class="kc-dashboard-page">
        <header class="kc-dashboard-header">
            <div class="kc-dashboard-header__title-group">
                <h1>Olá, <?php echo htmlspecialchars($userName); ?>!</h1>
                <p class="kc-dashboard-header__subtitle">Bem-vindo(a) ao seu painel Klube Cash.</p>
            </div>
            <div class="kc-dashboard-header__actions">
                <a href="<?php echo CLIENT_BALANCE_URL; ?>" class="kc-btn kc-btn--secondary">Ver Saldos</a>
                <a href="<?php echo CLIENT_STATEMENT_URL; ?>" class="kc-btn kc-btn--primary">Extrato Completo</a>
            </div>
        </header>

        <?php if ($hasError): ?>
            <div class="kc-alert kc-alert--danger">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php else: ?>

        <section class="kc-summary-cards">
            <div class="kc-card kc-summary-card">
                <h3 class="kc-summary-card__title">Saldo Disponível</h3>
                <div class="kc-summary-card__value"><?php echo formatCurrency($saldoTotalDisponivel); ?></div>
                <div class="kc-summary-card__change kc-summary-card__change--positive">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"></polyline></svg>
                    Pronto para usar
                </div>
            </div>
            <div class="kc-card kc-summary-card">
                <h3 class="kc-summary-card__title">Saldo Pendente</h3>
                <div class="kc-summary-card__value"><?php echo formatCurrency($totalSaldoPendente); ?></div>
                <div class="kc-summary-card__change kc-summary-card__change--warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    Aguardando liberação
                </div>
            </div>
            <div class="kc-card kc-summary-card">
                <h3 class="kc-summary-card__title">Total Usado</h3>
                <div class="kc-summary-card__value"><?php echo formatCurrency($estatisticasUso['total_usado']); ?></div>
                <div class="kc-summary-card__change">
                    <?php echo $estatisticasUso['total_usos']; ?> usos
                </div>
            </div>
            <div class="kc-card kc-summary-card">
                <h3 class="kc-summary-card__title">Total Recebido</h3>
                <div class="kc-summary-card__value"><?php echo formatCurrency($estatisticasUso['total_creditado']); ?></div>
                <div class="kc-summary-card__change">
                    Histórico completo
                </div>
            </div>
        </section>

        <section class="kc-dashboard-layout">
            <div class="kc-dashboard-layout__main">
                <div class="kc-card">
                    <div class="kc-card__header">
                        <h2 class="kc-card__title">Transações Recentes</h2>
                    </div>
                    <div class="kc-card__content">
                        <div class="kc-table-wrapper">
                            <table class="kc-table">
                                <thead>
                                    <tr>
                                        <th>Loja</th>
                                        <th>Data</th>
                                        <th>Valor Original</th>
                                        <th>Saldo Usado</th>
                                        <th>Valor Pago</th>
                                        <th>Cashback</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($transacoesRecentesComSaldo)): ?>
                                        <tr><td colspan="7" style="text-align: center; padding: 20px;">Nenhuma transação recente.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($transacoesRecentesComSaldo as $transacao):
                                            $valorOriginal = (float)$transacao['valor_total'];
                                            $saldoUsado = (float)$transacao['saldo_usado'];
                                            $valorPago = $valorOriginal - $saldoUsado;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($transacao['loja_nome']); ?></td>
                                            <td><?php echo formatDate($transacao['data_transacao']); ?></td>
                                            <td><?php echo formatCurrency($valorOriginal); ?></td>
                                            <td><?php echo $saldoUsado > 0 ? formatCurrency($saldoUsado) : '-'; ?></td>
                                            <td><?php echo formatCurrency($valorPago); ?></td>
                                            <td><?php echo formatCurrency($transacao['valor_cliente']); ?></td>
                                            <td>
                                                <span class="kc-badge kc-badge--<?php echo htmlspecialchars(strtolower($transacao['status'])); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($transacao['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="<?php echo CLIENT_STATEMENT_URL; ?>" class="kc-see-all-link">Ver todas as transações</a>
                    </div>
                </div>

                <div class="kc-card" style="margin-top: 25px;">
                     <div class="kc-card__header">
                        <h2 class="kc-card__title">Movimentações de Saldo</h2>
                    </div>
                    <div class="kc-card__content">
                        <div class="kc-scrollable-list">
                            <?php if (empty($movimentacoesRecentes)): ?>
                                <p style="text-align: center; padding: 20px;">Nenhuma movimentação de saldo encontrada.</p>
                            <?php else: ?>
                                <?php foreach ($movimentacoesRecentes as $movimento): ?>
                                <div class="kc-list-item">
                                    <div class="kc-list-item__icon kc-list-item__icon--movement <?php echo $movimento['tipo_operacao'] === 'uso' ? 'negative' : ''; ?>">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <?php if ($movimento['tipo_operacao'] === 'credito' || $movimento['tipo_operacao'] === 'estorno'): ?>
                                                <line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline>
                                            <?php else: /* uso */ ?>
                                                <line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline>
                                            <?php endif; ?>
                                        </svg>
                                    </div>
                                    <div class="kc-list-item__details">
                                        <div class="kc-list-item__title">
                                            <?php
                                                $desc = $movimento['tipo_operacao'] === 'credito' ? 'Cashback recebido' : ($movimento['tipo_operacao'] === 'uso' ? 'Saldo usado' : 'Estorno');
                                                echo htmlspecialchars($desc . ' - ' . $movimento['loja_nome']);
                                            ?>
                                        </div>
                                        <div class="kc-list-item__subtitle"><?php echo formatDateTime($movimento['data_operacao']); ?></div>
                                    </div>
                                    <div class="kc-list-item__amount <?php echo $movimento['tipo_operacao'] === 'uso' ? 'kc-list-item__amount--negative' : 'kc-list-item__amount--positive'; ?>">
                                        <?php echo ($movimento['tipo_operacao'] === 'uso' ? '-' : '+') . formatCurrency($movimento['valor']); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo CLIENT_BALANCE_URL; ?>" class="kc-see-all-link">Ver histórico completo de saldo</a>
                    </div>
                </div>
            </div>

            <aside class="kc-dashboard-layout__sidebar">
                <div class="kc-card">
                    <div class="kc-card__header">
                        <h2 class="kc-card__title">Saldos por Loja</h2>
                    </div>
                    <div class="kc-card__content">
                        <div class="kc-scrollable-list">
                            <?php if (empty($saldosPorLoja)): ?>
                                <p style="text-align: center; padding: 20px;">Nenhum saldo disponível nas lojas.</p>
                            <?php else: ?>
                                <?php foreach (array_slice($saldosPorLoja, 0, 5) as $saldo): ?>
                                <div class="kc-list-item">
                                    <div class="kc-list-item__icon kc-list-item__icon--store">
                                        <?php echo htmlspecialchars(strtoupper(substr($saldo['nome_fantasia'], 0, 1))); ?>
                                    </div>
                                    <div class="kc-list-item__details">
                                        <div class="kc-list-item__title"><?php echo htmlspecialchars($saldo['nome_fantasia']); ?></div>
                                        <div class="kc-list-item__subtitle"><?php echo formatCurrency($saldo['saldo_disponivel']); ?></div>
                                    </div>
                                    <a href="<?php echo CLIENT_STORES_URL . '?loja_id=' . $saldo['loja_id']; /* Ajuste o link se necessário */ ?>" class="kc-list-item__stats">Ver Loja</a>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo CLIENT_BALANCE_URL; ?>" class="kc-see-all-link">Ver todos os saldos</a>
                    </div>
                </div>

                <?php if (!empty($dashboardData['notificacoes'])): ?>
                <div class="kc-card" style="margin-top: 25px;">
                    <div class="kc-card__header">
                        <h2 class="kc-card__title">Notificações</h2>
                    </div>
                    <div class="kc-card__content">
                         <div class="kc-scrollable-list" style="max-height: 250px;"> <?php foreach ($dashboardData['notificacoes'] as $notificacao): ?>
                            <div class="kc-notification">
                                <h4 class="kc-notification__title"><?php echo htmlspecialchars($notificacao['titulo']); ?></h4>
                                <p class="kc-notification__text"><?php echo htmlspecialchars($notificacao['mensagem']); ?></p>
                                <p class="kc-notification__time"><?php echo formatDateTime($notificacao['data_criacao']); ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        </div>
                </div>
                <?php endif; ?>

                 <div class="kc-card chart-card" style="margin-top: 25px;"> <div class="kc-card__header">
                        <h2 class="kc-card__title">Desempenho Mensal</h2>
                    </div>
                    <div class="kc-card__content">
                        <div class="kc-chart-wrapper">
                            <canvas id="kcCashbackChart"></canvas>
                        </div>
                    </div>
                </div>
            </aside>
        </section>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('kcCashbackChart');
            if (ctx && typeof Chart !== 'undefined') {
                const chartData = <?php echo json_encode($dadosMensaisParaGrafico); ?>;

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: 'Cashback Recebido (R$)',
                            data: chartData.creditos,
                            borderColor: 'var(--primary-color)',
                            backgroundColor: 'rgba(255, 122, 0, 0.1)',
                            tension: 0.3,
                            fill: true,
                            pointBackgroundColor: 'var(--primary-color)',
                            pointBorderColor: 'var(--white)',
                            pointHoverRadius: 7,
                            pointHoverBackgroundColor: 'var(--primary-dark)'
                        }, {
                            label: 'Saldo Usado (R$)',
                            data: chartData.usos,
                            borderColor: 'var(--success-color)',
                            backgroundColor: 'rgba(76, 175, 80, 0.1)',
                            tension: 0.3,
                            fill: true,
                            pointBackgroundColor: 'var(--success-color)',
                            pointBorderColor: 'var(--white)',
                            pointHoverRadius: 7,
                            pointHoverBackgroundColor: '#388E3C' // Tom mais escuro de success-color
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: { color: 'rgba(0, 0, 0, 0.05)' },
                                ticks: {
                                    callback: function(value) {
                                        return 'R$ ' + value.toLocaleString('pt-BR');
                                    }
                                }
                            },
                            x: { grid: { display: false } }
                        }
                    }
                });
            } else {
                console.warn('Elemento canvas #kcCashbackChart não encontrado ou Chart.js não carregado.');
            }
        });
    </script>
</body>
</html>
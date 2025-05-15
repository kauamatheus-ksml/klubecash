<?php
// views/store/dashboard.php
// Painel principal para lojas parceiras no sistema Klube Cash

// Iniciar sessão e verificar autenticação
session_start();
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../controllers/StoreController.php';
require_once __DIR__ . '/../../controllers/TransactionController.php';
require_once __DIR__ . '/../../controllers/CommissionController.php';
require_once __DIR__ . '/../../config/constants.php';

// Verificar se o usuário está autenticado e é uma loja
if (!AuthController::isAuthenticated() || !AuthController::isStore()) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Acesso restrito a lojas parceiras.'));
    exit;
}

// Obter dados do usuário logado
$userData = AuthController::getCurrentUser();
$userId = $userData['id'];

// Obter dados da loja vinculada ao usuário
$storeData = StoreController::getStoreByUserId($userId);

if (!$storeData) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Perfil de loja não encontrado.'));
    exit;
}

$storeId = $storeData['id'];

// Obter dados para o dashboard
$pendingCommissions = TransactionController::getPendingTransactions($storeId);
$paymentHistory = TransactionController::getPaymentHistory($storeId);
$storeCommissions = CommissionController::getStoreCommissions($storeId);

// Calcular estatísticas
$totalTransactions = 0;
$totalSales = 0;
$totalCommissions = 0;
$pendingCommissionsCount = 0;
$pendingCommissionsValue = 0;

if ($pendingCommissions['status'] && isset($pendingCommissions['data']['totais'])) {
    $pendingCommissionsCount = $pendingCommissions['data']['totais']['total_transacoes'];
    $pendingCommissionsValue = $pendingCommissions['data']['totais']['total_valor_comissoes'];
    $totalSales += $pendingCommissions['data']['totais']['total_valor_compras'];
}

if ($storeCommissions['status'] && isset($storeCommissions['data']['totais'])) {
    $totalTransactions = $storeCommissions['data']['totais']['total_comissoes'];
    $totalCommissions = $storeCommissions['data']['totais']['total_valor'];
}

// Dados para gráficos
$lastTransactions = [];
$monthlyData = [];

// Obter dados para o gráfico mensal (últimos 6 meses)
$currentMonth = date('n');
$currentYear = date('Y');

for ($i = 0; $i < 6; $i++) {
    $month = $currentMonth - $i;
    $year = $currentYear;
    
    if ($month <= 0) {
        $month += 12;
        $year--;
    }
    
    $monthName = date('M', mktime(0, 0, 0, $month, 1, $year));
    $monthlyData[$i] = [
        'month' => $monthName,
        'year' => $year,
        'sales' => 0,
        'commissions' => 0
    ];
}

// Simular dados para o gráfico (em produção, estes viriam do banco de dados)
$monthlyData[0]['sales'] = 15000;
$monthlyData[0]['commissions'] = 1500;
$monthlyData[1]['sales'] = 12500;
$monthlyData[1]['commissions'] = 1250;
$monthlyData[2]['sales'] = 14000;
$monthlyData[2]['commissions'] = 1400;
$monthlyData[3]['sales'] = 10000;
$monthlyData[3]['commissions'] = 1000;
$monthlyData[4]['sales'] = 11500;
$monthlyData[4]['commissions'] = 1150;
$monthlyData[5]['sales'] = 13000;
$monthlyData[5]['commissions'] = 1300;

// Inverter para ordem cronológica
$monthlyData = array_reverse($monthlyData);

// Obter últimas 5 transações
if ($pendingCommissions['status'] && isset($pendingCommissions['data']['transacoes'])) {
    $lastTransactions = array_slice($pendingCommissions['data']['transacoes'], 0, 5);
}

// Incluir o cabeçalho e barra lateral
$pageTitle = "Dashboard da Loja";
$currentPage = "dashboard";
include_once __DIR__ . '/../components/header.php';
include_once __DIR__ . '/../components/sidebar.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Dashboard da Loja</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="<?= STORE_DASHBOARD_URL ?>">Home</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Info boxes -->
            <div class="row">
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-shopping-cart"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total de Vendas</span>
                            <span class="info-box-number">R$ <?= number_format($totalSales, 2, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-money-bill-wave"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total de Comissões</span>
                            <span class="info-box-number">R$ <?= number_format($totalCommissions, 2, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Comissões Pendentes</span>
                            <span class="info-box-number">R$ <?= number_format($pendingCommissionsValue, 2, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <div class="info-box">
                        <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-circle"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Transações Pendentes</span>
                            <span class="info-box-number"><?= $pendingCommissionsCount ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alertas -->
            <?php if ($pendingCommissionsCount > 0): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-warning">
                        <h5><i class="icon fas fa-exclamation-triangle"></i> Atenção!</h5>
                        Você possui <?= $pendingCommissionsCount ?> transações pendentes de pagamento, 
                        totalizando R$ <?= number_format($pendingCommissionsValue, 2, ',', '.') ?>.
                        <a href="<?= STORE_PENDING_TRANSACTIONS_URL ?>" class="btn btn-sm btn-warning ml-3">
                            Ver Transações Pendentes
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ações Rápidas -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Ações Rápidas</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 col-sm-6">
                                    <a href="<?= STORE_REGISTER_TRANSACTION_URL ?>" class="btn btn-primary btn-lg btn-block">
                                        <i class="fas fa-cash-register"></i> Nova Transação
                                    </a>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <a href="<?= STORE_BATCH_UPLOAD_URL ?>" class="btn btn-success btn-lg btn-block">
                                        <i class="fas fa-file-upload"></i> Upload em Lote
                                    </a>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <a href="<?= STORE_PAYMENT_URL ?>" class="btn btn-warning btn-lg btn-block">
                                        <i class="fas fa-money-check-alt"></i> Pagar Comissões
                                    </a>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <a href="<?= STORE_PAYMENT_HISTORY_URL ?>" class="btn btn-info btn-lg btn-block">
                                        <i class="fas fa-history"></i> Histórico de Pagamentos
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Vendas e Comissões -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Vendas e Comissões nos Últimos 6 Meses</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Últimas Transações -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Últimas Transações</h3>
                            <div class="card-tools">
                                <a href="<?= STORE_PENDING_TRANSACTIONS_URL ?>" class="btn btn-tool">
                                    <i class="fas fa-list"></i> Ver Todas
                                </a>
                            </div>
                        </div>
                        <div class="card-body table-responsive p-0">
                            <table class="table table-hover text-nowrap">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Cliente</th>
                                        <th>Valor da Venda</th>
                                        <th>Comissão</th>
                                        <th>Data</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($lastTransactions)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Nenhuma transação encontrada</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($lastTransactions as $transaction): ?>
                                        <tr>
                                            <td><?= $transaction['id'] ?></td>
                                            <td><?= $transaction['cliente_nome'] ?></td>
                                            <td>R$ <?= number_format($transaction['valor_total'], 2, ',', '.') ?></td>
                                            <td>R$ <?= number_format($transaction['valor_cashback'], 2, ',', '.') ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($transaction['data_transacao'])) ?></td>
                                            <td>
                                                <span class="badge badge-warning">
                                                    <?= $transaction['status'] === TRANSACTION_PENDING ? 'Pendente' : $transaction['status'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Scripts específicos para o dashboard -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function() {
        // Dados para o gráfico
        const monthlyData = <?= json_encode($monthlyData) ?>;
        
        // Configuração do gráfico
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: monthlyData.map(item => item.month + '/' + item.year),
                datasets: [
                    {
                        label: 'Vendas (R$)',
                        backgroundColor: 'rgba(60,141,188,0.9)',
                        borderColor: 'rgba(60,141,188,0.8)',
                        pointRadius: false,
                        pointColor: '#3b8bba',
                        pointStrokeColor: 'rgba(60,141,188,1)',
                        pointHighlightFill: '#fff',
                        pointHighlightStroke: 'rgba(60,141,188,1)',
                        data: monthlyData.map(item => item.sales)
                    },
                    {
                        label: 'Comissões (R$)',
                        backgroundColor: 'rgba(210, 214, 222, 1)',
                        borderColor: 'rgba(210, 214, 222, 1)',
                        pointRadius: false,
                        pointColor: 'rgba(210, 214, 222, 1)',
                        pointStrokeColor: '#c1c7d1',
                        pointHighlightFill: '#fff',
                        pointHighlightStroke: 'rgba(220,220,220,1)',
                        data: monthlyData.map(item => item.commissions)
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>

<?php
// Incluir o rodapé
include_once __DIR__ . '/../components/footer.php';
?>
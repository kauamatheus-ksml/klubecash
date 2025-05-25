<?php
// views/admin/balance.php
$activeMenu = 'saldo';

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AdminController.php';

session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Obter dados do saldo do administrador
try {
    $result = AdminController::getAdminBalance();
    
    if (!$result['status']) {
        $error = $result['message'];
    } else {
        $balanceData = $result['data'];
        $saldoTotal = $balanceData['saldo_total'];
        $saldoPendente = $balanceData['saldo_pendente'];
        $historico = $balanceData['historico'];
        $mensal = $balanceData['mensal'];
        $topLojas = $balanceData['top_lojas'];
    }
} catch (Exception $e) {
    $error = "Erro ao carregar dados do saldo: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <title>Saldo - Klube Cash</title>
    <link rel="stylesheet" href="../../assets/css/views/admin/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/views/admin/balance.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="dashboard-wrapper">
            <div class="dashboard-header">
                <h1>Saldo da Administração</h1>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php else: ?>
            
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-card-title">Saldo Total (Aprovado)</div>
                    <div class="stat-card-value">R$ <?php echo number_format($saldoTotal, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Comissões confirmadas</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Saldo Pendente</div>
                    <div class="stat-card-value">R$ <?php echo number_format($saldoPendente, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Comissões a receber</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Total Geral</div>
                    <div class="stat-card-value">R$ <?php echo number_format($saldoTotal + $saldoPendente, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Saldo total (aprovado + pendente)</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Transações</div>
                    <div class="stat-card-value"><?php echo count($historico); ?></div>
                    <div class="stat-card-subtitle">Total de transações</div>
                </div>
            </div>
            <div class="card klubecash-balance-card">
                <div class="card-header">
                    <div class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
                            <line x1="9" y1="9" x2="9.01" y2="9"></line>
                            <line x1="15" y1="9" x2="15.01" y2="9"></line>
                        </svg>
                        Saldo da Klube Cash
                    </div>
                </div>
                <div class="klubecash-balance-container">
                    <div class="balance-item">
                        <div class="balance-label">Saldo Disponível</div>
                        <div class="balance-value">R$ <?php echo number_format($balanceData['saldo_admin']['valor_disponivel'], 2, ',', '.'); ?></div>
                    </div>
                    
                    <div class="balance-item">
                        <div class="balance-label">Saldo Pendente</div>
                        <div class="balance-value">R$ <?php echo number_format($balanceData['saldo_admin']['valor_pendente'], 2, ',', '.'); ?></div>
                    </div>
                    
                    <div class="balance-item total">
                        <div class="balance-label">Saldo Total</div>
                        <div class="balance-value">R$ <?php echo number_format($balanceData['saldo_admin']['valor_total'], 2, ',', '.'); ?></div>
                    </div>
                </div>
            </div>

            <div class="card transactions-container">
                <div class="card-header">
                    <div class="card-title">Movimentações do Saldo</div>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Tipo</th>
                                <th>Valor</th>
                                <th>Código Transação</th>
                                <th>Loja</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($balanceData['movimentacoes'])): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center;">Nenhuma movimentação encontrada</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($balanceData['movimentacoes'] as $mov): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($mov['data_operacao'])); ?></td>
                                        <td><?php echo htmlspecialchars($mov['descricao']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $mov['tipo'] == 'credito' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($mov['tipo']); ?>
                                            </span>
                                        </td>
                                        <td>R$ <?php echo number_format($mov['valor'], 2, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($mov['codigo_transacao'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($mov['loja_nome'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="two-column-layout">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Comissões por Mês</div>
                    </div>
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Top Lojas (Comissões Geradas)</div>
                    </div>
                    
                    <div class="top-stores-list">
                        <?php foreach ($topLojas as $index => $loja): ?>
                            <div class="store-item">
                                <div class="store-rank">#<?php echo $index + 1; ?></div>
                                <div class="store-info">
                                    <div class="store-name"><?php echo htmlspecialchars($loja['nome_fantasia']); ?></div>
                                    <div class="store-details">
                                        R$ <?php echo number_format($loja['total'], 2, ',', '.'); ?> 
                                        (<?php echo $loja['quantidade']; ?> transações)
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="card transactions-container">
                <div class="card-header">
                    <div class="card-title">Histórico de Comissões</div>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Loja</th>
                                <th>Cliente</th>
                                <th>Valor da Venda</th>
                                <th>Comissão (5%)</th>
                                <th>Status</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historico)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">Nenhuma comissão encontrada</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($historico as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['codigo_transacao'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($item['loja_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($item['cliente_nome']); ?></td>
                                        <td>R$ <?php echo number_format($item['valor_venda'], 2, ',', '.'); ?></td>
                                        <td>R$ <?php echo number_format($item['valor_comissao'], 2, ',', '.'); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $item['status']; ?>">
                                                <?php echo ucfirst($item['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($item['data_transacao'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Dados para o gráfico mensal
        const monthlyData = {
            labels: [
                <?php 
                    if (!empty($mensal)) {
                        $monthLabels = array_map(function($item) {
                            $date = DateTime::createFromFormat('Y-m', $item['mes']);
                            return "'" . $date->format('M/Y') . "'";
                        }, array_reverse($mensal));
                        echo implode(', ', $monthLabels);
                    }
                ?>
            ],
            values: [
                <?php 
                    if (!empty($mensal)) {
                        $monthValues = array_map(function($item) {
                            return $item['total'];
                        }, array_reverse($mensal));
                        echo implode(', ', $monthValues);
                    }
                ?>
            ]
        };
        
        // Inicializar gráfico mensal
        const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
        const monthlyChart = new Chart(ctxMonthly, {
            type: 'bar',
            data: {
                labels: monthlyData.labels,
                datasets: [{
                    label: 'Comissões Recebidas (R$)',
                    data: monthlyData.values,
                    backgroundColor: '#FF7A00',
                    borderColor: '#E06E00',
                    borderWidth: 1
                }]
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
    </script>
    

</body>
</html>
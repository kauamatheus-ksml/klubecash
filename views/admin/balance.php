<?php
// views/admin/balance.php
$activeMenu = 'saldo';

require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AdminController.php';
require_once '../../controllers/StoreBalancePaymentController.php';

session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Obter dados do saldo do administrador
$error = '';
$balanceData = [];
$saldoTotal = 0;
$saldoPendente = 0;
$movimentacoes = [];
$estatisticas = [];

try {
    $db = Database::getConnection();
    
    // 1. Obter saldo admin
    $saldoStmt = $db->prepare("SELECT * FROM admin_saldo WHERE id = 1");
    $saldoStmt->execute();
    $saldoAdmin = $saldoStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$saldoAdmin) {
        // Criar registro inicial se não existir
        $createStmt = $db->prepare("
            INSERT INTO admin_saldo (id, valor_total, valor_disponivel, valor_pendente) 
            VALUES (1, 0, 0, 0)
        ");
        $createStmt->execute();
        $saldoAdmin = [
            'valor_total' => 0,
            'valor_disponivel' => 0,
            'valor_pendente' => 0,
            'ultima_atualizacao' => date('Y-m-d H:i:s')
        ];
    }
    
    // 2. Obter movimentações do saldo admin
    $movStmt = $db->prepare("
        SELECT 
            asm.*,
            tc.codigo_transacao,
            l.nome_fantasia as loja_nome
        FROM admin_saldo_movimentacoes asm
        LEFT JOIN transacoes_cashback tc ON asm.transacao_id = tc.id
        LEFT JOIN lojas l ON tc.loja_id = l.id
        ORDER BY asm.data_operacao DESC
        LIMIT 50
    ");
    $movStmt->execute();
    $movimentacoes = $movStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Obter estatísticas de comissões
    $statsStmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT tc.id) as total_transacoes,
            SUM(tc.valor_admin) as total_comissoes_recebidas,
            COUNT(CASE WHEN tc.status = 'pendente' THEN 1 END) as transacoes_pendentes,
            SUM(CASE WHEN tc.status = 'pendente' THEN tc.valor_admin ELSE 0 END) as comissoes_pendentes,
            COUNT(CASE WHEN tc.status = 'aprovado' THEN 1 END) as transacoes_aprovadas,
            SUM(CASE WHEN tc.status = 'aprovado' THEN tc.valor_admin ELSE 0 END) as comissoes_aprovadas
        FROM transacoes_comissao tcom
        JOIN transacoes_cashback tc ON tcom.transacao_id = tc.id
        WHERE tcom.tipo_usuario = 'admin'
    ");
    $statsStmt->execute();
    $estatisticas = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // 4. Obter dados mensais para gráfico
    $monthlyStmt = $db->prepare("
        SELECT 
            DATE_FORMAT(asm.data_operacao, '%Y-%m') as mes,
            SUM(CASE WHEN asm.tipo = 'credito' THEN asm.valor ELSE 0 END) as entrada,
            SUM(CASE WHEN asm.tipo = 'debito' THEN asm.valor ELSE 0 END) as saida,
            SUM(CASE WHEN asm.tipo = 'credito' THEN asm.valor ELSE -asm.valor END) as total
        FROM admin_saldo_movimentacoes asm
        WHERE asm.data_operacao >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(asm.data_operacao, '%Y-%m')
        ORDER BY mes DESC
        LIMIT 12
    ");
    $monthlyStmt->execute();
    $mensal = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Obter top lojas por comissões geradas
    $topLojasStmt = $db->prepare("
        SELECT 
            l.id,
            l.nome_fantasia,
            COUNT(tc.id) as quantidade_transacoes,
            SUM(tc.valor_admin) as total_comissoes
        FROM lojas l
        JOIN transacoes_cashback tc ON l.id = tc.loja_id
        JOIN transacoes_comissao tcom ON tc.id = tcom.transacao_id
        WHERE tcom.tipo_usuario = 'admin'
        AND tc.status IN ('aprovado', 'pendente')
        GROUP BY l.id, l.nome_fantasia
        ORDER BY total_comissoes DESC
        LIMIT 10
    ");
    $topLojasStmt->execute();
    $topLojas = $topLojasStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 6. Obter estatísticas de pagamentos de saldo
    $balanceStats = StoreBalancePaymentController::getBalanceStatistics();
    
    // Preparar dados para a view
    $balanceData = [
        'saldo_admin' => $saldoAdmin,
        'movimentacoes' => $movimentacoes,
        'estatisticas' => $estatisticas,
        'mensal' => $mensal,
        'top_lojas' => $topLojas,
        'balance_stats' => $balanceStats
    ];
    
    $saldoTotal = $saldoAdmin['valor_disponivel'];
    $saldoPendente = $saldoAdmin['valor_pendente'];
    
} catch (Exception $e) {
    $error = "Erro ao carregar dados do saldo: " . $e->getMessage();
    error_log("Erro em balance.php: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <title>Saldo da Administração - Klube Cash</title>
    <link rel="stylesheet" href="../../assets/css/views/admin/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/views/admin/balance.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #FF7A00;
        }
        
        .stat-card.balance {
            border-left-color: #28a745;
        }
        
        .stat-card.pending {
            border-left-color: #ffc107;
        }
        
        .stat-card.outgoing {
            border-left-color: #dc3545;
        }
        
        .stat-card-title {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .stat-card-value {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }
        
        .stat-card-subtitle {
            font-size: 12px;
            color: #999;
        }
        
        .two-column-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            padding: 20px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        
        .alert.error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        
        .movement-type {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .movement-type.credito {
            background-color: #d4edda;
            color: #155724;
        }
        
        .movement-type.debito {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .balance-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .balance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .balance-item {
            text-align: center;
            padding: 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            backdrop-filter: blur(10px);
        }
        
        .balance-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 8px;
        }
        
        .balance-value {
            font-size: 24px;
            font-weight: 700;
        }
        
        .quick-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #FF7A00;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        @media (max-width: 768px) {
            .two-column-layout {
                grid-template-columns: 1fr;
            }
            
            .balance-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <!-- Conteúdo Principal -->
    <div class="main-content" id="mainContent">
        <div class="dashboard-wrapper">
            <!-- Cabeçalho -->
            <div class="dashboard-header">
                <h1>💰 Saldo da Administração</h1>
                <p class="subtitle">Visão geral das receitas e movimentações financeiras da plataforma</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert error">
                    <strong>Erro:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php else: ?>
            
            <!-- Seção Principal do Saldo -->
            <div class="balance-section">
                <h2>💼 Resumo Financeiro</h2>
                <div class="balance-grid">
                    <div class="balance-item">
                        <div class="balance-label">💵 Saldo Disponível</div>
                        <div class="balance-value">R$ <?php echo number_format($balanceData['saldo_admin']['valor_disponivel'], 2, ',', '.'); ?></div>
                    </div>
                    
                    <div class="balance-item">
                        <div class="balance-label">⏳ Saldo Pendente</div>
                        <div class="balance-value">R$ <?php echo number_format($balanceData['saldo_admin']['valor_pendente'], 2, ',', '.'); ?></div>
                    </div>
                    
                    <div class="balance-item">
                        <div class="balance-label">📊 Saldo Total</div>
                        <div class="balance-value">R$ <?php echo number_format($balanceData['saldo_admin']['valor_total'], 2, ',', '.'); ?></div>
                    </div>
                </div>
                
                <div class="quick-actions">
                    <a href="<?php echo ADMIN_PAYMENTS_URL; ?>" class="btn-action btn-primary">
                        📋 Gerenciar Pagamentos
                    </a>
                    <a href="<?php echo ADMIN_PAYMENTS_URL; ?>?tab=balance" class="btn-action btn-secondary">
                        💳 Pagamentos de Saldo
                    </a>
                </div>
            </div>
            
            <!-- Cards de estatísticas principais -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-card-title">📈 Total de Transações</div>
                    <div class="stat-card-value"><?php echo number_format($balanceData['estatisticas']['total_transacoes'] ?? 0); ?></div>
                    <div class="stat-card-subtitle">Comissões processadas</div>
                </div>
                
                <div class="stat-card balance">
                    <div class="stat-card-title">💰 Comissões Aprovadas</div>
                    <div class="stat-card-value">R$ <?php echo number_format($balanceData['estatisticas']['comissoes_aprovadas'] ?? 0, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle"><?php echo number_format($balanceData['estatisticas']['transacoes_aprovadas'] ?? 0); ?> transações</div>
                </div>
                
                <div class="stat-card pending">
                    <div class="stat-card-title">⏳ Comissões Pendentes</div>
                    <div class="stat-card-value">R$ <?php echo number_format($balanceData['estatisticas']['comissoes_pendentes'] ?? 0, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle"><?php echo number_format($balanceData['estatisticas']['transacoes_pendentes'] ?? 0); ?> transações</div>
                </div>
                
                <div class="stat-card outgoing">
                    <div class="stat-card-title">💸 Saldo Pago às Lojas</div>
                    <div class="stat-card-value">R$ <?php echo number_format($balanceData['balance_stats']['valor_total_pago'] ?? 0, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Reembolsos realizados</div>
                </div>
                
                <div class="stat-card pending">
                    <div class="stat-card-title">⏳ Saldo Pendente às Lojas</div>
                    <div class="stat-card-value">R$ <?php echo number_format($balanceData['balance_stats']['valor_total_pendente'] ?? 0, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle"><?php echo number_format($balanceData['balance_stats']['total_lojas'] ?? 0); ?> lojas</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">🏪 Lojas Ativas</div>
                    <div class="stat-card-value"><?php echo count($balanceData['top_lojas']); ?></div>
                    <div class="stat-card-subtitle">Gerando comissões</div>
                </div>
            </div>

            <!-- Gráficos e estatísticas -->
            <div class="two-column-layout">
                <!-- Gráfico mensal -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📊 Movimentação Mensal</div>
                    </div>
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
                
                <!-- Top lojas -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">🏆 Top Lojas (Comissões)</div>
                    </div>
                    
                    <div class="top-stores-list">
                        <?php if (!empty($balanceData['top_lojas'])): ?>
                            <?php foreach ($balanceData['top_lojas'] as $index => $loja): ?>
                                <div class="store-item">
                                    <div class="store-rank">#<?php echo $index + 1; ?></div>
                                    <div class="store-info">
                                        <div class="store-name"><?php echo htmlspecialchars($loja['nome_fantasia']); ?></div>
                                        <div class="store-details">
                                            R$ <?php echo number_format($loja['total_comissoes'], 2, ',', '.'); ?> 
                                            (<?php echo $loja['quantidade_transacoes']; ?> transações)
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="store-item">
                                <div class="store-info">
                                    <div class="store-name">Nenhuma loja encontrada</div>
                                    <div class="store-details">Aguardando primeiras transações</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Histórico de Movimentações -->
            <div class="card transactions-container">
                <div class="card-header">
                    <div class="card-title">📋 Últimas Movimentações do Saldo</div>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Descrição</th>
                                <th>Tipo</th>
                                <th>Valor</th>
                                <th>Transação</th>
                                <th>Loja</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($balanceData['movimentacoes'])): ?>
                                <?php foreach ($balanceData['movimentacoes'] as $mov): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($mov['data_operacao'])); ?></td>
                                        <td><?php echo htmlspecialchars($mov['descricao']); ?></td>
                                        <td>
                                            <span class="movement-type <?php echo $mov['tipo']; ?>">
                                                <?php echo $mov['tipo'] == 'credito' ? '📈 Entrada' : '📉 Saída'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: <?php echo $mov['tipo'] == 'credito' ? '#28a745' : '#dc3545'; ?>; font-weight: 600;">
                                                <?php echo $mov['tipo'] == 'credito' ? '+' : '-'; ?>R$ <?php echo number_format($mov['valor'], 2, ',', '.'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($mov['codigo_transacao'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($mov['loja_nome'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px;">
                                        <div style="color: #666;">
                                            <strong>📭 Nenhuma movimentação encontrada</strong><br>
                                            <small>Movimentações aparecerão aqui conforme as transações forem processadas</small>
                                        </div>
                                    </td>
                                </tr>
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
                    if (!empty($balanceData['mensal'])) {
                        $monthLabels = array_map(function($item) {
                            $date = DateTime::createFromFormat('Y-m', $item['mes']);
                            return "'" . $date->format('M/Y') . "'";
                        }, array_reverse($balanceData['mensal']));
                        echo implode(', ', $monthLabels);
                    }
                ?>
            ],
            entrada: [
                <?php 
                    if (!empty($balanceData['mensal'])) {
                        $entradas = array_map(function($item) {
                            return $item['entrada'];
                        }, array_reverse($balanceData['mensal']));
                        echo implode(', ', $entradas);
                    }
                ?>
            ],
            saida: [
                <?php 
                    if (!empty($balanceData['mensal'])) {
                        $saidas = array_map(function($item) {
                            return $item['saida'];
                        }, array_reverse($balanceData['mensal']));
                        echo implode(', ', $saidas);
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
                datasets: [
                    {
                        label: 'Entradas (R$)',
                        data: monthlyData.entrada,
                        backgroundColor: '#28a745',
                        borderColor: '#1e7e34',
                        borderWidth: 1
                    },
                    {
                        label: 'Saídas (R$)',
                        data: monthlyData.saida,
                        backgroundColor: '#dc3545',
                        borderColor: '#c82333',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Movimentação Financeira Mensal'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return 'R$ ' + value.toLocaleString('pt-BR');
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': R$ ' + context.parsed.y.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                        }
                    }
                }
            }
        });
        
        // Auto-refresh da página a cada 5 minutos para manter dados atualizados
        setTimeout(function() {
            location.reload();
        }, 300000); // 5 minutos
    </script>
</body>
</html>
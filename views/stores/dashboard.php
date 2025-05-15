<?php
// views/stores/dashboard.php
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/StoreController.php';
require_once '../../controllers/TransactionController.php';
require_once '../../models/Store.php';

// Verificar se o usuário está logado
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . LOGIN_URL);
    exit;
}

// Verificar se o usuário é uma loja
if ($_SESSION['user_type'] !== 'loja') {
    header('Location: ' . CLIENT_DASHBOARD_URL);
    exit;
}

// Obter ID da loja associada ao usuário
$db = Database::getConnection();
$stmt = $db->prepare("SELECT id FROM lojas WHERE usuario_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$store = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$store) {
    // Se o usuário é do tipo loja mas não tem loja associada
    echo "Erro: Perfil de loja não encontrado. Entre em contato com o suporte.";
    exit;
}

$storeId = $store['id'];
$storeModel = new Store($storeId);

// Obter estatísticas para o dashboard
// Filtro padrão: últimos 30 dias
$filters = [
    'data_inicio' => date('Y-m-d', strtotime('-30 days')),
    'data_fim' => date('Y-m-d')
];

// Obter estatísticas da loja
$stats = $storeModel->getEstatisticas($filters);

// Obter últimas transações
$transacoesData = $storeModel->getTransacoes(['limit' => 5], 1, 5);
$ultimasTransacoes = $transacoesData['transacoes'];

// Obter comissões pendentes
$comissoesPendentes = TransactionController::getPendingTransactions($storeId, [], 1);
$totalPendente = 0;
if ($comissoesPendentes['status']) {
    $totalPendente = $comissoesPendentes['data']['totais']['total_valor_comissoes'];
}

// Título da página
$pageTitle = "Dashboard da Loja";
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Klube Cash</title>
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/store.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-layout">
        <!-- Incluir navbar -->
        <?php include('../components/navbar.php'); ?>
        
        <div class="main-container">
            <!-- Incluir sidebar para loja -->
            <?php 
            $activeMenu = 'dashboard'; 
            include('../components/sidebar-store.php'); 
            ?>
            
            <main id="mainContent" class="content">
                <div class="page-header">
                    <h1>Dashboard</h1>
                    <p>Bem-vindo ao painel de controle da sua loja no Klube Cash</p>
                </div>
                
                <!-- Cards de Resumo -->
                <div class="summary-cards">
                    <div class="card">
                        <div class="card-content">
                            <h3>Vendas Totais</h3>
                            <p class="card-value">R$ <?php echo number_format($stats['total_vendas'] ?? 0, 2, ',', '.'); ?></p>
                            <p class="card-period">Últimos 30 dias</p>
                        </div>
                        <div class="card-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-content">
                            <h3>Comissões Pendentes</h3>
                            <p class="card-value">R$ <?php echo number_format($totalPendente, 2, ',', '.'); ?></p>
                            <p class="card-period">A pagar</p>
                        </div>
                        <div class="card-icon warning">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-content">
                            <h3>Transações</h3>
                            <p class="card-value"><?php echo $stats['total_transacoes'] ?? 0; ?></p>
                            <p class="card-period">Últimos 30 dias</p>
                        </div>
                        <div class="card-icon success">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-content">
                            <h3>Ticket Médio</h3>
                            <p class="card-value">R$ <?php echo number_format($stats['ticket_medio'] ?? 0, 2, ',', '.'); ?></p>
                            <p class="card-period">Últimos 30 dias</p>
                        </div>
                        <div class="card-icon info">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                    </div>
                </div>
                
                <!-- Alerta de Comissões Pendentes -->
                <?php if ($totalPendente > 0): ?>
                <div class="alert warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <div>
                        <h4>Comissões Pendentes</h4>
                        <p>Você tem R$ <?php echo number_format($totalPendente, 2, ',', '.'); ?> em comissões pendentes de pagamento.</p>
                    </div>
                    <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="btn btn-warning">Ver Detalhes</a>
                </div>
                <?php endif; ?>
                
                <!-- Ações Rápidas -->
                <div class="quick-actions">
                    <h2>Ações Rápidas</h2>
                    <div class="actions-grid">
                        <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="action-card">
                            <div class="action-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </div>
                            <h3>Registrar Venda</h3>
                            <p>Cadastre uma nova transação de cliente</p>
                        </a>
                        
                        <a href="<?php echo STORE_BATCH_UPLOAD_URL; ?>" class="action-card">
                            <div class="action-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                            </div>
                            <h3>Upload em Lote</h3>
                            <p>Importe múltiplas transações de uma vez</p>
                        </a>
                        
                        <a href="<?php echo STORE_PAYMENT_URL; ?>" class="action-card">
                            <div class="action-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                            </div>
                            <h3>Realizar Pagamento</h3>
                            <p>Pague as comissões pendentes</p>
                        </a>
                        
                        <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" class="action-card">
                            <div class="action-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                            </div>
                            <h3>Histórico</h3>
                            <p>Veja seus pagamentos anteriores</p>
                        </a>
                    </div>
                </div>
                
                <!-- Gráfico de Desempenho -->
                <div class="chart-container">
                    <h2>Desempenho de Vendas</h2>
                    <div class="chart-wrapper">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
                
                <!-- Últimas Transações -->
                <div class="recent-transactions">
                    <div class="section-header">
                        <h2>Últimas Transações</h2>
                        <a href="<?php echo STORE_TRANSACTIONS_URL; ?>" class="link-more">Ver todas</a>
                    </div>
                    
                    <?php if (empty($ultimasTransacoes)): ?>
                        <div class="empty-state">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            <h3>Nenhuma transação encontrada</h3>
                            <p>Ainda não há registros de transações. Comece a registrar suas vendas!</p>
                            <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="btn btn-primary">Registrar Venda</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Cliente</th>
                                        <th>Valor</th>
                                        <th>Cashback</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ultimasTransacoes as $transacao): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($transacao['data_transacao'])); ?></td>
                                        <td><?php echo htmlspecialchars($transacao['nome_usuario']); ?></td>
                                        <td>R$ <?php echo number_format($transacao['valor_total'], 2, ',', '.'); ?></td>
                                        <td>R$ <?php echo number_format($transacao['valor_cashback'], 2, ',', '.'); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower($transacao['status']); ?>">
                                                <?php 
                                                    switch($transacao['status']) {
                                                        case 'pendente': echo 'Pendente'; break;
                                                        case 'aprovado': echo 'Aprovado'; break;
                                                        case 'cancelado': echo 'Cancelado'; break;
                                                        default: echo ucfirst($transacao['status']);
                                                    }
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="javascript:void(0)" class="btn-icon view-transaction" data-id="<?php echo $transacao['id']; ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <circle cx="11" cy="11" r="8"></circle>
                                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Modal para Detalhes da Transação -->
    <div id="transactionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalhes da Transação</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body" id="transactionDetails">
                <!-- Detalhes da transação serão carregados aqui via AJAX -->
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Carregando detalhes...</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/store.js"></script>
    <script>
        // Dados para o gráfico (simulados - seria melhor gerar via PHP)
        const salesData = {
            labels: [
                <?php 
                // Gerar labels para os últimos 10 dias
                for($i = 10; $i >= 0; $i--) {
                    echo "'" . date('d/m', strtotime("-$i days")) . "',";
                }
                ?>
            ],
            datasets: [{
                label: 'Vendas (R$)',
                data: [
                    <?php 
                    // Dados simulados - em uma implementação real, viriam do banco de dados
                    for($i = 0; $i < 11; $i++) {
                        echo rand(500, 5000) . ",";
                    }
                    ?>
                ],
                borderColor: '#FF7A00',
                backgroundColor: 'rgba(255, 122, 0, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        };

        // Configuração do gráfico
        const config = {
            type: 'line',
            data: salesData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'R$ ' + context.raw.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'R$ ' + value.toFixed(2).replace('.', ',');
                            }
                        }
                    }
                }
            },
        };

        // Inicializar o gráfico
        const myChart = new Chart(
            document.getElementById('salesChart'),
            config
        );

        // Funcionalidade do modal
        const modal = document.getElementById("transactionModal");
        const closeBtn = document.getElementsByClassName("close")[0];
        const viewButtons = document.querySelectorAll(".view-transaction");
        
        viewButtons.forEach(button => {
            button.addEventListener("click", function() {
                const transactionId = this.getAttribute("data-id");
                // Aqui você carregaria os detalhes da transação via AJAX
                document.getElementById("transactionDetails").innerHTML = `
                    <div class="transaction-info">
                        <div class="info-group">
                            <span class="label">ID da Transação:</span>
                            <span class="value">${transactionId}</span>
                        </div>
                        <div class="info-group">
                            <span class="label">Data:</span>
                            <span class="value">Carregando...</span>
                        </div>
                        <!-- Mais detalhes seriam carregados aqui -->
                    </div>
                `;
                modal.style.display = "block";
                
                // Simulação de carregamento AJAX (em produção, use fetch ou XMLHttpRequest)
                setTimeout(() => {
                    document.getElementById("transactionDetails").innerHTML = `
                        <div class="transaction-info">
                            <div class="info-group">
                                <span class="label">ID da Transação:</span>
                                <span class="value">${transactionId}</span>
                            </div>
                            <div class="info-group">
                                <span class="label">Data:</span>
                                <span class="value">01/05/2025 14:30</span>
                            </div>
                            <div class="info-group">
                                <span class="label">Cliente:</span>
                                <span class="value">João Silva (joao@email.com)</span>
                            </div>
                            <div class="info-group">
                                <span class="label">Valor da Venda:</span>
                                <span class="value">R$ 150,00</span>
                            </div>
                            <div class="info-group">
                                <span class="label">Cashback (5%):</span>
                                <span class="value">R$ 7,50</span>
                            </div>
                            <div class="info-group">
                                <span class="label">Comissão Klube Cash (5%):</span>
                                <span class="value">R$ 7,50</span>
                            </div>
                            <div class="info-group">
                                <span class="label">Status:</span>
                                <span class="value status-badge pendente">Pendente</span>
                            </div>
                            <div class="info-group">
                                <span class="label">Código Interno:</span>
                                <span class="value">VENDA-12345</span>
                            </div>
                        </div>
                    `;
                }, 1000);
            });
        });
        
        // Fechar o modal ao clicar no X
        closeBtn.addEventListener("click", function() {
            modal.style.display = "none";
        });
        
        // Fechar o modal ao clicar fora dele
        window.addEventListener("click", function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        });
    </script>
</body>
</html>
<?php
// views/client/dashboard.php
// Definir o menu ativo na sidebar
$activeMenu = 'painel';

// Incluir conexão com o banco de dados e arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/ClientController.php';
require_once '../../controllers/AuthController.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é cliente
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_CLIENT) {
    // Redirecionar para a página de login com mensagem de erro
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Obter dados do dashboard para o cliente logado
$userId = $_SESSION['user_id'];
$result = ClientController::getDashboardData($userId);

// Verificar se houve erro
$hasError = !$result['status'];
$errorMessage = $hasError ? $result['message'] : '';

// Dados para exibição no dashboard
$dashboardData = $hasError ? [] : $result['data'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    
    <!-- Inclusão de bibliotecas externas (Chart.js para gráficos) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="stylesheet" href="../../assets/css/views/client/dashboard.css">
</head>
<body>
    <!-- Incluir navegação sidebar e/ou navbar -->
    <?php include_once '../components/navbar.php'; ?>
    
    <div class="container" style="margin-top: 80px;">
        <!-- Cabeçalho do Dashboard -->
        <div class="dashboard-header">
            <div>
                <h1>Olá, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
                <p class="dashboard-subtitle">Bem-vindo ao seu painel de cashback</p>
            </div>
            <div>
            <a href="<?php echo CLIENT_STATEMENT_URL; ?>" class="btn btn-primary">Ver Extrato Completo</a>
            </div>
        </div>
        
        <?php if ($hasError): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php else: ?>
        
        <!-- Cards de Resumo -->
        <div class="summary-cards">
            <div class="card summary-card">
                <h3 class="card-title">Saldo Total de Cashback</h3>
                <div class="card-value">R$ <?php echo number_format($dashboardData['saldo_total'], 2, ',', '.'); ?></div>
                <div class="card-change positive">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="18 15 12 9 6 15"></polyline>
                    </svg>
                    Disponível para usar
                </div>
            </div>
            
            <div class="card summary-card">
                <h3 class="card-title">Total de Compras</h3>
                <div class="card-value">R$ <?php echo number_format($dashboardData['estatisticas']['total_compras'] ?? 0, 2, ',', '.'); ?></div>
                <div class="card-change">
                    <?php echo $dashboardData['estatisticas']['total_transacoes'] ?? 0; ?> transações
                </div>
            </div>
            
            <div class="card summary-card">
                <h3 class="card-title">Total de Cashback</h3>
                <div class="card-value">R$ <?php echo number_format($dashboardData['estatisticas']['total_cashback'] ?? 0, 2, ',', '.'); ?></div>
                <div class="card-change">
                    <?php 
                    $percentualCashback = 0;
                    if (!empty($dashboardData['estatisticas']['total_compras'])) {
                        $percentualCashback = ($dashboardData['estatisticas']['total_cashback'] / $dashboardData['estatisticas']['total_compras']) * 100;
                    }
                    echo number_format($percentualCashback, 2) . '% das compras';
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Grade Principal do Dashboard -->
        <div class="dashboard-grid">
            <!-- Coluna da Esquerda -->
            <div>
                <!-- Transações Recentes -->
                <div class="card">
                    <h3 class="card-title">Transações Recentes</h3>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Loja</th>
                                    <th>Data</th>
                                    <th>Valor</th>
                                    <th>Cashback</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dashboardData['transacoes_recentes'])): ?>
                                    <tr>
                                        <td colspan="5" style="text-align: center;">Nenhuma transação encontrada</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dashboardData['transacoes_recentes'] as $transacao): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($transacao['loja_nome']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($transacao['data_transacao'])); ?></td>
                                            <td>R$ <?php echo number_format($transacao['valor_total'], 2, ',', '.'); ?></td>
                                            <td>R$ <?php echo number_format($transacao['valor_cashback'], 2, ',', '.'); ?></td>
                                            <td>
                                                <?php 
                                                $statusClass = '';
                                                switch ($transacao['status']) {
                                                    case 'aprovado':
                                                        $statusClass = 'badge-success';
                                                        break;
                                                    case 'pendente':
                                                        $statusClass = 'badge-warning';
                                                        break;
                                                    case 'cancelado':
                                                        $statusClass = 'badge-danger';
                                                        break;
                                                }
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?>">
                                                    <?php echo ucfirst($transacao['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <a href="<?php echo CLIENT_STATEMENT_URL; ?>" class="see-all">Ver todas as transações</a>
                </div>
                
                <!-- Gráfico de Cashback -->
                <div class="card" style="margin-top: 20px;">
                    <h3 class="card-title">Histórico de Cashback</h3>
                    <div class="chart-container">
                        <canvas id="cashbackChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Coluna da Direita -->
            <div>
                <!-- Lojas Favoritas -->
                <div class="card">
                    <h3 class="card-title">Suas Lojas Favoritas</h3>
                    <div class="favorite-stores">
                        <?php if (empty($dashboardData['lojas_favoritas'])): ?>
                            <p style="text-align: center; padding: 20px;">Nenhuma loja favorita encontrada</p>
                        <?php else: ?>
                            <?php foreach ($dashboardData['lojas_favoritas'] as $loja): ?>
                                <div class="store-card">
                                    <div class="store-logo">
                                        <?php echo strtoupper(substr($loja['nome_fantasia'], 0, 1)); ?>
                                    </div>
                                    <div class="store-info">
                                        <h4 class="store-name"><?php echo htmlspecialchars($loja['nome_fantasia']); ?></h4>
                                        <p class="store-cashback">
                                            Cashback: <span><?php echo number_format($loja['porcentagem_cashback'] ?? 0, 2); ?>%</span>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <a href="<?php echo CLIENT_STORES_URL; ?>" class="see-all">Ver todas as lojas parceiras</a>
        
                </div>
                
                <!-- Notificações -->
                <div class="card" style="margin-top: 20px;">
                    <h3 class="card-title">Notificações</h3>
                    <?php if (empty($dashboardData['notificacoes'])): ?>
                        <p style="text-align: center; padding: 20px;">Nenhuma notificação disponível</p>
                    <?php else: ?>
                        <?php foreach ($dashboardData['notificacoes'] as $notificacao): ?>
                            <?php 
                            // Verificar se tem link
                            $hasLink = !empty($notificacao['link']);
                            ?>
                            <div class="notification" style="position: relative;">
                                <?php if ($hasLink): ?>
                                    <a href="<?php echo htmlspecialchars($notificacao['link']); ?>" style="text-decoration: none; color: inherit; display: block;">
                                <?php endif; ?>
                                
                                <h4 class="notification-title"><?php echo htmlspecialchars($notificacao['titulo']); ?></h4>
                                <p class="notification-text"><?php echo htmlspecialchars($notificacao['mensagem']); ?></p>
                                <p class="notification-time" style="font-size: 12px; color: #888; margin-top: 5px;">
                                    <?php echo date('d/m/Y H:i', strtotime($notificacao['data_criacao'])); ?>
                                </p>

                                
                                <?php if ($hasLink): ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Arquivos JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar gráfico de cashback
            const ctx = document.getElementById('cashbackChart');
            
            <?php if (!$hasError && !empty($dashboardData)): ?>
            // Dados para o gráfico (simulados, você pode ajustar com dados reais)
            const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'];
            const cashbackValues = [120, 190, 300, 250, 400, 350];
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Cashback (R$)',
                        data: cashbackValues,
                        borderColor: '#FF7A00',
                        backgroundColor: 'rgba(255, 122, 0, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>
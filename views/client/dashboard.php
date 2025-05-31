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

try {
    $db = Database::getConnection();
    
    // Obter dados básicos do dashboard
    $result = ClientController::getDashboardData($userId);
    
    // Verificar se houve erro
    $hasError = !$result['status'];
    $errorMessage = $hasError ? $result['message'] : '';
    
    // Dados para exibição no dashboard
    $dashboardData = $hasError ? [] : $result['data'];
    
    // Obter dados detalhados de saldo
    require_once '../../models/CashbackBalance.php';
    $balanceModel = new CashbackBalance();
    
    // Saldo total disponível
    $saldoTotalDisponivel = $balanceModel->getTotalBalance($userId);
    
    // Saldos por loja
    $saldosPorLoja = $balanceModel->getAllUserBalances($userId);
    
    // Saldos pendentes (cashback não liberado ainda)
    $saldoPendenteQuery = "
        SELECT 
            SUM(t.valor_cliente) as total_pendente,
            l.nome_fantasia,
            COUNT(*) as qtd_transacoes
        FROM transacoes_cashback t
        JOIN lojas l ON t.loja_id = l.id
        WHERE t.usuario_id = :user_id 
        AND t.status = 'pendente'
        GROUP BY l.id, l.nome_fantasia
        ORDER BY total_pendente DESC
    ";
    $saldoPendenteStmt = $db->prepare($saldoPendenteQuery);
    $saldoPendenteStmt->bindParam(':user_id', $userId);
    $saldoPendenteStmt->execute();
    $saldosPendentes = $saldoPendenteStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalSaldoPendente = array_sum(array_column($saldosPendentes, 'total_pendente'));
    
    // Movimentações recentes de saldo
    $movimentacoesQuery = "
        SELECT 
            cm.*,
            l.nome_fantasia as loja_nome,
            CASE 
                WHEN cm.transacao_origem_id IS NOT NULL THEN 'compra'
                WHEN cm.transacao_uso_id IS NOT NULL THEN 'compra_com_desconto'
                ELSE 'outro'
            END as origem_tipo
        FROM cashback_movimentacoes cm
        JOIN lojas l ON cm.loja_id = l.id
        WHERE cm.usuario_id = :user_id
        ORDER BY cm.data_operacao DESC
        LIMIT 10
    ";
    $movimentacoesStmt = $db->prepare($movimentacoesQuery);
    $movimentacoesStmt->bindParam(':user_id', $userId);
    $movimentacoesStmt->execute();
    $movimentacoesRecentes = $movimentacoesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Estatísticas de uso de saldo
    $usoSaldoQuery = "
        SELECT 
            SUM(CASE WHEN tipo_operacao = 'credito' THEN valor ELSE 0 END) as total_creditado,
            SUM(CASE WHEN tipo_operacao = 'uso' THEN valor ELSE 0 END) as total_usado,
            SUM(CASE WHEN tipo_operacao = 'estorno' THEN valor ELSE 0 END) as total_estornado,
            COUNT(CASE WHEN tipo_operacao = 'uso' THEN 1 END) as total_usos
        FROM cashback_movimentacoes 
        WHERE usuario_id = :user_id
    ";
    $usoSaldoStmt = $db->prepare($usoSaldoQuery);
    $usoSaldoStmt->bindParam(':user_id', $userId);
    $usoSaldoStmt->execute();
    $estatisticasUso = $usoSaldoStmt->fetch(PDO::FETCH_ASSOC);
    
    // Lojas onde mais usa saldo
    $lojasMaisUsadasQuery = "
        SELECT 
            l.nome_fantasia,
            SUM(cm.valor) as total_usado,
            COUNT(*) as qtd_usos
        FROM cashback_movimentacoes cm
        JOIN lojas l ON cm.loja_id = l.id
        WHERE cm.usuario_id = :user_id 
        AND cm.tipo_operacao = 'uso'
        GROUP BY l.id, l.nome_fantasia
        ORDER BY total_usado DESC
        LIMIT 5
    ";
    $lojasMaisUsadasStmt = $db->prepare($lojasMaisUsadasQuery);
    $lojasMaisUsadasStmt->bindParam(':user_id', $userId);
    $lojasMaisUsadasStmt->execute();
    $lojasMaisUsadas = $lojasMaisUsadasStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transações com uso de saldo
    $transacoesComSaldoQuery = "
        SELECT 
            t.*,
            l.nome_fantasia as loja_nome,
            COALESCE(
                (SELECT SUM(cm.valor) 
                FROM cashback_movimentacoes cm 
                WHERE cm.transacao_uso_id = t.id AND cm.tipo_operacao = 'uso'), 0
            ) as saldo_usado
        FROM transacoes_cashback t
        JOIN lojas l ON t.loja_id = l.id
        WHERE t.usuario_id = :user_id
        ORDER BY t.data_transacao DESC
        LIMIT 10
    ";
    $transacoesComSaldoStmt = $db->prepare($transacoesComSaldoQuery);
    $transacoesComSaldoStmt->bindParam(':user_id', $userId);
    $transacoesComSaldoStmt->execute();
    $transacoesRecentes = $transacoesComSaldoStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log('Erro ao carregar dashboard do cliente: ' . $e->getMessage());
    $hasError = true;
    $errorMessage = 'Erro ao carregar dados do dashboard.';
    $dashboardData = [];
    $saldoTotalDisponivel = 0;
    $saldosPorLoja = [];
    $saldosPendentes = [];
    $totalSaldoPendente = 0;
    $movimentacoesRecentes = [];
    $estatisticasUso = [
        'total_creditado' => 0,
        'total_usado' => 0,
        'total_estornado' => 0,
        'total_usos' => 0
    ];
    $lojasMaisUsadas = [];
    $transacoesRecentes = [];
}

// Função para formatar moeda
function formatCurrency($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

// Função para formatar data
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Função para formatar data e hora
function formatDateTime($dateTime) {
    return date('d/m/Y H:i', strtotime($dateTime));
}
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
                <a href="<?php echo CLIENT_BALANCE_URL; ?>" class="btn btn-secondary">Ver Saldos</a>
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
                <h3 class="card-title">Saldo Disponível</h3>
                <div class="card-value"><?php echo formatCurrency($saldoTotalDisponivel); ?></div>
                <div class="card-change positive">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="18 15 12 9 6 15"></polyline>
                    </svg>
                    Pronto para usar
                </div>
            </div>
            
            <div class="card summary-card">
                <h3 class="card-title">Saldo Pendente</h3>
                <div class="card-value"><?php echo formatCurrency($totalSaldoPendente); ?></div>
                <div class="card-change warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    Aguardando liberação
                </div>
            </div>
            
            <div class="card summary-card">
                <h3 class="card-title">Total Usado</h3>
                <div class="card-value"><?php echo formatCurrency($estatisticasUso['total_usado']); ?></div>
                <div class="card-change">
                    <?php echo $estatisticasUso['total_usos']; ?> usos
                </div>
            </div>
            
            <div class="card summary-card">
                <h3 class="card-title">Total Recebido</h3>
                <div class="card-value"><?php echo formatCurrency($estatisticasUso['total_creditado']); ?></div>
                <div class="card-change">
                    Histórico completo
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
                                    <th>Valor Original</th>
                                    <th>Saldo Usado</th>
                                    <th>Valor Pago</th>
                                    <th>Cashback Recebido</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transacoesRecentes)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center;">Nenhuma transação encontrada</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transacoesRecentes as $transacao): ?>
                                        <?php
                                        $valorOriginal = $transacao['valor_total'];
                                        $saldoUsado = $transacao['saldo_usado'];
                                        $valorPago = $valorOriginal - $saldoUsado;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($transacao['loja_nome']); ?></td>
                                            <td><?php echo formatDate($transacao['data_transacao']); ?></td>
                                            <td><?php echo formatCurrency($valorOriginal); ?></td>
                                            <td>
                                                <?php if ($saldoUsado > 0): ?>
                                                    <span style="color: #4CAF50;"><?php echo formatCurrency($saldoUsado); ?></span>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatCurrency($valorPago); ?></td>
                                            <!-- CORREÇÃO: Mostrar o cashback que o CLIENTE vai receber, não o da loja -->
                                            <td><?php echo formatCurrency($transacao['valor_cliente']); ?></td>
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
                
                <!-- Movimentações de Saldo -->
                <div class="card" style="margin-top: 20px;">
                    <h3 class="card-title">Movimentações de Saldo Recentes</h3>
                    <div class="movements-list">
                        <?php if (empty($movimentacoesRecentes)): ?>
                            <p style="text-align: center; padding: 20px;">Nenhuma movimentação de saldo encontrada</p>
                        <?php else: ?>
                            <?php foreach ($movimentacoesRecentes as $movimento): ?>
                                <div class="movement-item">
                                    <div class="movement-icon">
                                        <?php 
                                        $iconClass = '';
                                        $iconColor = '';
                                        switch ($movimento['tipo_operacao']) {
                                            case 'credito':
                                                $iconClass = 'arrow-down';
                                                $iconColor = '#4CAF50';
                                                break;
                                            case 'uso':
                                                $iconClass = 'arrow-up';
                                                $iconColor = '#FF9800';
                                                break;
                                            case 'estorno':
                                                $iconClass = 'rotate-ccw';
                                                $iconColor = '#2196F3';
                                                break;
                                        }
                                        ?>
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="<?php echo $iconColor; ?>" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <?php if ($movimento['tipo_operacao'] === 'credito'): ?>
                                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                                <polyline points="19 12 12 19 5 12"></polyline>
                                            <?php elseif ($movimento['tipo_operacao'] === 'uso'): ?>
                                                <line x1="12" y1="19" x2="12" y2="5"></line>
                                                <polyline points="5 12 12 5 19 12"></polyline>
                                            <?php else: ?>
                                                <polyline points="1 4 1 10 7 10"></polyline>
                                                <path d="m1 10 6-6v6"></path>
                                                <path d="M19 4a2 2 0 01-2 2H5"></path>
                                            <?php endif; ?>
                                        </svg>
                                    </div>
                                    <div class="movement-details">
                                        <div class="movement-description">
                                            <?php 
                                            switch ($movimento['tipo_operacao']) {
                                                case 'credito':
                                                    echo 'Cashback recebido - ' . htmlspecialchars($movimento['loja_nome']);
                                                    break;
                                                case 'uso':
                                                    echo 'Saldo usado - ' . htmlspecialchars($movimento['loja_nome']);
                                                    break;
                                                case 'estorno':
                                                    echo 'Estorno - ' . htmlspecialchars($movimento['loja_nome']);
                                                    break;
                                            }
                                            ?>
                                        </div>
                                        <div class="movement-date"><?php echo formatDateTime($movimento['data_operacao']); ?></div>
                                    </div>
                                    <div class="movement-amount">
                                        <?php 
                                        $amountClass = '';
                                        $prefix = '';
                                        switch ($movimento['tipo_operacao']) {
                                            case 'credito':
                                            case 'estorno':
                                                $amountClass = 'positive';
                                                $prefix = '+';
                                                break;
                                            case 'uso':
                                                $amountClass = 'negative';
                                                $prefix = '-';
                                                break;
                                        }
                                        ?>
                                        <span class="amount <?php echo $amountClass; ?>">
                                            <?php echo $prefix . formatCurrency($movimento['valor']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <a href="<?php echo CLIENT_BALANCE_URL; ?>" class="see-all">Ver histórico completo de saldo</a>
                </div>
            </div>
            
            <!-- Coluna da Direita -->
            <div>
                <!-- Saldos por Loja -->
                <div class="card">
                    <h3 class="card-title">Saldos por Loja</h3>
                    <div class="balance-by-store">
                        <?php if (empty($saldosPorLoja)): ?>
                            <p style="text-align: center; padding: 20px;">Nenhum saldo disponível</p>
                        <?php else: ?>
                            <?php foreach (array_slice($saldosPorLoja, 0, 5) as $saldo): ?>
                                <div class="store-balance-item">
                                    <div class="store-logo">
                                        <?php echo strtoupper(substr($saldo['nome_fantasia'], 0, 1)); ?>
                                    </div>
                                    <div class="store-info">
                                        <h4 class="store-name"><?php echo htmlspecialchars($saldo['nome_fantasia']); ?></h4>
                                        <p class="store-balance"><?php echo formatCurrency($saldo['saldo_disponivel']); ?></p>
                                    </div>
                                    <div class="store-stats">
                                        <span><?php echo $saldo['total_transacoes']; ?> transações</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <a href="<?php echo CLIENT_BALANCE_URL; ?>" class="see-all">Ver todos os saldos</a>
                </div>
                
                <!-- Saldos Pendentes -->
                <?php if (!empty($saldosPendentes)): ?>
                <div class="card" style="margin-top: 20px;">
                    <h3 class="card-title">Saldos Pendentes</h3>
                    <div class="pending-balances">
                        <div class="pending-info">
                            <p>Você tem cashback aguardando liberação. Estes valores serão disponibilizados assim que as lojas confirmarem o pagamento.</p>
                        </div>
                        <?php foreach ($saldosPendentes as $pendente): ?>
                            <div class="pending-item">
                                <div class="pending-store"><?php echo htmlspecialchars($pendente['nome_fantasia']); ?></div>
                                <div class="pending-details">
                                    <span class="pending-amount"><?php echo formatCurrency($pendente['total_pendente']); ?></span>
                                    <span class="pending-count"><?php echo $pendente['qtd_transacoes']; ?> transações</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Lojas Onde Mais Usa Saldo -->
                <?php if (!empty($lojasMaisUsadas)): ?>
                <div class="card" style="margin-top: 20px;">
                    <h3 class="card-title">Lojas Onde Mais Usa Saldo</h3>
                    <div class="top-stores-usage">
                        <?php foreach ($lojasMaisUsadas as $loja): ?>
                            <div class="usage-item">
                                <div class="usage-store"><?php echo htmlspecialchars($loja['nome_fantasia']); ?></div>
                                <div class="usage-stats">
                                    <span class="usage-amount"><?php echo formatCurrency($loja['total_usado']); ?></span>
                                    <span class="usage-count"><?php echo $loja['qtd_usos']; ?> usos</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Gráfico de Cashback -->
                <div class="card" style="margin-top: 20px;">
                    <h3 class="card-title">Histórico de Cashback</h3>
                    <div class="chart-container">
                        <canvas id="cashbackChart"></canvas>
                    </div>
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
            // Dados para o gráfico (você pode ajustar com dados reais do PHP)
            const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'];
            const cashbackValues = [120, 190, 300, 250, 400, 350];
            const usageValues = [50, 80, 150, 100, 200, 180];
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Cashback Recebido (R$)',
                        data: cashbackValues,
                        borderColor: '#FF7A00',
                        backgroundColor: 'rgba(255, 122, 0, 0.1)',
                        tension: 0.3,
                        fill: true
                    }, {
                        label: 'Saldo Usado (R$)',
                        data: usageValues,
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.3,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
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
<?php
// views/client/balance.php
// Definir o menu ativo na sidebar
$activeMenu = 'saldo';

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

// Obter saldo do cliente logado
$userId = $_SESSION['user_id'];

try {
    $db = Database::getConnection();
    
    // Obter saldo total disponível
    $saldoTotalQuery = "
        SELECT 
            SUM(saldo_disponivel) as total_disponivel,
            COUNT(DISTINCT loja_id) as total_lojas_com_saldo
        FROM cashback_saldos 
        WHERE usuario_id = :user_id AND saldo_disponivel > 0
    ";
    $saldoTotalStmt = $db->prepare($saldoTotalQuery);
    $saldoTotalStmt->bindParam(':user_id', $userId);
    $saldoTotalStmt->execute();
    $saldoTotal = $saldoTotalStmt->fetch(PDO::FETCH_ASSOC);
    
    // Obter saldos por loja
    $saldosPorLojaQuery = "
        SELECT 
            cs.*,
            l.nome_fantasia,
            l.categoria,
            l.porcentagem_cashback,
            (SELECT COUNT(*) FROM cashback_movimentacoes cm 
             WHERE cm.usuario_id = cs.usuario_id AND cm.loja_id = cs.loja_id) as total_movimentacoes,
            (SELECT MAX(data_operacao) FROM cashback_movimentacoes cm 
             WHERE cm.usuario_id = cs.usuario_id AND cm.loja_id = cs.loja_id) as ultima_movimentacao
        FROM cashback_saldos cs
        JOIN lojas l ON cs.loja_id = l.id
        WHERE cs.usuario_id = :user_id
        ORDER BY cs.saldo_disponivel DESC
    ";
    $saldosPorLojaStmt = $db->prepare($saldosPorLojaQuery);
    $saldosPorLojaStmt->bindParam(':user_id', $userId);
    $saldosPorLojaStmt->execute();
    $saldosPorLoja = $saldosPorLojaStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obter saldos pendentes (cashback não liberado ainda)
    $saldoPendenteQuery = "
        SELECT 
            SUM(t.valor_cliente) as total_pendente,
            l.nome_fantasia,
            COUNT(*) as qtd_transacoes,
            l.id as loja_id
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
    
    // Estatísticas gerais
    $estatisticasQuery = "
        SELECT 
            SUM(CASE WHEN tipo_operacao = 'credito' THEN valor ELSE 0 END) as total_creditado,
            SUM(CASE WHEN tipo_operacao = 'uso' THEN valor ELSE 0 END) as total_usado,
            SUM(CASE WHEN tipo_operacao = 'estorno' THEN valor ELSE 0 END) as total_estornado,
            COUNT(CASE WHEN tipo_operacao = 'uso' THEN 1 END) as total_usos,
            COUNT(CASE WHEN tipo_operacao = 'credito' THEN 1 END) as total_creditos,
            COUNT(DISTINCT loja_id) as total_lojas_utilizadas
        FROM cashback_movimentacoes 
        WHERE usuario_id = :user_id
    ";
    $estatisticasStmt = $db->prepare($estatisticasQuery);
    $estatisticasStmt->bindParam(':user_id', $userId);
    $estatisticasStmt->execute();
    $estatisticas = $estatisticasStmt->fetch(PDO::FETCH_ASSOC);
    
    // Movimentações recentes
    $movimentacoesQuery = "
        SELECT 
            cm.*,
            l.nome_fantasia as loja_nome
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
    
    // Top 5 lojas mais usadas (por valor)
    $topLojasQuery = "
        SELECT 
            l.nome_fantasia,
            SUM(cm.valor) as total_usado,
            COUNT(*) as qtd_usos,
            cs.saldo_disponivel
        FROM cashback_movimentacoes cm
        JOIN lojas l ON cm.loja_id = l.id
        LEFT JOIN cashback_saldos cs ON cs.loja_id = l.id AND cs.usuario_id = cm.usuario_id
        WHERE cm.usuario_id = :user_id 
        AND cm.tipo_operacao = 'uso'
        GROUP BY l.id, l.nome_fantasia, cs.saldo_disponivel
        ORDER BY total_usado DESC
        LIMIT 5
    ";
    $topLojasStmt = $db->prepare($topLojasQuery);
    $topLojasStmt->bindParam(':user_id', $userId);
    $topLojasStmt->execute();
    $topLojas = $topLojasStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Dados mensais para gráfico (últimos 6 meses)
    $dadosMensaisQuery = "
        SELECT 
            DATE_FORMAT(data_operacao, '%Y-%m') as mes,
            SUM(CASE WHEN tipo_operacao = 'credito' THEN valor ELSE 0 END) as creditos,
            SUM(CASE WHEN tipo_operacao = 'uso' THEN valor ELSE 0 END) as usos,
            SUM(CASE WHEN tipo_operacao = 'estorno' THEN valor ELSE 0 END) as estornos
        FROM cashback_movimentacoes
        WHERE usuario_id = :user_id
        AND data_operacao >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(data_operacao, '%Y-%m')
        ORDER BY mes ASC
    ";
    $dadosMensaisStmt = $db->prepare($dadosMensaisQuery);
    $dadosMensaisStmt->bindParam(':user_id', $userId);
    $dadosMensaisStmt->execute();
    $dadosMensais = $dadosMensaisStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasError = false;
    $errorMessage = '';
    
} catch (Exception $e) {
    error_log('Erro ao carregar dados de saldo: ' . $e->getMessage());
    $hasError = true;
    $errorMessage = 'Erro ao carregar dados de saldo.';
    
    // Valores padrão em caso de erro
    $saldoTotal = ['total_disponivel' => 0, 'total_lojas_com_saldo' => 0];
    $saldosPorLoja = [];
    $saldosPendentes = [];
    $totalSaldoPendente = 0;
    $estatisticas = [
        'total_creditado' => 0,
        'total_usado' => 0,
        'total_estornado' => 0,
        'total_usos' => 0,
        'total_creditos' => 0,
        'total_lojas_utilizadas' => 0
    ];
    $movimentacoesRecentes = [];
    $topLojas = [];
    $dadosMensais = [];
}

// Funções auxiliares
function formatCurrency($value) {
    return 'R$ ' . number_format($value ?: 0, 2, ',', '.');
}

function formatDate($date) {
    if (!$date) return 'Nunca';
    return date('d/m/Y', strtotime($date));
}

function formatDateTime($datetime) {
    if (!$datetime) return 'Nunca';
    return date('d/m/Y H:i', strtotime($datetime));
}

function formatMonth($yearMonth) {
    if (!$yearMonth) return '';
    $parts = explode('-', $yearMonth);
    $year = $parts[0];
    $month = $parts[1];
    
    $monthNames = [
        '01' => 'Jan', '02' => 'Fev', '03' => 'Mar',
        '04' => 'Abr', '05' => 'Mai', '06' => 'Jun',
        '07' => 'Jul', '08' => 'Ago', '09' => 'Set',
        '10' => 'Out', '11' => 'Nov', '12' => 'Dez'
    ];
    
    return $monthNames[$month] . '/' . substr($year, 2);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Saldo - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/views/client/balance.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Incluir navegação navbar -->
    <?php include_once '../components/navbar.php'; ?>
    
    <div class="container" style="margin-top: 80px;">
        <!-- Cabeçalho da Página -->
        <div class="page-header">
            <div>
                <h1>Meu Saldo de Cashback</h1>
                <p class="page-subtitle">Veja seus saldos disponíveis e histórico de movimentações</p>
            </div>
            <div class="header-actions">
                <a href="<?php echo CLIENT_STATEMENT_URL; ?>" class="btn btn-secondary">Ver Extrato</a>
                <a href="<?php echo CLIENT_DASHBOARD_URL; ?>" class="btn btn-primary">Voltar ao Dashboard</a>
            </div>
        </div>
        
        <?php if ($hasError): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php else: ?>
        
        <!-- Card de Resumo Geral -->
        <div class="summary-section">
            <div class="card total-balance-card">
                <div class="card-header">
                    <h2 class="card-title">Saldo Total Disponível</h2>
                    <div class="balance-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                        </svg>
                    </div>
                </div>
                <div class="card-body">
                    <div class="total-amount"><?php echo formatCurrency($saldoTotal['total_disponivel']); ?></div>
                    <div class="summary-stats">
                        <div class="stat">
                            <span class="stat-label">Lojas com saldo</span>
                            <span class="stat-value"><?php echo $saldoTotal['total_lojas_com_saldo']; ?></span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Total usado</span>
                            <span class="stat-value"><?php echo formatCurrency($estatisticas['total_usado']); ?></span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Usos realizados</span>
                            <span class="stat-value"><?php echo $estatisticas['total_usos']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Informações sobre saldos pendentes -->
            <?php if (!empty($saldosPendentes)): ?>
            <div class="card pending-balance-card">
                <div class="card-header">
                    <h3 class="card-title">Saldos Pendentes</h3>
                    <span class="pending-badge">Aguardando aprovação</span>
                </div>
                <div class="card-body">
                    <div class="total-pending"><?php echo formatCurrency($totalSaldoPendente); ?></div>
                    <p class="pending-description">
                        Você tem cashback aguardando confirmação de pagamento das lojas. 
                        Estes valores serão liberados assim que as lojas quitarem suas comissões.
                    </p>
                    <div class="pending-stores">
                        <?php foreach ($saldosPendentes as $pendente): ?>
                        <div class="pending-item">
                            <span class="store-name"><?php echo htmlspecialchars($pendente['nome_fantasia']); ?></span>
                            <span class="pending-amount"><?php echo formatCurrency($pendente['total_pendente']); ?></span>
                            <small class="pending-count"><?php echo $pendente['qtd_transacoes']; ?> transações</small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Lista de Saldos por Loja -->
        <div class="stores-section">
            <div class="section-header">
                <h2>Saldos por Loja</h2>
                <div class="view-options">
                    <button class="view-btn active" data-view="grid">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"></rect>
                            <rect x="14" y="3" width="7" height="7"></rect>
                            <rect x="14" y="14" width="7" height="7"></rect>
                            <rect x="3" y="14" width="7" height="7"></rect>
                        </svg>
                    </button>
                    <button class="view-btn" data-view="list">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="8" y1="6" x2="21" y2="6"></line>
                            <line x1="8" y1="12" x2="21" y2="12"></line>
                            <line x1="8" y1="18" x2="21" y2="18"></line>
                            <line x1="3" y1="6" x2="3.01" y2="6"></line>
                            <line x1="3" y1="12" x2="3.01" y2="12"></line>
                            <line x1="3" y1="18" x2="3.01" y2="18"></line>
                        </svg>
                    </button>
                </div>
            </div>
            
            <?php if (empty($saldosPorLoja)): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                    </div>
                    <h3>Nenhum saldo disponível</h3>
                    <p>Você ainda não possui saldo de cashback em nenhuma loja. Comece a fazer compras em nossas lojas parceiras para acumular cashback!</p>
                    <a href="<?php echo CLIENT_STORES_URL; ?>" class="btn btn-primary">Ver Lojas Parceiras</a>
                </div>
            <?php else: ?>
                <div class="stores-grid" id="storesContainer">
                    <?php foreach ($saldosPorLoja as $loja): ?>
                    <div class="store-balance-card">
                        <div class="store-header">
                            <div class="store-logo">
                                <div class="store-initial">
                                    <?php echo strtoupper(substr($loja['nome_fantasia'], 0, 1)); ?>
                                </div>
                            </div>
                            <div class="store-info">
                                <h3 class="store-name"><?php echo htmlspecialchars($loja['nome_fantasia']); ?></h3>
                                <span class="store-category"><?php echo htmlspecialchars($loja['categoria'] ?? 'Geral'); ?></span>
                            </div>
                        </div>
                        
                        <div class="store-balance">
                            <div class="balance-amount">
                                <?php echo formatCurrency($loja['saldo_disponivel']); ?>
                            </div>
                            <div class="balance-label">Saldo disponível</div>
                        </div>
                        
                        <!-- Store balance card - CORRIGIDO -->
                        <div class="store-stats">
                            <div class="store-stat">
                                <span class="stat-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                    </svg>
                                </span>
                                <!-- CORREÇÃO: Mostrar apenas o total creditado para o cliente -->
                                <span class="stat-text"><?php echo formatCurrency($loja['total_creditado']); ?> ganho</span>
                            </div>
                            <div class="store-stat">
                                <span class="stat-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7z"/>
                                    </svg>
                                </span>
                                <span class="stat-text"><?php echo formatCurrency($loja['total_usado']); ?> usado</span>
                            </div>
                            <div class="store-stat">
                                <span class="stat-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                </span>
                                <span class="stat-text">
                                    Última movimentação: <?php echo formatDate($loja['ultima_movimentacao']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- ADICIONADO: Informação sobre a loja -->
                        <div class="store-actions">
                            <button class="btn btn-outline btn-sm" onclick="viewStoreDetails(<?php echo $loja['loja_id']; ?>)">
                                Ver detalhes
                            </button>
                            <span class="cashback-rate"><?php echo number_format($loja['porcentagem_cashback'] / 2, 1); ?>% seu cashback</span>
                        </div>
                        
                        <div class="store-actions">
                            <button class="btn btn-outline btn-sm" onclick="viewStoreDetails(<?php echo $loja['loja_id']; ?>)">
                                Ver detalhes
                            </button>
                            <span class="cashback-rate"><?php echo number_format($loja['porcentagem_cashback'], 1); ?>% cashback</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Seção de Estatísticas e Movimentações -->
        <div class="dashboard-grid">
            <div>
                <!-- Movimentações Recentes -->
                <div class="card">
                    <h3 class="card-title">Movimentações Recentes</h3>
                    <div class="movements-list">
                        <?php if (empty($movimentacoesRecentes)): ?>
                            <p style="text-align: center; padding: 20px;">Nenhuma movimentação encontrada</p>
                        <?php else: ?>
                            <?php foreach ($movimentacoesRecentes as $movimento): ?>
                                <div class="movement-item">
                                    <div class="movement-icon">
                                        <?php 
                                        $iconColor = '';
                                        switch ($movimento['tipo_operacao']) {
                                            case 'credito':
                                                $iconColor = '#4CAF50';
                                                break;
                                            case 'uso':
                                                $iconColor = '#FF9800';
                                                break;
                                            case 'estorno':
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
                    <a href="<?php echo CLIENT_STATEMENT_URL; ?>" class="see-all">Ver histórico completo</a>
                </div>
                
                <!-- Top Lojas Mais Usadas -->
                <?php if (!empty($topLojas)): ?>
                <div class="card" style="margin-top: 20px;">
                    <h3 class="card-title">Top 5 - Lojas Onde Mais Uso Saldo</h3>
                    <div class="top-stores-usage">
                        <?php foreach ($topLojas as $loja): ?>
                            <div class="usage-item">
                                <div class="usage-store"><?php echo htmlspecialchars($loja['nome_fantasia']); ?></div>
                                <div class="usage-stats">
                                    <span class="usage-amount"><?php echo formatCurrency($loja['total_usado']); ?></span>
                                    <span class="usage-count"><?php echo $loja['qtd_usos']; ?> usos</span>
                                    <?php if ($loja['saldo_disponivel'] > 0): ?>
                                        <small class="current-balance">Saldo: <?php echo formatCurrency($loja['saldo_disponivel']); ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div>
                <!-- Estatísticas Gerais -->
                <div class="card">
                    <h3 class="card-title">Estatísticas Gerais</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo formatCurrency($estatisticas['total_creditado']); ?></div>
                            <div class="stat-label">Total Creditado</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo formatCurrency($estatisticas['total_usado']); ?></div>
                            <div class="stat-label">Total Usado</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $estatisticas['total_usos']; ?></div>
                            <div class="stat-label">Usos Realizados</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $estatisticas['total_lojas_utilizadas']; ?></div>
                            <div class="stat-label">Lojas Diferentes</div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráfico de Movimentações -->
                <div class="card" style="margin-top: 20px;">
                    <h3 class="card-title">Movimentações dos Últimos 6 Meses</h3>
                    <div class="chart-container">
                        <canvas id="balanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
    
    <!-- Modal de Detalhes da Loja -->
    <div id="storeDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalStoreTitle">Detalhes da Loja</h3>
                <button class="modal-close" onclick="closeStoreModal()">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            <div class="modal-body" id="modalStoreContent">
                <!-- Conteúdo será carregado via JavaScript -->
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Alternar visualização entre grid e lista
            const viewButtons = document.querySelectorAll('.view-btn');
            const storesContainer = document.getElementById('storesContainer');
            
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remover classe active de todos os botões
                    viewButtons.forEach(btn => btn.classList.remove('active'));
                    
                    // Adicionar classe active ao botão clicado
                    this.classList.add('active');
                    
                    // Alterar classe do container
                    const view = this.getAttribute('data-view');
                    storesContainer.className = view === 'list' ? 'stores-list' : 'stores-grid';
                });
            });
            
            // Configurar gráfico de movimentações
            const ctx = document.getElementById('balanceChart');
            
            <?php if (!empty($dadosMensais)): ?>
            // Dados do gráfico
            const labels = <?php echo json_encode(array_map('formatMonth', array_column($dadosMensais, 'mes'))); ?>;
            const creditos = <?php echo json_encode(array_column($dadosMensais, 'creditos')); ?>;
            const usos = <?php echo json_encode(array_column($dadosMensais, 'usos')); ?>;
            const estornos = <?php echo json_encode(array_column($dadosMensais, 'estornos')); ?>;
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Créditos',
                        data: creditos,
                        borderColor: '#4CAF50',
                        backgroundColor: 'rgba(76, 175, 80, 0.1)',
                        tension: 0.3,
                        fill: false
                    }, {
                        label: 'Usos',
                        data: usos,
                        borderColor: '#FF9800',
                        backgroundColor: 'rgba(255, 152, 0, 0.1)',
                        tension: 0.3,
                        fill: false
                    }, {
                        label: 'Estornos',
                        data: estornos,
                        borderColor: '#2196F3',
                        backgroundColor: 'rgba(33, 150, 243, 0.1)',
                        tension: 0.3,
                        fill: false
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
                            position: 'bottom'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'R$ ' + value.toLocaleString('pt-BR', {
                                        minimumFractionDigits: 0,
                                        maximumFractionDigits: 0
                                    });
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
        });
        
        // Função para visualizar detalhes da loja
        function viewStoreDetails(storeId) {
            // Implementação básica do modal
            document.getElementById('storeDetailsModal').style.display = 'block';
            document.getElementById('modalStoreContent').innerHTML = '<p>Carregando detalhes da loja...</p>';
            
            // Aqui você pode fazer uma requisição AJAX para buscar detalhes específicos da loja
            setTimeout(() => {
                document.getElementById('modalStoreContent').innerHTML = `
                    <div class="store-detail-content">
                        <p><strong>ID da Loja:</strong> ${storeId}</p>
                        <p>Detalhes completos da loja serão implementados aqui, incluindo:</p>
                        <ul>
                            <li>Histórico completo de transações</li>
                            <li>Gráfico de movimentações específicas</li>
                            <li>Informações detalhadas sobre cashback</li>
                        </ul>
                    </div>
                `;
            }, 500);
        }
        
        // Função para fechar o modal
        function closeStoreModal() {
            document.getElementById('storeDetailsModal').style.display = 'none';
        }
        
        // Fechar modal ao clicar fora dele
        window.onclick = function(event) {
            const modal = document.getElementById('storeDetailsModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>
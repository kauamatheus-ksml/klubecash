<?php
// views/client/dashboard.php
session_start();

// Verificar autenticação
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'cliente') {
    header("Location: /login?error=acesso_restrito");
    exit;
}

// Incluir dependências
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/ClientController.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Cliente';

// Buscar dados para o dashboard
try {
    // Obter saldo detalhado do cliente
    $balanceResult = ClientController::getClientBalanceDetails($userId);
    $balanceData = $balanceResult['status'] ? $balanceResult['data'] : [
        'saldo_total' => 0,
        'saldos_por_loja' => [],
        'total_lojas' => 0
    ];

    // Obter movimentações recentes
    $db = Database::getConnection();
    
    // Buscar últimas movimentações de todas as lojas
    $recentMovementsStmt = $db->prepare("
        SELECT 
            cm.*,
            l.nome_fantasia as loja_nome,
            l.logo as loja_logo,
            l.categoria as loja_categoria
        FROM cashback_movimentacoes cm
        JOIN lojas l ON cm.loja_id = l.id
        WHERE cm.usuario_id = ?
        ORDER BY cm.data_operacao DESC
        LIMIT 5
    ");
    $recentMovementsStmt->execute([$userId]);
    $recentMovements = $recentMovementsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar estatísticas do mês atual
    $currentMonth = date('Y-m');
    $monthStatsStmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT cm.id) as total_movimentacoes,
            COALESCE(SUM(CASE WHEN cm.tipo_operacao = 'credito' THEN cm.valor ELSE 0 END), 0) as cashback_recebido,
            COALESCE(SUM(CASE WHEN cm.tipo_operacao = 'uso' THEN cm.valor ELSE 0 END), 0) as saldo_usado,
            COUNT(DISTINCT cm.loja_id) as lojas_ativas
        FROM cashback_movimentacoes cm
        WHERE cm.usuario_id = ? 
        AND DATE_FORMAT(cm.data_operacao, '%Y-%m') = ?
    ");
    $monthStatsStmt->execute([$userId, $currentMonth]);
    $monthStats = $monthStatsStmt->fetch(PDO::FETCH_ASSOC);

    // Buscar notificações não lidas
    $notificationsStmt = $db->prepare("
        SELECT * FROM notificacoes 
        WHERE usuario_id = ? AND lida = 0 
        ORDER BY data_criacao DESC 
        LIMIT 3
    ");
    $notificationsStmt->execute([$userId]);
    $notifications = $notificationsStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log('Erro no dashboard: ' . $e->getMessage());
    $balanceData = ['saldo_total' => 0, 'saldos_por_loja' => [], 'total_lojas' => 0];
    $recentMovements = [];
    $monthStats = ['total_movimentacoes' => 0, 'cashback_recebido' => 0, 'saldo_usado' => 0, 'lojas_ativas' => 0];
    $notifications = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <title>Meu Cashback - Klube Cash</title>
    <link rel="stylesheet" href="../../assets/css/views/client/dashboard.css">
    <!-- Ícones modernos -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Cabeçalho Mobile/Desktop -->
    <header class="main-header">
        <div class="header-content">
            <div class="header-left">
                <img src="../../assets/images/logo.png" alt="Klube Cash" class="logo" onerror="this.style.display='none'">
                <h1 class="brand-name">Klube Cash</h1>
            </div>
            <div class="header-right">
                <span class="welcome-text">Olá, <?php echo htmlspecialchars($userName); ?>!</span>
                <div class="user-menu">
                    <button class="user-button" onclick="toggleUserMenu()">
                        <i class="fas fa-user-circle"></i>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="/cliente/perfil"><i class="fas fa-user"></i> Meu Perfil</a>
                        <a href="/cliente/extrato"><i class="fas fa-list"></i> Extrato Completo</a>
                        <a href="/logout"><i class="fas fa-sign-out-alt"></i> Sair</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Container Principal -->
    <div class="dashboard-container">
        
        <!-- Notificações Importantes (se houver) -->
        <?php if (!empty($notifications)): ?>
        <div class="notifications-bar">
            <div class="notifications-content">
                <i class="fas fa-bell notification-icon"></i>
                <div class="notifications-text">
                    <strong>Você tem <?php echo count($notifications); ?> notificação(ões)</strong>
                    <span><?php echo htmlspecialchars($notifications[0]['titulo']); ?></span>
                </div>
                <button class="notifications-close" onclick="this.parentElement.parentElement.style.display='none'">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Seção Principal: Meu Saldo -->
        <section class="balance-hero">
            <div class="balance-card">
                <div class="balance-header">
                    <h2><i class="fas fa-wallet"></i> Meu Saldo Total</h2>
                    <p class="balance-subtitle">Dinheiro que você pode usar nas suas lojas</p>
                </div>
                <div class="balance-amount">
                    <span class="currency">R$</span>
                    <span class="amount" id="totalBalance"><?php echo number_format($balanceData['saldo_total'], 2, ',', '.'); ?></span>
                </div>
                <div class="balance-actions">
                    <button class="action-btn primary" onclick="window.location.href='/cliente/lojas-parceiras'">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Usar Saldo</span>
                    </button>
                    <button class="action-btn secondary" onclick="window.location.href='/cliente/extrato'">
                        <i class="fas fa-history"></i>
                        <span>Ver Histórico</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Estatísticas Rápidas do Mês -->
        <section class="quick-stats">
            <h3><i class="fas fa-chart-line"></i> Este Mês</h3>
            <div class="stats-grid">
                <div class="stat-card green">
                    <div class="stat-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value">R$ <?php echo number_format($monthStats['cashback_recebido'], 2, ',', '.'); ?></span>
                        <span class="stat-label">Cashback Ganho</span>
                    </div>
                </div>
                
                <div class="stat-card blue">
                    <div class="stat-icon">
                        <i class="fas fa-minus-circle"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value">R$ <?php echo number_format($monthStats['saldo_usado'], 2, ',', '.'); ?></span>
                        <span class="stat-label">Saldo Usado</span>
                    </div>
                </div>
                
                <div class="stat-card orange">
                    <div class="stat-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-value"><?php echo $monthStats['lojas_ativas']; ?></span>
                        <span class="stat-label">Lojas Visitadas</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Meus Saldos por Loja -->
        <section class="stores-balance">
            <div class="section-header">
                <h3><i class="fas fa-store-alt"></i> Meus Saldos por Loja</h3>
                <p class="section-subtitle">Cada loja tem seu próprio saldo para você usar</p>
            </div>
            
            <?php if (!empty($balanceData['saldos_por_loja'])): ?>
                <div class="stores-grid">
                    <?php foreach ($balanceData['saldos_por_loja'] as $loja): ?>
                    <div class="store-card" onclick="openStoreDetails(<?php echo $loja['loja_id']; ?>)">
                        <div class="store-header">
                            <div class="store-logo">
                                <?php if (!empty($loja['logo'])): ?>
                                    <img src="<?php echo htmlspecialchars($loja['logo']); ?>" alt="<?php echo htmlspecialchars($loja['nome_fantasia']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-store"></i>
                                <?php endif; ?>
                            </div>
                            <div class="store-info">
                                <h4><?php echo htmlspecialchars($loja['nome_fantasia']); ?></h4>
                                <span class="store-category"><?php echo htmlspecialchars($loja['categoria']); ?></span>
                            </div>
                        </div>
                        <div class="store-balance">
                            <span class="balance-label">Saldo disponível:</span>
                            <span class="balance-value">R$ <?php echo number_format($loja['saldo_disponivel'], 2, ',', '.'); ?></span>
                        </div>
                        <div class="store-stats">
                            <small><i class="fas fa-chart-bar"></i> <?php echo $loja['porcentagem_cashback']; ?>% de cashback</small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h4>Ainda não há saldo acumulado</h4>
                    <p>Comece a fazer compras nas nossas lojas parceiras para ganhar cashback!</p>
                    <button class="action-btn primary" onclick="window.location.href='/cliente/lojas-parceiras'">
                        <i class="fas fa-shopping-bag"></i>
                        Ver Lojas Parceiras
                    </button>
                </div>
            <?php endif; ?>
        </section>

        <!-- Últimas Movimentações -->
        <section class="recent-activity">
            <div class="section-header">
                <h3><i class="fas fa-clock"></i> Atividade Recente</h3>
                <button class="link-button" onclick="window.location.href='/cliente/extrato'">
                    Ver tudo <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            
            <?php if (!empty($recentMovements)): ?>
                <div class="activity-list">
                    <?php foreach ($recentMovements as $movement): ?>
                    <div class="activity-item <?php echo $movement['tipo_operacao']; ?>">
                        <div class="activity-icon">
                            <?php if ($movement['tipo_operacao'] === 'credito'): ?>
                                <i class="fas fa-plus-circle"></i>
                            <?php elseif ($movement['tipo_operacao'] === 'uso'): ?>
                                <i class="fas fa-minus-circle"></i>
                            <?php else: ?>
                                <i class="fas fa-undo"></i>
                            <?php endif; ?>
                        </div>
                        <div class="activity-content">
                            <div class="activity-main">
                                <span class="activity-description">
                                    <?php 
                                    switch($movement['tipo_operacao']) {
                                        case 'credito':
                                            echo 'Cashback recebido';
                                            break;
                                        case 'uso':
                                            echo 'Saldo usado na compra';
                                            break;
                                        case 'estorno':
                                            echo 'Estorno de saldo';
                                            break;
                                    }
                                    ?>
                                </span>
                                <span class="activity-store"><?php echo htmlspecialchars($movement['loja_nome']); ?></span>
                            </div>
                            <div class="activity-details">
                                <span class="activity-date"><?php echo date('d/m/Y H:i', strtotime($movement['data_operacao'])); ?></span>
                            </div>
                        </div>
                        <div class="activity-amount <?php echo $movement['tipo_operacao'] === 'credito' ? 'positive' : 'negative'; ?>">
                            <?php echo $movement['tipo_operacao'] === 'credito' ? '+' : '-'; ?>R$ <?php echo number_format($movement['valor'], 2, ',', '.'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-activity">
                    <i class="fas fa-history"></i>
                    <p>Nenhuma atividade recente</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Ações Rápidas -->
        <section class="quick-actions">
            <h3><i class="fas fa-zap"></i> Ações Rápidas</h3>
            <div class="actions-grid">
                <button class="quick-action-btn" onclick="window.location.href='/cliente/lojas-parceiras'">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Encontrar Lojas</span>
                </button>
                <button class="quick-action-btn" onclick="window.location.href='/cliente/extrato'">
                    <i class="fas fa-file-alt"></i>
                    <span>Meu Extrato</span>
                </button>
                <button class="quick-action-btn" onclick="window.location.href='/cliente/perfil'">
                    <i class="fas fa-user-cog"></i>
                    <span>Meu Perfil</span>
                </button>
                <button class="quick-action-btn" onclick="shareApp()">
                    <i class="fas fa-share-alt"></i>
                    <span>Indicar Amigo</span>
                </button>
            </div>
        </section>
    </div>

    <!-- Modal para Detalhes da Loja -->
    <div class="modal-overlay" id="storeModal" onclick="closeStoreModal()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3>Detalhes do Saldo</h3>
                <button class="modal-close" onclick="closeStoreModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="storeModalContent">
                <!-- Conteúdo carregado via JavaScript -->
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Alternar menu do usuário
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }

        // Fechar menu ao clicar fora
        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            const dropdown = document.getElementById('userDropdown');
            
            if (!userMenu.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Abrir detalhes da loja
        function openStoreDetails(lojaId) {
            const modal = document.getElementById('storeModal');
            const content = document.getElementById('storeModalContent');
            
            // Mostrar loading
            content.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Carregando...</p>
                </div>
            `;
            modal.style.display = 'flex';
            
            // Buscar dados da loja
            fetch('/cliente/actions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=store_balance_details&loja_id=${lojaId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    showStoreDetails(data.data);
                } else {
                    content.innerHTML = `
                        <div class="error">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>Erro ao carregar dados da loja</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                content.innerHTML = `
                    <div class="error">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Erro de conexão</p>
                    </div>
                `;
            });
        }

        // Mostrar detalhes da loja no modal
        function showStoreDetails(data) {
            const content = document.getElementById('storeModalContent');
            const loja = data.loja;
            const saldo = data.saldo;
            const movimentacoes = data.movimentacoes || [];
            
            content.innerHTML = `
                <div class="store-details">
                    <div class="store-info-detailed">
                        <h4>${loja.nome_fantasia}</h4>
                        <p class="store-category">${loja.categoria}</p>
                        <p class="store-cashback">${loja.porcentagem_cashback}% de cashback</p>
                    </div>
                    
                    <div class="balance-info">
                        <div class="balance-item">
                            <span class="label">Saldo Disponível:</span>
                            <span class="value">R$ ${parseFloat(saldo.saldo_disponivel || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                        </div>
                        <div class="balance-item">
                            <span class="label">Total Creditado:</span>
                            <span class="value">R$ ${parseFloat(saldo.total_creditado || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                        </div>
                        <div class="balance-item">
                            <span class="label">Total Usado:</span>
                            <span class="value">R$ ${parseFloat(saldo.total_usado || 0).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                        </div>
                    </div>
                    
                    ${movimentacoes.length > 0 ? `
                        <div class="recent-movements">
                            <h5>Últimas Movimentações</h5>
                            <div class="movements-list">
                                ${movimentacoes.map(mov => `
                                    <div class="movement-item">
                                        <span class="movement-type ${mov.tipo_operacao}">
                                            ${mov.tipo_operacao === 'credito' ? 'Ganhou' : mov.tipo_operacao === 'uso' ? 'Usou' : 'Estorno'}
                                        </span>
                                        <span class="movement-amount">R$ ${parseFloat(mov.valor).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</span>
                                        <span class="movement-date">${new Date(mov.data_operacao).toLocaleDateString('pt-BR')}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="store-actions">
                        <button class="action-btn primary" onclick="window.location.href='/cliente/lojas-parceiras'">
                            <i class="fas fa-shopping-bag"></i>
                            Visitar Loja
                        </button>
                    </div>
                </div>
            `;
        }

        // Fechar modal
        function closeStoreModal() {
            document.getElementById('storeModal').style.display = 'none';
        }

        // Compartilhar app
        function shareApp() {
            if (navigator.share) {
                navigator.share({
                    title: 'Klube Cash',
                    text: 'Conheça o Klube Cash e ganhe cashback nas suas compras!',
                    url: window.location.origin
                });
            } else {
                // Fallback para browsers que não suportam Web Share API
                const text = `Conheça o Klube Cash e ganhe cashback nas suas compras! ${window.location.origin}`;
                navigator.clipboard.writeText(text).then(() => {
                    alert('Link copiado para a área de transferência!');
                });
            }
        }

        // Animação do valor do saldo
        document.addEventListener('DOMContentLoaded', function() {
            const balanceElement = document.getElementById('totalBalance');
            const finalValue = parseFloat(balanceElement.textContent.replace('.', '').replace(',', '.'));
            
            if (finalValue > 0) {
                let currentValue = 0;
                const increment = finalValue / 30;
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(timer);
                    }
                    balanceElement.textContent = currentValue.toLocaleString('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }, 50);
            }
        });
    </script>
</body>
</html>
<?php
// views/client/dashboard.php
// Dashboard do Cliente - Versão Completamente Reformulada
// Focado na experiência intuitiva e educativa

// Verificar autenticação
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'cliente') {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Incluir dependências
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/ClientController.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Cliente';

// Obter dados do dashboard usando o controller existente
$dashboardData = ClientController::getDashboardData($userId);
$balanceData = ClientController::getClientBalance($userId);

// Se houve erro ao carregar dados, usar valores padrão
if (!$dashboardData['status']) {
    $dashboardData['data'] = [
        'saldo_total' => 0,
        'transacoes_recentes' => [],
        'estatisticas' => ['total_transacoes' => 0, 'total_compras' => 0, 'total_cashback' => 0],
        'lojas_favoritas' => [],
        'notificacoes' => []
    ];
}

if (!$balanceData['status']) {
    $balanceData['data'] = [
        'saldo_total' => 0,
        'saldos_por_loja' => [],
        'saldos_pendentes' => [],
        'estatisticas' => []
    ];
}

$saldoTotal = $balanceData['data']['saldo_total'] ?? 0;
$saldosPorLoja = $balanceData['data']['saldos_por_loja'] ?? [];
$estatisticas = $dashboardData['data']['estatisticas'] ?? [];
$transacoesRecentes = array_slice($dashboardData['data']['transacoes_recentes'] ?? [], 0, 3);
$lojasFavoritas = array_slice($dashboardData['data']['lojas_favoritas'] ?? [], 0, 3);
$notificacoes = array_slice($dashboardData['data']['notificacoes'] ?? [], 0, 3);

// Determinar o primeiro nome para saudação mais pessoal
$primeiroNome = explode(' ', $userName)[0];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <title>Meu Cashback - Klube Cash</title>
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="../../assets/css/views/client/dashboard-new.css">
    
    <!-- Fontes Google -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Ícones Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Container Principal -->
    <div class="dashboard-container">
        
        <!-- Header com Saudação Personalizada -->
        <header class="welcome-header">
            <div class="welcome-content">
                <div class="greeting-section">
                    <h1 class="greeting-title">
                        Olá, <?php echo htmlspecialchars($primeiroNome); ?>! 👋
                    </h1>
                    <p class="greeting-subtitle">
                        Vamos ver como está seu cashback hoje
                    </p>
                </div>
                
                <div class="quick-actions">
                    <button class="action-btn primary" onclick="openBalanceModal()">
                        <i class="fas fa-wallet"></i>
                        <span>Ver Saldo</span>
                    </button>
                    <button class="action-btn secondary" onclick="location.href='lojas-parceiras.php'">
                        <i class="fas fa-store"></i>
                        <span>Encontrar Lojas</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Explicação do que é Cashback (para usuários leigos) -->
        <?php if ($estatisticas['total_transacoes'] == 0): ?>
        <section class="cashback-intro">
            <div class="intro-card">
                <div class="intro-icon">
                    <i class="fas fa-gift"></i>
                </div>
                <div class="intro-content">
                    <h3>O que é Cashback?</h3>
                    <p>
                        É simples: quando você compra em uma loja parceira, 
                        <strong>você ganha dinheiro de volta!</strong> 
                        Esse dinheiro pode ser usado em suas próximas compras na mesma loja.
                    </p>
                    <div class="intro-steps">
                        <div class="step">
                            <span class="step-number">1</span>
                            <span class="step-text">Compre em lojas parceiras</span>
                        </div>
                        <div class="step">
                            <span class="step-number">2</span>
                            <span class="step-text">Ganhe dinheiro de volta</span>
                        </div>
                        <div class="step">
                            <span class="step-number">3</span>
                            <span class="step-text">Use nas próximas compras</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Card Principal do Saldo -->
        <section class="main-balance-section">
            <div class="balance-hero-card">
                <div class="balance-header">
                    <div class="balance-info">
                        <h2 class="balance-label">Seu Saldo de Cashback</h2>
                        <div class="balance-amount">
                            R$ <span class="amount-value"><?php echo number_format($saldoTotal, 2, ',', '.'); ?></span>
                        </div>
                        <p class="balance-description">
                            <?php if ($saldoTotal > 0): ?>
                                💰 Você tem dinheiro para usar em suas próximas compras!
                            <?php else: ?>
                                🎯 Comece a comprar e ganhe cashback instantaneamente!
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="balance-visual">
                        <div class="piggy-bank-icon">
                            <i class="fas fa-piggy-bank"></i>
                        </div>
                        <?php if ($saldoTotal > 0): ?>
                        <div class="balance-sparkles">
                            <i class="fas fa-sparkles"></i>
                            <i class="fas fa-sparkles"></i>
                            <i class="fas fa-sparkles"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Estatísticas Rápidas -->
                <div class="quick-stats">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo $estatisticas['total_transacoes'] ?? 0; ?></span>
                            <span class="stat-label">Compras</span>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number">R$ <?php echo number_format($estatisticas['total_cashback'] ?? 0, 0, ',', '.'); ?></span>
                            <span class="stat-label">Cashback Total</span>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <div class="stat-info">
                            <span class="stat-number"><?php echo count($saldosPorLoja); ?></span>
                            <span class="stat-label">Lojas</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Saldos por Loja -->
        <?php if (!empty($saldosPorLoja)): ?>
        <section class="stores-balance-section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-store"></i>
                    Seus Saldos por Loja
                </h3>
                <p class="section-subtitle">Você pode usar esse dinheiro apenas na respectiva loja</p>
            </div>
            
            <div class="stores-grid">
                <?php foreach ($saldosPorLoja as $loja): ?>
                <div class="store-balance-card" onclick="openStoreDetails(<?php echo $loja['loja_id']; ?>)">
                    <div class="store-logo">
                        <?php if (!empty($loja['logo'])): ?>
                            <img src="../../uploads/store_logos/<?php echo htmlspecialchars($loja['logo']); ?>" alt="<?php echo htmlspecialchars($loja['nome_fantasia']); ?>">
                        <?php else: ?>
                            <div class="store-initial">
                                <?php echo strtoupper(substr($loja['nome_fantasia'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="store-info">
                        <h4 class="store-name"><?php echo htmlspecialchars($loja['nome_fantasia']); ?></h4>
                        <div class="store-balance-amount">
                            R$ <?php echo number_format($loja['saldo_disponivel'], 2, ',', '.'); ?>
                        </div>
                        <p class="store-stats">
                            <?php echo $loja['total_transacoes']; ?> compra<?php echo $loja['total_transacoes'] != 1 ? 's' : ''; ?>
                        </p>
                    </div>
                    
                    <div class="store-action">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Grid de Cartões Informativos -->
        <section class="info-cards-section">
            
            <!-- Últimas Compras -->
            <div class="info-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-clock"></i>
                        Últimas Compras
                    </h3>
                </div>
                
                <div class="card-content">
                    <?php if (!empty($transacoesRecentes)): ?>
                        <div class="transactions-list">
                            <?php foreach ($transacoesRecentes as $transacao): ?>
                            <div class="transaction-item">
                                <div class="transaction-info">
                                    <div class="transaction-store"><?php echo htmlspecialchars($transacao['loja_nome']); ?></div>
                                    <div class="transaction-date"><?php echo date('d/m/Y', strtotime($transacao['data_transacao'])); ?></div>
                                </div>
                                <div class="transaction-values">
                                    <div class="transaction-total">R$ <?php echo number_format($transacao['valor_total'], 2, ',', '.'); ?></div>
                                    <div class="transaction-cashback">+R$ <?php echo number_format($transacao['valor_cliente'], 2, ',', '.'); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="card-action-btn" onclick="location.href='extrato.php'">
                            Ver todas as compras
                        </button>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <p>Nenhuma compra ainda</p>
                            <button class="card-action-btn" onclick="location.href='lojas-parceiras.php'">
                                Encontrar lojas parceiras
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lojas Favoritas -->
            <div class="info-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-heart"></i>
                        Suas Lojas Favoritas
                    </h3>
                </div>
                
                <div class="card-content">
                    <?php if (!empty($lojasFavoritas)): ?>
                        <div class="favorites-list">
                            <?php foreach ($lojasFavoritas as $loja): ?>
                            <div class="favorite-item">
                                <div class="favorite-logo">
                                    <?php if (!empty($loja['logo'])): ?>
                                        <img src="../../uploads/store_logos/<?php echo htmlspecialchars($loja['logo']); ?>" alt="<?php echo htmlspecialchars($loja['nome_fantasia']); ?>">
                                    <?php else: ?>
                                        <div class="favorite-initial">
                                            <?php echo strtoupper(substr($loja['nome_fantasia'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="favorite-info">
                                    <div class="favorite-name"><?php echo htmlspecialchars($loja['nome_fantasia']); ?></div>
                                    <div class="favorite-cashback"><?php echo number_format($loja['porcentagem_cashback'], 1); ?>% cashback</div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="card-action-btn" onclick="location.href='lojas-parceiras.php'">
                            Ver todas as lojas
                        </button>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-store"></i>
                            <p>Descubra lojas incríveis</p>
                            <button class="card-action-btn" onclick="location.href='lojas-parceiras.php'">
                                Explorar lojas parceiras
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Notificações -->
            <div class="info-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bell"></i>
                        Novidades
                    </h3>
                </div>
                
                <div class="card-content">
                    <?php if (!empty($notificacoes)): ?>
                        <div class="notifications-list">
                            <?php foreach ($notificacoes as $notificacao): ?>
                            <div class="notification-item">
                                <div class="notification-icon notification-<?php echo $notificacao['tipo']; ?>">
                                    <i class="fas fa-<?php echo $notificacao['tipo'] == 'success' ? 'check-circle' : ($notificacao['tipo'] == 'warning' ? 'exclamation-triangle' : 'info-circle'); ?>"></i>
                                </div>
                                <div class="notification-content">
                                    <div class="notification-title"><?php echo htmlspecialchars($notificacao['titulo']); ?></div>
                                    <div class="notification-message"><?php echo htmlspecialchars($notificacao['mensagem']); ?></div>
                                    <div class="notification-date"><?php echo date('d/m/Y H:i', strtotime($notificacao['data_criacao'])); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <p>Tudo em dia!</p>
                            <small>Você será notificado sobre novos cashbacks</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Como Ganhar Mais Cashback -->
            <div class="info-card tips-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-lightbulb"></i>
                        Dicas para Ganhar Mais
                    </h3>
                </div>
                
                <div class="card-content">
                    <div class="tips-list">
                        <div class="tip-item">
                            <div class="tip-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="tip-text">
                                <strong>Explore nossas lojas parceiras</strong><br>
                                Cada loja oferece uma porcentagem diferente de cashback
                            </div>
                        </div>
                        
                        <div class="tip-item">
                            <div class="tip-icon">
                                <i class="fas fa-wallet"></i>
                            </div>
                            <div class="tip-text">
                                <strong>Use seu saldo acumulado</strong><br>
                                Seu cashback pode ser usado como desconto nas próximas compras
                            </div>
                        </div>
                        
                        <div class="tip-item">
                            <div class="tip-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="tip-text">
                                <strong>Compre regularmente</strong><br>
                                Quanto mais você compra, mais cashback acumula
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </section>

        <!-- Navegação Rápida -->
        <section class="quick-navigation">
            <h3 class="section-title">Acesso Rápido</h3>
            <div class="nav-grid">
                <a href="extrato.php" class="nav-item">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span>Meu Extrato</span>
                </a>
                <a href="lojas-parceiras.php" class="nav-item">
                    <i class="fas fa-store"></i>
                    <span>Lojas Parceiras</span>
                </a>
                <a href="perfil.php" class="nav-item">
                    <i class="fas fa-user-cog"></i>
                    <span>Meu Perfil</span>
                </a>
                <a href="suporte.php" class="nav-item">
                    <i class="fas fa-headset"></i>
                    <span>Suporte</span>
                </a>
            </div>
        </section>

    </div>

    <!-- Modal de Detalhes do Saldo -->
    <div id="balanceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalhes do Seu Saldo</h3>
                <button class="modal-close" onclick="closeBalanceModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div class="balance-breakdown">
                    <div class="breakdown-item">
                        <span class="breakdown-label">Saldo Total Disponível:</span>
                        <span class="breakdown-value">R$ <?php echo number_format($saldoTotal, 2, ',', '.'); ?></span>
                    </div>
                    <div class="breakdown-divider"></div>
                    <div class="breakdown-explanation">
                        <h4>Como usar seu saldo:</h4>
                        <ul>
                            <li>Seu saldo pode ser usado como desconto nas próximas compras</li>
                            <li>O saldo de cada loja só pode ser usado na própria loja</li>
                            <li>Você pode usar quanto quiser do seu saldo disponível</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="location.href='lojas-parceiras.php'">
                    Ir às Compras
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="../../assets/js/client/dashboard-new.js"></script>
    
    <script>
        // Animação dos números ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            animateNumbers();
            
            // Se for primeira visita, mostrar tour
            if (<?php echo $estatisticas['total_transacoes'] == 0 ? 'true' : 'false'; ?>) {
                setTimeout(showWelcomeTour, 2000);
            }
        });

        // Função para animar números
        function animateNumbers() {
            const amountElement = document.querySelector('.amount-value');
            if (amountElement) {
                const finalValue = parseFloat(amountElement.textContent.replace(',', '.'));
                animateCounter(amountElement, 0, finalValue, 1000);
            }

            // Animar estatísticas
            document.querySelectorAll('.stat-number').forEach(element => {
                const text = element.textContent;
                const num = parseFloat(text.replace(/[^\d,]/g, '').replace(',', '.'));
                if (!isNaN(num)) {
                    animateCounter(element, 0, num, 800, text.includes('R$'));
                }
            });
        }

        // Função de animação de contador
        function animateCounter(element, start, end, duration, isCurrency = false) {
            const startTime = performance.now();
            
            function updateCounter(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                const current = start + (end - start) * easeOutQuart(progress);
                
                if (isCurrency) {
                    element.textContent = current.toLocaleString('pt-BR', {
                        minimumFractionDigits: 0,
                        maximumFractionDigits: 0
                    });
                } else {
                    element.textContent = Math.floor(current).toString();
                }
                
                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                }
            }
            
            requestAnimationFrame(updateCounter);
        }

        // Função de easing
        function easeOutQuart(t) {
            return 1 - Math.pow(1 - t, 4);
        }

        // Modal do saldo
        function openBalanceModal() {
            document.getElementById('balanceModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeBalanceModal() {
            document.getElementById('balanceModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Detalhes da loja
        function openStoreDetails(storeId) {
            // Implementar abertura de modal ou redirecionamento
            console.log('Abrindo detalhes da loja:', storeId);
            // window.location.href = `loja-detalhes.php?id=${storeId}`;
        }

        // Tour de boas-vindas
        function showWelcomeTour() {
            // Implementar tour interativo para novos usuários
            console.log('Iniciando tour de boas-vindas');
        }

        // Fechar modal clicando fora
        window.onclick = function(event) {
            const modal = document.getElementById('balanceModal');
            if (event.target === modal) {
                closeBalanceModal();
            }
        }
    </script>
</body>
</html>
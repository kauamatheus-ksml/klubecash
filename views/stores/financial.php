<?php
// views/stores/financial.php
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

session_start();

// Verificações de autenticação
if (!AuthController::isAuthenticated()) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Você precisa fazer login para acessar esta página.'));
    exit;
}

if (!AuthController::isStore()) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Acesso restrito a lojas parceiras.'));
    exit;
}

$userId = AuthController::getCurrentUserId();
$db = Database::getConnection();
$storeQuery = $db->prepare("SELECT * FROM lojas WHERE usuario_id = :usuario_id");
$storeQuery->bindParam(':usuario_id', $userId);
$storeQuery->execute();

if ($storeQuery->rowCount() == 0) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Sua conta não está associada a nenhuma loja.'));
    exit;
}

$store = $storeQuery->fetch(PDO::FETCH_ASSOC);
$storeId = $store['id'];
$storeName = $store['nome_fantasia'];

// Definir menu ativo
$activeMenu = 'financial';

// Obter aba ativa (padrão: resumo)
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'resumo';
$validTabs = ['resumo', 'transacoes', 'pendentes', 'historico'];
if (!in_array($activeTab, $validTabs)) {
    $activeTab = 'resumo';
}

// Obter estatísticas básicas
try {
    // Total de vendas
    $salesQuery = $db->prepare("
        SELECT 
            COUNT(*) as total_vendas, 
            SUM(valor_total) as valor_total_vendas,
            SUM(COALESCE(saldo_usado, 0)) as total_saldo_usado
        FROM transacoes_cashback 
        WHERE loja_id = :loja_id
    ");
    $salesQuery->bindParam(':loja_id', $storeId);
    $salesQuery->execute();
    $salesStats = $salesQuery->fetch(PDO::FETCH_ASSOC);
    
    // Comissões pendentes
    $pendingQuery = $db->prepare("
        SELECT 
            COUNT(*) as total_pendentes, 
            SUM(valor_cashback) as valor_pendente,
            SUM(COALESCE(saldo_usado, 0)) as saldo_usado_pendente
        FROM transacoes_cashback 
        WHERE loja_id = :loja_id AND status = 'pendente'
    ");
    $pendingQuery->bindParam(':loja_id', $storeId);
    $pendingQuery->execute();
    $pendingStats = $pendingQuery->fetch(PDO::FETCH_ASSOC);
    
    // Comissões pagas
    $paidQuery = $db->prepare("
        SELECT 
            COUNT(*) as total_pagas, 
            SUM(valor_cashback) as valor_pago
        FROM transacoes_cashback 
        WHERE loja_id = :loja_id AND status = 'aprovado'
    ");
    $paidQuery->bindParam(':loja_id', $storeId);
    $paidQuery->execute();
    $paidStats = $paidQuery->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    // Valores padrão em caso de erro
    $salesStats = ['total_vendas' => 0, 'valor_total_vendas' => 0, 'total_saldo_usado' => 0];
    $pendingStats = ['total_pendentes' => 0, 'valor_pendente' => 0, 'saldo_usado_pendente' => 0];
    $paidStats = ['total_pagas' => 0, 'valor_pago' => 0];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <title>Financeiro - Klube Cash</title>
    
    <link rel="stylesheet" href="../../assets/css/views/stores/financial.css">
</head>
<body>
    <?php include_once '../components/sidebar-store.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="dashboard-wrapper">
            <!-- Header da página seguindo padrão do sistema -->
            <div class="dashboard-header">
                <h1>Financeiro</h1>
                <p class="subtitle">Gestão financeira completa para <?php echo htmlspecialchars($storeName); ?></p>
            </div>

            <!-- Cards de estatísticas seguindo padrão existente -->
            <div class="stats-container">
                <div class="stat-card sales">
                    <div class="stat-card-title">Total de Vendas</div>
                    <div class="stat-card-value">R$ <?php echo number_format($salesStats['valor_total_vendas'] ?? 0, 2, ',', '.'); ?></div>
                    <p class="stat-card-period"><?php echo number_format($salesStats['total_vendas'] ?? 0); ?> transações registradas</p>
                </div>
                
                <div class="stat-card paid">
                    <div class="stat-card-title">Comissões Pagas</div>
                    <div class="stat-card-value">R$ <?php echo number_format($paidStats['valor_pago'] ?? 0, 2, ',', '.'); ?></div>
                    <p class="stat-card-period"><?php echo number_format($paidStats['total_pagas'] ?? 0); ?> transações aprovadas</p>
                </div>
                
                <div class="stat-card pending">
                    <div class="stat-card-title">Pendente de Pagamento</div>
                    <div class="stat-card-value warning">R$ <?php echo number_format($pendingStats['valor_pendente'] ?? 0, 2, ',', '.'); ?></div>
                    <p class="stat-card-period"><?php echo number_format($pendingStats['total_pendentes'] ?? 0); ?> aguardando pagamento</p>
                </div>
                
                <div class="stat-card balance">
                    <div class="stat-card-title">Saldo Utilizado</div>
                    <div class="stat-card-value">R$ <?php echo number_format($salesStats['total_saldo_usado'] ?? 0, 2, ',', '.'); ?></div>
                    <p class="stat-card-period">Cashback usado pelos clientes</p>
                </div>
            </div>

            <!-- Navegação por abas seguindo padrão do sistema -->
            <div class="tabs-navigation">
                <button class="tab-button <?php echo $activeTab === 'resumo' ? 'active' : ''; ?>" 
                        onclick="switchTab('resumo')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                    </svg>
                    Resumo
                </button>
                <button class="tab-button <?php echo $activeTab === 'transacoes' ? 'active' : ''; ?>" 
                        onclick="switchTab('transacoes')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9H9V9h10v2zm-4 4H9v-2h6v2zm4-8H9V5h10v2z"/>
                    </svg>
                    Todas as Transações
                </button>
                <button class="tab-button <?php echo $activeTab === 'pendentes' ? 'active' : ''; ?>" 
                        onclick="switchTab('pendentes')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    Comissões Pendentes
                    <?php if (($pendingStats['total_pendentes'] ?? 0) > 0): ?>
                        <span class="badge"><?php echo $pendingStats['total_pendentes']; ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-button <?php echo $activeTab === 'historico' ? 'active' : ''; ?>" 
                        onclick="switchTab('historico')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M13 3c-4.97 0-9 4.03-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42C8.27 19.99 10.51 21 13 21c4.97 0 9-4.03 9-9s-4.03-9-9-9zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/>
                    </svg>
                    Histórico
                </button>
            </div>

            <!-- Conteúdo das abas -->
            <div class="tab-content">
                <!-- Aba Resumo -->
                <div id="tab-resumo" class="tab-pane <?php echo $activeTab === 'resumo' ? 'active' : ''; ?>">
                    <h3>Visão Geral Financeira</h3>
                    
                    <div class="alert info">
                        <strong>🎉 Página Financeira Consolidada!</strong><br>
                        Agora você tem acesso a todas as informações financeiras da sua loja em um só lugar. Use as abas acima para navegar entre as diferentes funcionalidades.
                    </div>
                    
                    <div class="quick-actions-grid">
                        <a href="?tab=transacoes" class="action-card">
                            <div class="icon">📋</div>
                            <h4>Ver Todas as Transações</h4>
                            <p>Consulte o histórico completo de vendas registradas na sua loja</p>
                        </a>
                        
                        <?php if (($pendingStats['total_pendentes'] ?? 0) > 0): ?>
                        <a href="?tab=pendentes" class="action-card">
                            <div class="icon">⏰</div>
                            <h4>Gerenciar Pendentes</h4>
                            <p><?php echo $pendingStats['total_pendentes']; ?> comissões aguardando pagamento</p>
                        </a>
                        <?php endif; ?>
                        
                        <a href="?tab=historico" class="action-card">
                            <div class="icon">📅</div>
                            <h4>Histórico de Pagamentos</h4>
                            <p>Consulte todos os pagamentos já realizados</p>
                        </a>
                        
                        <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="action-card">
                            <div class="icon">➕</div>
                            <h4>Registrar Nova Venda</h4>
                            <p>Adicione uma nova transação rapidamente</p>
                        </a>
                    </div>
                    
                    <?php if (($pendingStats['total_pendentes'] ?? 0) > 0): ?>
                    <div class="alert warning" style="margin-top: 30px;">
                        <strong>⚠️ Atenção:</strong> Você possui <strong><?php echo $pendingStats['total_pendentes']; ?> comissões pendentes</strong> no valor total de <strong>R$ <?php echo number_format($pendingStats['valor_pendente'], 2, ',', '.'); ?></strong>. 
                        <br><br>
                        <a href="?tab=pendentes" class="btn btn-warning">Gerenciar Pendências</a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Aba Transações -->
                <div id="tab-transacoes" class="tab-pane <?php echo $activeTab === 'transacoes' ? 'active' : ''; ?>">
                    <h3>Todas as Transações</h3>
                    
                    <div class="alert info">
                        <strong>🔄 Funcionalidade em Desenvolvimento</strong><br>
                        Esta seção está sendo aprimorada para oferecer uma experiência ainda melhor. Enquanto isso, você pode acessar a página original:
                        <br><br>
                        <a href="<?php echo STORE_TRANSACTIONS_URL; ?>" class="btn btn-primary" target="_blank">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
                            </svg>
                            Acessar Página de Transações
                        </a>
                    </div>
                </div>

                <!-- Aba Pendentes -->
                <div id="tab-pendentes" class="tab-pane <?php echo $activeTab === 'pendentes' ? 'active' : ''; ?>">
                    <h3>Comissões Pendentes</h3>
                    
                    <?php if (($pendingStats['total_pendentes'] ?? 0) > 0): ?>
                    <div class="alert warning">
                        <strong>⏰ Você tem <?php echo $pendingStats['total_pendentes']; ?> comissões pendentes</strong><br>
                        Valor total pendente: <strong>R$ <?php echo number_format($pendingStats['valor_pendente'], 2, ',', '.'); ?></strong><br>
                        Saldo usado pelos clientes: R$ <?php echo number_format($pendingStats['saldo_usado_pendente'] ?? 0, 2, ',', '.'); ?>
                    </div>
                    <?php else: ?>
                    <div class="alert success">
                        <strong>✅ Parabéns!</strong> Você não possui comissões pendentes no momento.
                    </div>
                    <?php endif; ?>
                    
                    <div class="alert info">
                        <strong>🔄 Funcionalidade em Desenvolvimento</strong><br>
                        Esta seção está sendo aprimorada para oferecer recursos avançados de gestão. Enquanto isso, você pode acessar a página original:
                        <br><br>
                        <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="btn btn-primary" target="_blank">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
                            </svg>
                            Gerenciar Comissões Pendentes
                        </a>
                    </div>
                </div>

                <!-- Aba Histórico -->
                <div id="tab-historico" class="tab-pane <?php echo $activeTab === 'historico' ? 'active' : ''; ?>">
                    <h3>Histórico de Pagamentos</h3>
                    
                    <div class="alert success">
                        <strong>📅 Histórico Completo</strong><br>
                        Total de transações pagas: <strong><?php echo number_format($paidStats['total_pagas'] ?? 0); ?></strong><br>
                        Valor total pago em comissões: <strong>R$ <?php echo number_format($paidStats['valor_pago'] ?? 0, 2, ',', '.'); ?></strong>
                    </div>
                    
                    <div class="alert info">
                        <strong>🔄 Funcionalidade em Desenvolvimento</strong><br>
                        Esta seção está sendo aprimorada para oferecer relatórios detalhados e filtros avançados. Enquanto isso, você pode acessar a página original:
                        <br><br>
                        <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" class="btn btn-primary" target="_blank">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 19H5V5h7V3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.11 0 2-.9 2-2v-7h-2v7zM14 3v2h3.59l-9.83 9.83 1.41 1.41L19 6.41V10h2V3h-7z"/>
                            </svg>
                            Ver Histórico Completo
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // JavaScript para navegação por abas seguindo padrão do sistema
        function switchTab(tabName) {
            // Atualizar URL sem recarregar página
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.pushState({}, '', url);
            
            // Remover classe active de todas as abas
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            
            // Ativar aba selecionada
            document.querySelector(`[onclick="switchTab('${tabName}')"]`).classList.add('active');
            document.getElementById(`tab-${tabName}`).classList.add('active');
        }

        // Responsividade da sidebar seguindo padrão do sistema
        document.addEventListener('DOMContentLoaded', function() {
            // Funcionalidade já existe na sidebar, apenas garantindo compatibilidade
        });
    </script>
</body>
</html>
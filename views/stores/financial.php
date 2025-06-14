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
    $salesQuery = $db->prepare("SELECT COUNT(*) as total_vendas, SUM(valor_total) as valor_total_vendas FROM transacoes_cashback WHERE loja_id = :loja_id");
    $salesQuery->bindParam(':loja_id', $storeId);
    $salesQuery->execute();
    $salesStats = $salesQuery->fetch(PDO::FETCH_ASSOC);
    
    // Comissões pendentes
    $pendingQuery = $db->prepare("SELECT COUNT(*) as total_pendentes, SUM(valor_cashback) as valor_pendente FROM transacoes_cashback WHERE loja_id = :loja_id AND status = 'pendente'");
    $pendingQuery->bindParam(':loja_id', $storeId);
    $pendingQuery->execute();
    $pendingStats = $pendingQuery->fetch(PDO::FETCH_ASSOC);
    
    // Comissões pagas
    $paidQuery = $db->prepare("SELECT COUNT(*) as total_pagas, SUM(valor_cashback) as valor_pago FROM transacoes_cashback WHERE loja_id = :loja_id AND status = 'aprovado'");
    $paidQuery->bindParam(':loja_id', $storeId);
    $paidQuery->execute();
    $paidStats = $paidQuery->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    // Valores padrão em caso de erro
    $salesStats = ['total_vendas' => 0, 'valor_total_vendas' => 0];
    $pendingStats = ['total_pendentes' => 0, 'valor_pendente' => 0];
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
    <style>
        /* CSS temporário incorporado para funcionar imediatamente */
        .financial-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .financial-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .financial-header h1 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .subtitle {
            color: #7f8c8d;
            font-size: 1rem;
        }
        
        .financial-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-content h3 {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .card-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 4px;
        }
        
        .card-value.warning {
            color: #f39c12;
        }
        
        .card-period {
            font-size: 0.8rem;
            color: #95a5a6;
        }
        
        .tabs-navigation {
            display: flex;
            background: white;
            border-radius: 12px;
            padding: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow-x: auto;
            gap: 4px;
        }
        
        .tab-button {
            background: transparent;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            color: #7f8c8d;
            white-space: nowrap;
        }
        
        .tab-button:hover {
            background: #f8f9fa;
            color: #2c3e50;
        }
        
        .tab-button.active {
            background: #3498db;
            color: white;
        }
        
        .tab-content {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            min-height: 400px;
        }
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            background: #e8f4fd;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        
        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <?php include_once '../components/sidebar-store.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="financial-wrapper">
            <!-- Header da página -->
            <div class="financial-header">
                <div class="header-content">
                    <h1>Financeiro</h1>
                    <p class="subtitle">Gestão financeira completa para <?php echo htmlspecialchars($storeName); ?></p>
                </div>
            </div>

            <!-- Resumo Financeiro -->
            <div class="financial-summary">
                <div class="summary-card">
                    <div class="card-content">
                        <h3>Total de Vendas</h3>
                        <div class="card-value">R$ <?php echo number_format($salesStats['valor_total_vendas'] ?? 0, 2, ',', '.'); ?></div>
                        <div class="card-period"><?php echo $salesStats['total_vendas'] ?? 0; ?> transações</div>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="card-content">
                        <h3>Comissões Pagas</h3>
                        <div class="card-value">R$ <?php echo number_format($paidStats['valor_pago'] ?? 0, 2, ',', '.'); ?></div>
                        <div class="card-period"><?php echo $paidStats['total_pagas'] ?? 0; ?> pagas</div>
                    </div>
                </div>
                
                <div class="summary-card">
                    <div class="card-content">
                        <h3>Pendente de Pagamento</h3>
                        <div class="card-value warning">R$ <?php echo number_format($pendingStats['valor_pendente'] ?? 0, 2, ',', '.'); ?></div>
                        <div class="card-period"><?php echo $pendingStats['total_pendentes'] ?? 0; ?> pendentes</div>
                    </div>
                </div>
            </div>

            <!-- Navegação por Abas -->
            <div class="tabs-navigation">
                <button class="tab-button <?php echo $activeTab === 'resumo' ? 'active' : ''; ?>" 
                        onclick="switchTab('resumo')">
                    📊 Resumo
                </button>
                <button class="tab-button <?php echo $activeTab === 'transacoes' ? 'active' : ''; ?>" 
                        onclick="switchTab('transacoes')">
                    📋 Todas as Transações
                </button>
                <button class="tab-button <?php echo $activeTab === 'pendentes' ? 'active' : ''; ?>" 
                        onclick="switchTab('pendentes')">
                    ⏰ Pendentes
                    <?php if (($pendingStats['total_pendentes'] ?? 0) > 0): ?>
                        <span style="background: #e74c3c; color: white; font-size: 0.7rem; padding: 2px 6px; border-radius: 10px; margin-left: 5px;"><?php echo $pendingStats['total_pendentes']; ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-button <?php echo $activeTab === 'historico' ? 'active' : ''; ?>" 
                        onclick="switchTab('historico')">
                    📅 Histórico
                </button>
            </div>

            <!-- Conteúdo das Abas -->
            <div class="tab-content">
                <!-- Aba Resumo -->
                <div id="tab-resumo" class="tab-pane <?php echo $activeTab === 'resumo' ? 'active' : ''; ?>">
                    <h3>Visão Geral Financeira</h3>
                    
                    <div class="alert">
                        <strong>📈 Nova Página Consolidada!</strong><br>
                        Esta página agora reúne todas as informações financeiras da sua loja em um só lugar:
                        <ul style="margin-top: 10px;">
                            <li>✅ Todas as suas transações</li>
                            <li>⏰ Comissões pendentes de pagamento</li>
                            <li>📅 Histórico completo de pagamentos</li>
                            <li>📊 Resumo estatístico</li>
                        </ul>
                    </div>
                    
                    <p>Utilize as abas acima para navegar entre as diferentes seções:</p>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                        <a href="?tab=transacoes" class="btn btn-primary" style="text-align: center; padding: 20px;">
                            📋 Ver Todas as Transações
                        </a>
                        
                        <?php if (($pendingStats['total_pendentes'] ?? 0) > 0): ?>
                        <a href="?tab=pendentes" class="btn btn-primary" style="text-align: center; padding: 20px; background: #f39c12;">
                            ⏰ Gerenciar Pendentes (<?php echo $pendingStats['total_pendentes']; ?>)
                        </a>
                        <?php endif; ?>
                        
                        <a href="?tab=historico" class="btn btn-primary" style="text-align: center; padding: 20px;">
                            📅 Histórico de Pagamentos
                        </a>
                    </div>
                </div>

                <!-- Aba Transações -->
                <div id="tab-transacoes" class="tab-pane <?php echo $activeTab === 'transacoes' ? 'active' : ''; ?>">
                    <h3>Todas as Transações</h3>
                    <p>Esta seção exibirá todas as transações da sua loja. <em>(Em desenvolvimento)</em></p>
                    
                    <div class="alert">
                        <strong>🔄 Migração em Andamento</strong><br>
                        Estamos consolidando as funcionalidades das páginas antigas nesta nova interface.
                        <br><br>
                        <strong>Acesso temporário às páginas originais:</strong><br>
                        <a href="<?php echo STORE_TRANSACTIONS_URL; ?>" target="_blank">→ Página de Transações Original</a>
                    </div>
                </div>

                <!-- Aba Pendentes -->
                <div id="tab-pendentes" class="tab-pane <?php echo $activeTab === 'pendentes' ? 'active' : ''; ?>">
                    <h3>Comissões Pendentes</h3>
                    <p>Gerencie suas comissões pendentes de pagamento aqui. <em>(Em desenvolvimento)</em></p>
                    
                    <div class="alert">
                        <strong>⏰ Você tem <?php echo $pendingStats['total_pendentes'] ?? 0; ?> comissões pendentes</strong><br>
                        Total pendente: R$ <?php echo number_format($pendingStats['valor_pendente'] ?? 0, 2, ',', '.'); ?>
                        <br><br>
                        <strong>Acesso temporário à página original:</strong><br>
                        <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" target="_blank">→ Página de Comissões Pendentes Original</a>
                    </div>
                </div>

                <!-- Aba Histórico -->
                <div id="tab-historico" class="tab-pane <?php echo $activeTab === 'historico' ? 'active' : ''; ?>">
                    <h3>Histórico de Pagamentos</h3>
                    <p>Consulte todo o histórico de pagamentos realizados. <em>(Em desenvolvimento)</em></p>
                    
                    <div class="alert">
                        <strong>📅 Histórico Completo</strong><br>
                        Todas as suas transações pagas: <?php echo $paidStats['total_pagas'] ?? 0; ?> transações
                        <br><br>
                        <strong>Acesso temporário à página original:</strong><br>
                        <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" target="_blank">→ Página de Histórico Original</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // JavaScript básico para trocar abas
        function switchTab(tabName) {
            // Atualizar URL
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
    </script>
</body>
</html>
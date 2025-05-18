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
$result = ClientController::getClientBalance($userId);

// Verificar se houve erro
$hasError = !$result['status'];
$errorMessage = $hasError ? $result['message'] : '';

// Dados para exibição na página
$balanceData = $hasError ? [] : $result['data'];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Saldo - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    
    <link rel="stylesheet" href="../../assets/css/views/client/balance.css">
</head>
<body>
    <!-- Incluir navegação navbar -->
    <?php include_once '../components/navbar.php'; ?>
    
    <div class="container" style="margin-top: 80px;">
        <!-- Cabeçalho da Página -->
        <div class="page-header">
            <div>
                <h1>Meu Saldo de Cashback</h1>
                <p class="page-subtitle">Veja seus saldos disponíveis em cada loja parceira</p>
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
                    <div class="total-amount">R$ <?php echo number_format($balanceData['saldo_total'], 2, ',', '.'); ?></div>
                    <div class="summary-stats">
                        <div class="stat">
                            <span class="stat-label">Lojas com saldo</span>
                            <span class="stat-value"><?php echo $balanceData['estatisticas']['total_lojas_com_saldo']; ?></span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Total de compras</span>
                            <span class="stat-value">R$ <?php echo number_format($balanceData['estatisticas']['total_compras'], 2, ',', '.'); ?></span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Transações</span>
                            <span class="stat-value"><?php echo $balanceData['estatisticas']['total_transacoes']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Informações sobre saldos pendentes -->
            <?php if (!empty($balanceData['saldos_pendentes'])): ?>
            <div class="card pending-balance-card">
                <div class="card-header">
                    <h3 class="card-title">Saldos Pendentes</h3>
                    <span class="pending-badge">Aguardando aprovação</span>
                </div>
                <div class="card-body">
                    <p class="pending-description">
                        Você tem cashback aguardando confirmação de pagamento das lojas. 
                        Estes valores serão liberados assim que as lojas quitarem suas comissões.
                    </p>
                    <div class="pending-stores">
                        <?php foreach ($balanceData['saldos_pendentes'] as $pendente): ?>
                        <div class="pending-item">
                            <span class="store-name"><?php echo htmlspecialchars($pendente['nome_fantasia']); ?></span>
                            <span class="pending-amount">R$ <?php echo number_format($pendente['saldo_pendente'], 2, ',', '.'); ?></span>
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
            
            <?php if (empty($balanceData['saldos_por_loja'])): ?>
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
                    <?php foreach ($balanceData['saldos_por_loja'] as $loja): ?>
                    <div class="store-balance-card">
                        <div class="store-header">
                            <div class="store-logo">
                                <?php if (!empty($loja['logo'])): ?>
                                    <img src="../../uploads/store_logos/<?php echo htmlspecialchars($loja['logo']); ?>" 
                                         alt="<?php echo htmlspecialchars($loja['nome_fantasia']); ?>">
                                <?php else: ?>
                                    <div class="store-initial">
                                        <?php echo strtoupper(substr($loja['nome_fantasia'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="store-info">
                                <h3 class="store-name"><?php echo htmlspecialchars($loja['nome_fantasia']); ?></h3>
                                <span class="store-category"><?php echo htmlspecialchars($loja['categoria'] ?? 'Geral'); ?></span>
                            </div>
                        </div>
                        
                        <div class="store-balance">
                            <div class="balance-amount">
                                R$ <?php echo number_format($loja['saldo_disponivel'], 2, ',', '.'); ?>
                            </div>
                            <div class="balance-label">Saldo disponível</div>
                        </div>
                        
                        <div class="store-stats">
                            <div class="store-stat">
                                <span class="stat-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                        <line x1="1" y1="10" x2="23" y2="10"></line>
                                    </svg>
                                </span>
                                <span class="stat-text"><?php echo $loja['total_transacoes']; ?> transações</span>
                            </div>
                            <div class="store-stat">
                                <span class="stat-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                                    </svg>
                                </span>
                                <span class="stat-text">R$ <?php echo number_format($loja['total_compras'], 2, ',', '.'); ?> em compras</span>
                            </div>
                            <div class="store-stat">
                                <span class="stat-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12 6 12 12 16 14"></polyline>
                                    </svg>
                                </span>
                                <span class="stat-text">
                                    Última compra: <?php echo date('d/m/Y', strtotime($loja['ultima_transacao'])); ?>
                                </span>
                            </div>
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
        
        <!-- Seção de Estatísticas -->
        <div class="statistics-section">
            <div class="card">
                <h3 class="card-title">Estatísticas Gerais</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $balanceData['estatisticas']['total_lojas_utilizadas']; ?></div>
                        <div class="stat-label">Lojas diferentes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $balanceData['estatisticas']['total_transacoes_historico']; ?></div>
                        <div class="stat-label">Total de transações</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">R$ <?php echo number_format($balanceData['estatisticas']['total_compras_historico'], 2, ',', '.'); ?></div>
                        <div class="stat-label">Total em compras</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">R$ <?php echo number_format($balanceData['estatisticas']['total_cashback_historico'], 2, ',', '.'); ?></div>
                        <div class="stat-label">Total em cashback</div>
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
        });
        
        // Função para visualizar detalhes da loja
        function viewStoreDetails(storeId) {
            // Aqui você pode implementar a abertura do modal com detalhes da loja
            // Por exemplo, fazer uma requisição AJAX para buscar as transações específicas desta loja
            console.log('Visualizar detalhes da loja ID:', storeId);
            
            // Implementação básica do modal
            document.getElementById('storeDetailsModal').style.display = 'block';
            document.getElementById('modalStoreContent').innerHTML = '<p>Carregando detalhes...</p>';
            
            // Aqui você adicionaria a lógica para carregar os detalhes reais
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
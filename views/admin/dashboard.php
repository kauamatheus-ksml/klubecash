<?php
//views/admin/dashboard.php
// Definir o menu ativo na sidebar
$activeMenu = 'painel';

// Incluir conexão com o banco de dados
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../models/CashbackBalance.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // Redirecionar para a página de login com mensagem de erro
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Obter dados do usuário logado
try {
    $db = Database::getConnection();
    
    // Buscar informações do usuário
    $userId = $_SESSION['user_id'];
    $userStmt = $db->prepare("SELECT nome FROM usuarios WHERE id = ? AND tipo = 'admin' AND status = 'ativo'");
    $userStmt->execute([$userId]);
    $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        // Se não encontrar usuário ativo e admin, redirecionar
        header("Location: " . LOGIN_URL . "?error=acesso_restrito");
        exit;
    }
    
    $adminName = $userData['nome'];
    
    // Total de usuários
    $userCountStmt = $db->query("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'cliente'");
    $totalUsers = $userCountStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de lojas
    $storeStmt = $db->query("SELECT COUNT(*) as total FROM lojas WHERE status = 'aprovado'");
    $totalStores = $storeStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de cashback
    $cashbackStmt = $db->query("SELECT SUM(valor_cashback) as total FROM transacoes_cashback");
    $totalCashback = $cashbackStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Estatísticas de saldo - total de saldo disponível em todas as lojas
    $saldoDisponivelStmt = $db->query("SELECT SUM(saldo_disponivel) as total FROM cashback_saldos");
    $totalSaldoDisponivel = $saldoDisponivelStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Total de saldo usado pelos clientes
    $saldoUsadoStmt = $db->query("
        SELECT SUM(valor) as total 
        FROM cashback_movimentacoes 
        WHERE tipo_operacao = 'uso'
    ");
    $totalSaldoUsado = $saldoUsadoStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Estatísticas de transações com saldo
    $transacoesComSaldoStmt = $db->query("
        SELECT 
            COUNT(DISTINCT t.id) as total_transacoes,
            COUNT(DISTINCT CASE WHEN cm.id IS NOT NULL THEN t.id END) as transacoes_com_saldo,
            COUNT(DISTINCT CASE WHEN cm.id IS NULL THEN t.id END) as transacoes_sem_saldo
        FROM transacoes_cashback t
        LEFT JOIN cashback_movimentacoes cm ON t.id = cm.transacao_uso_id AND cm.tipo_operacao = 'uso'
        WHERE t.status = 'aprovado'
    ");
    $estatisticasSaldo = $transacoesComSaldoStmt->fetch(PDO::FETCH_ASSOC);
    
    // Percentual de transações com uso de saldo
    $percentualComSaldo = $estatisticasSaldo['total_transacoes'] > 0 ? 
        ($estatisticasSaldo['transacoes_com_saldo'] / $estatisticasSaldo['total_transacoes']) * 100 : 0;
    
    // Lojas pendentes de aprovação
    $pendingStores = $db->query("
        SELECT id, nome_fantasia, razao_social, cnpj 
        FROM lojas 
        WHERE status = 'pendente' 
        ORDER BY data_cadastro DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Últimas transações com informações de saldo usado
    $recentTransactions = $db->query("
        SELECT 
            t.id, 
            t.valor_total, 
            t.valor_cashback, 
            t.codigo_transacao,
            t.data_transacao,
            u.nome as usuario, 
            l.nome_fantasia as loja, 
            l.razao_social,
            COALESCE(
                (SELECT SUM(cm.valor) 
                 FROM cashback_movimentacoes cm 
                 WHERE cm.transacao_uso_id = t.id AND cm.tipo_operacao = 'uso'), 0
            ) as saldo_usado
        FROM transacoes_cashback t
        JOIN usuarios u ON t.usuario_id = u.id
        JOIN lojas l ON t.loja_id = l.id
        ORDER BY t.data_transacao DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Impacto financeiro do saldo no sistema
    $impactoFinanceiroStmt = $db->query("
        SELECT 
            SUM(t.valor_total) as valor_vendas_originais,
            SUM(t.valor_total - COALESCE(cm_sum.saldo_usado, 0)) as valor_vendas_liquidas,
            SUM(t.valor_cashback) as comissoes_recebidas
        FROM transacoes_cashback t
        LEFT JOIN (
            SELECT 
                transacao_uso_id,
                SUM(valor) as saldo_usado
            FROM cashback_movimentacoes 
            WHERE tipo_operacao = 'uso'
            GROUP BY transacao_uso_id
        ) cm_sum ON t.id = cm_sum.transacao_uso_id
        WHERE t.status = 'aprovado'
    ");
    $impactoFinanceiro = $impactoFinanceiroStmt->fetch(PDO::FETCH_ASSOC);
    
    // Top 5 clientes que mais usaram saldo
    $topClientesSaldoStmt = $db->query("
        SELECT 
            u.nome,
            u.email,
            SUM(cm.valor) as total_saldo_usado,
            COUNT(cm.id) as vezes_usado
        FROM cashback_movimentacoes cm
        JOIN usuarios u ON cm.usuario_id = u.id
        WHERE cm.tipo_operacao = 'uso'
        GROUP BY u.id, u.nome, u.email
        ORDER BY total_saldo_usado DESC
        LIMIT 5
    ");
    $topClientesSaldo = $topClientesSaldoStmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Erro ao carregar estatísticas: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <title>Dashboard - Klube Cash</title>
    <link rel="stylesheet" href="../../assets/css/views/admin/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/views/admin/dashboard1.css">
</head>
<body>
    <?php include_once '../components/sidebar.php'; ?>
    
    <!-- Conteúdo Principal -->
    <div class="main-content" id="mainContent">
        <div class="dashboard-wrapper">
            <!-- Cabeçalho -->
            <div class="dashboard-header">
                <h1>Bem Vindo, <?php echo htmlspecialchars($adminName); ?>!</h1>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php else: ?>
            
            <!-- Cards de estatísticas principais -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-card-title">Usuários Registrados</div>
                    <div class="stat-card-value"><?php echo number_format($totalUsers); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Lojas Parceiras</div>
                    <div class="stat-card-value"><?php echo number_format($totalStores); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Total de Cashback</div>
                    <div class="stat-card-value">R$ <?php echo number_format($totalCashback, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Total creditado aos clientes</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-card-title">Saldo Disponível</div>
                    <div class="stat-card-value">R$ <?php echo number_format($totalSaldoDisponivel, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Saldo acumulado pelos clientes</div>
                </div>
            </div>
            
            <!-- Cards de estatísticas de saldo -->
            <div class="stats-container">
                <div class="stat-card balance-stats">
                    <div class="stat-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"></circle>
                            <path d="M12 6v6l4 2"></path>
                        </svg>
                        Saldo Usado
                    </div>
                    <div class="stat-card-value">R$ <?php echo number_format($totalSaldoUsado, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Total usado pelos clientes</div>
                </div>
                
                <div class="stat-card balance-stats">
                    <div class="stat-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Transações c/ Saldo
                    </div>
                    <div class="stat-card-value"><?php echo number_format($estatisticasSaldo['transacoes_com_saldo']); ?></div>
                    <div class="stat-card-subtitle"><?php echo number_format($percentualComSaldo, 1); ?>% do total</div>
                </div>
                
                <div class="stat-card balance-stats">
                    <div class="stat-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        Taxa de Uso
                    </div>
                    <div class="stat-card-value"><?php echo number_format($percentualComSaldo, 1); ?>%</div>
                    <div class="stat-card-subtitle">Clientes usando saldo</div>
                </div>
                
                <div class="stat-card balance-stats">
                    <div class="stat-card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="1" x2="12" y2="23"></line>
                            <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                        </svg>
                        Economia Clientes
                    </div>
                    <div class="stat-card-value">R$ <?php echo number_format($totalSaldoUsado, 2, ',', '.'); ?></div>
                    <div class="stat-card-subtitle">Total economizado</div>
                </div>
            </div>
            
            <!-- Seção de impacto financeiro -->
            <div class="two-column-layout">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                            Impacto Financeiro do Saldo
                        </div>
                    </div>
                    <div class="financial-impact">
                        <div class="impact-item">
                            <span class="impact-label">Valor original das vendas:</span>
                            <span class="impact-value">R$ <?php echo number_format($impactoFinanceiro['valor_vendas_originais'] ?? 0, 2, ',', '.'); ?></span>
                        </div>
                        <div class="impact-item">
                            <span class="impact-label">Desconto via saldo:</span>
                            <span class="impact-value balance-discount">- R$ <?php echo number_format($totalSaldoUsado, 2, ',', '.'); ?></span>
                        </div>
                        <div class="impact-item">
                            <span class="impact-label">Valor líquido das vendas:</span>
                            <span class="impact-value">R$ <?php echo number_format($impactoFinanceiro['valor_vendas_liquidas'] ?? 0, 2, ',', '.'); ?></span>
                        </div>
                        <div class="impact-item total">
                            <span class="impact-label">Comissões recebidas:</span>
                            <span class="impact-value">R$ <?php echo number_format($impactoFinanceiro['comissoes_recebidas'] ?? 0, 2, ',', '.'); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                            Top Clientes - Uso de Saldo
                        </div>
                    </div>
                    
                    <?php if (!empty($topClientesSaldo)): ?>
                        <div class="top-clients-list">
                            <?php foreach ($topClientesSaldo as $index => $cliente): ?>
                                <div class="client-item">
                                    <div class="client-rank">#<?php echo $index + 1; ?></div>
                                    <div class="client-info">
                                        <div class="client-name"><?php echo htmlspecialchars($cliente['nome']); ?></div>
                                        <div class="client-details">
                                            R$ <?php echo number_format($cliente['total_saldo_usado'], 2, ',', '.'); ?> 
                                            (<?php echo $cliente['vezes_usado']; ?> uso<?php echo $cliente['vezes_usado'] > 1 ? 's' : ''; ?>)
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state-small">
                            <p>Nenhum cliente usou saldo ainda.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Layout de duas colunas -->
            <div class="two-column-layout">
                <!-- Aprovar Lojas -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Aprovar Lojas</div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table">
                            <thead> 
                                <tr>
                                    <th>Nome da Loja</th>
                                    <th>Tipo</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pendingStores)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: center;">Nenhuma loja pendente de aprovação</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pendingStores as $store): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($store['nome_fantasia']); ?></td>
                                            <td>Varejo</td>
                                            <td>
                                                <button class="btn btn-primary" onclick="approveStore(<?php echo $store['id']; ?>)">Aprovar</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Notificações -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Notificações</div>
                    </div>
                    
                    <div class="notifications-container">
                        <div class="notification-empty">
                            Nenhuma notificação
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Últimas Transações -->
            <div class="card transactions-container">
                <div class="card-header">
                    <div class="card-title">Ultimas Transações</div>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Loja</th>
                                <th>Valor Original</th>
                                <th>Saldo Usado</th>
                                <th>Cashback</th>
                                <th>Data</th>
                                <th>Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentTransactions)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">Nenhuma transação encontrada</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($transaction['codigo_transacao'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['usuario']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($transaction['loja']); ?>
                                            <?php if ($transaction['saldo_usado'] > 0): ?>
                                                <span class="balance-indicator" title="Cliente usou saldo">💰</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>R$ <?php echo number_format($transaction['valor_total'], 2, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($transaction['saldo_usado'] > 0): ?>
                                                <span class="saldo-usado">R$ <?php echo number_format($transaction['saldo_usado'], 2, ',', '.'); ?></span>
                                            <?php else: ?>
                                                <span class="sem-saldo">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>R$ <?php echo number_format($transaction['valor_cashback'], 2, ',', '.'); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($transaction['data_transacao'])); ?></td>
                                        <td>
                                            <button class="btn btn-primary" onclick="viewTransaction(<?php echo $transaction['id']; ?>)">Detalhar</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Função para aprovar uma loja
        function approveStore(storeId) {
            if (confirm('Tem certeza que deseja aprovar esta loja?')) {
                // Criar requisição AJAX para aprovar a loja
                const xhr = new XMLHttpRequest();
                xhr.open('POST', '<?php echo SITE_URL; ?>/controllers/StoreController.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (this.status === 200) {
                        alert('Loja aprovada com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro ao aprovar loja. Por favor, tente novamente.');
                    }
                };
                xhr.send('action=approve&id=' + storeId);
            }
        }
        
        // Função para visualizar detalhes de uma transação
        function viewTransaction(transactionId) {
            window.location.href = '<?php echo SITE_URL; ?>/admin/transacao/' + transactionId;
        }
        
        // Animar números nos cards de estatísticas
        document.addEventListener('DOMContentLoaded', function() {
            const statValues = document.querySelectorAll('.stat-card-value');
            statValues.forEach(element => {
                const value = element.textContent;
                if (value.includes('R$') || value.includes('%') || !isNaN(value.replace(/[^\d]/g, ''))) {
                    element.style.opacity = '0';
                    setTimeout(() => {
                        element.style.transition = 'opacity 0.5s ease';
                        element.style.opacity = '1';
                    }, Math.random() * 500);
                }
            });
        });
    </script>
    
    
</body>
</html>
<?php
//views/admin/dashboard.php
// Definir o menu ativo na sidebar
$activeMenu = 'painel';

// Incluir conexão com o banco de dados
require_once '../../config/database.php';
require_once '../../config/constants.php';

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
    
    // Lojas pendentes de aprovação
    $pendingStores = $db->query("
        SELECT id, nome_fantasia, razao_social, cnpj 
        FROM lojas 
        WHERE status = 'pendente' 
        ORDER BY data_cadastro DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Últimas transações
    $recentTransactions = $db->query("
        SELECT t.id, t.valor_total, t.valor_cashback, u.nome as usuario, l.nome_fantasia as loja, l.razao_social
        FROM transacoes_cashback t
        JOIN usuarios u ON t.usuario_id = u.id
        JOIN lojas l ON t.loja_id = l.id
        ORDER BY t.data_transacao DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
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
            
            <!-- Cards de estatísticas -->
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
                                <th>Usuário</th>
                                <th>Tipo</th>
                                <th>Valor</th>
                                <th>Detalhes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentTransactions)): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center;">Nenhuma transação encontrada</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentTransactions as $transaction): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($transaction['loja']); ?></td>
                                        <td>Varejo</td>
                                        <td>R$ <?php echo number_format($transaction['valor_cashback'], 2, ',', '.'); ?></td>
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
            window.location.href = '<?php echo SITE_URL; ?>/admin/transaction/' + transactionId;
        }
    </script>
</body>
</html>
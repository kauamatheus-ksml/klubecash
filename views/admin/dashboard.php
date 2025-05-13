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
    <style>
        :root {
            --primary-color: #FF7A00;
            --primary-light: #FFF0E6;
            --white: #FFFFFF;
            --light-gray: #F5F5F5;
            --dark-gray: #333333;
            --medium-gray: #666666;
            --success-color: #4CAF50;
            --danger-color: #F44336;
            --warning-color: #FFC107;
            --border-radius: 15px;
            --shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            --font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Reset e estilos gerais */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: var(--font-family);
        }
        
        body {
            background-color: #FFF9F2;
            overflow-x: hidden;
        }
        
        /* Container principal */
        .main-content {
            padding-left: 250px;
            transition: padding-left 0.3s ease;
        }
        
        /* Dashboard wrapper */
        .dashboard-wrapper {
            background-color: #FFF9F2;
            min-height: 100vh;
            padding: 30px;
        }
        
        /* Cabeçalho */
        .dashboard-header {
            margin-bottom: 30px;
        }
        
        .dashboard-header h1 {
            font-size: 24px;
            color: var(--dark-gray);
            font-weight: 600;
        }
        
        /* Cards de estatísticas */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            border: 1px solid #FFD9B3;
            display: flex;
            flex-direction: column;
        }
        
        .stat-card-title {
            font-size: 18px;
            color: var(--primary-color);
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .stat-card-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark-gray);
        }
        
        /* Layout de duas colunas */
        .two-column-layout {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        /* Cards gerais */
        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow);
            border: 1px solid #FFD9B3;
        }
        
        .card-header {
            margin-bottom: 15px;
        }
        
        .card-title {
            font-size: 18px;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        /* Tabelas */
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th, .table td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid #EEEEEE;
        }
        
        .table th {
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        /* Botões */
        .btn {
            padding: 6px 15px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: #E06E00;
        }
        
        /* Card de notificações */
        .notifications-container {
            min-height: 200px;
        }
        
        .notification-empty {
            color: var(--medium-gray);
            text-align: center;
            padding: 30px 0;
        }

        /* Card de transações */
        .transactions-container {
            margin-bottom: 30px;
        }
        
        /* Responsividade */
        @media (max-width: 768px) {
            .main-content {
                padding-left: 0;
            }
            
            .dashboard-wrapper {
                padding: 75px 20px;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .two-column-layout {
                grid-template-columns: 1fr;
            }
            
            .table-responsive {
                overflow-x: auto;
            }
        }
    </style>
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
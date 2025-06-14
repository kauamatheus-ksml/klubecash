<?php
// views/stores/payment-history.php
// Definir o menu ativo na sidebar
$activeMenu = 'payment-history';

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/TransactionController.php';
require_once '../../models/CashbackBalance.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é uma loja
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'loja') {
    // Redirecionar para a página de login com mensagem de erro
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Obter ID do usuário logado
$userId = $_SESSION['user_id'];

// Obter dados da loja associada ao usuário
$db = Database::getConnection();
$storeQuery = $db->prepare("SELECT id, nome_fantasia FROM lojas WHERE usuario_id = :usuario_id");
$storeQuery->bindParam(':usuario_id', $userId);
$storeQuery->execute();

// Verificar se o usuário tem uma loja associada
if ($storeQuery->rowCount() == 0) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Sua conta não está associada a nenhuma loja. Entre em contato com o suporte.'));
    exit;
}

// Obter os dados da loja
$store = $storeQuery->fetch(PDO::FETCH_ASSOC);
$storeId = $store['id'];
$storeName = $store['nome_fantasia'];

// Definir parâmetros de paginação e filtros
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$filters = [];

// Aplicar filtros se fornecidos
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $filters['data_inicio'] = $_GET['data_inicio'];
}
if (isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $filters['data_fim'] = $_GET['data_fim'];
}
if (isset($_GET['metodo_pagamento']) && !empty($_GET['metodo_pagamento'])) {
    $filters['metodo_pagamento'] = $_GET['metodo_pagamento'];
}

// Obter histórico de pagamentos com informações de saldo
$result = TransactionController::getPaymentHistoryWithBalance($storeId, $filters, $page);

// Calcular estatísticas
$totalPagamentos = 0;
$totalAprovados = 0;
$totalPendentes = 0;
$totalRejeitados = 0;
$valorTotalPagamentos = 0;
$valorTotalVendasOriginais = 0;
$totalSaldoUsado = 0;

if ($result['status'] && isset($result['data']['pagamentos'])) {
    foreach ($result['data']['pagamentos'] as &$payment) {
        if ($payment['metodo_pagamento'] === 'pix_openpix') {
            // Corrigir valor de vendas
            if ($payment['valor_vendas_originais'] == 0) {
                $payment['valor_vendas_originais'] = $payment['valor_total'] / 0.10;
            }
        }
        
        $totalPagamentos++;
        $valorTotalPagamentos += $payment['valor_total'];
        $valorTotalVendasOriginais += $payment['valor_vendas_originais'];
        $totalSaldoUsado += $payment['total_saldo_usado'];
        
        switch ($payment['status']) {
            case 'aprovado':
                $totalAprovados++;
                break;
            case 'pendente':
            case 'pix_aguardando':
                $totalPendentes++;
                break;
            case 'rejeitado':
            case 'cancelado':
                $totalRejeitados++;
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Pagamentos - <?php echo htmlspecialchars($storeName); ?> | Klube Cash</title>
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/main.css?v=<?php echo ASSETS_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/store.css?v=<?php echo ASSETS_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/responsive.css?v=<?php echo ASSETS_VERSION; ?>">
    
    <!-- CSS Específico para esta página -->
    <style>
        /* Estilos específicos para o histórico de pagamentos */
        .payments-container {
            margin-top: 20px;
        }
        
        .payment-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .summary-card .value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .filters-card {
            margin-bottom: 20px;
        }
        
        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .form-buttons {
            display: flex;
            gap: 10px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table th,
        .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-aprovado {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pendente,
        .status-pix_aguardando {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-rejeitado,
        .status-cancelado {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .valor-detalhado {
            font-size: 14px;
        }
        
        .valor-detalhado strong {
            display: block;
            margin-bottom: 2px;
        }
        
        .valor-detalhado small {
            color: #6c757d;
        }
        
        .btn-action {
            margin: 2px;
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .pagination-links {
            display: flex;
            gap: 10px;
        }
        
        .page-link {
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .page-link:hover {
            background-color: #0056b3;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            background-color: #f8f9fa;
            border-radius: 8px 8px 0 0;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .info-section {
            margin-bottom: 20px;
            padding: 15px;
            border-left: 4px solid #007bff;
            background-color: #f8f9fa;
        }
        
        .info-section h4 {
            margin-top: 0;
            color: #007bff;
        }
        
        .info-section ul {
            margin-bottom: 0;
        }
        
        .info-section li {
            margin-bottom: 8px;
        }
        
        @media (max-width: 768px) {
            .payment-summary {
                grid-template-columns: 1fr;
            }
            
            .filters-form {
                grid-template-columns: 1fr;
            }
            
            .form-buttons {
                flex-direction: column;
            }
            
            .table-responsive {
                overflow-x: auto;
            }
            
            .pagination {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .pagination-links {
                justify-content: center;
                flex-wrap: wrap;
            }
        }
    </style>
</head>
<body>
    <div class="main-wrapper">
        <!-- Sidebar -->
        <?php include '../components/sidebar-store.php'; ?>
        
        <!-- Conteúdo Principal -->
        <div class="main-content">
            <!-- Header -->
            <?php include '../components/header-store.php'; ?>
            
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="<?php echo STORE_DASHBOARD_URL; ?>">Dashboard</a>
                <span>Histórico de Pagamentos</span>
            </div>
            
            <!-- Resumo de Estatísticas -->
            <div class="payment-summary">
                <div class="summary-card">
                    <h3>💰 TOTAL PAGO</h3>
                    <div class="value">R$ <?php echo number_format($valorTotalPagamentos, 2, ',', '.'); ?></div>
                    <small><?php echo $totalPagamentos; ?> pagamentos</small>
                </div>
                <div class="summary-card">
                    <h3>✅ APROVADOS</h3>
                    <div class="value"><?php echo $totalAprovados; ?></div>
                    <small>Pagamentos confirmados</small>
                </div>
                <div class="summary-card">
                    <h3>⏳ PENDENTES</h3>
                    <div class="value"><?php echo $totalPendentes; ?></div>
                    <small>Aguardando processamento</small>
                </div>
                <div class="summary-card">
                    <h3>❌ REJEITADOS</h3>
                    <div class="value"><?php echo $totalRejeitados; ?></div>
                    <small>Pagamentos com problema</small>
                </div>
            </div>
            
            <!-- Filtros -->
            <div class="card filters-card">
                <div class="card-header">
                    <div class="card-title">🔍 Filtros de Busca</div>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="filters-form">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="">Todos os status</option>
                                    <option value="pendente" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                                    <option value="pix_aguardando" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pix_aguardando') ? 'selected' : ''; ?>>PIX Aguardando</option>
                                    <option value="aprovado" <?php echo (isset($_GET['status']) && $_GET['status'] === 'aprovado') ? 'selected' : ''; ?>>Aprovado</option>
                                    <option value="rejeitado" <?php echo (isset($_GET['status']) && $_GET['status'] === 'rejeitado') ? 'selected' : ''; ?>>Rejeitado</option>
                                    <option value="cancelado" <?php echo (isset($_GET['status']) && $_GET['status'] === 'cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="metodo_pagamento">Método</label>
                                <select id="metodo_pagamento" name="metodo_pagamento">
                                    <option value="">Todos os métodos</option>
                                    <option value="pix_mercadopago" <?php echo (isset($_GET['metodo_pagamento']) && $_GET['metodo_pagamento'] === 'pix_mercadopago') ? 'selected' : ''; ?>>PIX Mercado Pago</option>
                                    <option value="transferencia" <?php echo (isset($_GET['metodo_pagamento']) && $_GET['metodo_pagamento'] === 'transferencia') ? 'selected' : ''; ?>>Transferência</option>
                                    <option value="pix_openpix" <?php echo (isset($_GET['metodo_pagamento']) && $_GET['metodo_pagamento'] === 'pix_openpix') ? 'selected' : ''; ?>>PIX OpenPix</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="data_inicio">Data Início</label>
                                <input type="date" id="data_inicio" name="data_inicio" value="<?php echo isset($_GET['data_inicio']) ? htmlspecialchars($_GET['data_inicio']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="data_fim">Data Fim</label>
                                <input type="date" id="data_fim" name="data_fim" value="<?php echo isset($_GET['data_fim']) ? htmlspecialchars($_GET['data_fim']) : ''; ?>">
                            </div>
                            
                            <div class="form-buttons">
                                <button type="submit" class="btn btn-primary">Filtrar</button>
                                <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" class="btn btn-secondary">Limpar</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Listagem de Pagamentos -->
            <div class="card payments-container">
                <div class="card-header">
                    <div class="card-title">📋 Histórico de Pagamentos</div>
                </div>
                
                <?php if ($result['status'] && count($result['data']['pagamentos']) > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Data</th>
                                    <th>Valor Vendas</th>
                                    <th>Saldo Usado</th>
                                    <th>Comissão Paga</th>
                                    <th>Método</th>
                                    <th>Status</th>
                                    <th>Transações</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result['data']['pagamentos'] as $payment): ?>
                                    <tr>
                                        <td><?php echo $payment['id']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($payment['data_registro']) - 10800); ?></td>
                                        <td>
                                            <div class="valor-detalhado">
                                                <strong>R$ <?php echo number_format($payment['valor_vendas_originais'], 2, ',', '.'); ?></strong>
                                                <small>Vendas originais</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="valor-detalhado">
                                                <strong>R$ <?php echo number_format($payment['total_saldo_usado'], 2, ',', '.'); ?></strong>
                                                <small>Cashback usado</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="valor-detalhado">
                                                <strong>R$ <?php echo number_format($payment['valor_total'], 2, ',', '.'); ?></strong>
                                                <small>10% de comissão</small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $metodos = [
                                                'pix_mercadopago' => 'PIX Mercado Pago',
                                                'transferencia' => 'Transferência',
                                                'pix_openpix' => 'PIX OpenPix'
                                            ];
                                            echo isset($metodos[$payment['metodo_pagamento']]) 
                                                ? $metodos[$payment['metodo_pagamento']] 
                                                : ucfirst($payment['metodo_pagamento']);
                                            ?>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $payment['status']; ?>">
                                                <?php
                                                $statusLabels = [
                                                    'pendente' => 'Pendente',
                                                    'pix_aguardando' => 'PIX Aguardando',
                                                    'aprovado' => 'Aprovado',
                                                    'rejeitado' => 'Rejeitado',
                                                    'cancelado' => 'Cancelado'
                                                ];
                                                echo isset($statusLabels[$payment['status']]) 
                                                    ? $statusLabels[$payment['status']] 
                                                    : ucfirst($payment['status']);
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-action btn-info" onclick="viewPaymentDetails(<?php echo $payment['id']; ?>)">
                                                <span style="margin-right: 5px;">👁️</span>
                                                Ver (<?php echo $payment['qtd_transacoes']; ?>)
                                            </button>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                                                <?php
                                                // Exibir botão de pagar/renovar PIX para pagamentos que não foram aprovados ainda
                                                // Isso inclui 'pendente', 'pix_aguardando', 'rejeitado', 'cancelado', etc.
                                                // A página payment-pix.php cuidará da lógica de verificar se o PIX existente expirou e gerar um novo se necessário.
                                                if ($payment['metodo_pagamento'] === 'pix_mercadopago' && $payment['status'] !== 'aprovado'): 
                                                ?>
                                                    <a href="<?php echo STORE_PAYMENT_PIX_URL; ?>?payment_id=<?php echo $payment['id']; ?>" class="btn btn-action btn-warning">
                                                        <span style="margin-right: 5px;">💰</span>
                                                        Pagar/Renovar PIX
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($payment['status'] === 'pix_aguardando' && !empty($payment['mp_payment_id'])): ?>
                                                    <button class="btn btn-action btn-info" onclick="checkPaymentStatus(<?php echo $payment['id']; ?>, '<?php echo $payment['mp_payment_id']; ?>')">
                                                        <span style="margin-right: 5px;">🔍</span>
                                                        Verificar Status
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Paginação -->
                    <?php if ($result['data']['paginacao']['total_paginas'] > 1): ?>
                        <div class="pagination">
                            <div class="pagination-info">
                                Página <?php echo $result['data']['paginacao']['pagina_atual']; ?> de <?php echo $result['data']['paginacao']['total_paginas']; ?>
                            </div>
                            <div class="pagination-links">
                                <?php if ($result['data']['paginacao']['pagina_atual'] > 1): ?>
                                    <a href="?page=1<?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['metodo_pagamento']) ? '&metodo_pagamento=' . urlencode($_GET['metodo_pagamento']) : ''; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?>" class="page-link">Primeira</a>
                                    <a href="?page=<?php echo $result['data']['paginacao']['pagina_atual'] - 1; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['metodo_pagamento']) ? '&metodo_pagamento=' . urlencode($_GET['metodo_pagamento']) : ''; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?>" class="page-link">Anterior</a>
                                <?php endif; ?>
                                
                                <?php if ($result['data']['paginacao']['pagina_atual'] < $result['data']['paginacao']['total_paginas']): ?>
                                    <a href="?page=<?php echo $result['data']['paginacao']['pagina_atual'] + 1; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['metodo_pagamento']) ? '&metodo_pagamento=' . urlencode($_GET['metodo_pagamento']) : ''; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?>" class="page-link">Próxima</a>
                                    <a href="?page=<?php echo $result['data']['paginacao']['total_paginas']; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['metodo_pagamento']) ? '&metodo_pagamento=' . urlencode($_GET['metodo_pagamento']) : ''; ?><?php echo isset($_GET['data_inicio']) ? '&data_inicio=' . urlencode($_GET['data_inicio']) : ''; ?><?php echo isset($_GET['data_fim']) ? '&data_fim=' . urlencode($_GET['data_fim']) : ''; ?>" class="page-link">Última</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📊</div>
                        <h3>Nenhum pagamento encontrado</h3>
                        <p>
                            <?php if (!empty($filters)): ?>
                                Não foram encontrados pagamentos com os filtros aplicados.
                                <br><a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>">Limpar filtros</a>
                            <?php else: ?>
                                Você ainda não realizou nenhum pagamento de comissões.
                                <br><a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>">Ver comissões pendentes</a>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Modal de Informações/Ajuda -->
            <div class="card info-card">
                <div class="card-header">
                    <div class="card-title">ℹ️ Informações Importantes</div>
                </div>
                <div class="card-body">
                    <div class="info-container">
                        <div class="info-section">
                            <h4>💰 Como Funciona o Pagamento:</h4>
                            <ul>
                                <li>Você deve pagar 10% de comissão sobre o valor efetivo recebido do cliente</li>
                                <li>O valor efetivo é calculado como: Valor da Venda - Saldo de Cashback Usado</li>
                                <li>Após o pagamento ser aprovado, 5% é liberado como cashback para o cliente e 5% fica para a Klube Cash</li>
                                <li>O cashback só é liberado após a confirmação do seu pagamento</li>
                            </ul>
                        </div>
                        
                        <div class="info-section">
                            <h4>📋 Status dos Pagamentos:</h4>
                            <ul>
                                <li><strong>Pendente:</strong> Pagamento registrado, aguardando processamento</li>
                                <li><strong>PIX Aguardando:</strong> PIX gerado, aguardando confirmação de pagamento</li>
                                <li><strong>Aprovado:</strong> Pagamento confirmado e processado</li>
                                <li><strong>Rejeitado:</strong> Pagamento rejeitado pelo administrador</li>
                                <li><strong>Cancelado:</strong> Pagamento cancelado</li>
                            </ul>
                        </div>
                        
                        <div class="info-section">
                            <h4>ℹ️ Dicas Importantes:</h4>
                            <ul>
                                <li>Mantenha seus comprovantes de pagamento organizados</li>
                                <li>Realize pagamentos regularmente para liberar o cashback dos clientes</li>
                                <li>Em caso de rejeição, verifique o motivo e faça um novo pagamento</li>
                                <li>O valor da comissão é sempre calculado sobre o valor efetivamente pago pelo cliente</li>
                                <li>Entre em contato com o suporte em caso de dúvidas sobre pagamentos</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Detalhes de Pagamento -->
    <div id="paymentDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📋 Detalhes do Pagamento</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <!-- Conteúdo será carregado via JavaScript -->
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="<?php echo JS_URL; ?>/main.js?v=<?php echo ASSETS_VERSION; ?>"></script>
    <script>
        // Função para visualizar detalhes do pagamento
        function viewPaymentDetails(paymentId) {
            // Mostrar modal com loading
            const modal = document.getElementById('paymentDetailsModal');
            const content = document.getElementById('paymentDetailsContent');
            
            content.innerHTML = '<div style="text-align: center; padding: 40px;"><div class="loading-spinner"></div><p>Carregando detalhes...</p></div>';
            modal.style.display = 'block';
            
            // Fazer requisição para buscar detalhes
            fetch(`<?php echo SITE_URL; ?>/api/get-payment-details.php?payment_id=${paymentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status) {
                        displayPaymentDetails(data.data);
                    } else {
                        content.innerHTML = `<div class="error-message">${data.message}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    content.innerHTML = '<div class="error-message">Erro ao carregar detalhes do pagamento.</div>';
                });
        }
        
        // Função para exibir detalhes do pagamento
        function displayPaymentDetails(data) {
            const { payment, transactions } = data;
            const content = document.getElementById('paymentDetailsContent');
            
            let html = `
                <div class="payment-info-section">
                    <h3>💰 Informações do Pagamento</h3>
                    <div class="payment-details-grid">
                        <div class="detail-item">
                            <span class="detail-label">ID do Pagamento:</span>
                            <span class="detail-value">#${payment.id}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Data/Hora:</span>
                            <span class="detail-value">${formatDate(payment.data_registro)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Valor Total das Vendas:</span>
                            <span class="detail-value">R$ ${formatCurrency(payment.valor_vendas_originais)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total de Saldo Usado:</span>
                            <span class="detail-value">R$ ${formatCurrency(payment.total_saldo_usado)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Comissão Paga (10%):</span>
                            <span class="detail-value">R$ ${formatCurrency(payment.valor_total)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Método de Pagamento:</span>
                            <span class="detail-value">${formatPaymentMethod(payment.metodo_pagamento)}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value status-${payment.status}">${formatStatus(payment.status)}</span>
                        </div>
                    </div>
                </div>
            `;
            
            // Informações de aprovação/rejeição
            if (payment.status === 'aprovado' || payment.status === 'rejeitado') {
                html += `
                    <div class="approval-section">
                        <h3>${payment.status === 'aprovado' ? '✅ Informações de Aprovação' : '❌ Motivo da Rejeição'}</h3>
                        <div class="approval-details">
                            ${payment.data_aprovacao ? `
                            <div class="approval-item">
                                <span class="approval-label">Data:</span>
                                <span class="approval-value">${formatDate(payment.data_aprovacao)}</span>
                            </div>
                            ` : ''}
                            ${payment.observacao_admin ? `
                            <div class="approval-item">
                                <span class="approval-label">Observação do Administrador:</span>
                                <span class="approval-value">${escapeHtml(payment.observacao_admin)}</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            }
            
            // Lista de transações incluídas no pagamento com informações de saldo
            html += `
                <div class="transactions-section">
                    <h3>📋 Transações Incluídas (${transactions.length})</h3>
                    ${transactions.length > 0 ? `
                    <div class="transactions-table-container">
                        <table class="transactions-table">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Cliente</th>
                                    <th>Data</th>
                                    <th>Valor Venda</th>
                                    <th>Saldo Usado</th>
                                    <th>Valor Efetivo</th>
                                    <th>Cashback</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${transactions.map(transaction => {
                                    const saldoUsado = parseFloat(transaction.saldo_usado) || 0;
                                    const valorEfetivo = parseFloat(transaction.valor_total) - saldoUsado;
                                    return `
                                    <tr>
                                        <td>
                                            <code>${escapeHtml(transaction.codigo_transacao || 'N/A')}</code>
                                        </td>
                                        <td>
                                            <div class="cliente-info">
                                                <strong>${escapeHtml(transaction.cliente_nome || 'N/A')}</strong>
                                                <small>${escapeHtml(transaction.cliente_email || '')}</small>
                                                ${saldoUsado > 0 ? `<br><span class="saldo-usado-tag">💰 Usou saldo</span>` : ''}
                                            </div>
                                        </td>
                                        <td>${formatDate(transaction.data_transacao)}</td>
                                        <td>R$ ${formatCurrency(transaction.valor_total)}</td>
                                        <td>R$ ${formatCurrency(saldoUsado)}</td>
                                        <td><strong>R$ ${formatCurrency(valorEfetivo)}</strong></td>
                                        <td>R$ ${formatCurrency(transaction.valor_cashback)}</td>
                                    </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                    ` : '<p>Nenhuma transação encontrada.</p>'}
                </div>
            `;
            
            content.innerHTML = html;
        }
        
        // Função para verificar status do pagamento PIX
        function checkPaymentStatus(paymentId, mpPaymentId) {
            // Fazer requisição para verificar status
            fetch(`<?php echo SITE_URL; ?>/api/check-payment-status.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    payment_id: paymentId,
                    mp_payment_id: mpPaymentId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    alert('Status verificado com sucesso! A página será recarregada.');
                    location.reload();
                } else {
                    alert('Erro ao verificar status: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao verificar status do pagamento.');
            });
        }
        
        // Funções auxiliares de formatação
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR') + ' ' + date.toLocaleTimeString('pt-BR');
        }
        
        function formatCurrency(value) {
            if (!value) return '0,00';
            return parseFloat(value).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        function formatPaymentMethod(method) {
            const methods = {
                'pix_mercadopago': 'PIX Mercado Pago',
                'transferencia': 'Transferência Bancária',
                'pix_openpix': 'PIX OpenPix'
            };
            return methods[method] || method;
        }
        
        function formatStatus(status) {
            const statuses = {
                'pendente': 'Pendente',
                'pix_aguardando': 'PIX Aguardando',
                'aprovado': 'Aprovado',
                'rejeitado': 'Rejeitado',
                'cancelado': 'Cancelado'
            };
            return statuses[status] || status;
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Event listeners para modal
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('paymentDetailsModal');
            const closeBtn = document.querySelector('.close');
            
            closeBtn.onclick = function() {
                modal.style.display = 'none';
            }
            
            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>
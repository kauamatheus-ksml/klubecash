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
                $payment['valor_vendas_originais'] = $payment['valor_total'] / 0.1;
            }
        }
        
        $totalPagamentos++;
        $valorTotalPagamentos += $payment['valor_total'];
        $valorTotalVendasOriginais += $payment['valor_vendas_originais'];
        $totalSaldoUsado += $payment['valor_saldo_usado'] ?? 0;
        
        switch ($payment['status']) {
            case 'aprovado':
                $totalAprovados++;
                break;
            case 'pendente':
                $totalPendentes++;
                break;
            case 'rejeitado':
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
    
    <!-- CSS -->
    <link rel="stylesheet" href="../../assets/css/main.css">
    <link rel="stylesheet" href="../../assets/css/store.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    
    <!-- Fontes -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .payment-history-page {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.approved {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }

        .stat-card.pending {
            background: linear-gradient(135deg, #FF9800 0%, #f57c00 100%);
        }

        .stat-card.rejected {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
        }

        .stat-card.total {
            background: linear-gradient(135deg, #2196F3 0%, #1976d2 100%);
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .filters-section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .filters-title {
            color: #2c3e50;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            color: #555;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .filter-group select,
        .filter-group input {
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #667eea;
        }

        .filter-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .payments-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }

        .payments-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px 25px;
            border-bottom: 1px solid #dee2e6;
        }

        .payments-title {
            color: #2c3e50;
            font-size: 20px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .payment-card {
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .payment-card:last-child {
            border-bottom: none;
        }

        .payment-card:hover {
            background-color: #f8f9fa;
        }

        .payment-header {
            padding: 20px 25px;
            cursor: pointer;
            display: flex;
            justify-content: between;
            align-items: center;
            gap: 20px;
        }

        .payment-info {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            align-items: center;
        }

        .payment-field {
            display: flex;
            flex-direction: column;
        }

        .payment-field label {
            color: #666;
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .payment-field span {
            color: #2c3e50;
            font-weight: 600;
            font-size: 14px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.aprovado {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.pendente {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.rejeitado {
            background: #f8d7da;
            color: #721c24;
        }

        .payment-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 12px;
            border-radius: 6px;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .payment-details {
            padding: 0 25px 20px 25px;
            background: #f8f9fa;
            display: none;
        }

        .payment-details.show {
            display: block;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .detail-card h4 {
            color: #2c3e50;
            font-size: 16px;
            margin: 0 0 15px 0;
            font-weight: 600;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px solid #f0f0f0;
        }

        .detail-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
            font-size: 14px;
        }

        .detail-value {
            color: #2c3e50;
            font-weight: 600;
            font-size: 14px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
            gap: 10px;
        }

        .pagination a,
        .pagination span {
            padding: 10px 15px;
            margin: 0 2px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination a {
            background: #f8f9fa;
            color: #667eea;
            border: 1px solid #dee2e6;
        }

        .pagination a:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }

        .pagination .current {
            background: #667eea;
            color: white;
            border: 1px solid #667eea;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
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
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            padding: 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }

        .close {
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            background: none;
            border: none;
            color: white;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.3s ease;
        }

        .close:hover {
            background-color: rgba(255,255,255,0.1);
        }

        .modal-body {
            padding: 25px;
        }

        .receipt-container {
            text-align: center;
        }

        .receipt-image-container {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            display: inline-block;
            max-width: 100%;
        }

        .receipt-image-container img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .info-section h4 {
            color: #2c3e50;
            margin: 0 0 15px 0;
            font-size: 16px;
            font-weight: 600;
        }

        .info-section ul {
            margin: 0;
            padding-left: 20px;
        }

        .info-section li {
            color: #555;
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .help-section {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            padding: 25px;
            border-radius: 15px;
            margin-top: 30px;
        }

        .help-title {
            color: #2c3e50;
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .expand-icon {
            transition: transform 0.3s ease;
            font-size: 18px;
            color: #667eea;
        }

        .expanded .expand-icon {
            transform: rotate(180deg);
        }

        @media (max-width: 768px) {
            .payment-history-page {
                padding: 15px;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .filter-actions {
                flex-direction: column;
            }

            .payment-info {
                grid-template-columns: 1fr;
            }

            .payment-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                margin: 5% auto;
            }
        }
    </style>
</head>
<body>
    <?php include_once '../../views/components/sidebar.php'; ?>
    
    <div class="main-content">
        <?php include_once '../../views/components/navbar.php'; ?>
        
        <div class="payment-history-page">
            <!-- Cabeçalho da Página -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-history"></i>
                    Histórico de Pagamentos
                </h1>
            </div>

            <!-- Estatísticas -->
            <div class="stats-grid">
                <div class="stat-card total">
                    <div class="stat-number"><?php echo $totalPagamentos; ?></div>
                    <div class="stat-label">Total de Pagamentos</div>
                </div>
                <div class="stat-card approved">
                    <div class="stat-number"><?php echo $totalAprovados; ?></div>
                    <div class="stat-label">Aprovados</div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-number"><?php echo $totalPendentes; ?></div>
                    <div class="stat-label">Pendentes</div>
                </div>
                <div class="stat-card rejected">
                    <div class="stat-number"><?php echo $totalRejeitados; ?></div>
                    <div class="stat-label">Rejeitados</div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filters-section">
                <h3 class="filters-title">
                    <i class="fas fa-filter"></i>
                    Filtros de Busca
                </h3>
                
                <form method="GET" action="">
                    <div class="filters-grid">
                        <div class="filter-group">
                            <label for="status">Status</label>
                            <select name="status" id="status">
                                <option value="">Todos os Status</option>
                                <option value="pendente" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pendente') ? 'selected' : ''; ?>>Pendente</option>
                                <option value="aprovado" <?php echo (isset($_GET['status']) && $_GET['status'] === 'aprovado') ? 'selected' : ''; ?>>Aprovado</option>
                                <option value="rejeitado" <?php echo (isset($_GET['status']) && $_GET['status'] === 'rejeitado') ? 'selected' : ''; ?>>Rejeitado</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="data_inicio">Data Início</label>
                            <input type="date" name="data_inicio" id="data_inicio" value="<?php echo isset($_GET['data_inicio']) ? htmlspecialchars($_GET['data_inicio']) : ''; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="data_fim">Data Fim</label>
                            <input type="date" name="data_fim" id="data_fim" value="<?php echo isset($_GET['data_fim']) ? htmlspecialchars($_GET['data_fim']) : ''; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label for="metodo_pagamento">Método</label>
                            <select name="metodo_pagamento" id="metodo_pagamento">
                                <option value="">Todos os Métodos</option>
                                <option value="pix_mercadopago" <?php echo (isset($_GET['metodo_pagamento']) && $_GET['metodo_pagamento'] === 'pix_mercadopago') ? 'selected' : ''; ?>>PIX Mercado Pago</option>
                                <option value="pix_openpix" <?php echo (isset($_GET['metodo_pagamento']) && $_GET['metodo_pagamento'] === 'pix_openpix') ? 'selected' : ''; ?>>PIX OpenPix</option>
                                <option value="transferencia" <?php echo (isset($_GET['metodo_pagamento']) && $_GET['metodo_pagamento'] === 'transferencia') ? 'selected' : ''; ?>>Transferência</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                            Filtrar
                        </button>
                        <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Limpar Filtros
                        </a>
                    </div>
                </form>
            </div>

            <!-- Lista de Pagamentos -->
            <div class="payments-container">
                <div class="payments-header">
                    <h3 class="payments-title">
                        <i class="fas fa-credit-card"></i>
                        Seus Pagamentos
                    </h3>
                </div>

                <?php if ($result['status'] && !empty($result['data']['pagamentos'])): ?>
                    <?php foreach ($result['data']['pagamentos'] as $payment): ?>
                        <div class="payment-card">
                            <div class="payment-header" onclick="togglePaymentDetails(<?php echo $payment['id']; ?>)">
                                <div class="payment-info">
                                    <div class="payment-field">
                                        <label>ID do Pagamento</label>
                                        <span>#<?php echo $payment['id']; ?></span>
                                    </div>
                                    
                                    <div class="payment-field">
                                        <label>Data</label>
                                        <span><?php echo date('d/m/Y H:i', strtotime($payment['data_registro'])); ?></span>
                                    </div>
                                    
                                    <div class="payment-field">
                                        <label>Valor</label>
                                        <span>R$ <?php echo number_format($payment['valor_total'], 2, ',', '.'); ?></span>
                                    </div>
                                    
                                    <div class="payment-field">
                                        <label>Método</label>
                                        <span>
                                            <?php
                                            switch($payment['metodo_pagamento']) {
                                                case 'pix_mercadopago':
                                                    echo 'PIX Mercado Pago';
                                                    break;
                                                case 'pix_openpix':
                                                    echo 'PIX OpenPix';
                                                    break;
                                                case 'transferencia':
                                                    echo 'Transferência';
                                                    break;
                                                default:
                                                    echo ucfirst($payment['metodo_pagamento']);
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    
                                    <div class="payment-field">
                                        <label>Status</label>
                                        <span class="status-badge <?php echo $payment['status']; ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="payment-actions">
                                    <?php if ($payment['status'] === 'pendente' && $payment['metodo_pagamento'] === 'pix_mercadopago' && !empty($payment['mp_payment_id'])): ?>
                                        <button class="btn btn-small btn-info" onclick="event.stopPropagation(); checkPaymentStatus(<?php echo $payment['id']; ?>, '<?php echo $payment['mp_payment_id']; ?>')">
                                            <i class="fas fa-sync"></i>
                                            Verificar Status
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($payment['comprovante'])): ?>
                                        <button class="btn btn-small btn-info" onclick="event.stopPropagation(); viewReceipt('<?php echo $payment['comprovante']; ?>')">
                                            <i class="fas fa-file-image"></i>
                                            Ver Comprovante
                                        </button>
                                    <?php endif; ?>
                                    
                                    <i class="fas fa-chevron-down expand-icon"></i>
                                </div>
                            </div>
                            
                            <div class="payment-details" id="details-<?php echo $payment['id']; ?>">
                                <div class="details-grid">
                                    <div class="detail-card">
                                        <h4><i class="fas fa-info-circle"></i> Informações do Pagamento</h4>
                                        <div class="detail-item">
                                            <span class="detail-label">ID do Pagamento:</span>
                                            <span class="detail-value">#<?php echo $payment['id']; ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Data de Registro:</span>
                                            <span class="detail-value"><?php echo date('d/m/Y H:i:s', strtotime($payment['data_registro'])); ?></span>
                                        </div>
                                        <?php if ($payment['data_aprovacao']): ?>
                                            <div class="detail-item">
                                                <span class="detail-label">Data de Aprovação:</span>
                                                <span class="detail-value"><?php echo date('d/m/Y H:i:s', strtotime($payment['data_aprovacao'])); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Método de Pagamento:</span>
                                            <span class="detail-value">
                                                <?php
                                                switch($payment['metodo_pagamento']) {
                                                    case 'pix_mercadopago':
                                                        echo 'PIX via Mercado Pago';
                                                        break;
                                                    case 'pix_openpix':
                                                        echo 'PIX via OpenPix';
                                                        break;
                                                    case 'transferencia':
                                                        echo 'Transferência Bancária';
                                                        break;
                                                    default:
                                                        echo ucfirst($payment['metodo_pagamento']);
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($payment['mp_payment_id'])): ?>
                                            <div class="detail-item">
                                                <span class="detail-label">ID Mercado Pago:</span>
                                                <span class="detail-value"><?php echo $payment['mp_payment_id']; ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="detail-card">
                                        <h4><i class="fas fa-calculator"></i> Valores Detalhados</h4>
                                        <div class="detail-item">
                                            <span class="detail-label">Valor Total do Pagamento:</span>
                                            <span class="detail-value">R$ <?php echo number_format($payment['valor_total'], 2, ',', '.'); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Vendas Originais:</span>
                                            <span class="detail-value">R$ <?php echo number_format($payment['valor_vendas_originais'], 2, ',', '.'); ?></span>
                                        </div>
                                        <?php if (isset($payment['valor_saldo_usado']) && $payment['valor_saldo_usado'] > 0): ?>
                                            <div class="detail-item">
                                                <span class="detail-label">Saldo Usado:</span>
                                                <span class="detail-value">R$ <?php echo number_format($payment['valor_saldo_usado'], 2, ',', '.'); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Quantidade de Transações:</span>
                                            <span class="detail-value"><?php echo $payment['qtd_transacoes']; ?> transação(ões)</span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($payment['observacao_admin']): ?>
                                        <div class="detail-card" style="grid-column: 1 / -1;">
                                            <h4><i class="fas fa-comment"></i> Observação do Administrador</h4>
                                            <p style="margin: 0; color: #555; line-height: 1.6;">
                                                <?php echo nl2br(htmlspecialchars($payment['observacao_admin'])); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Paginação -->
                    <?php if (isset($result['data']['pagination']) && $result['data']['pagination']['total_pages'] > 1): ?>
                        <div class="pagination">
                            <?php
                            $pagination = $result['data']['pagination'];
                            $current_page = $pagination['current_page'];
                            $total_pages = $pagination['total_pages'];
                            
                            // Construir URL com filtros
                            $query_params = $_GET;
                            unset($query_params['page']);
                            $base_url = STORE_PAYMENT_HISTORY_URL . '?' . http_build_query($query_params) . '&page=';
                            
                            if ($current_page > 1): ?>
                                <a href="<?php echo $base_url . ($current_page - 1); ?>">&laquo; Anterior</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                                <?php if ($i == $current_page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="<?php echo $base_url . $i; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <a href="<?php echo $base_url . ($current_page + 1); ?>">Próxima &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-credit-card"></i>
                        <h3>Nenhum pagamento encontrado</h3>
                        <p>Você ainda não realizou nenhum pagamento ou não há pagamentos que correspondam aos filtros aplicados.</p>
                        <a href="<?php echo STORE_PAYMENT_URL; ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Realizar Primeiro Pagamento
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Seção de Ajuda -->
            <div class="help-section">
                <h3 class="help-title">
                    <i class="fas fa-question-circle"></i>
                    Como Funciona o Sistema de Pagamentos
                </h3>
                
                <div class="info-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div class="info-section">
                        <h4>💳 Métodos de Pagamento:</h4>
                        <ul>
                            <li>PIX via Mercado Pago (recomendado para aprovação automática)</li>
                            <li>PIX via OpenPix (alternativa rápida e segura)</li>
                            <li>Transferência bancária (requer aprovação manual)</li>
                        </ul>
                    </div>
                    
                    <div class="info-section">
                        <h4>📊 Status dos Pagamentos:</h4>
                        <ul>
                            <li><strong>Pendente:</strong> Aguardando confirmação do pagamento</li>
                            <li><strong>Aprovado:</strong> Pagamento confirmado e cashback liberado</li>
                            <li><strong>Rejeitado:</strong> Pagamento não foi aceito</li>
                        </ul>
                    </div>
                    
                    <div class="info-section">
                        <h4>ℹ️ Dicas Importantes:</h4>
                        <ul>
                            <li>Mantenha seus comprovantes de pagamento organizados</li>
                            <li>Realize pagamentos regularmente para liberar o cashback dos clientes</li>
                            <li>Em caso de rejeição, verifique o motivo e faça um novo pagamento</li>
                            <li>O valor da comissão é sempre calculado sobre o valor efetivamente pago pelo cliente</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Detalhes de Pagamento -->
    <div id="paymentDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Detalhes do Pagamento</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body" id="paymentDetailsContent">
                <p>Carregando detalhes...</p>
            </div>
        </div>
    </div>
    
    <!-- Modal de Comprovante -->
    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Comprovante de Pagamento</h2>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body receipt-container" id="receiptContent">
                <div class="receipt-image-container">
                    <img id="receiptImage" src="" alt="Comprovante de Pagamento">
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Função para alternar detalhes do pagamento
        function togglePaymentDetails(paymentId) {
            const details = document.getElementById(`details-${paymentId}`);
            const card = details.closest('.payment-card');
            const icon = card.querySelector('.expand-icon');
            
            if (details.classList.contains('show')) {
                details.classList.remove('show');
                card.classList.remove('expanded');
                details.style.display = 'none';
            } else {
                details.classList.add('show');
                card.classList.add('expanded');
                details.style.display = 'block';
            }
        }

        // Função para visualizar comprovante
        function viewReceipt(receiptPath) {
            const modal = document.getElementById('receiptModal');
            const img = document.getElementById('receiptImage');
            
            img.src = '../../' + receiptPath;
            modal.style.display = 'block';
        }

        // Função para mostrar notificação
        function showNotification(message, type = 'info') {
            // Implementar sistema de notificações aqui
            alert(message);
        }

        // Função para formatar dinheiro
        function formatMoney(value) {
            return parseFloat(value).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Fechar modais quando clicar no X ou fora do modal
        document.addEventListener('DOMContentLoaded', function() {
            const modals = document.querySelectorAll('.modal');
            const closes = document.querySelectorAll('.close');

            closes.forEach(close => {
                close.addEventListener('click', function() {
                    this.closest('.modal').style.display = 'none';
                });
            });

            modals.forEach(modal => {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        this.style.display = 'none';
                    }
                });
            });

            // Adicionar interatividade aos headers dos cards
            const paymentHeaders = document.querySelectorAll('.payment-header');
            paymentHeaders.forEach(header => {
                header.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = '#f8f9fa';
                });
                
                header.addEventListener('mouseleave', function() {
                    if (!card.classList.contains('expanded')) {
                        this.style.backgroundColor = '';
                    }
                });
            });
        });
        
        // Verificar status de pagamento PIX
        async function checkPaymentStatus(paymentId, mpPaymentId) {
            try {
                const response = await fetch(`../../api/mercadopago.php?action=status&mp_payment_id=${mpPaymentId}`);
                const result = await response.json();
                
                if (result.status && result.data.status === 'approved') {
                    alert('✅ Pagamento PIX confirmado! A página será recarregada para mostrar o status atualizado.');
                    window.location.reload();
                } else if (result.data && result.data.status === 'pending') {
                    alert('⏳ Pagamento PIX ainda está pendente. Continue aguardando ou tente novamente em alguns minutos.');
                } else if (result.data && result.data.status === 'rejected') {
                    alert('❌ Pagamento PIX foi rejeitado. Você precisará fazer um novo pagamento.');
                } else {
                    alert('ℹ️ Status atual: ' + (result.data ? result.data.status : 'Desconhecido'));
                }
            } catch (error) {
                console.error('Erro ao verificar status:', error);
                alert('Erro ao verificar status do pagamento.');
            }
        }
    </script>
</body>
</html>
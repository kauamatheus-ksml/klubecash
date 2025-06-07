<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';

session_start();

// Verificar se usuário está logado e é loja
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'loja') {
    header('Location: ' . LOGIN_URL);
    exit;
}

$userId = $_SESSION['user_id'];
$transactionIds = $_GET['transactions'] ?? '';

if (empty($transactionIds)) {
    header('Location: ' . STORE_PENDING_TRANSACTIONS_URL);
    exit;
}

$transactionIds = explode(',', $transactionIds);

try {
    $db = Database::getConnection();
    
    // Buscar transações selecionadas
    $placeholders = str_repeat('?,', count($transactionIds) - 1) . '?';
    $stmt = $db->prepare("
        SELECT t.*, c.nome as cliente_nome, c.email as cliente_email
        FROM transacoes_cashback t
        LEFT JOIN usuarios c ON t.usuario_id = c.id
        JOIN lojas l ON t.loja_id = l.id
        WHERE t.id IN ($placeholders) 
        AND l.usuario_id = ? 
        AND t.status IN ('pendente', 'pagamento_pendente')
        ORDER BY t.data_transacao DESC
    ");
    
    $params = array_merge($transactionIds, [$userId]);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($transactions)) {
        header('Location: ' . STORE_PENDING_TRANSACTIONS_URL);
        exit;
    }
    
    // Calcular totais
    $valorTotalVendas = 0;
    $valorTotalComissao = 0;
    $saldoUsadoTotal = 0;
    
    foreach ($transactions as $transaction) {
        $valorTotalVendas += $transaction['valor_total'];
        $valorTotalComissao += $transaction['valor_total'] * 0.10;
        $saldoUsadoTotal += $transaction['saldo_usado'] ?? 0;
    }
    
} catch (Exception $e) {
    error_log('Erro ao carregar formulário de pagamento: ' . $e->getMessage());
    header('Location: ' . STORE_PENDING_TRANSACTIONS_URL);
    exit;
}

$pageTitle = 'Pagamento de Comissões';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/store.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/payment-form.css">
</head>
<body class="store-area">
    
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <div class="container">
        <div class="payment-form-container">
            
            <!-- Header -->
            <div class="form-header">
                <h1>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="1" y="3" width="15" height="13"></rect>
                        <polygon points="16,8 20,8 20,14 16,14"></polygon>
                        <circle cx="1.5" cy="7.5" r="1.5"></circle>
                    </svg>
                    Pagamento de Comissões
                </h1>
                <p>Confirme os dados e escolha a forma de pagamento</p>
            </div>
            
            <!-- Resumo do Pagamento -->
            <div class="payment-summary">
                <h2>Resumo do Pagamento</h2>
                
                <div class="summary-grid">
                    <div class="summary-item">
                        <span class="label">Transações selecionadas:</span>
                        <span class="value"><?php echo count($transactions); ?></span>
                    </div>
                    
                    <div class="summary-item">
                        <span class="label">Valor total das vendas:</span>
                        <span class="value">R$ <?php echo number_format($valorTotalVendas, 2, ',', '.'); ?></span>
                    </div>
                    
                    <div class="summary-item">
                        <span class="label">Saldo usado pelos clientes:</span>
                        <span class="value">R$ <?php echo number_format($saldoUsadoTotal, 2, ',', '.'); ?></span>
                    </div>
                    
                    <div class="summary-item highlight">
                        <span class="label">Valor a pagar (10%):</span>
                        <span class="value">R$ <?php echo number_format($valorTotalComissao, 2, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Formulário de Pagamento -->
            <form id="paymentForm" class="payment-form">
                <input type="hidden" name="transaction_ids" value="<?php echo implode(',', $transactionIds); ?>">
                
                <div class="form-section">
                    <h3>Método de Pagamento</h3>
                    
                    <div class="payment-methods">
                        <label class="payment-method active">
                            <input type="radio" name="payment_method" value="pix_openpix" checked>
                            <div class="method-content">
                                <div class="method-icon">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="1" y="3" width="15" height="13"></rect>
                                        <polygon points="16,8 20,8 20,14 16,14"></polygon>
                                        <circle cx="1.5" cy="7.5" r="1.5"></circle>
                                    </svg>
                                </div>
                                <div class="method-info">
                                    <h4>PIX</h4>
                                    <p>Pagamento instantâneo via PIX</p>
                                    <small>Aprovação automática em até 2 minutos</small>
                                </div>
                                <div class="method-badge">
                                    <span>Recomendado</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="btn btn-secondary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15,18 9,12 15,6"></polyline>
                        </svg>
                        Voltar
                    </a>
                    
                    <button type="submit" class="btn btn-primary" id="submitPayment">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9,11 12,14 22,4"></polyline>
                            <path d="M21,12v7a2,2 0,0 1,-2,2H5a2,2 0,0 1,-2,-2V5a2,2 0,0 1,2,-2h11"></path>
                        </svg>
                        Confirmar Pagamento
                    </button>
                </div>
            </form>
            
            <!-- Lista de Transações -->
            <div class="transactions-details">
                <h3>Transações incluídas (<?php echo count($transactions); ?>)</h3>
                
                <div class="transactions-table-container">
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th>Valor Venda</th>
                                <th>Saldo Usado</th>
                                <th>Comissão</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td>
                                    <code><?php echo htmlspecialchars($transaction['codigo_transacao'] ?? 'N/A'); ?></code>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($transaction['cliente_nome'] ?? 'Cliente não identificado'); ?>
                                    <?php if (!empty($transaction['cliente_email'])): ?>
                                        <br><small><?php echo htmlspecialchars($transaction['cliente_email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($transaction['data_transacao'])); ?></td>
                                <td>R$ <?php echo number_format($transaction['valor_total'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($transaction['saldo_usado'] ?? 0, 2, ',', '.'); ?></td>
                                <td class="highlight">R$ <?php echo number_format($transaction['valor_total'] * 0.10, 2, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- Loading Modal -->
    <div id="loadingModal" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="loading-spinner"></div>
            <p>Processando pagamento...</p>
        </div>
    </div>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/payment-form.js"></script>
    
    <script>
        window.paymentConfig = {
            baseUrl: '<?php echo SITE_URL; ?>',
            paymentController: '<?php echo SITE_URL; ?>/controllers/PaymentController.php',
            valorTotal: <?php echo $valorTotalComissao; ?>,
            transactionIds: <?php echo json_encode($transactionIds); ?>
        };
    </script>
    
</body>
</html>
<?php
// views/stores/payment.php
// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/TransactionController.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é uma loja
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'loja') {
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

// Verificar se viemos da página de comissões pendentes
$selectedTransactions = [];
$totalValue = 0;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'payment_form') {
        // Receber transações selecionadas da página anterior
        if (isset($_POST['transacoes']) && is_array($_POST['transacoes'])) {
            $selectedTransactions = $_POST['transacoes'];
            
            // Buscar dados das transações selecionadas
            if (!empty($selectedTransactions)) {
                $placeholders = implode(',', array_fill(0, count($selectedTransactions), '?'));
                $stmt = $db->prepare("
                    SELECT t.*, u.nome as cliente_nome 
                    FROM transacoes_cashback t
                    JOIN usuarios u ON t.usuario_id = u.id
                    WHERE t.id IN ($placeholders) AND t.loja_id = ? AND t.status = 'pendente'
                ");
                
                $params = array_merge($selectedTransactions, [$storeId]);
                $stmt->execute($params);
                $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($transactions as $transaction) {
                    $totalValue += $transaction['valor_cashback'];
                }
            }
        } else {
            $error = 'Nenhuma transação selecionada.';
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'process_payment') {
        // Processar o pagamento
        $transactionIds = $_POST['transaction_ids'] ?? '';
        $metodoPagamento = $_POST['metodo_pagamento'] ?? '';
        $numeroReferencia = $_POST['numero_referencia'] ?? '';
        $observacao = $_POST['observacao'] ?? '';
        $valorTotal = floatval($_POST['valor_total'] ?? 0);
        
        if (empty($transactionIds) || empty($metodoPagamento) || $valorTotal <= 0) {
            $error = 'Dados obrigatórios não informados.';
        } else {
            // Upload do comprovante
            $comprovante = '';
            if (isset($_FILES['comprovante']) && $_FILES['comprovante']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../uploads/comprovantes/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileInfo = pathinfo($_FILES['comprovante']['name']);
                $extension = strtolower($fileInfo['extension']);
                
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'pdf'])) {
                    $comprovante = 'comprovante_' . $storeId . '_' . time() . '.' . $extension;
                    move_uploaded_file($_FILES['comprovante']['tmp_name'], $uploadDir . $comprovante);
                }
            }
            
            // Preparar dados do pagamento
            $paymentData = [
                'loja_id' => $storeId,
                'transacoes' => explode(',', $transactionIds),
                'valor_total' => $valorTotal,
                'metodo_pagamento' => $metodoPagamento,
                'numero_referencia' => $numeroReferencia,
                'comprovante' => $comprovante,
                'observacao' => $observacao
            ];
            
            // Registrar pagamento
            $result = TransactionController::registerPayment($paymentData);
            
            if ($result['status']) {
                $success = $result['message'];
                // Limpar dados da sessão
                $selectedTransactions = [];
                $totalValue = 0;
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Se não temos transações selecionadas, redirecionar
if (empty($selectedTransactions) && !$success && !$error) {
    header('Location: ' . STORE_PENDING_TRANSACTIONS_URL);
    exit;
}

// Buscar dados das transações para exibir
$transactions = [];
if (!empty($selectedTransactions)) {
    $placeholders = implode(',', array_fill(0, count($selectedTransactions), '?'));
    $stmt = $db->prepare("
        SELECT t.*, u.nome as cliente_nome 
        FROM transacoes_cashback t
        JOIN usuarios u ON t.usuario_id = u.id
        WHERE t.id IN ($placeholders) AND t.loja_id = ?
    ");
    
    $params = array_merge($selectedTransactions, [$storeId]);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recalcular total
    $totalValue = 0;
    foreach ($transactions as $transaction) {
        $totalValue += $transaction['valor_cashback'];
    }
}

$activeMenu = 'payment';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <title>Realizar Pagamento - Klube Cash</title>
    <style>
        /* CSS similar ao da página de transações pendentes, adaptado para pagamento */
        :root {
            --primary-color: #FF7A00;
            --primary-dark: #E06E00;
            --primary-light: #FFF0E6;
            --secondary-color: #2A3F54;
            --success-color: #28A745;
            --danger-color: #DC3545;
            --white: #FFFFFF;
            --light-gray: #F8F9FA;
            --medium-gray: #6C757D;
            --dark-gray: #343A40;
            --border-radius: 12px;
            --shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F5F7FA;
            margin: 0;
            padding: 0;
            color: var(--dark-gray);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
            min-height: 100vh;
        }
        
        .dashboard-header {
            margin-bottom: 2rem;
        }
        
        .dashboard-header h1 {
            font-size: 1.75rem;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }
        
        .subtitle {
            color: var(--medium-gray);
            font-size: 1rem;
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert.success {
            background-color: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }
        
        .alert.error {
            background-color: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }
        
        .card {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            border-bottom: 1px solid #E1E5E9;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--secondary-color);
            margin: 0;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-gray);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: var(--medium-gray);
            color: var(--white);
        }
        
        .btn-secondary:hover {
            background-color: #5A6C7D;
        }
        
        .payment-summary {
            background: var(--primary-light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .summary-item:last-child {
            margin-bottom: 0;
            font-weight: 700;
            font-size: 1.1rem;
            padding-top: 0.75rem;
            border-top: 1px solid #FFB366;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #E1E5E9;
        }
        
        .table th {
            background-color: var(--light-gray);
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include_once '../components/sidebar-store.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="dashboard-header">
            <h1>Realizar Pagamento</h1>
            <p class="subtitle">Pague as comissões devidas para liberar o cashback aos seus clientes</p>
        </div>
        
        <?php if (!empty($success)): ?>
            <div class="alert success">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <?php echo htmlspecialchars($success); ?>
                <div style="margin-left: auto;">
                    <a href="<?php echo STORE_PAYMENT_HISTORY_URL; ?>" class="btn btn-secondary">Ver Histórico</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert error">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($transactions)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Transações Selecionadas</h2>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th>Valor Venda</th>
                                <th>Comissão</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($transaction['codigo_transacao'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['cliente_nome']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($transaction['data_transacao'])); ?></td>
                                    <td>R$ <?php echo number_format($transaction['valor_total'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($transaction['valor_cashback'], 2, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="payment-summary">
                    <div class="summary-item">
                        <span>Transações selecionadas:</span>
                        <span><?php echo count($transactions); ?></span>
                    </div>
                    <div class="summary-item">
                        <span>Valor total a pagar:</span>
                        <span>R$ <?php echo number_format($totalValue, 2, ',', '.'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Dados do Pagamento</h2>
                </div>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="process_payment">
                    <input type="hidden" name="transaction_ids" value="<?php echo implode(',', $selectedTransactions); ?>">
                    <input type="hidden" name="valor_total" value="<?php echo $totalValue; ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="metodo_pagamento">Método de Pagamento *</label>
                            <select id="metodo_pagamento" name="metodo_pagamento" required>
                                <option value="">Selecione o método</option>
                                <option value="pix">PIX</option>
                                <option value="transferencia">Transferência Bancária</option>
                                <option value="ted">TED</option>
                                <option value="boleto">Boleto</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="numero_referencia">Número de Referência</label>
                            <input type="text" id="numero_referencia" name="numero_referencia" 
                                   placeholder="Número da transação, ID do PIX, etc.">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="comprovante">Comprovante de Pagamento *</label>
                        <input type="file" id="comprovante" name="comprovante" accept="image/*,.pdf" required>
                        <small style="display: block; margin-top: 0.5rem; color: var(--medium-gray);">
                            Formatos aceitos: JPG, PNG, PDF (máx. 5MB)
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="observacao">Observações</label>
                        <textarea id="observacao" name="observacao" rows="3" 
                                  placeholder="Informações adicionais sobre o pagamento..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Confirmar Pagamento</button>
                        <a href="<?php echo STORE_PENDING_TRANSACTIONS_URL; ?>" class="btn btn-secondary">Voltar</a>
                    </div>
                </form>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Informações Importantes</h2>
                </div>
                <div style="color: var(--medium-gray); line-height: 1.6;">
                    <p>• O pagamento será analisado pela nossa equipe em até 24 horas após o envio.</p>
                    <p>• Após a aprovação, o cashback será automaticamente liberado para os clientes.</p>
                    <p>• Em caso de rejeição, você receberá uma notificação com o motivo e poderá enviar um novo comprovante.</p>
                    <p>• Mantenha o comprovante original até a confirmação da aprovação.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
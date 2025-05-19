<?php
// views/stores/register-transaction.php
// Incluir arquivos de configuração
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/StoreController.php';
require_once '../../controllers/TransactionController.php';
require_once '../../controllers/CommissionController.php';

// Iniciar sessão e verificar autenticação
session_start();

// Verificar se o usuário está logado
if (!AuthController::isAuthenticated()) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Você precisa fazer login para acessar esta página.'));
    exit;
}

// Verificar se o usuário é do tipo loja
if (!AuthController::isStore()) {
    header('Location: ' . CLIENT_DASHBOARD_URL . '?error=' . urlencode('Acesso restrito a lojas parceiras.'));
    exit;
}

// Obter ID do usuário logado
$userId = AuthController::getCurrentUserId();

// Obter dados da loja associada ao usuário
$db = Database::getConnection();
$storeQuery = $db->prepare("SELECT * FROM lojas WHERE usuario_id = :usuario_id");
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

// Verificar se o formulário foi enviado
$success = false;
$error = '';
$transactionData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $clientEmail = $_POST['cliente_email'] ?? '';
    $valorTotal = $_POST['valor_total'] ?? '';
    $codigoTransacao = $_POST['codigo_transacao'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $dataTransacao = $_POST['data_transacao'] ?? date('Y-m-d H:i:s');
    $usarSaldo = isset($_POST['usar_saldo']) && $_POST['usar_saldo'] === 'sim';
    $valorSaldoUsado = floatval($_POST['valor_saldo_usado'] ?? 0);
    
    // Buscar usuário pelo email
    $userQuery = $db->prepare("SELECT id, nome FROM usuarios WHERE email = :email AND tipo = :tipo AND status = :status");
    $userQuery->bindParam(':email', $clientEmail);
    $tipoCliente = USER_TYPE_CLIENT;
    $userQuery->bindParam(':tipo', $tipoCliente);
    $status = USER_ACTIVE;
    $userQuery->bindParam(':status', $status);
    $userQuery->execute();
    
    if ($userQuery->rowCount() === 0) {
        $error = 'Cliente não encontrado ou não está ativo. Verifique o email informado.';
    } else {
        $client = $userQuery->fetch(PDO::FETCH_ASSOC);
        
        // Se vai usar saldo, verificar se tem saldo suficiente
        if ($usarSaldo && $valorSaldoUsado > 0) {
            require_once '../../models/CashbackBalance.php';
            $balanceModel = new CashbackBalance();
            $saldoDisponivel = $balanceModel->getStoreBalance($client['id'], $storeId);
            
            if ($saldoDisponivel < $valorSaldoUsado) {
                $error = 'Saldo insuficiente. Cliente possui R$ ' . number_format($saldoDisponivel, 2, ',', '.') . ' disponível.';
            } else {
                // Ajustar o valor total da transação descontando o saldo usado
                $valorTotal = $valorTotal - $valorSaldoUsado;
                
                if ($valorTotal < 0) {
                    $error = 'O valor do saldo usado não pode ser maior que o valor total da venda.';
                }
            }
        }
        
        if (empty($error)) {
            // Preparar dados da transação
            $transactionData = [
                'usuario_id' => $client['id'],
                'loja_id' => $storeId,
                'valor_total' => $valorTotal,
                'codigo_transacao' => $codigoTransacao,
                'descricao' => $descricao,
                'data_transacao' => $dataTransacao,
                'usar_saldo' => $usarSaldo,
                'valor_saldo_usado' => $valorSaldoUsado
            ];
            
            // Registrar transação
            $result = TransactionController::registerTransaction($transactionData);
            
            if ($result['status']) {
                // Se usou saldo, debitar do saldo do cliente
                if ($usarSaldo && $valorSaldoUsado > 0) {
                    $balanceModel = new CashbackBalance();
                    $descricaoUso = "Uso do saldo na compra - Código: " . $codigoTransacao;
                    $balanceModel->useBalance($client['id'], $storeId, $valorSaldoUsado, $descricaoUso, $result['data']['transaction_id']);
                }
                
                $success = true;
                $transactionData = [];
            } else {
                $error = $result['message'];
            }
        }
    }
}

// Definir menu ativo
$activeMenu = 'register-transaction';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Venda - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    
    <style>
        /* Estilos existentes + novos estilos para busca de cliente */
        :root {
            --primary-color: #FF7A00;
            --primary-dark: #E06E00;
            --primary-light: #FFF0E6;
            --secondary-color: #2A3F54;
            --success-color: #28A745;
            --warning-color: #FFC107; 
            --danger-color: #DC3545;
            --info-color: #17A2B8;
            --light-gray: #F8F9FA;
            --medium-gray: #6C757D;
            --dark-gray: #343A40;
            --white: #FFFFFF;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #F5F7FA;
    color: var(--dark-gray);
    line-height: 1.5;
    margin: 0;
    padding: 0;
}

/* Layout do dashboard */
.dashboard-container {
    display: flex;
    min-height: 100vh;
}

.main-content {
    flex: 1;
    padding: 1.5rem;
    margin-left: 250px; /* Largura da sidebar */
    transition: margin-left 0.3s ease;
}

/* Cabeçalho */
.dashboard-header {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.dashboard-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--secondary-color);
    margin-bottom: 0.5rem;
}

.welcome-user {
    color: var(--medium-gray);
    font-size: 1rem;
}

/* Alertas */
.alert {
    background-color: var(--white);
    border-radius: var(--border-radius);
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
    border-left: 4px solid;
}

.alert.success {
    border-color: var(--success-color);
}

.alert.success svg {
    color: var(--success-color);
}

.alert.error {
    border-color: var(--danger-color);
}

.alert.error svg {
    color: var(--danger-color);
}

.alert h4 {
    margin: 0 0 0.35rem 0;
    font-size: 1.1rem;
    color: var(--dark-gray);
}

.alert p {
    margin: 0;
    color: var(--medium-gray);
    font-size: 0.9rem;
}

.btn-success {
    background-color: var(--success-color);
    color: var(--white);
    font-weight: 600;
    padding: 8px 15px;
    border-radius: 5px;
    text-decoration: none;
    transition: background-color 0.3s;
    white-space: nowrap;
}

.btn-success:hover {
    background-color: #218838;
    transform: translateY(-2px);
}

/* Card de conteúdo */
.content-card {
    background-color: var(--white);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
}

.form-wrapper {
    max-width: 1000px;
    margin: 0 auto;
}

/* Formulário */
.form-row {
    margin-bottom: 1.5rem;
}

.form-row.two-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--secondary-color);
}

.form-group input, 
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid #E1E5EA;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: var(--transition);
    background-color: var(--white);
}

.form-group input:focus, 
.form-group textarea:focus,
.form-group select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.1);
    outline: none;
}

.form-group small {
    display: block;
    margin-top: 0.35rem;
    color: var(--medium-gray);
    font-size: 0.85rem;
}

/* Calculadora de cashback */
.cashback-calculator {
    background-color: var(--light-gray);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin: 1.5rem 0 2rem;
}

.cashback-calculator h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: var(--secondary-color);
    font-size: 1.2rem;
    font-weight: 600;
}

.cashback-details {
    border: 1px solid #E1E5EA;
    border-radius: 8px;
    background-color: var(--white);
    overflow: hidden;
}

.cashback-item {
    display: flex;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #E1E5EA;
}

.cashback-item:last-child {
    border-bottom: none;
}

.cashback-item.total {
    background-color: #F5F7FA;
    font-weight: 700;
}

.cashback-label {
    color: var(--secondary-color);
}

.cashback-value {
    font-weight: 600;
}

.cashback-note {
    margin-top: 0.75rem;
    font-size: 0.9rem;
    color: var(--medium-gray);
}

/* Botões */
.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: var(--border-radius);
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
    cursor: pointer;
    transition: var(--transition);
    border: none;
}

.btn-primary {
    background-color: var(--primary-color);
    color: var(--white);
}

.btn-primary:hover {
    background-color: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 122, 0, 0.25);
}

.btn-secondary {
    background-color: #E1E5EA;
    color: var(--dark-gray);
}

.btn-secondary:hover {
    background-color: #D1D5DB;
    transform: translateY(-2px);
}

/* Seção de ajuda */
.help-section {
    background-color: var(--white);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow-md);
    margin-bottom: 2rem;
}

.help-section h3 {
    font-size: 1.25rem;
    color: var(--secondary-color);
    margin-top: 0;
    margin-bottom: 1.25rem;
    font-weight: 600;
}

.accordion {
    border: 1px solid #E1E5EA;
    border-radius: 8px;
    overflow: hidden;
}

.accordion-item {
    border-bottom: 1px solid #E1E5EA;
}

.accordion-item:last-child {
    border-bottom: none;
}

.accordion-header {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem;
    background-color: var(--white);
    border: none;
    text-align: left;
    font-size: 1rem;
    font-weight: 600;
    color: var(--secondary-color);
    cursor: pointer;
    transition: var(--transition);
}

.accordion-header:hover {
    background-color: var(--light-gray);
}

.accordion-icon {
    font-size: 1.5rem;
    font-weight: 400;
    transition: var(--transition);
}

.accordion-item.active .accordion-icon {
    transform: rotate(45deg);
}

.accordion-content {
    padding: 0 1.25rem;
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.accordion-content p {
    padding-bottom: 1.25rem;
    margin: 0;
    color: var(--medium-gray);
}

/* Responsividade */
@media (max-width: 1199.98px) {
    .form-row.two-columns {
        gap: 1rem;
    }
}

@media (max-width: 991.98px) {
    .main-content {
        margin-left: 0; /* Remove a margem quando a sidebar é ocultada */
    }
    
    .dashboard-header {
        margin-top: 60px;
    }
}

@media (max-width: 767.98px) {
    .form-row.two-columns {
        grid-template-columns: 1fr;
        gap: 0;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
    
    .alert {
        flex-direction: column;
        text-align: center;
        align-items: center;
    }
    
    .alert .btn {
        margin-left: 0;
        margin-top: 1rem;
        width: 100%;
    }
}

@media (max-width: 575.98px) {
    .main-content {
        padding: 1rem;
    }
    
    .dashboard-title {
        font-size: 1.5rem;
    }
    
    .content-card, 
    .help-section {
        padding: 1.25rem;
    }
    
    .cashback-calculator {
        padding: 1rem;
    }
    
    .cashback-item {
        padding: 0.75rem 1rem;
    }
}

        /* Busca de cliente */
        .client-search-container {
            position: relative;
        }

        .email-input-group {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .email-input-wrapper {
            flex: 1;
        }

        .search-client-btn {
            padding: 0.75rem 1rem;
            background-color: var(--info-color);
            color: var(--white);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            white-space: nowrap;
            min-width: 120px;
        }

        .search-client-btn:hover {
            background-color: #138496;
        }

        .search-client-btn:disabled {
            background-color: var(--medium-gray);
            cursor: not-allowed;
        }

        .client-info-card {
            margin-top: 15px;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background-color: var(--white);
            display: none;
        }

        .client-info-card.success {
            border-color: var(--success-color);
            background-color: #f0fff4;
        }

        .client-info-card.warning {
            border-color: var(--warning-color);
            background-color: #fffbf0;
        }

        .client-info-card.error {
            border-color: var(--danger-color);
            background-color: #fff5f5;
        }

        .client-info-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .client-info-icon {
            width: 20px;
            height: 20px;
        }

        .client-info-title {
            font-weight: 600;
            margin: 0;
        }

        .client-info-details {
            margin-left: 30px;
        }

        .client-info-item {
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .client-info-label {
            font-weight: 500;
            color: var(--medium-gray);
        }

        .client-info-value {
            color: var(--dark-gray);
        }

        .balance-info {
            margin-top: 15px;
            padding: 15px;
            background-color: var(--light-gray);
            border-radius: 8px;
        }

        .balance-amount {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--success-color);
        }

        .use-balance-section {
            margin-top: 15px;
            padding: 15px;
            border: 2px dashed var(--info-color);
            border-radius: 8px;
            background-color: #f8fffe;
        }

        .use-balance-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .use-balance-toggle input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .use-balance-inputs {
            display: none;
            margin-top: 10px;
        }

        .use-balance-inputs.active {
            display: block;
        }

        .balance-slider-container {
            margin-top: 10px;
        }

        .balance-slider {
            width: 100%;
            -webkit-appearance: none;
            height: 5px;
            border-radius: 5px;
            background: #d3d3d3;
            outline: none;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .balance-slider:hover {
            opacity: 1;
        }

        .balance-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--info-color);
            cursor: pointer;
        }

        .balance-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--info-color);
            cursor: pointer;
            border: none;
        }

        .balance-values {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--medium-gray);
            margin-top: 5px;
        }

        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsividade para mobile */
        @media (max-width: 768px) {
            .email-input-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-client-btn {
                width: 100%;
                margin-top: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Incluir sidebar/menu lateral -->
        <?php include_once '../components/sidebar-store.php'; ?>
        
        <div class="main-content" id="mainContent">
            <div class="dashboard-header">
                <div>
                    <h1 class="dashboard-title">Registrar Venda</h1>
                    <p class="welcome-user">Registre suas vendas para oferecer cashback aos clientes do Klube Cash</p>
                </div>
            </div>
            
            <?php if ($success): ?>
            <div class="alert success">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <div>
                    <h4>Transação registrada com sucesso!</h4>
                    <p>O cashback será liberado para o cliente assim que o pagamento da comissão for realizado e aprovado.</p>
                </div>
                <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="btn btn-success">Registrar Nova</a>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
            <div class="alert error">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <div>
                    <h4>Erro ao registrar transação</h4>
                    <p><?php echo $error; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="content-card">
                <div class="form-wrapper">
                    <form id="transactionForm" method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cliente_email">Email do Cliente*</label>
                                <div class="client-search-container">
                                    <div class="email-input-group">
                                        <div class="email-input-wrapper">
                                            <input type="email" id="cliente_email" name="cliente_email" 
                                                   placeholder="Email do cliente cadastrado no Klube Cash" required
                                                   value="<?php echo isset($transactionData['cliente_email']) ? htmlspecialchars($transactionData['cliente_email']) : ''; ?>">
                                            <small>O cliente deve estar cadastrado no Klube Cash</small>
                                        </div>
                                        <button type="button" id="searchClientBtn" class="search-client-btn">
                                            <span class="btn-text">Buscar Cliente</span>
                                            <span class="loading-spinner" style="display: none;"></span>
                                        </button>
                                    </div>
                                    
                                    <!-- Card de informações do cliente -->
                                    <div id="clientInfoCard" class="client-info-card">
                                        <div class="client-info-header">
                                            <svg class="client-info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="12" cy="7" r="4"></circle>
                                            </svg>
                                            <h4 class="client-info-title" id="clientInfoTitle">Informações do Cliente</h4>
                                        </div>
                                        <div class="client-info-details" id="clientInfoDetails">
                                            <!-- Será preenchido dinamicamente -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-row two-columns">
                            <div class="form-group">
                                <label for="valor_total">Valor Total da Venda (R$)*</label>
                                <input type="number" id="valor_total" name="valor_total" min="<?php echo MIN_TRANSACTION_VALUE; ?>" step="0.01" required
                                value="<?php echo isset($transactionData['valor_total']) ? htmlspecialchars($transactionData['valor_total']) : ''; ?>"
                                placeholder="Valor total da compra">
                                <small>Valor mínimo: R$ <?php echo number_format(MIN_TRANSACTION_VALUE, 2, ',', '.'); ?></small>
                            </div>
                            
                            <div class="form-group">
                                <label for="codigo_transacao">Código da Transação*</label>
                                <input type="text" id="codigo_transacao" name="codigo_transacao" required
                                value="<?php echo isset($transactionData['codigo_transacao']) ? htmlspecialchars($transactionData['codigo_transacao']) : ''; ?>"
                                placeholder="Código/número da venda no seu sistema">
                                <small>Identificador único da venda no seu sistema</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="data_transacao">Data da Venda</label>
                                <input type="datetime-local" id="data_transacao" name="data_transacao"
                                value="<?php echo isset($transactionData['data_transacao']) ? date('Y-m-d\TH:i', strtotime($transactionData['data_transacao'])) : date('Y-m-d\TH:i'); ?>">
                                <small>Deixe em branco para usar a data/hora atual</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="descricao">Descrição (opcional)</label>
                                <textarea id="descricao" name="descricao" rows="3" placeholder="Detalhes adicionais sobre a venda"><?php echo isset($transactionData['descricao']) ? htmlspecialchars($transactionData['descricao']) : ''; ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Campos ocultos para uso de saldo -->
                        <input type="hidden" id="usar_saldo" name="usar_saldo" value="nao">
                        <input type="hidden" id="valor_saldo_usado" name="valor_saldo_usado" value="0">
                        
                        <div class="cashback-calculator">
                            <h3>Simulação de Cashback</h3>
                            <div class="cashback-details">
                                <div class="cashback-item">
                                    <span class="cashback-label">Valor da Venda:</span>
                                    <span class="cashback-value" id="display-valor-venda">R$ 0,00</span>
                                </div>
                                <div class="cashback-item">
                                    <span class="cashback-label">Cashback do Cliente (<?php echo DEFAULT_CASHBACK_CLIENT; ?>%):</span>
                                    <span class="cashback-value" id="display-valor-cliente">R$ 0,00</span>
                                </div>
                                <div class="cashback-item">
                                    <span class="cashback-label">Comissão Klube Cash (<?php echo DEFAULT_CASHBACK_ADMIN; ?>%):</span>
                                    <span class="cashback-value" id="display-valor-admin">R$ 0,00</span>
                                </div>
                                <div class="cashback-item total">
                                    <span class="cashback-label">Valor Total a Pagar (<?php echo DEFAULT_CASHBACK_TOTAL; ?>%):</span>
                                    <span class="cashback-value" id="display-valor-total">R$ 0,00</span>
                                </div>
                            </div>
                            <div class="cashback-note">
                                <p>* O valor de cashback será liberado para o cliente após o pagamento da comissão.</p>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Registrar Venda</button>
                            <a href="<?php echo STORE_DASHBOARD_URL; ?>" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Seção de ajuda existente -->
            <div class="help-section">
                <h3>Dúvidas Frequentes</h3>
                <div class="accordion">
                    <div class="accordion-item">
                        <button class="accordion-header">
                            <span>Como o cliente recebe o cashback?</span>
                            <span class="accordion-icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Após o registro da venda, o cliente visualizará o cashback como "pendente" em seu painel. Quando sua loja realizar o pagamento da comissão para o Klube Cash, o valor será liberado automaticamente para o cliente.</p>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <button class="accordion-header">
                            <span>Posso usar o saldo do cliente na venda?</span>
                            <span class="accordion-icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Sim! Se o cliente possui saldo de cashback em sua loja, você pode usar parte ou todo o saldo na nova venda. Basta buscar o cliente pelo email e escolher o valor a ser usado.</p>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <button class="accordion-header">
                            <span>E se o cliente não estiver cadastrado?</span>
                            <span class="accordion-icon">+</span>
                        </button>
                        <div class="accordion-content">
                            <p>Apenas clientes já cadastrados no Klube Cash podem receber cashback. Você pode convidar seus clientes a se cadastrarem em nossa plataforma para começarem a ganhar cashback em suas compras.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Variáveis globais
        let clientData = null;
        let clientBalance = 0;
        const storeId = <?php echo $storeId; ?>;
        
        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            const valorInput = document.getElementById('valor_total');
            const emailInput = document.getElementById('cliente_email');
            const searchBtn = document.getElementById('searchClientBtn');
            
            // Event listeners
            valorInput.addEventListener('input', atualizarSimulacao);
            searchBtn.addEventListener('click', buscarCliente);
            emailInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscarCliente();
                }
            });
            
            // Accordion para ajuda
            setupAccordion();
            
            // Inicializar simulação
            atualizarSimulacao();
        });
        
        // Função para buscar cliente
        async function buscarCliente() {
            const email = document.getElementById('cliente_email').value.trim();
            const searchBtn = document.getElementById('searchClientBtn');
            const clientInfoCard = document.getElementById('clientInfoCard');
            
            if (!email) {
                alert('Por favor, digite um email válido');
                return;
            }
            
            // Mostrar loading
            searchBtn.disabled = true;
            searchBtn.querySelector('.btn-text').textContent = 'Buscando...';
            searchBtn.querySelector('.loading-spinner').style.display = 'inline-block';
            
            try {
                const response = await fetch('../../api/store-client-search.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'search_client',
                        email: email,
                        store_id: storeId
                    })
                });
                
                const data = await response.json();
                
                if (data.status) {
                    clientData = data.data;
                    clientBalance = data.data.saldo || 0;
                    mostrarInfoCliente(data.data);
                } else {
                    mostrarErroCliente(data.message);
                }
            } catch (error) {
                console.error('Erro ao buscar cliente:', error);
                mostrarErroCliente('Erro ao buscar cliente. Tente novamente.');
            } finally {
                // Esconder loading
                searchBtn.disabled = false;
                searchBtn.querySelector('.btn-text').textContent = 'Buscar Cliente';
                searchBtn.querySelector('.loading-spinner').style.display = 'none';
            }
        }
        
        // Função para mostrar informações do cliente
        function mostrarInfoCliente(client) {
            const clientInfoCard = document.getElementById('clientInfoCard');
            const clientInfoTitle = document.getElementById('clientInfoTitle');
            const clientInfoDetails = document.getElementById('clientInfoDetails');
            
            // Remover classes de erro
            clientInfoCard.className = 'client-info-card success';
            clientInfoCard.style.display = 'block';
            
            clientInfoTitle.textContent = 'Cliente Encontrado';
            
            let detailsHTML = `
                <div class="client-info-item">
                    <span class="client-info-label">Nome:</span>
                    <span class="client-info-value">${client.nome}</span>
                </div>
                <div class="client-info-item">
                    <span class="client-info-label">Email:</span>
                    <span class="client-info-value">${client.email}</span>
                </div>
                <div class="client-info-item">
                    <span class="client-info-label">Status:</span>
                    <span class="client-info-value">Cliente ativo</span>
                </div>
            `;
            
            if (client.saldo > 0) {
                detailsHTML += `
                    <div class="balance-info">
                        <div class="client-info-item">
                            <span class="client-info-label">Saldo disponível nesta loja:</span>
                            <span class="balance-amount">R$ ${formatCurrency(client.saldo)}</span>
                        </div>
                        <div class="use-balance-section">
                            <div class="use-balance-toggle">
                                <input type="checkbox" id="useBalanceCheck" onchange="toggleUseBalance()">
                                <label for="useBalanceCheck">Usar saldo do cliente nesta venda</label>
                            </div>
                            <div class="use-balance-inputs" id="useBalanceInputs">
                                <div class="form-group">
                                    <label for="balanceAmountInput">Valor a usar do saldo</label>
                                    <div class="input-group">
                                        <span class="input-group-text">R$</span>
                                        <input type="number" id="balanceAmountInput" 
                                               step="0.01" min="0" max="${client.saldo}"
                                               placeholder="0,00" onchange="updateBalanceSlider()">
                                    </div>
                                </div>
                                <div class="balance-slider-container">
                                    <input type="range" id="balanceSlider" class="balance-slider"
                                           min="0" max="${client.saldo}" step="0.01" value="0"
                                           oninput="updateBalanceInput()">
                                    <div class="balance-values">
                                        <span>R$ 0,00</span>
                                        <span>R$ ${formatCurrency(client.saldo)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                detailsHTML += `
                    <div class="client-info-item">
                        <span class="client-info-label">Saldo:</span>
                        <span class="client-info-value">Nenhum saldo disponível nesta loja</span>
                    </div>
                `;
            }
            
            clientInfoDetails.innerHTML = detailsHTML;
        }
        
        // Função para mostrar erro na busca do cliente
        function mostrarErroCliente(message) {
            const clientInfoCard = document.getElementById('clientInfoCard');
            const clientInfoTitle = document.getElementById('clientInfoTitle');
            const clientInfoDetails = document.getElementById('clientInfoDetails');
            
            clientInfoCard.className = 'client-info-card error';
            clientInfoCard.style.display = 'block';
            
            clientInfoTitle.textContent = 'Cliente Não Encontrado';
            clientInfoDetails.innerHTML = `
                <div class="client-info-item">
                    <span class="client-info-value">${message}</span>
                </div>
                <div class="client-info-item">
                    <span class="client-info-value">Verifique se o email está correto e se o cliente está cadastrado no Klube Cash.</span>
                </div>
            `;
            
            // Limpar dados do cliente
            clientData = null;
            clientBalance = 0;
        }
        
        // Função para alternar uso de saldo
        function toggleUseBalance() {
            const useBalanceCheck = document.getElementById('useBalanceCheck');
            const useBalanceInputs = document.getElementById('useBalanceInputs');
            const usarSaldoHidden = document.getElementById('usar_saldo');
            
            if (useBalanceCheck.checked) {
                useBalanceInputs.classList.add('active');
                usarSaldoHidden.value = 'sim';
            } else {
                useBalanceInputs.classList.remove('active');
                usarSaldoHidden.value = 'nao';
                document.getElementById('valor_saldo_usado').value = '0';
                document.getElementById('balanceAmountInput').value = '';
                document.getElementById('balanceSlider').value = '0';
                atualizarSimulacao();
            }
        }
        
        // Função para atualizar slider baseado no input
        function updateBalanceSlider() {
            const balanceInput = document.getElementById('balanceAmountInput');
            const balanceSlider = document.getElementById('balanceSlider');
            const valorSaldoUsado = document.getElementById('valor_saldo_usado');
            
            let value = parseFloat(balanceInput.value) || 0;
            
            // Limitar ao máximo permitido
            if (value > clientBalance) {
                value = clientBalance;
                balanceInput.value = value.toFixed(2);
            }
            
            balanceSlider.value = value;
            valorSaldoUsado.value = value;
            
            atualizarSimulacao();
        }
        
        // Função para atualizar input baseado no slider
        function updateBalanceInput() {
            const balanceInput = document.getElementById('balanceAmountInput');
            const balanceSlider = document.getElementById('balanceSlider');
            const valorSaldoUsado = document.getElementById('valor_saldo_usado');
            
            const value = parseFloat(balanceSlider.value);
            balanceInput.value = value.toFixed(2);
            valorSaldoUsado.value = value;
            
            atualizarSimulacao();
        }
        
        // Função para formatar valores como moeda
        function formatCurrency(value) {
            return parseFloat(value).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }
        
        // Função para calcular e atualizar a simulação
        function atualizarSimulacao() {
            const valorInput = document.getElementById('valor_total');
            const displayValorVenda = document.getElementById('display-valor-venda');
            const displayValorCliente = document.getElementById('display-valor-cliente');
            const displayValorAdmin = document.getElementById('display-valor-admin');
            const displayValorTotal = document.getElementById('display-valor-total');
            
            let valor = parseFloat(valorInput.value) || 0;
            
            // Subtrair saldo usado se aplicável
            const usarSaldo = document.getElementById('usar_saldo').value === 'sim';
            const valorSaldoUsado = parseFloat(document.getElementById('valor_saldo_usado').value) || 0;
            
            if (usarSaldo && valorSaldoUsado > 0) {
                valor = Math.max(0, valor - valorSaldoUsado);
            }
            
            // Porcentagens de cashback
            const porcentagemCliente = <?php echo DEFAULT_CASHBACK_CLIENT; ?>;
            const porcentagemAdmin = <?php echo DEFAULT_CASHBACK_ADMIN; ?>;
            const porcentagemTotal = <?php echo DEFAULT_CASHBACK_TOTAL; ?>;
            
            const valorCliente = valor * porcentagemCliente / 100;
            const valorAdmin = valor * porcentagemAdmin / 100;
            const valorTotal = valor * porcentagemTotal / 100;
            
            // Mostrar valor original da venda
            displayValorVenda.textContent = `R$ ${formatCurrency(parseFloat(valorInput.value) || 0)}`;
            displayValorCliente.textContent = `R$ ${formatCurrency(valorCliente)}`;
            displayValorAdmin.textContent = `R$ ${formatCurrency(valorAdmin)}`;
            displayValorTotal.textContent = `R$ ${formatCurrency(valorTotal)}`;
            
            // Mostrar informação sobre saldo usado
            if (usarSaldo && valorSaldoUsado > 0) {
                displayValorVenda.textContent += ` (- R$ ${formatCurrency(valorSaldoUsado)} saldo)`;
            }
        }
        
        // Função para setup do accordion
        function setupAccordion() {
            const accordionItems = document.querySelectorAll('.accordion-item');
            
            accordionItems.forEach(item => {
                const header = item.querySelector('.accordion-header');
                const content = item.querySelector('.accordion-content');
                const icon = item.querySelector('.accordion-icon');
                
                header.addEventListener('click', () => {
                    const isActive = item.classList.contains('active');
                    
                    // Fechar todos os itens
                    accordionItems.forEach(i => {
                        i.classList.remove('active');
                        i.querySelector('.accordion-content').style.maxHeight = '0';
                        i.querySelector('.accordion-icon').textContent = '+';
                    });
                    
                    // Se o item clicado não estava ativo, abri-lo
                    if (!isActive) {
                        item.classList.add('active');
                        content.style.maxHeight = content.scrollHeight + 'px';
                        icon.textContent = '-';
                    }
                });
            });
        }
        
        // Validação de formulário
        document.getElementById('transactionForm').addEventListener('submit', function(e) {
            const valorTotal = parseFloat(document.getElementById('valor_total').value) || 0;
            const valorSaldoUsado = parseFloat(document.getElementById('valor_saldo_usado').value) || 0;
            const minValue = <?php echo MIN_TRANSACTION_VALUE; ?>;
            
            if ((valorTotal - valorSaldoUsado) < 0) {
                e.preventDefault();
                alert('O valor do saldo usado não pode ser maior que o valor total da venda');
                return;
            }
            
            if (valorTotal < minValue) {
                e.preventDefault();
                alert(`O valor mínimo para transação é R$ ${minValue.toFixed(2).replace('.', ',')}`);
                document.getElementById('valor_total').focus();
                return;
            }
            
            if (!clientData) {
                e.preventDefault();
                alert('Por favor, busque o cliente antes de registrar a venda');
                return;
            }
        });
    </script>
</body>
</html>
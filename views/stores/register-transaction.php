<?php
// views/stores/register-transaction.php
// Incluir arquivos de configuração
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/StoreController.php'; // Se usado para buscar dados da loja
require_once '../../controllers/TransactionController.php';
// require_once '../../controllers/CommissionController.php'; // Se necessário

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

if ($storeQuery->rowCount() == 0) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Sua conta não está associada a nenhuma loja. Entre em contato com o suporte.'));
    exit;
}

$store = $storeQuery->fetch(PDO::FETCH_ASSOC);
$storeId = $store['id'];

$success = false;
$error = '';
$form_data_post = []; // Para repopular campos em caso de erro no POST, se necessário

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("FORM DEBUG: Dados POST recebidos: " . print_r($_POST, true));
    $form_data_post = $_POST; // Guardar dados para repopular

    $clientId = intval($_POST['cliente_id_hidden'] ?? 0);
    $valorTotal = floatval($_POST['valor_total'] ?? 0);
    $codigoTransacao = $_POST['codigo_transacao'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $dataTransacao = $_POST['data_transacao'] ?? date('Y-m-d H:i:s');
    $usarSaldo = isset($_POST['usar_saldo_hidden_submit']) && $_POST['usar_saldo_hidden_submit'] === 'sim'; // Campo específico para submissão
    $valorSaldoUsado = floatval($_POST['valor_saldo_usado_hidden_submit'] ?? 0); // Campo específico para submissão

    if ($clientId <= 0) {
        $error = 'Cliente não foi identificado corretamente. Por favor, tente novamente.';
    } else {
        $userQuery = $db->prepare("SELECT id, nome, email FROM usuarios WHERE id = :id AND tipo = :tipo AND status = :status");
        $userQuery->bindParam(':id', $clientId, PDO::PARAM_INT);
        $tipoClienteConst = USER_TYPE_CLIENT;
        $userQuery->bindParam(':tipo', $tipoClienteConst);
        $statusConst = USER_ACTIVE;
        $userQuery->bindParam(':status', $statusConst);
        $userQuery->execute();

        if ($userQuery->rowCount() === 0) {
            $error = 'Cliente não encontrado ou não está ativo. Verifique o cliente selecionado.';
        } else {
            $client = $userQuery->fetch(PDO::FETCH_ASSOC);

            // Validações adicionais antes de chamar registerTransaction
            if ($usarSaldo && $valorSaldoUsado > 0) {
                require_once '../../models/CashbackBalance.php';
                $balanceModel = new CashbackBalance();
                $saldoDisponivel = $balanceModel->getStoreBalance($client['id'], $storeId);

                if ($saldoDisponivel < $valorSaldoUsado) {
                    $error = 'Saldo insuficiente. Cliente possui R$ ' . number_format($saldoDisponivel, 2, ',', '.') . ' disponível.';
                } else if ($valorSaldoUsado > $valorTotal) {
                    $error = 'O valor do saldo usado não pode ser maior que o valor total da venda.';
                }
            }
             if (empty($codigoTransacao)) {
                $error = 'O código da transação é obrigatório.';
            }
            if ($valorTotal < MIN_TRANSACTION_VALUE) {
                 $error = 'O valor total da venda deve ser de no mínimo R$ ' . number_format(MIN_TRANSACTION_VALUE, 2, ',', '.');
            }


            if (empty($error)) {
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

                $result = TransactionController::registerTransaction($transactionData);

                if ($result['status']) {
                    $success = true;
                    $form_data_post = []; // Limpar dados após sucesso
                    // header('Location: ' . STORE_TRANSACTIONS_URL . '?success_register=1'); // Exemplo de redirecionamento
                    // exit;
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}

$activeMenu = 'register-transaction';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Venda - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/views/stores/register-transaction.css"> {/* Seu CSS base */}
    <style>
        /* ESTILOS PARA O WIZARD - Idealmente mover para register-transaction.css */
        .wizard-container {
            background: #fff;
            padding: 20px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            margin: 0 auto;
            max-width: 800px; /* Ajuste conforme necessário */
        }

        .wizard-progress {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            padding: 0;
            list-style: none;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            color: var(--medium-gray);
            position: relative;
            flex: 1;
        }
        .progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px; /* Ajustar para alinhar com o centro do círculo */
            left: calc(50% + 20px);
            width: calc(100% - 40px);
            height: 2px;
            background-color: var(--light-gray);
            z-index: -1;
        }

        .progress-step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: var(--light-gray);
            color: var(--medium-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 5px;
            border: 2px solid var(--light-gray);
            transition: background-color 0.3s, border-color 0.3s, color 0.3s;
        }

        .progress-step-label {
            font-size: var(--font-size-xs);
        }

        .progress-step.active .progress-step-number {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--white);
        }
         .progress-step.active .progress-step-label {
            color: var(--primary-color);
            font-weight: bold;
        }

        .progress-step.completed .progress-step-number {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: var(--white);
        }
        .progress-step.completed:not(:last-child)::after {
             background-color: var(--success-color);
        }


        .wizard-step {
            display: none; /* Esconder todos os passos por padrão */
            animation: fadeIn 0.5s ease-in-out;
        }

        .wizard-step.active {
            display: block; /* Mostrar passo ativo */
        }

        .wizard-step-header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .wizard-step-header .step-main-title {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }
        .wizard-step-header .step-subtitle {
            font-size: var(--font-size-base);
            color: var(--medium-gray);
        }


        .wizard-step-content {
            margin-bottom: 30px;
        }
        .wizard-step-content .form-group { /* Reutilizando seu estilo */
            margin-bottom: 20px;
        }

        .wizard-step-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .wizard-step-actions .btn { /* Reutilizando seu estilo */
            min-width: 120px;
        }
        
        .summary-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: var(--light-gray);
            border-radius: var(--border-radius-sm);
        }
        .summary-section h4 {
            margin-top: 0;
            margin-bottom: 10px;
            color: var(--secondary-color);
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: var(--font-size-sm);
        }
        .summary-item strong {
            color: var(--dark-gray);
        }
        .summary-item span {
            color: var(--medium-gray);
        }

        /* Herda estilos de register-transaction.css para campos, botões, etc. */
        /* Adicione aqui estilos específicos para o wizard se necessário */

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsividade básica para o wizard */
        @media (max-width: 768px) {
            .wizard-progress {
                font-size: 0.8em; /* Reduzir tamanho da fonte do progresso */
            }
            .progress-step-label {
                 display: none; /* Opcional: esconder labels em telas pequenas */
            }
            .progress-step:not(:last-child)::after {
                left: calc(50% + 15px);
                width: calc(100% - 30px);
            }
            .wizard-step-actions {
                flex-direction: column;
                gap: 10px;
            }
            .wizard-step-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include_once '../components/sidebar-store.php'; ?>
        
        <div class="main-content" id="mainContent">
            <div class="dashboard-header">
                <div>
                    <h1 class="dashboard-title">Registrar Nova Venda</h1>
                    <p class="welcome-user">Siga os passos para registrar uma venda e oferecer cashback.</p>
                </div>
            </div>
            
            <?php if ($success): ?>
            <div class="alert success">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <div><h4>Transação registrada com sucesso!</h4><p>O cashback será liberado para o cliente após o pagamento e aprovação da comissão.</p></div>
                <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="btn btn-success">Registrar Nova</a>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
            <div class="alert error">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <div><h4>Erro ao registrar transação</h4><p><?php echo htmlspecialchars($error); ?></p></div>
            </div>
            <?php endif; ?>

            <?php if (!$success): // Só mostrar o formulário/wizard se não houve sucesso no POST atual ?>
            <div class="wizard-container">
                <ul class="wizard-progress">
                    <li class="progress-step active" data-step="1">
                        <span class="progress-step-number">1</span>
                        <span class="progress-step-label">Cliente</span>
                    </li>
                    <li class="progress-step" data-step="2">
                        <span class="progress-step-number">2</span>
                        <span class="progress-step-label">Venda</span>
                    </li>
                    <li class="progress-step" data-step="3">
                        <span class="progress-step-number">3</span>
                        <span class="progress-step-label">Saldo</span>
                    </li>
                    <li class="progress-step" data-step="4">
                        <span class="progress-step-number">4</span>
                        <span class="progress-step-label">Revisão</span>
                    </li>
                </ul>

                <form id="transactionForm" method="POST" action="">
                    <input type="hidden" id="cliente_id_hidden" name="cliente_id_hidden" value="<?php echo htmlspecialchars($form_data_post['cliente_id_hidden'] ?? ''); ?>">
                    <input type="hidden" id="usar_saldo_hidden_submit" name="usar_saldo_hidden_submit" value="<?php echo htmlspecialchars($form_data_post['usar_saldo_hidden_submit'] ?? 'nao'); ?>">
                    <input type="hidden" id="valor_saldo_usado_hidden_submit" name="valor_saldo_usado_hidden_submit" value="<?php echo htmlspecialchars($form_data_post['valor_saldo_usado_hidden_submit'] ?? '0'); ?>">

                    <div class="wizard-step active" id="step-1">
                        <div class="wizard-step-header">
                            <h3 class="step-main-title">Passo 1: Identificar o Cliente</h3>
                            <p class="step-subtitle">Busque o cliente pelo Email ou CPF.</p>
                        </div>
                        <div class="wizard-step-content">
                            <div class="form-group">
                                <label for="search_term">Buscar Cliente (Email ou CPF)*</label>
                                <div class="client-search-container">
                                     <div class="email-input-group">
                                        <div class="email-input-wrapper">
                                            <input type="text" id="search_term" name="search_term_display" class="form-control" placeholder="Digite o Email ou CPF do cliente" value="<?php echo htmlspecialchars($form_data_post['search_term_display'] ?? ''); ?>">
                                            <small>O cliente deve estar cadastrado no Klube Cash.</small>
                                        </div>
                                        <button type="button" id="searchClientBtn" class="btn btn-info search-client-btn">
                                            <span class="btn-text">Buscar</span>
                                            <span class="loading-spinner" style="display: none;"></span>
                                        </button>
                                    </div>
                                    <div id="clientInfoCard" class="client-info-card" style="display:none;">
                                        {conteúdo do card do cliente aqui, preenchido via JS}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="wizard-step-actions">
                            <button type="button" class="btn btn-primary" id="goToStep2Btn" disabled>Avançar para Detalhes da Venda</button>
                        </div>
                    </div>

                    <div class="wizard-step" id="step-2">
                        <div class="wizard-step-header">
                             <h3 class="step-main-title">Passo 2: Detalhes da Venda</h3>
                             <p class="step-subtitle">Informe os dados principais da transação.</p>
                        </div>
                        <div class="wizard-step-content">
                            <div class="form-row two-columns">
                                <div class="form-group">
                                    <label for="valor_total">Valor Total da Venda (R$)*</label>
                                    <input type="number" id="valor_total" name="valor_total" class="form-control" min="<?php echo MIN_TRANSACTION_VALUE; ?>" step="0.01" placeholder="Ex: 50,00" value="<?php echo htmlspecialchars($form_data_post['valor_total'] ?? ''); ?>" required>
                                    <small>Mínimo: R$ <?php echo number_format(MIN_TRANSACTION_VALUE, 2, ',', '.'); ?></small>
                                </div>
                                <div class="form-group">
                                    <label for="codigo_transacao">Código da Transação*</label>
                                    <div class="codigo-input-group">
                                        <input type="text" id="codigo_transacao" name="codigo_transacao" class="form-control" placeholder="Seu código interno ou gere um" value="<?php echo htmlspecialchars($form_data_post['codigo_transacao'] ?? ''); ?>" required>
                                        <button type="button" id="generateCodeBtn" class="btn btn-secondary generate-code-btn" title="Gerar código automaticamente">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"><path fill="currentColor" d="M16.84 2.75a.75.75 0 0 0-1.05.21L6.53 15.79A.75.75 0 0 0 7.06 17h5.19a.75.75 0 0 0 .67-.41l4.69-8.32a.75.75 0 0 0-.1-.96l-1.9-1.91a.75.75 0 0 0-.53-.22Zm-.33 1.53l.8.8L13.6 11H9.6l3.91-6.93ZM5.06 17A.75.75 0 0 0 4.53 16l-2.6-4.63a.75.75 0 0 0-1.11.16L0 12.69V21.5a.75.75 0 0 0 .75.75h8.81a.75.75 0 0 0 .7-.41l.91-1.62a.75.75 0 0 0-.1-.96l-1.91-1.91a.75.75 0 0 0-.53-.22H5.06Zm-.84-.75H7.8l.8.8L5.54 21H1.5V14.3l.94-.53Z"/></svg>
                                            <span class="btn-text">Gerar</span>
                                        </button>
                                    </div>
                                    <small>Identificador único da sua venda.</small>
                                </div>
                            </div>
                            <div class="form-row two-columns">
                                <div class="form-group">
                                    <label for="data_transacao">Data da Venda</label>
                                    <input type="datetime-local" id="data_transacao" name="data_transacao" class="form-control" value="<?php echo htmlspecialchars(!empty($form_data_post['data_transacao']) ? date('Y-m-d\TH:i', strtotime($form_data_post['data_transacao'])) : date('Y-m-d\TH:i')); ?>">
                                    <small>Padrão: data e hora atuais.</small>
                                </div>
                                <div class="form-group">
                                    <label for="descricao">Descrição (Opcional)</label>
                                    <input type="text" id="descricao" name="descricao" class="form-control" placeholder="Ex: Compra de produto X" value="<?php echo htmlspecialchars($form_data_post['descricao'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="wizard-step-actions">
                            <button type="button" class="btn btn-secondary" id="backToStep1Btn">Voltar</button>
                            <button type="button" class="btn btn-primary" id="goToStep3Btn">Avançar para Uso de Saldo</button>
                        </div>
                    </div>

                    <div class="wizard-step" id="step-3">
                         <div class="wizard-step-header">
                            <h3 class="step-main-title">Passo 3: Usar Saldo do Cliente</h3>
                            <p class="step-subtitle">Verifique se o cliente deseja usar o saldo Klube Cash disponível nesta loja.</p>
                        </div>
                        <div class="wizard-step-content">
                            {A SEÇÃO DE SALDO DO SEU HTML ORIGINAL (id="saldoSection") ENTRARIA AQUI, adaptada}
                            <div id="saldoSectionWizard" class="saldo-section" style="display: none;">
                                <h3>💰 Usar Saldo do Cliente</h3>
                                <div class="saldo-info">
                                    <div class="saldo-disponivel">
                                        <span>Saldo disponível nesta loja: </span>
                                        <span id="saldoDisponivelWizard" class="saldo-value">R$ 0,00</span>
                                    </div>
                                    <div class="usar-saldo-toggle">
                                        <label class="toggle-switch">
                                            <input type="checkbox" id="usarSaldoCheckWizard">
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <span>Usar saldo nesta venda</span>
                                    </div>
                                </div>
                                <div id="saldoControlsWizard" class="saldo-controls" style="display: none;">
                                    <div class="form-group">
                                        <label for="valorSaldoUsadoWizard">Valor do saldo a usar (R$)</label>
                                        <input type="number" id="valorSaldoUsadoWizard" name="valor_saldo_usado_display" class="form-control" min="0" step="0.01" value="0">
                                        <small>Máximo: <span id="maxSaldoWizard">R$ 0,00</span></small>
                                    </div>
                                    <div class="saldo-buttons">
                                        <button type="button" id="usarTodoSaldoWizard" class="btn btn-outline-primary btn-sm btn-saldo">Usar Todo Saldo</button>
                                        <button type="button" id="usar50SaldoWizard" class="btn btn-outline-primary btn-sm btn-saldo">Usar 50%</button>
                                        <button type="button" id="limparSaldoWizard" class="btn btn-outline-secondary btn-sm btn-saldo">Limpar</button>
                                    </div>
                                    <div class="calculo-preview">
                                        <div class="calculo-item"><span>Valor original:</span><span id="valorOriginalWizard">R$ 0,00</span></div>
                                        <div class="calculo-item"><span>Saldo usado:</span><span id="valorSaldoUsadoPreviewWizard">R$ 0,00</span></div>
                                        <div class="calculo-item valor-final"><span>Valor a pagar:</span><span id="valorFinalWizard">R$ 0,00</span></div>
                                    </div>
                                </div>
                            </div>
                            <p id="semSaldoMsg" style="display:none; text-align:center; margin-top:20px; color: var(--medium-gray);">Este cliente não possui saldo disponível nesta loja.</p>
                        </div>
                        <div class="wizard-step-actions">
                            <button type="button" class="btn btn-secondary" id="backToStep2Btn">Voltar</button>
                            <button type="button" class="btn btn-primary" id="goToStep4Btn">Avançar para Revisão</button>
                        </div>
                    </div>

                    <div class="wizard-step" id="step-4">
                         <div class="wizard-step-header">
                            <h3 class="step-main-title">Passo 4: Revisão e Confirmação</h3>
                            <p class="step-subtitle">Confira todos os dados antes de registrar a venda.</p>
                        </div>
                        <div class="wizard-step-content">
                            <div class="summary-section">
                                <h4>Cliente</h4>
                                <div class="summary-item"><strong>Nome:</strong> <span id="summary_client_name">-</span></div>
                                <div class="summary-item"><strong>Email:</strong> <span id="summary_client_email">-</span></div>
                                <div class="summary-item" id="summary_client_cpf_item" style="display:none;"><strong>CPF:</strong> <span id="summary_client_cpf">-</span></div>
                            </div>
                            <div class="summary-section">
                                <h4>Detalhes da Venda</h4>
                                <div class="summary-item"><strong>Valor Total:</strong> <span id="summary_valor_total">-</span></div>
                                <div class="summary-item"><strong>Código:</strong> <span id="summary_codigo_transacao">-</span></div>
                                <div class="summary-item"><strong>Data:</strong> <span id="summary_data_transacao">-</span></div>
                                <div class="summary-item" id="summary_descricao_item" style="display:none;"><strong>Descrição:</strong> <span id="summary_descricao">-</span></div>
                            </div>
                            <div class="summary-section" id="summary_saldo_section" style="display:none;">
                                <h4>Uso de Saldo</h4>
                                <div class="summary-item"><strong>Saldo Usado:</strong> <span id="summary_saldo_usado">-</span></div>
                                <div class="summary-item"><strong>Valor Efetivamente Pago:</strong> <span id="summary_valor_pago_final">-</span></div>
                            </div>
                             <div class="cashback-calculator" id="summary_cashback_calculator">
                                <h3>Simulação de Comissão e Cashback</h3>
                                 {A SIMULAÇÃO DO SEU HTML ORIGINAL (class="cashback-details") ENTRARIA AQUI, com IDs diferentes para o resumo}
                                <div class="cashback-details">
                                    <div class="cashback-item"><span class="cashback-label">Valor da Venda Original:</span><span class="cashback-value" id="summary_display_valor_venda">R$ 0,00</span></div>
                                    <div class="cashback-item saldo-row" id="summary_cashback_saldo_row" style="display: none;"><span class="cashback-label">Saldo Usado:</span><span class="cashback-value" id="summary_display_saldo_usado">R$ 0,00</span></div>
                                    <div class="cashback-item"><span class="cashback-label">Valor Base para Comissão:</span><span class="cashback-value" id="summary_display_valor_pago">R$ 0,00</span></div>
                                    <div class="cashback-item"><span class="cashback-label">Cashback Cliente (5%):</span><span class="cashback-value" id="summary_display_valor_cliente">R$ 0,00</span></div>
                                    <div class="cashback-item"><span class="cashback-label">Receita Klube (5%):</span><span class="cashback-value" id="summary_display_valor_admin">R$ 0,00</span></div>
                                    <div class="cashback-item total"><span class="cashback-label">Sua Comissão (10%):</span><span class="cashback-value" id="summary_display_valor_total_comissao">R$ 0,00</span></div>
                                </div>
                                <div class="cashback-note"><p>* Comissão calculada sobre o valor base (após desconto de saldo).</p></div>
                            </div>
                        </div>
                        <div class="wizard-step-actions">
                            <button type="button" class="btn btn-secondary" id="backToStep3Btn">Voltar</button>
                            <button type="submit" class="btn btn-success">Confirmar e Registrar Venda</button>
                        </div>
                    </div>
                </form>
            </div>
            <?php endif; ?>

            
        </div>
    </div>
    
<script>
    // ========================================
    // VARIÁVEIS GLOBAIS DO WIZARD
    // ========================================
    let currentStep = 1;
    const totalSteps = 4;
    let clientData = null; // { id, nome, email, saldo, cpf (opcional) }
    let clientBalance = 0;
    const storeId = <?php echo $storeId; ?>;

    // ========================================
    // ELEMENTOS DO DOM (Seletores principais)
    // ========================================
    // Botões de navegação
    const goToStep2Btn = document.getElementById('goToStep2Btn');
    const backToStep1Btn = document.getElementById('backToStep1Btn');
    const goToStep3Btn = document.getElementById('goToStep3Btn');
    const backToStep2Btn = document.getElementById('backToStep2Btn');
    const goToStep4Btn = document.getElementById('goToStep4Btn');
    const backToStep3Btn = document.getElementById('backToStep3Btn');

    // Inputs e elementos dos passos
    const searchTermInput = document.getElementById('search_term');
    const searchClientBtn = document.getElementById('searchClientBtn');
    const clientInfoCard = document.getElementById('clientInfoCard');
    const clientInfoDetails = document.getElementById('clientInfoDetails'); // Para preencher o card

    const valorTotalInput = document.getElementById('valor_total');
    const codigoTransacaoInput = document.getElementById('codigo_transacao');
    const dataTransacaoInput = document.getElementById('data_transacao');
    const descricaoInput = document.getElementById('descricao');
    const generateCodeBtn = document.getElementById('generateCodeBtn');

    const saldoSectionWizard = document.getElementById('saldoSectionWizard');
    const saldoDisponivelWizard = document.getElementById('saldoDisponivelWizard');
    const usarSaldoCheckWizard = document.getElementById('usarSaldoCheckWizard');
    const saldoControlsWizard = document.getElementById('saldoControlsWizard');
    const valorSaldoUsadoWizard = document.getElementById('valorSaldoUsadoWizard');
    const maxSaldoWizard = document.getElementById('maxSaldoWizard');
    const semSaldoMsg = document.getElementById('semSaldoMsg');

    // Campos hidden para submissão final
    const clienteIdHidden = document.getElementById('cliente_id_hidden');
    const usarSaldoHiddenSubmit = document.getElementById('usar_saldo_hidden_submit');
    const valorSaldoUsadoHiddenSubmit = document.getElementById('valor_saldo_usado_hidden_submit');

    // Elementos do resumo (Passo 4)
    const summaryClientName = document.getElementById('summary_client_name');
    const summaryClientEmail = document.getElementById('summary_client_email');
    const summaryClientCpfItem = document.getElementById('summary_client_cpf_item');
    const summaryClientCpf = document.getElementById('summary_client_cpf');
    const summaryValorTotal = document.getElementById('summary_valor_total');
    const summaryCodigoTransacao = document.getElementById('summary_codigo_transacao');
    const summaryDataTransacao = document.getElementById('summary_data_transacao');
    const summaryDescricaoItem = document.getElementById('summary_descricao_item');
    const summaryDescricao = document.getElementById('summary_descricao');
    const summarySaldoSection = document.getElementById('summary_saldo_section');
    const summarySaldoUsado = document.getElementById('summary_saldo_usado');
    const summaryValorPagoFinal = document.getElementById('summary_valor_pago_final');
    // ... (outros seletores para a simulação de cashback no resumo)
    const summaryDisplayValorVenda = document.getElementById('summary_display_valor_venda');
    const summaryCashbackSaldoRow = document.getElementById('summary_cashback_saldo_row');
    const summaryDisplaySaldoUsado = document.getElementById('summary_display_saldo_usado');
    const summaryDisplayValorPago = document.getElementById('summary_display_valor_pago');
    const summaryDisplayValorCliente = document.getElementById('summary_display_valor_cliente');
    const summaryDisplayValorAdmin = document.getElementById('summary_display_valor_admin');
    const summaryDisplayValorTotalComissao = document.getElementById('summary_display_valor_total_comissao');


    // ========================================
    // FUNÇÕES DO WIZARD (Navegação e Controle)
    // ========================================
    function updateWizardProgress() {
        document.querySelectorAll('.progress-step').forEach(stepEl => {
            const stepNumber = parseInt(stepEl.dataset.step);
            stepEl.classList.remove('active', 'completed');
            if (stepNumber < currentStep) {
                stepEl.classList.add('completed');
            } else if (stepNumber === currentStep) {
                stepEl.classList.add('active');
            }
        });
    }

    function showStep(stepNumber) {
        document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
        const nextStepElement = document.getElementById(`step-${stepNumber}`);
        if (nextStepElement) {
            nextStepElement.classList.add('active');
            currentStep = stepNumber;
            updateWizardProgress();
        } else {
            console.error(`Step ${stepNumber} not found.`);
        }
    }

    function validateStep1() {
        if (!clientData || !clientData.id) {
            mostrarNotificacao('Por favor, busque e confirme um cliente para prosseguir.', 'warning');
            searchTermInput.focus();
            return false;
        }
        clienteIdHidden.value = clientData.id; // Popular campo hidden
        return true;
    }

    function validateStep2() {
        const valorTotal = parseFloat(valorTotalInput.value);
        const minTransactionValue = <?php echo MIN_TRANSACTION_VALUE; ?>;
        if (isNaN(valorTotal) || valorTotal < minTransactionValue) {
            mostrarNotificacao(`O valor total da venda deve ser de no mínimo R$ ${formatCurrency(minTransactionValue)}.`, 'warning');
            valorTotalInput.focus();
            return false;
        }
        if (!codigoTransacaoInput.value.trim()) {
            mostrarNotificacao('O código da transação é obrigatório.', 'warning');
            codigoTransacaoInput.focus();
            return false;
        }
        return true;
    }
    
    function validateStep3() {
        const valorSaldoUsado = parseFloat(valorSaldoUsadoWizard.value) || 0;
        const valorTotal = parseFloat(valorTotalInput.value) || 0;

        if (usarSaldoCheckWizard.checked) {
            if (valorSaldoUsado < 0) {
                 mostrarNotificacao('O valor do saldo a ser usado não pode ser negativo.', 'warning');
                 valorSaldoUsadoWizard.focus();
                 return false;
            }
            if (valorSaldoUsado > clientBalance) {
                mostrarNotificacao('Você não pode usar mais saldo do que o cliente possui.', 'warning');
                valorSaldoUsadoWizard.focus();
                return false;
            }
            if (valorSaldoUsado > valorTotal) {
                mostrarNotificacao('O saldo usado não pode ser maior que o valor total da venda.', 'warning');
                valorSaldoUsadoWizard.focus();
                return false;
            }
        }
        // Atualiza os campos hidden para o submit final
        usarSaldoHiddenSubmit.value = usarSaldoCheckWizard.checked ? 'sim' : 'nao';
        valorSaldoUsadoHiddenSubmit.value = usarSaldoCheckWizard.checked ? (parseFloat(valorSaldoUsadoWizard.value) || 0).toFixed(2) : '0';
        return true;
    }

    function populateSummary() {
        // Cliente
        summaryClientName.textContent = clientData?.nome || '-';
        summaryClientEmail.textContent = clientData?.email || '-';
        if (clientData?.cpf) {
            summaryClientCpf.textContent = clientData.cpf;
            summaryClientCpfItem.style.display = 'flex';
        } else {
            summaryClientCpfItem.style.display = 'none';
        }

        // Venda
        const valorTotalVenda = parseFloat(valorTotalInput.value) || 0;
        summaryValorTotal.textContent = `R$ ${formatCurrency(valorTotalVenda)}`;
        summaryCodigoTransacao.textContent = codigoTransacaoInput.value || '-';
        summaryDataTransacao.textContent = dataTransacaoInput.value ? new Date(dataTransacaoInput.value).toLocaleString('pt-BR') : '-';
        if(descricaoInput.value.trim()){
            summaryDescricao.textContent = descricaoInput.value.trim();
            summaryDescricaoItem.style.display = 'flex';
        } else {
            summaryDescricao.textContent = '-';
            summaryDescricaoItem.style.display = 'none';
        }


        // Saldo
        const usarSaldo = usarSaldoHiddenSubmit.value === 'sim';
        const valorSaldoUsado = parseFloat(valorSaldoUsadoHiddenSubmit.value) || 0;
        
        if (usarSaldo && valorSaldoUsado > 0) {
            summarySaldoUsado.textContent = `R$ ${formatCurrency(valorSaldoUsado)}`;
            const valorPagoFinalCalc = Math.max(0, valorTotalVenda - valorSaldoUsado);
            summaryValorPagoFinal.textContent = `R$ ${formatCurrency(valorPagoFinalCalc)}`;
            summarySaldoSection.style.display = 'block';
        } else {
            summarySaldoUsado.textContent = `R$ 0,00`;
            summaryValorPagoFinal.textContent = `R$ ${formatCurrency(valorTotalVenda)}`;
            summarySaldoSection.style.display = 'none';
        }
        
        // Simulação Cashback no Resumo
        let valorBaseComissao = valorTotalVenda;
        summaryDisplayValorVenda.textContent = `R$ ${formatCurrency(valorTotalVenda)}`;
        if (usarSaldo && valorSaldoUsado > 0) {
            valorBaseComissao = Math.max(0, valorTotalVenda - valorSaldoUsado);
            summaryCashbackSaldoRow.style.display = 'flex';
            summaryDisplaySaldoUsado.textContent = `R$ ${formatCurrency(valorSaldoUsado)}`;
        } else {
            summaryCashbackSaldoRow.style.display = 'none';
            summaryDisplaySaldoUsado.textContent = `R$ 0,00`;
        }
        summaryDisplayValorPago.textContent = `R$ ${formatCurrency(valorBaseComissao)}`;

        const pctCliente = parseFloat(<?php echo DEFAULT_CASHBACK_CLIENT; ?>);
        const pctAdmin = parseFloat(<?php echo DEFAULT_CASHBACK_ADMIN; ?>);
        const pctTotal = parseFloat(<?php echo DEFAULT_CASHBACK_TOTAL; ?>);

        summaryDisplayValorCliente.textContent = `R$ ${formatCurrency(valorBaseComissao * pctCliente / 100)}`;
        summaryDisplayValorAdmin.textContent = `R$ ${formatCurrency(valorBaseComissao * pctAdmin / 100)}`;
        summaryDisplayValorTotalComissao.textContent = `R$ ${formatCurrency(valorBaseComissao * pctTotal / 100)}`;
    }


    // ========================================
    // LÓGICA DE EVENTOS DO WIZARD
    // ========================================
    document.addEventListener('DOMContentLoaded', function() {
        showStep(1); // Iniciar no passo 1

        goToStep2Btn.addEventListener('click', () => {
            if (validateStep1()) showStep(2);
        });
        backToStep1Btn.addEventListener('click', () => showStep(1));

        goToStep3Btn.addEventListener('click', () => {
            if (validateStep2()) {
                // Lógica para mostrar/esconder seção de saldo baseada no clientBalance
                if (clientBalance > 0) {
                    saldoSectionWizard.style.display = 'block';
                    semSaldoMsg.style.display = 'none';
                    saldoDisponivelWizard.textContent = 'R$ ' + formatCurrency(clientBalance);
                    maxSaldoWizard.textContent = 'R$ ' + formatCurrency(clientBalance);
                    valorSaldoUsadoWizard.max = clientBalance;
                     // Resetar campos de saldo ao entrar na etapa
                    usarSaldoCheckWizard.checked = false;
                    saldoControlsWizard.style.display = 'none';
                    valorSaldoUsadoWizard.value = '0';
                    atualizarPreviewSaldoWizard(); // Atualiza preview e campos hidden
                } else {
                    saldoSectionWizard.style.display = 'none';
                    semSaldoMsg.style.display = 'block';
                    // Garantir que campos hidden de saldo estão zerados se não há saldo
                    usarSaldoHiddenSubmit.value = 'nao';
                    valorSaldoUsadoHiddenSubmit.value = '0';
                }
                showStep(3);
            }
        });
        backToStep2Btn.addEventListener('click', () => showStep(2));
        
        goToStep4Btn.addEventListener('click', () => {
            if (validateStep3()){
                populateSummary();
                showStep(4);
            }
        });
        backToStep3Btn.addEventListener('click', () => {
             if (clientBalance > 0) { // Se havia saldo, volta pro 3
                showStep(3);
            } else { // Se não tinha saldo, pulou o 3, então volta pro 2
                showStep(2);
            }
        });
        
        // Lógica para busca de cliente (adaptada do seu JS original)
        searchClientBtn.addEventListener('click', buscarClienteWizard);
        searchTermInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarClienteWizard();
            }
        });
        if(generateCodeBtn) generateCodeBtn.addEventListener('click', gerarCodigoTransacaoWizard);

        // Listeners para a seção de saldo do wizard
        usarSaldoCheckWizard.addEventListener('change', toggleUsarSaldoWizard);
        valorSaldoUsadoWizard.addEventListener('input', atualizarPreviewSaldoWizard);
        valorSaldoUsadoWizard.addEventListener('blur', atualizarPreviewSaldoWizard); // Garante atualização

        document.getElementById('usarTodoSaldoWizard').addEventListener('click', () => {
            valorSaldoUsadoWizard.value = Math.min(clientBalance, parseFloat(valorTotalInput.value) || clientBalance).toFixed(2);
            atualizarPreviewSaldoWizard();
        });
        document.getElementById('usar50SaldoWizard').addEventListener('click', () => {
             valorSaldoUsadoWizard.value = (Math.min(clientBalance, parseFloat(valorTotalInput.value) || clientBalance) * 0.5).toFixed(2);
            atualizarPreviewSaldoWizard();
        });
        document.getElementById('limparSaldoWizard').addEventListener('click', () => {
            valorSaldoUsadoWizard.value = '0';
            atualizarPreviewSaldoWizard();
        });

        // Inicializar accordion de ajuda
        setupAccordion(); // Sua função original
        adicionarNotificationStyles(); // Sua função original
        
        // Repopular busca de cliente se houver erro no POST e cliente_id_hidden estiver preenchido
        if (clienteIdHidden.value && clienteIdHidden.value !== '0') {
            // Simular uma busca para repopular o card do cliente e habilitar o botão de avançar
            // Isso é um pouco mais complexo, pois precisa dos dados do cliente que não estão no form_data_post diretamente
            // Pode ser necessário fazer uma chamada AJAX aqui para buscar os dados do cliente se $error estiver setado
            // Por ora, apenas habilitar o botão se o ID estiver lá.
            if(goToStep2Btn) goToStep2Btn.disabled = false;

             // Tentar preencher o clientData se possível (requer mais dados no form_data_post ou nova busca)
            if ("<?php echo !empty($error) && !empty($form_data_post['cliente_id_hidden']); ?>") {
                // Idealmente, teríamos mais dados do cliente para preencher clientData
                // Ex: clientData = {id: clienteIdHidden.value, nome: "<?php echo htmlspecialchars($form_data_post['client_name_for_repopulate'] ?? ''); ?>", ...};
                // Se não, uma nova busca AJAX seria necessária para repopular o card.
                // Para simplificar, vamos assumir que o usuário terá que buscar novamente se a página recarregar com erro.
                // Mas se os dados principais do formulário estiverem lá, podemos tentar avançar o wizard.
                 if (parseInt(clienteIdHidden.value) > 0 && valorTotalInput.value && codigoTransacaoInput.value) {
                    // Isso é uma heurística. Se os dados principais parecem estar lá após um erro de POST,
                    // e o cliente estava selecionado, talvez o usuário estivesse em um passo mais avançado.
                    // Mas é mais seguro começar do 1 ou do passo onde o erro ocorreu.
                    // A lógica de repopulação de um wizard multi-passo no lado do servidor pode ser complexa.
                    // Para agora, vamos apenas logar.
                    console.log("Tentando repopular wizard após erro de POST. Cliente ID:", clienteIdHidden.value);
                 }
            }
        }


        // Validação final do formulário antes do submit
        document.getElementById('transactionForm').addEventListener('submit', function(e){
            // As validações de cada passo já garantem que os dados obrigatórios estão lá.
            // Ocultar todos os passos para evitar que o usuário veja a transição durante o submit
            // e para garantir que apenas os campos corretos sejam enviados (principalmente os hiddens).
            // No entanto, como os campos dos passos 2 e 3 (valor_total, codigo_transacao etc)
            // são necessários no POST, não podemos escondê-los completamente.
            // A submissão é feita a partir do passo 4.
            // Os campos hidden (cliente_id_hidden, usar_saldo_hidden_submit, valor_saldo_usado_hidden_submit)
            // e os inputs visíveis dos passos anteriores (valor_total, codigo_transacao, data_transacao, descricao)
            // já farão parte do POST.
            console.log("Formulário submetido. Dados que serão enviados:", 
                new FormData(e.target)
            );
        });

    });

    // ========================================
    // FUNÇÕES ADAPTADAS DO SEU JS ORIGINAL
    // ========================================
    async function buscarClienteWizard() {
        const searchTerm = searchTermInput.value.trim();
        if (!searchTerm) {
            mostrarNotificacao('Por favor, digite um Email ou CPF válido.', 'warning');
            return;
        }

        searchClientBtn.disabled = true;
        searchClientBtn.querySelector('.btn-text').textContent = 'Buscando...';
        searchClientBtn.querySelector('.loading-spinner').style.display = 'inline-block';
        clientInfoCard.style.display = 'none'; // Esconder card antigo

        try {
            const response = await fetch('../../api/store-client-search.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'search_client', search_term: searchTerm, store_id: storeId })
            });
            const data = await response.json();

            if (data.status) {
                clientData = data.data; // Armazena todos os dados, incluindo id, nome, email, saldo, cpf
                clientBalance = parseFloat(data.data.saldo) || 0;
                
                let detailsHTML = `
                    <div class="client-info-item"><span class="client-info-label">Nome:</span><span class="client-info-value">${clientData.nome}</span></div>
                    <div class="client-info-item"><span class="client-info-label">Email:</span><span class="client-info-value">${clientData.email}</span></div>
                    ${clientData.cpf ? `<div class="client-info-item"><span class="client-info-label">CPF:</span><span class="client-info-value">${clientData.cpf}</span></div>` : ''}
                    <div class="client-info-item"><span class="client-info-label">Status:</span><span class="client-info-value">Cliente Ativo</span></div>
                    <div class="client-info-item"><span class="client-info-label">Saldo nesta loja:</span><span class="client-info-value">${clientBalance > 0 ? 'R$ ' + formatCurrency(clientBalance) : 'Nenhum saldo'}</span></div>
                `;
                clientInfoDetails.innerHTML = detailsHTML; // Preenche o card (que não está mais no seu HTML original, adaptar se quiser mostrá-lo)
                
                // Atualizar o card de informações do cliente no Passo 1 (se você mantiver um card lá)
                // Exemplo, se você tiver um <div id="clientSearchResultCard"> no Passo 1:
                const clientSearchResultCard = document.getElementById('clientInfoCard'); // Ou um novo ID se preferir
                if(clientSearchResultCard){
                    clientSearchResultCard.innerHTML = `
                        <div class="client-info-header">
                            <h4 class="client-info-title">Cliente Encontrado</h4>
                        </div>
                        <div class="client-info-details">${detailsHTML}</div>`;
                    clientSearchResultCard.className = 'client-info-card success';
                    clientSearchResultCard.style.display = 'block';
                }

                if(goToStep2Btn) goToStep2Btn.disabled = false; // Habilitar botão para avançar
                mostrarNotificacao('Cliente encontrado com sucesso!', 'success');

            } else {
                clientData = null;
                clientBalance = 0;
                 const clientSearchResultCard = document.getElementById('clientInfoCard');
                 if(clientSearchResultCard){
                    clientSearchResultCard.innerHTML = `
                        <div class="client-info-header">
                             <h4 class="client-info-title">Cliente Não Encontrado</h4>
                        </div>
                        <div class="client-info-details"><p>${data.message}</p></div>`;
                    clientSearchResultCard.className = 'client-info-card error';
                    clientSearchResultCard.style.display = 'block';
                 }
                if(goToStep2Btn) goToStep2Btn.disabled = true;
                mostrarNotificacao(data.message, 'error');
            }
        } catch (error) {
            console.error('Erro ao buscar cliente:', error);
            clientData = null;
            clientBalance = 0;
            if(goToStep2Btn) goToStep2Btn.disabled = true;
            mostrarNotificacao('Erro de comunicação ao buscar cliente.', 'error');
        } finally {
            searchClientBtn.disabled = false;
            searchClientBtn.querySelector('.btn-text').textContent = 'Buscar';
            searchClientBtn.querySelector('.loading-spinner').style.display = 'none';
        }
    }

    function toggleUsarSaldoWizard() {
        if (usarSaldoCheckWizard.checked) {
            saldoControlsWizard.style.display = 'block';
            usarSaldoHiddenSubmit.value = 'sim'; // Atualiza o hidden para o submit final
            // Tentar usar o máximo possível por padrão, limitado pelo valor da venda
            const valorVendaAtual = parseFloat(valorTotalInput.value) || 0;
            valorSaldoUsadoWizard.value = Math.min(clientBalance, valorVendaAtual).toFixed(2);

        } else {
            saldoControlsWizard.style.display = 'none';
            usarSaldoHiddenSubmit.value = 'nao'; // Atualiza o hidden para o submit final
            valorSaldoUsadoWizard.value = '0';
        }
        atualizarPreviewSaldoWizard();
    }
    
    function atualizarPreviewSaldoWizard() {
        const valorTotalVenda = parseFloat(valorTotalInput.value) || 0;
        let valorSaldoInput = parseFloat(valorSaldoUsadoWizard.value) || 0;

        // Validações
        if (valorSaldoInput < 0) valorSaldoInput = 0;
        if (valorSaldoInput > clientBalance) valorSaldoInput = clientBalance;
        if (valorSaldoInput > valorTotalVenda) valorSaldoInput = valorTotalVenda;
        
        valorSaldoUsadoWizard.value = valorSaldoInput.toFixed(2); // Corrigir valor no input se necessário

        const valorFinalCalc = Math.max(0, valorTotalVenda - valorSaldoInput);

        document.getElementById('valorOriginalWizard').textContent = 'R$ ' + formatCurrency(valorTotalVenda);
        document.getElementById('valorSaldoUsadoPreviewWizard').textContent = 'R$ ' + formatCurrency(valorSaldoInput);
        document.getElementById('valorFinalWizard').textContent = 'R$ ' + formatCurrency(valorFinalCalc);
        
        // Atualizar o campo hidden que será efetivamente submetido com o formulário
        // Este campo será usado no PHP $_POST
        if (usarSaldoCheckWizard.checked) {
            valorSaldoUsadoHiddenSubmit.value = valorSaldoInput.toFixed(2);
        } else {
            valorSaldoUsadoHiddenSubmit.value = '0';
        }
    }

    function gerarCodigoTransacaoWizard() {
        const agora = new Date();
        const ano = agora.getFullYear().toString().slice(-2);
        const mes = String(agora.getMonth() + 1).padStart(2, '0');
        const dia = String(agora.getDate()).padStart(2, '0');
        const hora = String(agora.getHours()).padStart(2, '0');
        const min = String(agora.getMinutes()).padStart(2, '0');
        const seg = String(agora.getSeconds()).padStart(2, '0');
        const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        codigoTransacaoInput.value = `KC${ano}${mes}${dia}${hora}${min}${seg}${random}`;
        mostrarNotificacao('Código da transação gerado!', 'success');
    }

    // Funções de utilidade (formatCurrency, setupAccordion, mostrarNotificacao, adicionarNotificationStyles)
    // Mantenha suas funções originais aqui, elas são boas.
    function formatCurrency(value) { return parseFloat(value).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function setupAccordion() {
        const accordionItems = document.querySelectorAll('.accordion-item');
        accordionItems.forEach(item => {
            const header = item.querySelector('.accordion-header');
            if(!header) return;
            const content = item.querySelector('.accordion-content');
            const icon = item.querySelector('.accordion-icon');
            header.addEventListener('click', () => {
                const isActive = item.classList.contains('active');
                accordionItems.forEach(i => {
                    i.classList.remove('active');
                    i.querySelector('.accordion-content').style.maxHeight = '0';
                    if(i.querySelector('.accordion-icon')) i.querySelector('.accordion-icon').textContent = '+';
                });
                if (!isActive) {
                    item.classList.add('active');
                    content.style.maxHeight = content.scrollHeight + 'px';
                    if(icon) icon.textContent = '-';
                }
            });
        });
    }
    function mostrarNotificacao(mensagem, tipo = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${tipo}`;
        notification.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"><path fill="currentColor" d="m10.6 16.6l7.05-7.05l-1.4-1.4l-5.65 5.65l-2.85-2.85l-1.4 1.4zM12 22q-2.075 0-3.9-.788t-3.175-2.137q-1.35-1.35-2.137-3.175T2 12q0-2.075.788-3.9t2.137-3.175q1.35-1.35 3.175-2.138T12 2q2.075 0 3.9.788t3.175 2.138q1.35 1.35 2.137 3.175T22 12q0 2.075-.788 3.9t-2.137 3.175q-1.35 1.35-3.175 2.137T12 22Z"/></svg><span>${mensagem}</span>`;
        let bgColor = 'var(--info-color)';
        if (tipo === 'success') bgColor = 'var(--success-color)';
        else if (tipo === 'error') bgColor = 'var(--danger-color)';
        else if (tipo === 'warning') bgColor = 'var(--warning-color)';
        notification.style.cssText = `position: fixed; top: 20px; right: 20px; background: ${bgColor}; color: white; padding: 1rem 1.5rem; border-radius: var(--border-radius-sm); box-shadow: var(--shadow-lg); display: flex; align-items: center; gap: 0.75rem; font-size: var(--font-size-sm); font-weight: 600; z-index: 10000; animation: slideInRight 0.3s ease-out; max-width: 300px;`;
        document.body.appendChild(notification);
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-in forwards';
            setTimeout(() => { if (notification.parentNode) notification.parentNode.removeChild(notification); }, 300);
        }, 3000);
    }
    function adicionarNotificationStyles() {
        if (document.getElementById('notificationAnimationStyles')) return;
        const styles = document.createElement('style');
        styles.id = 'notificationAnimationStyles';
        styles.textContent = `@keyframes slideInRight{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}} @keyframes slideOutRight{from{transform:translateX(0);opacity:1}to{transform:translateX(100%);opacity:0}}`;
        document.head.appendChild(styles);
    }

    // Atualizar previews de saldo e simulação ao mudar valor total no passo 2
    valorTotalInput.addEventListener('input', () => {
        if (currentStep >= 2) { // Só atualiza se já passou ou está no passo do valor total
             // Se estiver no passo 3 (uso de saldo), precisa recalcular o preview de lá
            if (currentStep === 3 && usarSaldoCheckWizard.checked) {
                 const valorVendaAtual = parseFloat(valorTotalInput.value) || 0;
                 valorSaldoUsadoWizard.value = Math.min(clientBalance, valorVendaAtual).toFixed(2);
                 atualizarPreviewSaldoWizard();
            }
            // Se estiver no passo 4 (revisão), precisa atualizar o resumo
            if (currentStep === 4) {
                populateSummary();
            }
        }
    });
    // Seção de Saldo: atualizar hidden quando valor de saldo usado muda
    valorSaldoUsadoWizard.addEventListener('input', () => {
        if (usarSaldoCheckWizard.checked) {
            valorSaldoUsadoHiddenSubmit.value = (parseFloat(valorSaldoUsadoWizard.value) || 0).toFixed(2);
        } else {
            valorSaldoUsadoHiddenSubmit.value = '0';
        }
         // Se estiver no passo 4 (revisão), precisa atualizar o resumo
        if (currentStep === 4) {
            populateSummary();
        }
    });
     // Seção de Saldo: atualizar hidden quando checkbox de usar saldo muda
    usarSaldoCheckWizard.addEventListener('change', () => {
        usarSaldoHiddenSubmit.value = usarSaldoCheckWizard.checked ? 'sim' : 'nao';
        if (!usarSaldoCheckWizard.checked) {
            valorSaldoUsadoHiddenSubmit.value = '0';
        }
         // Se estiver no passo 4 (revisão), precisa atualizar o resumo
        if (currentStep === 4) {
            populateSummary();
        }
    });


</script>
</body>
</html>
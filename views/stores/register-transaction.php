<?php
// views/stores/register-transaction.php
// Incluir arquivos de configuração
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/StoreController.php';
require_once '../../controllers/TransactionController.php';
require_once '../../controllers/CommissionController.php';
require_once '../../utils/StoreHelper.php';

// Iniciar sessão
session_start();

// Verificação simplificada
StoreHelper::requireStoreAccess();

// Obter dados da loja - SE a verificação passou, os dados existem
$storeId = StoreHelper::getCurrentStoreId();
$store = AuthController::getStoreData();

// NOVO: Obter configurações completas da loja incluindo MVP e cashback
$isStoreMvp = false;
$porcentagemCliente = 5.00;
$porcentagemAdmin = 5.00;
$porcentagemTotal = 10.00;
$cashbackAtivo = true;

if ($storeId) {
    try {
        $db = Database::getConnection();
        $configStmt = $db->prepare("
            SELECT u.mvp,
                   COALESCE(l.porcentagem_cliente, 5.00) as porcentagem_cliente,
                   COALESCE(l.porcentagem_admin, 5.00) as porcentagem_admin,
                   COALESCE(l.cashback_ativo, 1) as cashback_ativo
            FROM lojas l 
            JOIN usuarios u ON l.usuario_id = u.id 
            WHERE l.id = :loja_id
        ");
        $configStmt->bindParam(':loja_id', $storeId);
        $configStmt->execute();
        $configResult = $configStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($configResult) {
            $isStoreMvp = ($configResult['mvp'] === 'sim');
            $porcentagemCliente = (float) $configResult['porcentagem_cliente'];
            $porcentagemAdmin = (float) $configResult['porcentagem_admin'];
            $porcentagemTotal = $porcentagemCliente + $porcentagemAdmin;
            $cashbackAtivo = ($configResult['cashback_ativo'] == 1);
        }
        
        // Log das configurações para debug
        error_log("STORE CONFIG: Loja {$storeId} - Cliente: {$porcentagemCliente}%, Admin: {$porcentagemAdmin}%, MVP: " . ($isStoreMvp ? 'SIM' : 'NÃO') . ", Ativo: " . ($cashbackAtivo ? 'SIM' : 'NÃO'));
        
    } catch (Exception $e) {
        error_log("Erro ao obter configurações da loja: " . $e->getMessage());
    }
}

// Esta verificação não deveria ser necessária, mas vamos manter como fallback
if (!$storeId || !$store) {
    // Se chegou aqui, há problema na sessão - vamos limpar e tentar novamente
    session_destroy();
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Sessão inválida. Faça login novamente.'));
    exit;
}

// Verificar se o formulário foi enviado
$success = false;
$error = '';
$transactionData = [];
$isMvpTransaction = false;
$transactionResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log dos dados recebidos
    error_log("FORM DEBUG: Dados POST recebidos: " . print_r($_POST, true));
    
    // Obter dados do formulário
    $clientId = intval($_POST['cliente_id_hidden'] ?? 0);
    $valorTotal = floatval($_POST['valor_total'] ?? 0);
    $codigoTransacao = $_POST['codigo_transacao'] ?? '';
    $descricao = $_POST['descricao'] ?? '';
    $dataTransacao = $_POST['data_transacao'] ?? date('Y-m-d H:i:s');
    
    // Dados de saldo
    $usarSaldo = isset($_POST['usar_saldo']) && $_POST['usar_saldo'] === 'sim';
    $valorSaldoUsado = floatval($_POST['valor_saldo_usado'] ?? 0);

    if ($clientId <= 0) {
        $error = 'Cliente não selecionado. Por favor, busque e selecione um cliente.';
    } else {
        // Buscar usuário pelo ID
        $db = Database::getConnection();
        $userQuery = $db->prepare("SELECT id, nome, email FROM usuarios WHERE id = :id AND tipo = :tipo AND status = :status");
        $userQuery->bindParam(':id', $clientId, PDO::PARAM_INT);
        $tipoCliente = USER_TYPE_CLIENT;
        $userQuery->bindParam(':tipo', $tipoCliente);
        $status = USER_ACTIVE;
        $userQuery->bindParam(':status', $status);
        $userQuery->execute();

        if ($userQuery->rowCount() === 0) {
            $error = 'Cliente não encontrado ou não está ativo. Verifique o cliente selecionado.';
        } else {
            $client = $userQuery->fetch(PDO::FETCH_ASSOC);
            
            // Se vai usar saldo, verificar se tem saldo suficiente
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
                
                // Debug dos dados enviados
                error_log("FORM DEBUG: Dados para TransactionController: " . print_r($transactionData, true));
                
                // === TRACE: REGISTRO VIA INTERFACE DA LOJA ===
                if (file_exists('../../trace-integration.php')) {
                    error_log("[TRACE] register-transaction.php - Chamando TransactionController::registerTransaction", 3, '../../integration_trace.log');
                    error_log("[TRACE] register-transaction.php - Dados enviados: " . json_encode($transactionData), 3, '../../integration_trace.log');
                }
                
                // Registrar transação usando versão corrigida
                $result = TransactionController::registerTransactionFixed($transactionData);
                
                // === TRACE: RESULTADO DA CHAMADA ===
                if (file_exists('../../trace-integration.php')) {
                    error_log("[TRACE] register-transaction.php - Resultado recebido: " . json_encode($result), 3, '../../integration_trace.log');
                    if ($result['status'] && isset($result['data']['transaction_id'])) {
                        error_log("[TRACE] register-transaction.php - Transação criada com ID: " . $result['data']['transaction_id'], 3, '../../integration_trace.log');
                    } else {
                        error_log("[TRACE] register-transaction.php - FALHA no registro: " . ($result['message'] ?? 'Sem mensagem'), 3, '../../integration_trace.log');
                    }
                }
                
                if ($result['status']) {
                    $success = true;
                    $transactionResult = $result;
                    $isMvpTransaction = isset($result['data']['is_mvp']) && $result['data']['is_mvp'];
                    
                    // === INTEGRAÇÃO WHATSAPP DIRETA ===
                    try {
                        require_once '../../utils/NotificationTrigger.php';
                        $notificationResult = NotificationTrigger::send($result['data']['transaction_id']);
                        error_log("[TRACE] register-transaction.php - Notificação enviada: " . json_encode($notificationResult), 3, '../../integration_trace.log');
                    } catch (Exception $e) {
                        error_log("[TRACE] register-transaction.php - ERRO na notificação: " . $e->getMessage(), 3, '../../integration_trace.log');
                    }
                    
                    // ✅ AUDITORIA: Registrar quem criou a transação
                    StoreHelper::logUserAction($_SESSION['user_id'], 'criou_transacao', [
                        'loja_id' => $storeId,
                        'transaction_id' => $result['data']['transaction_id'],
                        'valor_total' => $valorTotal,
                        'cliente_id' => $clientId,
                        'codigo_transacao' => $codigoTransacao,
                        'valor_saldo_usado' => $valorSaldoUsado
                    ]);
                    
                    $transactionData = [];
                    error_log("FORM DEBUG: Transação registrada com sucesso - ID: " . $result['data']['transaction_id']);
                } else {
                    $error = $result['message'];
                    error_log("FORM DEBUG: Erro ao registrar - " . $result['message']);
                }
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/views/stores/profile.css">
    <!-- CSS Customizado para a nova interface -->
    <link rel="stylesheet" href="../../assets/css/views/stores/register-transaction.css">
    <style>
/* Estilos específicos para cliente visitante nesta página */
.visitor-client-section {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border: 2px solid #f39c12;
    border-radius: 12px;
    padding: 20px;
    margin-top: 15px;
    display: none;
    transition: all 0.3s ease-out;
}

.visitor-client-section.show {
    display: block;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.visitor-alert {
    background: #fff3cd;
    border: 1px solid #f39c12;
    color: #856404;
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.visitor-form {
    background: white;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.visitor-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    justify-content: flex-end;
}

.btn-create-visitor {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.btn-create-visitor:hover {
    background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
}

.btn-cancel-visitor {
    background: #6c757d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-cancel-visitor:hover {
    background: #5a6268;
}
/* === ESTILOS PARA CLIENTE UNIVERSAL === */
.client-type-badge.visitante_universal {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    color: white;
}

.client-type-badge.visitante_proprio {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
    color: white;
}

.client-type-badge.cadastrado {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
}

.client-info-item[style*="background: #e8f4fd"] {
    border-radius: 6px;
    padding: 12px;
    margin: 8px 0;
}

.client-info-item[style*="background: #d4edda"] {
    border-radius: 6px;
    padding: 12px;
    margin: 8px 0;
}
/* Responsividade básica */
@media (max-width: 768px) {
    .visitor-actions {
        flex-direction: column;
    }
    
    .btn-create-visitor,
    .btn-cancel-visitor {
        width: 100%;
        justify-content: center;
    }
}
</style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Incluir sidebar/menu lateral -->
        <?php include_once '../components/sidebar-store.php'; ?>
        
        <div class="main-content" id="mainContent">
            <!-- Header da Página -->
            <div class="page-header">
                <h1 class="page-title">
                    ✨ Registrar Nova Venda
                    <?php if ($isStoreMvp): ?>
                        <span style="background: linear-gradient(45deg, #FFD700, #FFA500); color: #8B4513; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.7rem; font-weight: bold; margin-left: 1rem; box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);">🏆 LOJA MVP</span>
                    <?php endif; ?>
                </h1>
                <p class="page-subtitle">
                    <?php if (!$cashbackAtivo): ?>
                        ⚠️ <strong>Atenção:</strong> O cashback está temporariamente desabilitado para sua loja
                    <?php elseif ($isStoreMvp): ?>
                        🎯 Como loja MVP, suas transações são aprovadas instantaneamente com <?php echo number_format($porcentagemCliente, 1); ?>% de cashback imediato!
                    <?php else: ?>
                        Cadastre sua venda em 4 passos simples e ofereça <?php echo number_format($porcentagemCliente, 1); ?>% de cashback aos seus clientes
                    <?php endif; ?>
                </p>
            </div>
            
            <!-- Alertas de Sucesso/Erro -->
            <?php if ($success): ?>
            <div class="alert success" <?php echo $isMvpTransaction ? 'style="background: linear-gradient(135deg, #FFD700 0%, #FFF8DC 100%); border-color: #FFD700;"' : ''; ?>>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <div>
                    <?php if ($isMvpTransaction): ?>
                        <h4>🏆 Transação MVP Aprovada Instantaneamente!</h4>
                        <p><strong>Parabéns!</strong> Como loja MVP, sua transação foi aprovada automaticamente e o cashback de <strong>R$ <?php echo number_format($transactionResult['data']['valor_cashback'], 2, ',', '.'); ?></strong> já foi creditado na conta do cliente.</p>
                        <p>✅ <strong>Status:</strong> Aprovada e processada instantaneamente<br>
                           💰 <strong>Cashback:</strong> Creditado automaticamente<br>
                           🎯 <strong>Privilégio MVP:</strong> Sem necessidade de pagamento de comissão</p>
                    <?php else: ?>
                        <h4>🎉 Transação registrada com sucesso!</h4>
                        <p>O cashback será liberado para o cliente assim que o pagamento da comissão for realizado e aprovado.</p>
                    <?php endif; ?>
                </div>
                <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="nav-btn nav-btn-primary">
                    <?php echo $isMvpTransaction ? 'Registrar Nova MVP' : 'Registrar Nova'; ?>
                </a>
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
                    <h4>❌ Erro ao registrar transação</h4>
                    <p><?php echo $error; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Configurações Atuais da Loja -->
            <?php if (!$success): ?>
            <div class="store-config-info" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 1px solid #dee2e6; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #6c757d;">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 1v6m0 10v6m11-7h-6m-10 0H1"></path>
                    </svg>
                    <strong style="color: #495057;">Configurações de Cashback da Loja</strong>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; font-size: 14px;">
                    <div>
                        <span style="color: #6c757d;">Cashback do Cliente:</span>
                        <strong style="color: #28a745;"><?php echo number_format($porcentagemCliente, 1); ?>%</strong>
                    </div>
                    <div>
                        <span style="color: #6c757d;">Comissão Plataforma:</span>
                        <strong style="color: <?php echo $isStoreMvp && $porcentagemAdmin == 0 ? '#ffc107' : '#007bff'; ?>;">
                            <?php echo number_format($porcentagemAdmin, 1); ?>%
                            <?php if ($isStoreMvp && $porcentagemAdmin == 0): ?>(MVP - Isento)<?php endif; ?>
                        </strong>
                    </div>
                    <div>
                        <span style="color: #6c757d;">Status:</span>
                        <strong style="color: <?php echo $cashbackAtivo ? '#28a745' : '#dc3545'; ?>;">
                            <?php echo $cashbackAtivo ? 'Ativo' : 'Desabilitado'; ?>
                        </strong>
                    </div>
                    <div>
                        <span style="color: #6c757d;">Tipo de Loja:</span>
                        <strong style="color: <?php echo $isStoreMvp ? '#ffc107' : '#6c757d'; ?>;">
                            <?php echo $isStoreMvp ? '🏆 MVP' : 'Normal'; ?>
                        </strong>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Indicador de Progresso -->
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-line" id="progressLine"></div>
                    <div class="progress-step active" id="step1">1</div>
                    <div class="progress-step" id="step2">2</div>
                    <div class="progress-step" id="step3">3</div>
                    <div class="progress-step" id="step4">4</div>
                </div>
                <div class="progress-labels">
                    <div class="progress-label active">Identificar Cliente</div>
                    <div class="progress-label">Dados da Venda</div>
                    <div class="progress-label">Usar Saldo</div>
                    <div class="progress-label">Confirmar</div>
                </div>
            </div>
            
            <!-- Container do Formulário -->
            <div class="form-container">
                <form id="transactionForm" method="POST" action="">
                    <!-- PASSO 1: Identificar Cliente -->
                    <div class="step-card active" id="stepCard1">
                        <div class="step-header">
                            <div class="step-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="m22 2-5 5"></path>
                                    <path d="m17 7 5-5"></path>
                                </svg>
                            </div>
                            <h2 class="step-title">Identificar Cliente</h2>
                            <p class="step-description">Digite o email, CPF ou telefone do cliente cadastrado no Klube Cash para continuar</p>
                        </div>

                        
                        <div class="client-search-container">
                            <div class="form-group">
                                <label for="search_term" class="form-label required">Email, CPF ou Telefone do Cliente</label>
                                <div class="search-input-group">
                                    <div class="search-input-wrapper">
                                        <input type="text" id="search_term" name="search_term" class="form-input"
                                            placeholder="exemplo@email.com, 123.456.789-00 ou (38) 99999-9999" required
                                            value="<?php echo isset($_POST['search_term']) ? htmlspecialchars($_POST['search_term']) : ''; ?>">
                                        <small class="form-help">🔍 Digite o email, CPF ou telefone completo do cliente cadastrado no Klube Cash</small>
                                    </div>
                                    <button type="button" id="searchClientBtn" class="search-btn">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <path d="m21 21-4.35-4.35"></path>
                                        </svg>
                                        <span class="btn-text">Buscar Cliente</span>
                                        <span class="loading-spinner"></span>
                                    </button>
                                </div>
                            </div>
                                                            
                            <div id="clientInfoCard" class="client-info-card">
                                <div class="client-info-header">
                                    <svg class="client-info-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    <h4 class="client-info-title" id="clientInfoTitle">Informações do Cliente</h4>
                                </div>
                                <div class="client-info-details" id="clientInfoDetails"></div>
                            </div>
                            <div id="visitor-client-section" class="visitor-client-section">
                                <div class="visitor-alert">
                                    <i class="fas fa-user-plus"></i>
                                    <div>
                                        <strong>Cliente não encontrado?</strong>
                                        Você pode prosseguir com a venda criando um cadastro simplificado para este cliente.
                                    </div>
                                </div>
                                
                                <div class="visitor-form">
                                    <h4>
                                        <i class="fas fa-user-clock"></i>
                                        Criar Cliente Visitante
                                    </h4>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="visitor-name" class="form-label">
                                                    <i class="fas fa-user"></i>
                                                    Nome do Cliente *
                                                </label>
                                                <input type="text" 
                                                    id="visitor-name" 
                                                    class="form-control" 
                                                    placeholder="Digite o nome completo do cliente"
                                                    maxlength="100"
                                                    required>
                                                <small class="form-text text-muted">
                                                    Este será o nome usado para identificar o cliente
                                                </small>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="visitor-phone" class="form-label">
                                                    <i class="fas fa-phone"></i>
                                                    Telefone *
                                                </label>
                                                <input type="text" 
                                                    id="visitor-phone" 
                                                    class="form-control" 
                                                    placeholder="(11) 99999-9999"
                                                    maxlength="15"
                                                    required>
                                                <small class="form-text text-muted">
                                                    O telefone será usado para identificar o cliente
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>Importante:</strong>
                                        <ul class="mb-0 mt-2">
                                            <li>Este cliente será vinculado apenas à sua loja</li>
                                            <li>O cliente poderá acumular saldo normalmente</li>
                                            <li>O saldo só poderá ser usado em sua loja</li>
                                            <li>O cliente receberá mensagens no WhatsApp sobre suas compras</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="visitor-actions">
                                        <button type="button" 
                                                class="btn-create-visitor" 
                                                onclick="createVisitorClient()">
                                            <i class="fas fa-user-plus"></i>
                                            Criar Cliente e Prosseguir
                                        </button>
                                        
                                        <button type="button" 
                                                class="btn-cancel-visitor" 
                                                onclick="cancelVisitorCreation()">
                                            <i class="fas fa-times"></i>
                                            Cancelar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="step-navigation">
                            <div></div>
                            <button type="button" class="nav-btn nav-btn-primary" id="nextToStep2" disabled>
                                Próximo: Dados da Venda
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- PASSO 2: Dados da Venda -->
                    <div class="step-card" id="stepCard2">
                        <div class="step-header">
                            <div class="step-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="1" x2="12" y2="23"></line>
                                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                </svg>
                            </div>
                            <h2 class="step-title">Dados da Venda</h2>
                            <p class="step-description">Informe o valor total da venda e outros detalhes importantes</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="valor_total" class="form-label required">Valor Total da Venda</label>
                            <input type="number" id="valor_total" name="valor_total" class="form-input" 
                                   min="<?php echo MIN_TRANSACTION_VALUE; ?>" step="0.01" required
                                   value="<?php echo isset($transactionData['valor_total']) ? htmlspecialchars($transactionData['valor_total']) : ''; ?>"
                                   placeholder="0,00">
                            <small class="form-help">💰 Valor mínimo: R$ <?php echo number_format(MIN_TRANSACTION_VALUE, 2, ',', '.'); ?></small>
                        </div>
                        
                        <div class="form-group">
                            <label for="codigo_transacao" class="form-label required">Código da Transação</label>
                            <div class="code-input-group">
                                <input type="text" id="codigo_transacao" name="codigo_transacao" class="form-input" required
                                       value="<?php echo isset($transactionData['codigo_transacao']) ? htmlspecialchars($transactionData['codigo_transacao']) : ''; ?>"
                                       placeholder="Código/número da venda no seu sistema">
                                <button type="button" id="generateCodeBtn" class="generate-code-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2v4"></path>
                                        <path d="m16.2 7.8 2.9-2.9"></path>
                                        <path d="M18 12h4"></path>
                                        <path d="m16.2 16.2 2.9 2.9"></path>
                                        <path d="M12 18v4"></path>
                                        <path d="m4.9 19.1 2.9-2.9"></path>
                                        <path d="M2 12h4"></path>
                                        <path d="m4.9 4.9 2.9 2.9"></path>
                                    </svg>
                                    <span class="btn-text">Gerar</span>
                                </button>
                            </div>
                            <small class="form-help">🏷️ Identificador único da venda. Use seu código interno ou clique em "Gerar"</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="data_transacao" class="form-label">Data da Venda</label>
                            <input type="datetime-local" id="data_transacao" name="data_transacao" class="form-input"
                                   value="<?php echo isset($transactionData['data_transacao']) ? date('Y-m-d\TH:i', strtotime($transactionData['data_transacao'])) : date('Y-m-d\TH:i'); ?>">
                            <small class="form-help">📅 Deixe em branco para usar a data/hora atual</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="descricao" class="form-label">Descrição (opcional)</label>
                            <textarea id="descricao" name="descricao" rows="3" class="form-input" 
                                      placeholder="Detalhes adicionais sobre a venda"><?php echo isset($transactionData['descricao']) ? htmlspecialchars($transactionData['descricao']) : ''; ?></textarea>
                        </div>
                        
                        <div class="step-navigation">
                            <button type="button" class="nav-btn nav-btn-secondary" id="backToStep1">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                                Voltar
                            </button>
                            <button type="button" class="nav-btn nav-btn-primary" id="nextToStep3">
                                Próximo: Verificar Saldo
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- PASSO 3: Usar Saldo -->
                    <div class="step-card" id="stepCard3">
                        <div class="step-header">
                            <div class="step-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                    <line x1="1" y1="10" x2="23" y2="10"></line>
                                </svg>
                            </div>
                            <h2 class="step-title">Usar Saldo do Cliente</h2>
                            <p class="step-description">O cliente pode usar seu saldo de cashback para abater no valor da compra</p>
                        </div>
                        
                        <div id="balanceSection" class="balance-section">
                            <div class="balance-header">
                                <div class="balance-info">
                                    <div class="balance-available">Saldo disponível:</div>
                                    <div class="balance-value" id="saldoDisponivel">R$ 0,00</div>
                                </div>
                                <div class="balance-toggle">
                                    <label class="toggle-switch">
                                        <input type="checkbox" id="usarSaldoCheck" name="usar_saldo_check">
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span>Usar saldo nesta venda</span>
                                </div>
                            </div>
                            
                            <div id="balanceControls" class="balance-controls">
                                <div class="balance-input-group">
                                    <label for="valorSaldoUsado" class="form-label">Valor do saldo a usar (R$)</label>
                                    <input type="number" id="valorSaldoUsado" name="valor_saldo_usado_input" 
                                           min="0" step="0.01" value="0" class="form-input">
                                    <small class="form-help">Máximo: <span id="maxSaldo">R$ 0,00</span></small>
                                </div>
                                
                                <div class="balance-buttons">
                                    <button type="button" id="usarTodoSaldo" class="balance-btn">💯 Usar Todo Saldo</button>
                                    <button type="button" id="usar50Saldo" class="balance-btn">✂️ Usar 50%</button>
                                    <button type="button" id="limparSaldo" class="balance-btn">🗑️ Limpar</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="step-navigation">
                            <button type="button" class="nav-btn nav-btn-secondary" id="backToStep2">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                                Voltar
                            </button>
                            <button type="button" class="nav-btn nav-btn-primary" id="nextToStep4">
                                Finalizar: Ver Resumo
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- PASSO 4: Resumo e Confirmação -->
                    <div class="step-card" id="stepCard4">
                        <div class="step-header">
                            <div class="step-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                            </div>
                            <h2 class="step-title">Confirmar Transação</h2>
                            <p class="step-description">Revise todos os dados antes de registrar a venda</p>
                        </div>
                        
                        <div class="cashback-simulator">
                            <div class="simulator-header">
                                <div class="simulator-icon">🧮</div>
                                <div class="simulator-title">Resumo da Transação</div>
                            </div>
                            <div class="simulator-details">
                                <div class="simulator-item">
                                    <span class="simulator-label">Cliente:</span>
                                    <span class="simulator-value" id="resumoCliente">-</span>
                                </div>
                                <div class="simulator-item">
                                    <span class="simulator-label">Código da Transação:</span>
                                    <span class="simulator-value" id="resumoCodigo">-</span>
                                </div>
                                <div class="simulator-item">
                                    <span class="simulator-label">Valor Total da Venda:</span>
                                    <span class="simulator-value" id="resumoValorVenda">R$ 0,00</span>
                                </div>
                                <div class="simulator-item balance-used" id="resumoSaldoRow" style="display: none;">
                                    <span class="simulator-label">Saldo Usado pelo Cliente:</span>
                                    <span class="simulator-value" id="resumoSaldoUsado">R$ 0,00</span>
                                </div>
                                <div class="simulator-item">
                                    <span class="simulator-label">Valor Efetivamente Pago:</span>
                                    <span class="simulator-value" id="resumoValorPago">R$ 0,00</span>
                                </div>
                                <div class="simulator-item">
                                    <span class="simulator-label">Cashback do Cliente (<?php echo number_format($porcentagemCliente, 1); ?>%):</span>
                                    <span class="simulator-value" id="resumoCashbackCliente">R$ 0,00</span>
                                </div>
                                <div class="simulator-item">
                                    <span class="simulator-label">
                                        <?php if ($isStoreMvp): ?>
                                            Comissão Plataforma (<?php echo number_format($porcentagemAdmin, 1); ?>%) - MVP:
                                        <?php else: ?>
                                            Receita Klube Cash (<?php echo number_format($porcentagemAdmin, 1); ?>%):
                                        <?php endif; ?>
                                    </span>
                                    <span class="simulator-value" id="resumoReceitaAdmin">
                                        <?php if ($isStoreMvp && $porcentagemAdmin == 0): ?>
                                            R$ 0,00 (MVP)
                                        <?php else: ?>
                                            R$ 0,00
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="simulator-item total">
                                    <span class="simulator-label">
                                        <?php if ($isStoreMvp): ?>
                                            Total (<?php echo number_format($porcentagemTotal, 1); ?>%) - MVP:
                                        <?php else: ?>
                                            Comissão Total a Pagar (<?php echo number_format($porcentagemTotal, 1); ?>%):
                                        <?php endif; ?>
                                    </span>
                                    <span class="simulator-value" id="resumoComissaoTotal">R$ 0,00</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Campos ocultos para envio -->
                        <input type="hidden" id="usar_saldo" name="usar_saldo" value="nao">
                        <input type="hidden" id="valor_saldo_usado_hidden" name="valor_saldo_usado" value="0">
                        <input type="hidden" id="cliente_id_hidden" name="cliente_id_hidden" value="">
                        
                        <div class="step-navigation">
                            <button type="button" class="nav-btn nav-btn-secondary" id="backToStep3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                                Voltar
                            </button>
                            <button type="submit" class="nav-btn nav-btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                ✨ Registrar Venda
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="../../assets/js/views/stores/register-transaction.js"></script>
</body>
</html>
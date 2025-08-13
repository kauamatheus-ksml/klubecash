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
                
                // Registrar transação
                $result = TransactionController::registerTransaction($transactionData);
                
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
    
    <!-- CSS Customizado para a nova interface -->
    <style>
        
        /* ========================================
           VARIÁVEIS CSS E RESET
        ======================================== */
        :root {
            --primary-color: #FF7A00;
            --primary-dark: #E06E00;
            --primary-light: #FFF0E6;
            --secondary-color: #2A3F54;
            --success-color: #28A745;
            --success-light: #D4F6DD;
            --warning-color: #FFC107;
            --warning-light: #FFF8E1;
            --danger-color: #DC3545;
            --danger-light: #FADBD8;
            --info-color: #17A2B8;
            --info-light: #D1ECF1;
            --light-gray: #F8F9FA;
            --medium-gray: #6C757D;
            --dark-gray: #343A40;
            --white: #FFFFFF;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.15);
            --border-radius: 12px;
            --border-radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--dark-gray);
            line-height: 1.6;
            min-height: 100vh;
        }

        /* ========================================
           LAYOUT PRINCIPAL
        ======================================== */
        .dashboard-container {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        .main-content {
            flex: 1;
            padding: 1rem;
            margin-left: 280px;
            transition: var(--transition);
            min-height: 100vh;
            background: transparent;
        }

        /* ========================================
           HEADER DA PÁGINA
        ======================================== */
        .page-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem 0;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .page-subtitle {
            color: var(--medium-gray);
            font-size: 1.1rem;
            font-weight: 400;
        }

        /* ========================================
           INDICADOR DE PROGRESSO
        ======================================== */
        .progress-container {
            max-width: 800px;
            margin: 0 auto 3rem;
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--shadow-md);
        }

        .progress-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin-bottom: 1rem;
        }

        .progress-bar::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--light-gray);
            border-radius: 2px;
            z-index: 1;
        }

        .progress-line {
            position: absolute;
            top: 20px;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
            border-radius: 2px;
            z-index: 2;
            transition: width 0.5s ease;
            width: 0%;
        }

        .progress-step {
            position: relative;
            z-index: 3;
            background: var(--white);
            border: 3px solid var(--light-gray);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            color: var(--medium-gray);
            transition: var(--transition);
        }

        .progress-step.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--white);
            transform: scale(1.1);
        }

        .progress-step.completed {
            background: var(--success-color);
            border-color: var(--success-color);
            color: var(--white);
        }

        .progress-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 1rem;
        }

        .progress-label {
            text-align: center;
            font-size: 0.875rem;
            color: var(--medium-gray);
            font-weight: 500;
            flex: 1;
        }

        .progress-label.active {
            color: var(--primary-color);
            font-weight: 700;
        }

        /* ========================================
           CONTAINER DO FORMULÁRIO
        ======================================== */
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .step-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2.5rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            display: none;
            animation: fadeInUp 0.5s ease-out;
        }

        .step-card.active {
            display: block;
        }

        .step-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .step-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: var(--white);
            font-size: 2rem;
        }

        .step-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }

        .step-description {
            color: var(--medium-gray);
            font-size: 1rem;
            max-width: 500px;
            margin: 0 auto;
        }

        /* ========================================
           CAMPOS DE FORMULÁRIO
        ======================================== */
        .form-group {
            margin-bottom: 2rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--secondary-color);
            font-size: 1rem;
        }

        .form-label.required::after {
            content: '*';
            color: var(--danger-color);
            margin-left: 4px;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid #E1E5EA;
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            font-family: inherit;
            transition: var(--transition);
            background: var(--white);
            box-shadow: var(--shadow-sm);
        }

        .form-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(255, 122, 0, 0.1);
            outline: none;
            transform: translateY(-1px);
        }

        .form-input:invalid {
            border-color: var(--danger-color);
            box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.1);
        }

        .form-help {
            display: block;
            margin-top: 0.5rem;
            color: var(--medium-gray);
            font-size: 0.875rem;
            line-height: 1.4;
        }

        /* ========================================
           BUSCA DE CLIENTE
        ======================================== */
        .client-search-container {
            background: linear-gradient(135deg, var(--info-light) 0%, var(--white) 100%);
            border: 2px dashed var(--info-color);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .search-input-group {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            margin-bottom: 1rem;
        }

        .search-input-wrapper {
            flex: 1;
        }

        .search-btn {
            padding: 1rem 2rem;
            background: linear-gradient(135deg, var(--info-color) 0%, #0D8AA8 100%);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: var(--transition);
            white-space: nowrap;
            min-width: 140px;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .search-btn:disabled {
            background: var(--medium-gray);
            cursor: not-allowed;
            transform: none;
        }

        .loading-spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top: 2px solid var(--white);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: none;
        }

        /* ========================================
           CARD DE INFORMAÇÕES DO CLIENTE
        ======================================== */
        .client-info-card {
            margin-top: 1.5rem;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border: 2px solid;
            background: var(--white);
            display: none;
            animation: fadeInUp 0.5s ease-out;
        }

        .client-info-card.success {
            border-color: var(--success-color);
            background: linear-gradient(135deg, var(--white) 0%, var(--success-light) 100%);
        }

        .client-info-card.error {
            border-color: var(--danger-color);
            background: linear-gradient(135deg, var(--white) 0%, var(--danger-light) 100%);
        }

        .client-info-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .client-info-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }

        .client-info-title {
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
            color: var(--secondary-color);
        }

        .client-info-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-left: 40px;
        }

        .client-info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .client-info-label {
            font-weight: 500;
            color: var(--medium-gray);
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .client-info-value {
            color: var(--dark-gray);
            font-weight: 600;
            font-size: 1rem;
        }

        /* ========================================
           SEÇÃO DE SALDO
        ======================================== */
        .balance-section {
            background: linear-gradient(135deg, var(--success-light) 0%, var(--white) 100%);
            border: 2px solid var(--success-color);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin: 2rem 0;
            display: none;
        }

        .balance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .balance-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .balance-available {
            font-size: 1.2rem;
            font-weight: 500;
            color: var(--dark-gray);
        }

        .balance-value {
            font-weight: 700;
            color: var(--success-color);
            font-size: 1.5rem;
        }

        .balance-toggle {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 32px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #CBD5E0;
            transition: var(--transition);
            border-radius: 32px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 24px;
            width: 24px;
            left: 4px;
            bottom: 4px;
            background: var(--white);
            transition: var(--transition);
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        input:checked + .toggle-slider {
            background: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 122, 0, 0.2);
        }

        input:checked + .toggle-slider:before {
            transform: translateX(28px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }

        .balance-controls {
            border-top: 2px solid rgba(40, 167, 69, 0.1);
            padding-top: 1.5rem;
            margin-top: 1.5rem;
            display: none;
        }

        .balance-input-group {
            margin-bottom: 1rem;
        }

        .balance-buttons {
            display: flex;
            gap: 0.75rem;
            margin: 1rem 0;
            flex-wrap: wrap;
        }

        .balance-btn {
            padding: 0.75rem 1.25rem;
            border: 2px solid var(--primary-color);
            background: var(--white);
            color: var(--primary-color);
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: var(--transition);
        }

        .balance-btn:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* ========================================
           GERADOR DE CÓDIGO
        ======================================== */
        .code-input-group {
            display: flex;
            gap: 0.75rem;
            align-items: stretch;
        }

        .code-input-group .form-input {
            flex: 1;
            margin-bottom: 0;
        }

        .generate-code-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, var(--secondary-color) 0%, #1a2332 100%);
            color: var(--white);
            border: 2px solid var(--secondary-color);
            border-radius: var(--border-radius-sm);
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: var(--transition);
            white-space: nowrap;
            min-width: 100px;
            box-shadow: var(--shadow-sm);
        }

        .generate-code-btn:hover {
            background: linear-gradient(135deg, #1a2332 0%, var(--secondary-color) 100%);
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
        }

        .generate-code-btn svg {
            flex-shrink: 0;
            transition: transform 0.3s ease;
        }

        .generate-code-btn:hover svg {
            transform: rotate(180deg);
        }

        /* ========================================
           SIMULAÇÃO DE CASHBACK
        ======================================== */
        .cashback-simulator {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--white) 100%);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin: 2rem 0;
            border: 2px solid var(--primary-color);
        }

        .simulator-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .simulator-icon {
            width: 60px;
            height: 60px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.5rem;
        }

        .simulator-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .simulator-details {
            background: var(--white);
            border-radius: var(--border-radius-sm);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(0,0,0,0.05);
        }

        .simulator-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: var(--transition);
        }

        .simulator-item:hover {
            background: var(--light-gray);
        }

        .simulator-item:last-child {
            border-bottom: none;
        }

        .simulator-item.total {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .simulator-item.balance-used {
            background: linear-gradient(135deg, var(--success-light) 0%, var(--white) 100%);
            border-left: 4px solid var(--success-color);
        }

        .simulator-label {
            color: var(--secondary-color);
            font-weight: 500;
        }

        .simulator-item.total .simulator-label {
            color: var(--white);
        }

        .simulator-value {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary-color);
        }

        .simulator-item.total .simulator-value {
            color: var(--white);
        }

        /* ========================================
           NAVEGAÇÃO ENTRE ETAPAS
        ======================================== */
        .step-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid rgba(0,0,0,0.05);
        }

        .nav-btn {
            padding: 1rem 2rem;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
            min-width: 160px;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: var(--white);
            border-color: var(--primary-color);
        }

        .nav-btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .nav-btn-secondary {
            background: var(--white);
            color: var(--dark-gray);
            border-color: #E1E5EA;
        }

        .nav-btn-secondary:hover {
            background: var(--light-gray);
            transform: translateY(-2px);
        }

        .nav-btn:disabled {
            background: var(--medium-gray);
            color: var(--white);
            cursor: not-allowed;
            transform: none;
            opacity: 0.6;
        }

        /* ========================================
           ALERTAS
        ======================================== */
        .alert {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            border-left: 4px solid;
            animation: slideIn 0.5s ease-out;
        }

        .alert.success {
            border-color: var(--success-color);
            background: linear-gradient(135deg, var(--white) 0%, var(--success-light) 100%);
        }

        .alert.error {
            border-color: var(--danger-color);
            background: linear-gradient(135deg, var(--white) 0%, var(--danger-light) 100%);
        }

        .alert svg {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
        }

        .alert h4 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .alert p {
            margin: 0;
            color: var(--medium-gray);
            font-size: 0.9rem;
        }

        /* ========================================
           ANIMAÇÕES
        ======================================== */
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ========================================
           RESPONSIVIDADE
        ======================================== */
        @media (max-width: 1199.98px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
        }

        @media (max-width: 767.98px) {
            .page-title {
                font-size: 2rem;
            }

            .step-card {
                padding: 1.5rem;
            }

            .search-input-group {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }

            .search-btn {
                width: 100%;
                justify-content: center;
            }

            .step-navigation {
                flex-direction: column;
                gap: 1rem;
            }

            .nav-btn {
                width: 100%;
                justify-content: center;
            }

            .client-info-details {
                grid-template-columns: 1fr;
                margin-left: 0;
            }

            .simulator-item {
                padding: 1rem;
                font-size: 0.9rem;
            }

            .balance-buttons {
                justify-content: space-between;
            }

            .balance-btn {
                flex: 1;
                text-align: center;
                min-width: 0;
            }

            .code-input-group {
                flex-direction: column;
                gap: 1rem;
            }

            .generate-code-btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 575.98px) {
            .step-card,
            .progress-container {
                padding: 1.25rem;
            }

            .step-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }

            .step-title {
                font-size: 1.5rem;
            }

            .nav-btn {
                padding: 0.875rem 1.5rem;
                font-size: 0.9rem;
            }

            .progress-step {
                width: 35px;
                height: 35px;
                font-size: 0.875rem;
            }

            .progress-label {
                font-size: 0.75rem;
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
                <h1 class="page-title">✨ Registrar Nova Venda</h1>
                <p class="page-subtitle">Cadastre sua venda em 4 passos simples e ofereça cashback aos seus clientes</p>
            </div>
            
            <!-- Alertas de Sucesso/Erro -->
            <?php if ($success): ?>
            <div class="alert success">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <div>
                    <h4>🎉 Transação registrada com sucesso!</h4>
                    <p>O cashback será liberado para o cliente assim que o pagamento da comissão for realizado e aprovado.</p>
                </div>
                <a href="<?php echo STORE_REGISTER_TRANSACTION_URL; ?>" class="nav-btn nav-btn-primary">Registrar Nova</a>
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
                                    <span class="simulator-label">Cashback do Cliente (5%):</span>
                                    <span class="simulator-value" id="resumoCashbackCliente">R$ 0,00</span>
                                </div>
                                <div class="simulator-item">
                                    <span class="simulator-label">Receita Klube Cash (5%):</span>
                                    <span class="simulator-value" id="resumoReceitaAdmin">R$ 0,00</span>
                                </div>
                                <div class="simulator-item total">
                                    <span class="simulator-label">Comissão Total a Pagar (10%):</span>
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
    
    <script>
        // ========================================
        // VARIÁVEIS GLOBAIS
        // ========================================

        let currentStep = 1;
        let clientData = null;
        let clientBalance = 0;
        const storeId = <?php echo $storeId; ?>;

        // ========================================
        // INICIALIZAÇÃO
        // ========================================

        document.addEventListener('DOMContentLoaded', function() {
            initializeEventListeners();
            updateProgressBar();
        });

        function initializeEventListeners() {
            // Navegação entre passos
            document.getElementById('nextToStep2').addEventListener('click', () => goToStep(2));
            document.getElementById('nextToStep3').addEventListener('click', () => goToStep(3));
            document.getElementById('nextToStep4').addEventListener('click', () => goToStep(4));
            document.getElementById('backToStep1').addEventListener('click', () => goToStep(1));
            document.getElementById('backToStep2').addEventListener('click', () => goToStep(2));
            document.getElementById('backToStep3').addEventListener('click', () => goToStep(3));

            // Busca de cliente
            document.getElementById('searchClientBtn').addEventListener('click', buscarCliente);
            document.getElementById('search_term').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscarCliente();
                }
            });

            // Eventos do formulário
            document.getElementById('valor_total').addEventListener('input', updateSimulation);
            document.getElementById('codigo_transacao').addEventListener('input', updateSummary);
            document.getElementById('generateCodeBtn').addEventListener('click', gerarCodigoTransacao);

            // Eventos de saldo
            document.getElementById('usarSaldoCheck').addEventListener('change', toggleUsarSaldo);
            document.getElementById('valorSaldoUsado').addEventListener('input', updateBalancePreview);
            document.getElementById('usarTodoSaldo').addEventListener('click', () => useBalanceAmount(1));
            document.getElementById('usar50Saldo').addEventListener('click', () => useBalanceAmount(0.5));
            document.getElementById('limparSaldo').addEventListener('click', () => useBalanceAmount(0));

            // Validação do formulário
            document.getElementById('transactionForm').addEventListener('submit', validateForm);
        }

        // ========================================
        // NAVEGAÇÃO ENTRE PASSOS
        // ========================================

        function goToStep(step) {
            // Validar passo atual antes de prosseguir
            if (step > currentStep && !validateCurrentStep()) {
                return;
            }

            // Esconder todos os cards
            document.querySelectorAll('.step-card').forEach(card => {
                card.classList.remove('active');
            });

            // Mostrar card do passo atual
            document.getElementById(`stepCard${step}`).classList.add('active');

            // Atualizar progresso
            currentStep = step;
            updateProgressBar();

            // Atualizar resumo se for o último passo
            if (step === 4) {
                updateSummary();
            }

            // Scroll para o topo
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function validateCurrentStep() {
            switch (currentStep) {
                case 1:
                    if (!clientData) {
                        showNotification('Por favor, busque e selecione um cliente primeiro', 'warning');
                        return false;
                    }
                    return true;

                case 2:
                    const valorTotal = parseFloat(document.getElementById('valor_total').value) || 0;
                    const codigoTransacao = document.getElementById('codigo_transacao').value.trim();

                    if (valorTotal <= 0) {
                        showNotification('Por favor, informe o valor total da venda', 'warning');
                        document.getElementById('valor_total').focus();
                        return false;
                    }

                    if (!codigoTransacao) {
                        showNotification('Por favor, informe o código da transação', 'warning');
                        document.getElementById('codigo_transacao').focus();
                        return false;
                    }
                    return true;

                case 3:
                    return true; // Passo de saldo é opcional

                default:
                    return true;
            }
        }

        function updateProgressBar() {
            const progressLine = document.getElementById('progressLine');
            const progressSteps = document.querySelectorAll('.progress-step');
            const progressLabels = document.querySelectorAll('.progress-label');

            // Calcular porcentagem de progresso
            const progressPercent = ((currentStep - 1) / 3) * 100;
            progressLine.style.width = `${progressPercent}%`;

            // Atualizar status dos passos
            progressSteps.forEach((step, index) => {
                const stepNumber = index + 1;
                step.classList.remove('active', 'completed');
                
                if (stepNumber < currentStep) {
                    step.classList.add('completed');
                    step.innerHTML = '✓';
                } else if (stepNumber === currentStep) {
                    step.classList.add('active');
                    step.innerHTML = stepNumber;
                } else {
                    step.innerHTML = stepNumber;
                }
            });

            // Atualizar labels
            progressLabels.forEach((label, index) => {
                label.classList.remove('active');
                if (index + 1 === currentStep) {
                    label.classList.add('active');
                }
            });
        }

        // ========================================
        // BUSCA DE CLIENTE
        // ========================================

        async function buscarCliente() {
            const searchTerm = document.getElementById('search_term').value.trim();
            const searchBtn = document.getElementById('searchClientBtn');
            const clientInfoCard = document.getElementById('clientInfoCard');

            if (!searchTerm) {
                showNotification('Por favor, digite um email, CPF ou telefone válido', 'warning');
                return;
            }

            // Estado de loading
            searchBtn.disabled = true;
            searchBtn.querySelector('.btn-text').textContent = 'Buscando...';
            searchBtn.querySelector('.loading-spinner').style.display = 'inline-block';

            try {
                const response = await fetch('../../api/store-client-search.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'search_client',
                        search_term: searchTerm,
                        store_id: storeId
                    })
                });

                const data = await response.json();

                if (data.status) {
                    clientData = data.data;
                    clientBalance = data.data.saldo || 0;
                    mostrarInfoCliente(data.data);
                    hideVisitorSection(); // Esconder seção de visitante
                    document.getElementById('nextToStep2').disabled = false;
                } else {
                    mostrarErroCliente(data.message);
                    
                    // Mostrar opção de criar visitante se disponível
                    if (data.can_create_visitor) {
                        currentSearchTerm = data.search_term;
                        currentSearchType = data.search_type;
                        showVisitorOption();
                    }
                    
                    document.getElementById('nextToStep2').disabled = true;
                }
            } catch (error) {
                console.error('Erro ao buscar cliente:', error);
                mostrarErroCliente('Erro ao buscar cliente. Tente novamente.');
                document.getElementById('nextToStep2').disabled = true;
            } finally {
                searchBtn.disabled = false;
                searchBtn.querySelector('.btn-text').textContent = 'Buscar Cliente';
                searchBtn.querySelector('.loading-spinner').style.display = 'none';
            }
        }

        function mostrarInfoCliente(client) {
            const clientInfoCard = document.getElementById('clientInfoCard');
            const clientInfoTitle = document.getElementById('clientInfoTitle');
            const clientInfoDetails = document.getElementById('clientInfoDetails');

            clientInfoCard.className = 'client-info-card success';
            clientInfoCard.style.display = 'block';
            clientInfoTitle.textContent = '✅ Cliente Encontrado';

            clientInfoDetails.innerHTML = `
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
                    <span class="client-info-value">✅ Cliente ativo</span>
                </div>
                <div class="client-info-item">
                    <span class="client-info-label">Saldo disponível:</span>
                    <span class="client-info-value">${client.saldo > 0 ? '💰 R$ ' + formatCurrency(client.saldo) : '💰 Nenhum saldo disponível'}</span>
                </div>
            `;

            document.getElementById('cliente_id_hidden').value = client.id;
            showNotification('Cliente encontrado com sucesso!', 'success');
        }

        function mostrarErroCliente(message) {
            const clientInfoCard = document.getElementById('clientInfoCard');
            const clientInfoTitle = document.getElementById('clientInfoTitle');
            const clientInfoDetails = document.getElementById('clientInfoDetails');

            clientInfoCard.className = 'client-info-card error';
            clientInfoCard.style.display = 'block';
            clientInfoTitle.textContent = '❌ Cliente Não Encontrado';

            clientInfoDetails.innerHTML = `
                <div class="client-info-item">
                    <span class="client-info-value">${message}</span>
                </div>
                <div class="client-info-item">
                    <span class="client-info-value">🔍 Verifique se o email/CPF está correto e se o cliente está cadastrado no Klube Cash.</span>
                </div>
            `;

            clientData = null;
            clientBalance = 0;
            document.getElementById('cliente_id_hidden').value = '';
        }

        // ========================================
        // GERENCIAMENTO DE SALDO
        // ========================================

        function toggleUsarSaldo() {
            const usarSaldoCheck = document.getElementById('usarSaldoCheck');
            const balanceControls = document.getElementById('balanceControls');
            const usarSaldoHidden = document.getElementById('usar_saldo');

            if (usarSaldoCheck.checked) {
                balanceControls.style.display = 'block';
                usarSaldoHidden.value = 'sim';
                
                // Auto-usar todo o saldo disponível
                const valorTotal = parseFloat(document.getElementById('valor_total').value) || 0;
                if (valorTotal > 0 && clientBalance > 0) {
                    const maxUsable = Math.min(clientBalance, valorTotal);
                    document.getElementById('valorSaldoUsado').value = maxUsable.toFixed(2);
                    updateBalancePreview();
                }
            } else {
                balanceControls.style.display = 'none';
                usarSaldoHidden.value = 'nao';
                document.getElementById('valorSaldoUsado').value = 0;
                document.getElementById('valor_saldo_usado_hidden').value = '0';
                updateBalancePreview();
            }
        }

        function useBalanceAmount(percentage) {
            const valor = clientBalance * percentage;
            document.getElementById('valorSaldoUsado').value = valor.toFixed(2);
            updateBalancePreview();
        }

        function updateBalancePreview() {
            const valorSaldoUsado = parseFloat(document.getElementById('valorSaldoUsado').value) || 0;
            document.getElementById('valor_saldo_usado_hidden').value = valorSaldoUsado;
            updateSimulation();
        }

        // ========================================
        // GERAÇÃO DE CÓDIGO
        // ========================================

        function gerarCodigoTransacao() {
            const generateBtn = document.getElementById('generateCodeBtn');
            const codigoInput = document.getElementById('codigo_transacao');

            generateBtn.disabled = true;
            generateBtn.querySelector('.btn-text').textContent = 'Gerando...';

            setTimeout(() => {
                const agora = new Date();
                const ano = agora.getFullYear().toString().slice(-2);
                const mes = String(agora.getMonth() + 1).padStart(2, '0');
                const dia = String(agora.getDate()).padStart(2, '0');
                const hora = String(agora.getHours()).padStart(2, '0');
                const minuto = String(agora.getMinutes()).padStart(2, '0');
                const segundo = String(agora.getSeconds()).padStart(2, '0');
                const random = Math.floor(Math.random() * 100000).toString().padStart(5, '0');

                const codigo = `KC${ano}${mes}${dia}${hora}${minuto}${segundo}${random}`;
                codigoInput.value = codigo;

                generateBtn.disabled = false;
                generateBtn.querySelector('.btn-text').textContent = 'Gerar';

                showNotification('Código gerado com sucesso!', 'success');
            }, 800);
        }

        // ========================================
        // SIMULAÇÃO E RESUMO
        // ========================================

        function updateSimulation() {
            const valorTotal = parseFloat(document.getElementById('valor_total').value) || 0;
            const usarSaldo = document.getElementById('usar_saldo').value === 'sim';
            const valorSaldoUsado = parseFloat(document.getElementById('valor_saldo_usado_hidden').value) || 0;

            let valorPago = valorTotal;
            if (usarSaldo && valorSaldoUsado > 0) {
                valorPago = Math.max(0, valorTotal - valorSaldoUsado);
            }

            // Atualizar seção de saldo se cliente tem saldo
            if (clientBalance > 0) {
                document.getElementById('balanceSection').style.display = 'block';
                document.getElementById('saldoDisponivel').textContent = 'R$ ' + formatCurrency(clientBalance);
                document.getElementById('maxSaldo').textContent = 'R$ ' + formatCurrency(clientBalance);
                document.getElementById('valorSaldoUsado').max = clientBalance;
            } else {
                document.getElementById('balanceSection').style.display = 'none';
            }
        }

        function updateSummary() {
            if (!clientData) return;

            const valorTotal = parseFloat(document.getElementById('valor_total').value) || 0;
            const usarSaldo = document.getElementById('usar_saldo').value === 'sim';
            const valorSaldoUsado = parseFloat(document.getElementById('valor_saldo_usado_hidden').value) || 0;
            const codigoTransacao = document.getElementById('codigo_transacao').value;

            let valorPago = valorTotal;
            if (usarSaldo && valorSaldoUsado > 0) {
                valorPago = Math.max(0, valorTotal - valorSaldoUsado);
            }

            const cashbackCliente = valorPago * 0.05;
            const receitaAdmin = valorPago * 0.05;
            const comissaoTotal = valorPago * 0.10;

            // Atualizar resumo
            document.getElementById('resumoCliente').textContent = clientData.nome;
            document.getElementById('resumoCodigo').textContent = codigoTransacao || 'Não informado';
            document.getElementById('resumoValorVenda').textContent = 'R$ ' + formatCurrency(valorTotal);
            document.getElementById('resumoValorPago').textContent = 'R$ ' + formatCurrency(valorPago);
            document.getElementById('resumoCashbackCliente').textContent = 'R$ ' + formatCurrency(cashbackCliente);
            document.getElementById('resumoReceitaAdmin').textContent = 'R$ ' + formatCurrency(receitaAdmin);
            document.getElementById('resumoComissaoTotal').textContent = 'R$ ' + formatCurrency(comissaoTotal);

            // Mostrar/esconder linha de saldo usado
            const resumoSaldoRow = document.getElementById('resumoSaldoRow');
            if (usarSaldo && valorSaldoUsado > 0) {
                resumoSaldoRow.style.display = 'flex';
                document.getElementById('resumoSaldoUsado').textContent = 'R$ ' + formatCurrency(valorSaldoUsado);
            } else {
                resumoSaldoRow.style.display = 'none';
            }
        }

        // ========================================
        // UTILITÁRIOS
        // ========================================

        function formatCurrency(value) {
            return parseFloat(value).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            
            const icons = {
                success: '✅',
                warning: '⚠️',
                error: '❌',
                info: 'ℹ️'
            };

            notification.innerHTML = `
                <span style="font-size: 1.2rem; margin-right: 0.5rem;">${icons[type] || icons.info}</span>
                <span>${message}</span>
            `;

            const colors = {
                success: '#28A745',
                warning: '#FFC107',
                error: '#DC3545',
                info: '#17A2B8'
            };

            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${colors[type] || colors.info};
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                font-weight: 600;
                max-width: 350px;
                animation: slideInRight 0.3s ease-out;
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease-in forwards';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }

        function validateForm(e) {
            console.log('🔍 Validando formulário...', {
                clientData: clientData,
                currentStep: currentStep
            });

            // Verificar se cliente foi selecionado
            if (!clientData) {
                e.preventDefault();
                showNotification('Por favor, selecione um cliente primeiro', 'error');
                goToStep(1);
                return false;
            }

            // Verificar valor total
            const valorTotal = parseFloat(document.getElementById('valor_total').value) || 0;
            if (valorTotal <= 0) {
                e.preventDefault();
                showNotification('Por favor, informe o valor total da venda', 'error');
                goToStep(2);
                return false;
            }

            // Verificar código da transação
            const codigoTransacao = document.getElementById('codigo_transacao').value.trim();
            if (!codigoTransacao) {
                e.preventDefault();
                showNotification('Por favor, informe o código da transação', 'error');
                goToStep(2);
                return false;
            }

            // Se chegou até aqui, está tudo ok
            console.log('✅ Validação passou - enviando formulário');
            showNotification('Registrando venda...', 'info');
            
            // Deixar o formulário ser enviado normalmente
            return true;
        }

        // Adicionar estilos de animação
        const animationStyles = document.createElement('style');
        animationStyles.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(animationStyles);


        // === FUNÇÕES PARA CLIENTE VISITANTE ===
        let currentSearchTerm = '';
        let currentSearchType = '';

        function showVisitorOption() {
            const visitorSection = document.getElementById('visitor-client-section');
            if (visitorSection) {
                visitorSection.classList.add('show');
                
                // Preparar o campo de acordo com o tipo de busca
                const visitorPhoneInput = document.getElementById('visitor-phone');
                if (currentSearchType === 'telefone') {
                    visitorPhoneInput.value = formatPhone(currentSearchTerm);
                    visitorPhoneInput.readOnly = true;
                } else {
                    visitorPhoneInput.value = '';
                    visitorPhoneInput.readOnly = false;
                }
            }
        }

        function hideVisitorSection() {
            const visitorSection = document.getElementById('visitor-client-section');
            if (visitorSection) {
                visitorSection.classList.remove('show');
                
                // Limpar campos
                document.getElementById('visitor-name').value = '';
                document.getElementById('visitor-phone').value = '';
                document.getElementById('visitor-phone').readOnly = false;
            }
        }

        async function createVisitorClient() {
            const nome = document.getElementById('visitor-name').value.trim();
            const telefone = document.getElementById('visitor-phone').value.trim();

            // Validações
            if (!nome || nome.length < 2) {
                showNotification('Nome é obrigatório e deve ter pelo menos 2 caracteres.', 'warning');
                return;
            }

            const phoneClean = telefone.replace(/[^0-9]/g, '');
            if (!phoneClean || phoneClean.length < 10) {
                showNotification('Telefone é obrigatório e deve ter pelo menos 10 dígitos.', 'warning');
                return;
            }

            try {
                const response = await fetch('../../api/store-client-search.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_visitor_client',
                        nome: nome,
                        telefone: phoneClean,
                        store_id: storeId
                    })
                });

                const data = await response.json();

                if (data.status) {
                    // Cliente visitante criado com sucesso
                    showNotification('Cliente visitante criado com sucesso!', 'success');
                    clientData = data.data;
                    clientBalance = 0;
                    mostrarInfoCliente(data.data);
                    hideVisitorSection();
                    document.getElementById('nextToStep2').disabled = false;
                    
                    // Atualizar campo de busca
                    document.getElementById('search_term').value = telefone;
                } else {
                    showNotification(data.message, 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                showNotification('Erro ao criar cliente visitante. Tente novamente.', 'error');
            }
        }

        function cancelVisitorCreation() {
            hideVisitorSection();
            document.getElementById('search_term').focus();
        }

        function formatPhone(phone) {
            if (!phone) return '';
            const cleaned = phone.replace(/[^0-9]/g, '');
            
            if (cleaned.length === 11) {
                return `(${cleaned.slice(0, 2)}) ${cleaned.slice(2, 7)}-${cleaned.slice(7)}`;
            } else if (cleaned.length === 10) {
                return `(${cleaned.slice(0, 2)}) ${cleaned.slice(2, 6)}-${cleaned.slice(6)}`;
            }
            
            return phone;
        }
    </script>
</body>
</html>
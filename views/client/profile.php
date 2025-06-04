<?php
// views/client/profile.php
// Definir o menu ativo
$activeMenu = 'perfil';

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/ClientController.php';

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se o usuário está logado e é cliente
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== USER_TYPE_CLIENT) {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

$userId = $_SESSION['user_id'];

// Inicializar variáveis para mensagens de feedback
$personalInfoMessage = '';
$personalInfoSuccess = false;
$addressMessage = '';
$addressSuccess = false;
$passwordMessage = '';
$passwordSuccess = false;

// Função para registrar erros em log e exibir mensagem amigável
function logError($message, $error) {
    error_log($message . ': ' . $error);
    return "Ops! Algo deu errado. Tente novamente em alguns instantes.";
}

// Processamento de formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Formulário de informações pessoais
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'personal_info') {
        try {
            $updateData = [
                'nome' => $_POST['nome'] ?? '',
                'cpf' => $_POST['cpf'] ?? '', // Novo campo CPF
                'contato' => [
                    'telefone' => $_POST['telefone'] ?? '',
                    'celular' => $_POST['celular'] ?? '',
                    'email_alternativo' => $_POST['email_alternativo'] ?? ''
                ]
            ];
            
            $result = ClientController::updateProfile($userId, $updateData);
            $personalInfoSuccess = $result['status'];
            $personalInfoMessage = $result['message'];
            
        } catch (Exception $e) {
            $personalInfoSuccess = false;
            $personalInfoMessage = logError('Erro ao atualizar informações pessoais', $e->getMessage());
        }
    }
    
    // Formulário de endereço (mantém o mesmo)
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'address') {
        try {
            $updateData = [
                'endereco' => [
                    'cep' => $_POST['cep'] ?? '',
                    'logradouro' => $_POST['logradouro'] ?? '',
                    'numero' => $_POST['numero'] ?? '',
                    'complemento' => $_POST['complemento'] ?? '',
                    'bairro' => $_POST['bairro'] ?? '',
                    'cidade' => $_POST['cidade'] ?? '',
                    'estado' => $_POST['estado'] ?? '',
                    'principal' => 1
                ]
            ];
            
            $result = ClientController::updateProfile($userId, $updateData);
            $addressSuccess = $result['status'];
            $addressMessage = $result['message'];
            
        } catch (Exception $e) {
            $addressSuccess = false;
            $addressMessage = logError('Erro ao atualizar endereço', $e->getMessage());
        }
    }
    
    // Formulário de alteração de senha (mantém o mesmo)
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'password') {
        try {
            $senhaAtual = $_POST['senha_atual'] ?? '';
            $novaSenha = $_POST['nova_senha'] ?? '';
            $confirmarSenha = $_POST['confirmar_senha'] ?? '';
            
            // Validação básica
            if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
                $passwordSuccess = false;
                $passwordMessage = 'Por favor, preencha todos os campos de senha.';
            } else if ($novaSenha !== $confirmarSenha) {
                $passwordSuccess = false;
                $passwordMessage = 'As senhas não são iguais. Verifique e tente novamente.';
            } else if (strlen($novaSenha) < PASSWORD_MIN_LENGTH) {
                $passwordSuccess = false;
                $passwordMessage = 'Sua nova senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres.';
            } else {
                $updateData = [
                    'senha_atual' => $senhaAtual,
                    'nova_senha' => $novaSenha
                ];
                
                $result = ClientController::updateProfile($userId, $updateData);
                $passwordSuccess = $result['status'];
                $passwordMessage = $result['message'];
            }
            
        } catch (Exception $e) {
            $passwordSuccess = false;
            $passwordMessage = logError('Erro ao atualizar senha', $e->getMessage());
        }
    }
}

// Carregar dados do perfil depois de qualquer atualização
try {
    $profileResult = ClientController::getProfileData($userId);
    
    if (!$profileResult['status']) {
        $error = true;
        $errorMessage = $profileResult['message'];
        $profileData = [];
    } else {
        $error = false;
        $profileData = $profileResult['data'];
        
        // Garantir que as chaves existam para evitar erros
        if (!isset($profileData['contato']) || !is_array($profileData['contato'])) {
            $profileData['contato'] = [];
        }
        
        if (!isset($profileData['endereco']) || !is_array($profileData['endereco'])) {
            $profileData['endereco'] = [];
        }
        
        if (!isset($profileData['estatisticas']) || !is_array($profileData['estatisticas'])) {
            $profileData['estatisticas'] = [
                'total_cashback' => 0,
                'total_transacoes' => 0,
                'total_compras' => 0,
                'total_lojas_utilizadas' => 0
            ];
        }
    }
} catch (Exception $e) {
    $error = true;
    $errorMessage = logError('Erro ao carregar dados do perfil', $e->getMessage());
    $profileData = [];
}

// Calcular progresso do perfil (ATUALIZADO para incluir CPF)
$profileCompletion = 0;
$totalSteps = 6; // Mantém 6 passos
$completedSteps = 0;

if (!empty($profileData['perfil']['nome'])) $completedSteps++;

// MODIFICADO: CPF conta como completo se existe (editável ou não)
if (!empty($profileData['perfil']['cpf'])) {
    $completedSteps++;
    $cpfPendente = false; // Se já tem CPF, não está mais pendente
} else {
    $cpfPendente = $profileData['perfil']['cpf_editavel']; // Só pendente se ainda pode editar
}

if (!empty($profileData['contato']['telefone']) || !empty($profileData['contato']['celular'])) $completedSteps++;
if (!empty($profileData['contato']['email_alternativo'])) $completedSteps++;
if (!empty($profileData['endereco']['cep']) && !empty($profileData['endereco']['logradouro'])) $completedSteps++;
if (!empty($profileData['endereco']['cidade']) && !empty($profileData['endereco']['estado'])) $completedSteps++;

$profileCompletion = ($completedSteps / $totalSteps) * 100;

// Verificar se CPF está pendente para mostrar alerta
$cpfPendente = empty($profileData['perfil']['cpf']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* Mantém todo o CSS existente e adiciona apenas estes estilos para o alerta de CPF */
        
        /* Alerta de CPF pendente */
        .cpf-alert {
            background: linear-gradient(135deg, #FF7A00, #FF9500);
            color: white;
            padding: 20px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            text-align: center;
            box-shadow: var(--shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .cpf-alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .cpf-alert h3 {
            margin-bottom: 10px;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .cpf-alert p {
            margin: 0;
            opacity: 0.95;
        }

        /* Destaque para o campo CPF quando necessário */
        .cpf-required .form-control {
            border-color: var(--warning-color);
            background-color: #FFF9E6;
        }

        .cpf-required .form-label::after {
            content: ' (Obrigatório)';
            color: var(--warning-color);
            font-weight: 700;
            font-size: 0.85rem;
        }

        /* === Mantém todo o CSS existente do arquivo original === */
        :root {
            --primary-color: #FF7A00;
            --primary-light: #FFF4E8;
            --primary-dark: #E06E00;
            --white: #FFFFFF;
            --light-gray: #F8F9FA;
            --medium-gray: #6C757D;
            --dark-gray: #343A40;
            --success-color: #28A745;
            --danger-color: #DC3545;
            --warning-color: #FFC107;
            --info-color: #17A2B8;
            --border-radius: 16px;
            --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.05);
            --shadow-medium: 0 4px 20px rgba(0, 0, 0, 0.1);
            --shadow-strong: 0 8px 30px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --gradient-primary: linear-gradient(135deg, #FF7A00 0%, #FF9500 100%);
            --gradient-light: linear-gradient(135deg, #FFF4E8 0%, #FFE8CC 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            line-height: 1.6;
            color: var(--dark-gray);
        }

        /* Container principal */
        .profile-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            margin-top: 80px;
        }

        /* Header do perfil */
        .profile-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .profile-header h1 {
            font-size: clamp(2rem, 4vw, 3rem);
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .profile-header p {
            font-size: 1.1rem;
            color: var(--medium-gray);
            max-width: 600px;
            margin: 0 auto;
        }

        /* Indicador de progresso */
        .progress-section {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-medium);
            position: relative;
            overflow: hidden;
        }

        .progress-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        .progress-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .progress-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .progress-title i {
            color: var(--primary-color);
            font-size: 1.5rem;
        }

        .progress-percentage {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .progress-bar-container {
            width: 100%;
            height: 12px;
            background: var(--light-gray);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .progress-bar {
            height: 100%;
            background: var(--gradient-primary);
            border-radius: 6px;
            transition: var(--transition);
            position: relative;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: progress-shine 2s infinite;
        }

        @keyframes progress-shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .progress-text {
            color: var(--medium-gray);
            font-size: 0.95rem;
        }

        /* Layout principal */
        .profile-layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 30px;
            align-items: start;
        }

        /* Card de informações do usuário */
        .user-info-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 40px 30px;
            box-shadow: var(--shadow-medium);
            text-align: center;
            position: sticky;
            top: 100px;
        }

        .user-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--white);
            font-weight: 700;
            margin: 0 auto 25px;
            position: relative;
            box-shadow: var(--shadow-medium);
        }

        .user-avatar::after {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: var(--gradient-primary);
            z-index: -1;
            opacity: 0.3;
        }

        .user-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--dark-gray);
        }

        .user-email {
            color: var(--medium-gray);
            margin-bottom: 25px;
        }

        .user-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 30px;
        }

        .stat-item {
            background: var(--primary-light);
            padding: 20px 15px;
            border-radius: 12px;
            border: 2px solid transparent;
            transition: var(--transition);
        }

        .stat-item:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .stat-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--medium-gray);
            margin-top: 4px;
        }

        /* Cards de formulário */
        .form-section {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .form-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 0;
            box-shadow: var(--shadow-medium);
            overflow: hidden;
            transition: var(--transition);
        }

        .form-card:hover {
            box-shadow: var(--shadow-strong);
            transform: translateY(-2px);
        }

        .form-card-header {
            background: var(--gradient-light);
            padding: 25px 30px;
            border-bottom: 1px solid #e9ecef;
            position: relative;
        }

        .form-card-header::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 30px;
            right: 30px;
            height: 2px;
            background: var(--gradient-primary);
        }

        .form-card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--dark-gray);
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0;
        }

        .form-card-title i {
            color: var(--primary-color);
            font-size: 1.4rem;
        }

        .form-card-body {
            padding: 30px;
        }

        /* Alertas */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .alert i {
            font-size: 1.2rem;
        }

        /* Formulários */
        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark-gray);
            font-size: 0.95rem;
        }

        .form-label .required {
            color: var(--danger-color);
            margin-left: 4px;
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: var(--transition);
            background: var(--white);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.1);
            transform: translateY(-1px);
        }

        .form-control:disabled {
            background: var(--light-gray);
            color: var(--medium-gray);
            cursor: not-allowed;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-help {
            font-size: 0.85rem;
            color: var(--medium-gray);
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Botões */
        .btn {
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            min-width: 140px;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
            box-shadow: 0 4px 15px rgba(255, 122, 0, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 122, 0, 0.4);
        }

        .btn i {
            font-size: 1.1rem;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .profile-container {
                padding: 15px;
                margin-top: 70px;
            }

            .profile-layout {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .user-info-card {
                position: static;
                padding: 25px 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .progress-header {
                flex-direction: column;
                text-align: center;
            }

            .user-stats {
                grid-template-columns: 1fr;
                gap: 10px;
            }

            .form-card-header,
            .form-card-body {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .profile-container {
                padding: 10px;
            }

            .progress-section,
            .form-card-body {
                padding: 20px 15px;
            }

            .user-avatar {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }
        }

        /* Animações */
        .form-card {
            opacity: 0;
            transform: translateY(20px);
            animation: slideUp 0.6s ease-out forwards;
        }

        .form-card:nth-child(1) { animation-delay: 0.1s; }
        .form-card:nth-child(2) { animation-delay: 0.2s; }
        .form-card:nth-child(3) { animation-delay: 0.3s; }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Melhorias de acessibilidade */
        .form-control:focus-visible {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        .btn:focus-visible {
            outline: 2px solid var(--primary-color);
            outline-offset: 2px;
        }

        /* Estados de carregamento */
        .btn.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn.loading::after {
            content: '';
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        /* Estilos para CPF verificado/fixo */
        .cpf-verified {
            color: var(--success-color);
            font-weight: 600;
            font-size: 0.9rem;
            margin-left: 8px;
        }

        .cpf-verified i {
            margin-right: 4px;
        }

        .form-control:disabled {
            background-color: #f8f9fa !important;
            color: var(--medium-gray) !important;
            cursor: not-allowed;
            border-color: #e9ecef;
            opacity: 0.8;
        }

        .form-help.cpf-fixed {
            color: var(--success-color);
            font-weight: 500;
        }

        .form-help.cpf-fixed i {
            color: var(--success-color);
        }

        /* Destaque visual para campo CPF fixo */
        .form-group:has(.form-control:disabled) {
            position: relative;
        }

        .form-group:has(.form-control:disabled)::before {
            content: '';
            position: absolute;
            left: -5px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, var(--success-color), transparent);
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <!-- Incluir navbar -->
    <?php include_once '../components/navbar.php'; ?>
    
    <div class="profile-container">
        <!-- Header do perfil -->
        <div class="profile-header">
            <h1><i class="fas fa-user-circle"></i> Meu Perfil</h1>
            <p>Mantenha suas informações sempre atualizadas para uma experiência completa no Klube Cash</p>
        </div>

        <?php if (isset($error) && $error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php else: ?>

        <!-- Alerta de CPF pendente (NOVO) -->
        <?php if ($cpfPendente): ?>
            <div class="cpf-alert">
                <h3><i class="fas fa-exclamation-triangle"></i> Complete seu perfil</h3>
                <p>Para aproveitar todos os benefícios do Klube Cash, é necessário informar seu CPF. Isso garante maior segurança nas suas transações.</p>
            </div>
        <?php endif; ?>

        <!-- Indicador de progresso -->
        <div class="progress-section">
            <div class="progress-header">
                <div class="progress-title">
                    <i class="fas fa-chart-line"></i>
                    <span>Completude do Perfil</span>
                </div>
                <div class="progress-percentage"><?php echo round($profileCompletion); ?>%</div>
            </div>
            <div class="progress-bar-container">
                <div class="progress-bar" style="width: <?php echo $profileCompletion; ?>%"></div>
            </div>
            <p class="progress-text">
                <?php if ($profileCompletion == 100): ?>
                    🎉 Parabéns! Seu perfil está completo
                <?php elseif ($profileCompletion >= 80): ?>
                    Quase lá! Faltam poucos detalhes para completar seu perfil
                <?php elseif ($profileCompletion >= 50): ?>
                    Bom progresso! Continue preenchendo para melhorar sua experiência
                <?php else: ?>
                    Complete seu perfil para aproveitar todos os benefícios do Klube Cash
                <?php endif; ?>
            </p>
        </div>

        <!-- Layout principal -->
        <div class="profile-layout">
            <!-- Card de informações do usuário -->
            <div class="user-info-card">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($profileData['perfil']['nome'] ?? 'U', 0, 1)); ?>
                </div>
                <h2 class="user-name"><?php echo htmlspecialchars($profileData['perfil']['nome'] ?? 'Usuário'); ?></h2>
                <p class="user-email"><?php echo htmlspecialchars($profileData['perfil']['email'] ?? ''); ?></p>
                
                <!-- Mostrar CPF formatado se disponível (NOVO) -->
                <?php if (!empty($profileData['perfil']['cpf'])): ?>
                    <p class="user-cpf" style="color: var(--medium-gray); font-size: 0.9rem; margin-bottom: 25px;">
                        <i class="fas fa-id-card"></i> CPF: <?php echo Validator::formataCPF($profileData['perfil']['cpf']); ?>
                    </p>
                <?php endif; ?>
                
                <div class="user-stats">
                    <div class="stat-item">
                        <span class="stat-value">R$ <?php echo number_format($profileData['estatisticas']['total_cashback'] ?? 0, 2, ',', '.'); ?></span>
                        <span class="stat-label">Cashback Total</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $profileData['estatisticas']['total_transacoes'] ?? 0; ?></span>
                        <span class="stat-label">Transações</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $profileData['estatisticas']['total_lojas_utilizadas'] ?? 0; ?></span>
                        <span class="stat-label">Lojas Parceiras</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value">
                            <?php 
                            $status = $profileData['perfil']['status'] ?? 'Ativo';
                            echo $status === 'ativo' ? '✅ Ativo' : ucfirst($status);
                            ?>
                        </span>
                        <span class="stat-label">Status da Conta</span>
                    </div>
                </div>
            </div>

            <!-- Seção de formulários -->
            <div class="form-section">
                <!-- Formulário de informações pessoais (ATUALIZADO) -->
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">
                            <i class="fas fa-user"></i>
                            Informações Pessoais
                        </h3>
                    </div>
                    <div class="form-card-body">
                        <?php if (!empty($personalInfoMessage)): ?>
                            <div class="alert <?php echo $personalInfoSuccess ? 'alert-success' : 'alert-danger'; ?>">
                                <i class="fas <?php echo $personalInfoSuccess ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                                <?php echo htmlspecialchars($personalInfoMessage); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST" id="personalInfoForm">
                            <input type="hidden" name="form_type" value="personal_info">
                            
                            <div class="form-group">
                                <label class="form-label" for="nome">
                                    Nome Completo <span class="required">*</span>
                                </label>
                                <input type="text" id="nome" name="nome" class="form-control" 
                                       value="<?php echo htmlspecialchars($profileData['perfil']['nome'] ?? ''); ?>" 
                                       required placeholder="Digite seu nome completo">
                            </div>
                            
                            <!-- MODIFICADO: Campo CPF com verificação de edição -->
                            <div class="form-group <?php echo ($cpfPendente && $profileData['perfil']['cpf_editavel']) ? 'cpf-required' : ''; ?>">
                                <label class="form-label" for="cpf">
                                    CPF 
                                    <?php if ($cpfPendente && $profileData['perfil']['cpf_editavel']): ?>
                                        <span class="required">*</span>
                                    <?php endif; ?>
                                    <?php if (!$profileData['perfil']['cpf_editavel']): ?>
                                        <span class="cpf-verified"><i class="fas fa-check-circle"></i> Verificado</span>
                                    <?php endif; ?>
                                </label>
                                <input type="text" id="cpf" name="cpf" class="form-control" 
                                    value="<?php echo htmlspecialchars($profileData['perfil']['cpf'] ?? ''); ?>" 
                                    placeholder="000.000.000-00"
                                    maxlength="14"
                                    <?php echo !$profileData['perfil']['cpf_editavel'] ? 'disabled' : ''; ?>
                                    <?php echo ($cpfPendente && $profileData['perfil']['cpf_editavel']) ? 'required' : ''; ?>>
                                
                                <?php if (!$profileData['perfil']['cpf_editavel']): ?>
                                    <p class="form-help cpf-fixed">
                                        <i class="fas fa-lock"></i>
                                        CPF verificado e validado. Não pode ser alterado por segurança.
                                    </p>
                                <?php else: ?>
                                    <p class="form-help">
                                        <i class="fas fa-shield-alt"></i>
                                        Seu CPF é necessário para maior segurança nas transações
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="email">E-mail Principal</label>
                                <input type="email" id="email" class="form-control" 
                                       value="<?php echo htmlspecialchars($profileData['perfil']['email'] ?? ''); ?>" 
                                       disabled>
                                <p class="form-help">
                                    <i class="fas fa-info-circle"></i>
                                    O e-mail principal não pode ser alterado por segurança
                                </p>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="telefone">Telefone</label>
                                    <input type="tel" id="telefone" name="telefone" class="form-control" 
                                           value="<?php echo htmlspecialchars($profileData['contato']['telefone'] ?? ''); ?>"
                                           placeholder="(00) 0000-0000">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="celular">Celular</label>
                                    <input type="tel" id="celular" name="celular" class="form-control" 
                                           value="<?php echo htmlspecialchars($profileData['contato']['celular'] ?? ''); ?>"
                                           placeholder="(00) 00000-0000">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="email_alternativo">E-mail Alternativo</label>
                                <input type="email" id="email_alternativo" name="email_alternativo" class="form-control" 
                                       value="<?php echo htmlspecialchars($profileData['contato']['email_alternativo'] ?? ''); ?>"
                                       placeholder="email.alternativo@exemplo.com">
                                <p class="form-help">
                                    <i class="fas fa-info-circle"></i>
                                    Usado para recuperação de conta e comunicações importantes
                                </p>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Salvar Informações
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Formulário de endereço (mantém o mesmo) -->
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">
                            <i class="fas fa-map-marker-alt"></i>
                            Endereço
                        </h3>
                    </div>
                    <div class="form-card-body">
                        <?php if (!empty($addressMessage)): ?>
                            <div class="alert <?php echo $addressSuccess ? 'alert-success' : 'alert-danger'; ?>">
                                <i class="fas <?php echo $addressSuccess ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                                <?php echo htmlspecialchars($addressMessage); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST" id="addressForm">
                            <input type="hidden" name="form_type" value="address">
                            
                            <div class="form-group">
                                <label class="form-label" for="cep">CEP</label>
                                <input type="text" id="cep" name="cep" class="form-control" 
                                       value="<?php echo htmlspecialchars($profileData['endereco']['cep'] ?? ''); ?>"
                                       placeholder="00000-000" maxlength="9">
                                <p class="form-help">
                                    <i class="fas fa-magic"></i>
                                    Digite o CEP para preencher automaticamente o endereço
                                </p>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group" style="grid-column: span 2;">
                                    <label class="form-label" for="logradouro">Logradouro</label>
                                    <input type="text" id="logradouro" name="logradouro" class="form-control" 
                                           value="<?php echo htmlspecialchars($profileData['endereco']['logradouro'] ?? ''); ?>"
                                           placeholder="Rua, Avenida, etc.">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="numero">Número</label>
                                    <input type="text" id="numero" name="numero" class="form-control" 
                                           value="<?php echo htmlspecialchars($profileData['endereco']['numero'] ?? ''); ?>"
                                           placeholder="123">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="complemento">Complemento</label>
                                <input type="text" id="complemento" name="complemento" class="form-control" 
                                       value="<?php echo htmlspecialchars($profileData['endereco']['complemento'] ?? ''); ?>"
                                       placeholder="Apartamento, Bloco, etc. (opcional)">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="bairro">Bairro</label>
                                    <input type="text" id="bairro" name="bairro" class="form-control" 
                                           value="<?php echo htmlspecialchars($profileData['endereco']['bairro'] ?? ''); ?>"
                                           placeholder="Centro">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="cidade">Cidade</label>
                                    <input type="text" id="cidade" name="cidade" class="form-control" 
                                           value="<?php echo htmlspecialchars($profileData['endereco']['cidade'] ?? ''); ?>"
                                           placeholder="São Paulo">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="estado">Estado</label>
                                    <input type="text" id="estado" name="estado" class="form-control" 
                                           value="<?php echo htmlspecialchars($profileData['endereco']['estado'] ?? ''); ?>"
                                           placeholder="SP" maxlength="2">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Salvar Endereço
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Formulário de alteração de senha (mantém o mesmo) -->
                <div class="form-card">
                    <div class="form-card-header">
                        <h3 class="form-card-title">
                            <i class="fas fa-shield-alt"></i>
                            Segurança da Conta
                        </h3>
                    </div>
                    <div class="form-card-body">
                        <?php if (!empty($passwordMessage)): ?>
                            <div class="alert <?php echo $passwordSuccess ? 'alert-success' : 'alert-danger'; ?>">
                                <i class="fas <?php echo $passwordSuccess ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                                <?php echo htmlspecialchars($passwordMessage); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST" id="passwordForm">
                            <input type="hidden" name="form_type" value="password">
                            
                            <div class="form-group">
                                <label class="form-label" for="senha_atual">Senha Atual <span class="required">*</span></label>
                                <input type="password" id="senha_atual" name="senha_atual" class="form-control" 
                                       required placeholder="Digite sua senha atual">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label" for="nova_senha">Nova Senha <span class="required">*</span></label>
                                    <input type="password" id="nova_senha" name="nova_senha" class="form-control" 
                                           required placeholder="Digite a nova senha">
                                    <p class="form-help">
                                        <i class="fas fa-key"></i>
                                        Mínimo de <?php echo PASSWORD_MIN_LENGTH; ?> caracteres
                                    </p>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="confirmar_senha">Confirmar Nova Senha <span class="required">*</span></label>
                                    <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" 
                                           required placeholder="Digite novamente a nova senha">
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-lock"></i>
                                Alterar Senha
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Script aprimorado para preenchimento automático de endereço via CEP
        document.getElementById('cep').addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            
            if (cep.length !== 8) {
                return;
            }

            // Mostrar loading
            this.style.opacity = '0.6';
            this.disabled = true;
            
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        document.getElementById('logradouro').value = data.logradouro;
                        document.getElementById('bairro').value = data.bairro;
                        document.getElementById('cidade').value = data.localidade;
                        document.getElementById('estado').value = data.uf;
                        document.getElementById('numero').focus();
                        
                        // Mostrar feedback visual
                        showNotification('✅ Endereço preenchido automaticamente!', 'success');
                    } else {
                        showNotification('❌ CEP não encontrado', 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    showNotification('⚠️ Erro ao buscar CEP', 'warning');
                })
                .finally(() => {
                    // Remover loading
                    this.style.opacity = '1';
                    this.disabled = false;
                });
        });

        // Aplicar máscara no CEP
        document.getElementById('cep').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 5) {
                value = value.substring(0, 5) + '-' + value.substring(5, 8);
            }
            this.value = value;
        });

        // NOVO: Aplicar máscara no CPF
        document.getElementById('cpf').addEventListener('input', function() {
            // Se o campo está desabilitado, não aplicar máscara
            if (this.disabled) {
                return;
            }
            
            let value = this.value.replace(/\D/g, '');
            
            if (value.length <= 11) {
                // Aplicar máscara: 000.000.000-00
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})/, '$1-$2');
            }
            
            this.value = value;
        });

        // NOVO: Validação de CPF em tempo real
        document.getElementById('cpf').addEventListener('blur', function() {
            // Se o campo está desabilitado, não validar
            if (this.disabled) {
                return;
            }
            
            const cpf = this.value.replace(/\D/g, '');
            
            if (cpf.length === 0) {
                return; // Campo vazio é permitido se não for obrigatório
            }
            
            if (!validarCPF(cpf)) {
                this.style.borderColor = 'var(--danger-color)';
                showNotification('❌ CPF inválido', 'error');
            } else {
                this.style.borderColor = 'var(--success-color)';
                showNotification('✅ CPF válido', 'success');
            }
        });

        // NOVO: Função para validar CPF
        function validarCPF(cpf) {
            if (cpf.length !== 11) return false;
            
            // Eliminar CPFs conhecidos como inválidos
            if (/^(\d)\1{10}$/.test(cpf)) return false;
            
            // Validar 1º dígito
            let soma = 0;
            for (let i = 0; i < 9; i++) {
                soma += parseInt(cpf.charAt(i)) * (10 - i);
            }
            let resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            if (resto !== parseInt(cpf.charAt(9))) return false;
            
            // Validar 2º dígito
            soma = 0;
            for (let i = 0; i < 10; i++) {
                soma += parseInt(cpf.charAt(i)) * (11 - i);
            }
            resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            if (resto !== parseInt(cpf.charAt(10))) return false;
            
            return true;
        }

        // Validação em tempo real da senha
        document.getElementById('nova_senha').addEventListener('input', function() {
            const password = this.value;
            const minLength = <?php echo PASSWORD_MIN_LENGTH; ?>;
            
            if (password.length > 0 && password.length < minLength) {
                this.style.borderColor = 'var(--danger-color)';
            } else if (password.length >= minLength) {
                this.style.borderColor = 'var(--success-color)';
            } else {
                this.style.borderColor = '#e9ecef';
            }
        });

        // Validação de confirmação de senha
        document.getElementById('confirmar_senha').addEventListener('input', function() {
            const password = document.getElementById('nova_senha').value;
            const confirm = this.value;
            
            if (confirm.length > 0) {
                if (password === confirm) {
                    this.style.borderColor = 'var(--success-color)';
                } else {
                    this.style.borderColor = 'var(--danger-color)';
                }
            } else {
                this.style.borderColor = '#e9ecef';
            }
        });

        // Função para mostrar notificações
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 10px;
                color: white;
                font-weight: 500;
                z-index: 9999;
                transform: translateX(400px);
                transition: transform 0.3s ease;
                ${type === 'success' ? 'background: var(--success-color);' : ''}
                ${type === 'error' ? 'background: var(--danger-color);' : ''}
                ${type === 'warning' ? 'background: var(--warning-color); color: var(--dark-gray);' : ''}
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Animar entrada
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Remover após 3 segundos
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }

        // Loading nos formulários
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            });
        });

        // Aplicar máscaras nos telefones
        function phoneMask(input) {
            input.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                if (value.length <= 10) {
                    value = value.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                } else {
                    value = value.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                }
                this.value = value;
            });
        }

        phoneMask(document.getElementById('telefone'));
        phoneMask(document.getElementById('celular'));
    </script>
</body>
</html>
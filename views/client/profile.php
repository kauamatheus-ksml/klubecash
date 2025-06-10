<?php
// views/client/profile.php
// Definir o menu ativo
$activeMenu = 'perfil';

// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../controllers/ClientController.php';
require_once '../../utils/Validator.php';

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
                'cpf' => $_POST['cpf'] ?? '',
                'contato' => [
                    'telefone' => $_POST['telefone'] ?? '',
                    'celular' => $_POST['celular'] ?? '',
                    'email_alternativo' => $_POST['email_alternativo'] ?? ''
                ]
            ];
            
            $result = ClientController::updateProfile($userId, $updateData);
            
            // Armazenar mensagem na sessão
            $_SESSION['personal_info_success'] = $result['status'];
            $_SESSION['personal_info_message'] = $result['message'];
            
        } catch (Exception $e) {
            $_SESSION['personal_info_success'] = false;
            $_SESSION['personal_info_message'] = logError('Erro ao atualizar informações pessoais', $e->getMessage());
        }
        
        // Redirecionar para evitar reenvio e recarregar dados
        header("Location: " . CLIENT_PROFILE_URL . "?updated=1#personal-info");
        exit;
    }
    
    // Formulário de endereço
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
                    'estado' => $_POST['estado'] ?? ''
                ]
            ];
            
            $result = ClientController::updateProfile($userId, $updateData);
            
            $_SESSION['address_success'] = $result['status'];
            $_SESSION['address_message'] = $result['message'];
            
        } catch (Exception $e) {
            $_SESSION['address_success'] = false;
            $_SESSION['address_message'] = logError('Erro ao atualizar endereço', $e->getMessage());
        }
        
        header("Location: " . CLIENT_PROFILE_URL . "?updated=1#address-info");
        exit;
    }
    
    // Formulário de alteração de senha
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'password') {
        try {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('A confirmação da senha não confere.');
            }
            
            $result = ClientController::changePassword($userId, $currentPassword, $newPassword);
            
            $_SESSION['password_success'] = $result['status'];
            $_SESSION['password_message'] = $result['message'];
            
        } catch (Exception $e) {
            $_SESSION['password_success'] = false;
            $_SESSION['password_message'] = $e->getMessage();
        }
        
        header("Location: " . CLIENT_PROFILE_URL . "?updated=1#password");
        exit;
    }
}

// Obter mensagens da sessão e limpar
$personalInfoMessage = $_SESSION['personal_info_message'] ?? '';
$personalInfoSuccess = $_SESSION['personal_info_success'] ?? false;
$addressMessage = $_SESSION['address_message'] ?? '';
$addressSuccess = $_SESSION['address_success'] ?? false;
$passwordMessage = $_SESSION['password_message'] ?? '';
$passwordSuccess = $_SESSION['password_success'] ?? false;

// Limpar mensagens da sessão após obter
unset($_SESSION['personal_info_message'], $_SESSION['personal_info_success']);
unset($_SESSION['address_message'], $_SESSION['address_success']);
unset($_SESSION['password_message'], $_SESSION['password_success']);

// Carregar dados do perfil
$error = false;
$errorMessage = '';
$profileData = [];

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
        if (!isset($profileData['perfil']) || !is_array($profileData['perfil'])) {
            $profileData['perfil'] = [
                'nome' => '',
                'email' => '',
                'cpf' => '',
                'telefone' => '',
                'cpf_editavel' => true
            ];
        }
        
        if (!isset($profileData['contato']) || !is_array($profileData['contato'])) {
            $profileData['contato'] = [
                'telefone' => '',
                'celular' => '',
                'email_alternativo' => ''
            ];
        }
        
        if (!isset($profileData['endereco']) || !is_array($profileData['endereco'])) {
            $profileData['endereco'] = [
                'cep' => '',
                'logradouro' => '',
                'numero' => '',
                'complemento' => '',
                'bairro' => '',
                'cidade' => '',
                'estado' => ''
            ];
        }
        
        if (!isset($profileData['estatisticas']) || !is_array($profileData['estatisticas'])) {
            $profileData['estatisticas'] = [
                'total_cashback' => 0,
                'total_transacoes' => 0,
                'total_compras' => 0,
                'total_lojas_utilizadas' => 0
            ];
        }
        
        // Verificar se CPF pode ser editado
        $profileData['perfil']['cpf_editavel'] = empty($profileData['perfil']['cpf']);
    }
} catch (Exception $e) {
    $error = true;
    $errorMessage = logError('Erro ao carregar dados do perfil', $e->getMessage());
    $profileData = [
        'perfil' => ['nome' => '', 'email' => '', 'cpf' => '', 'cpf_editavel' => true],
        'contato' => ['telefone' => '', 'celular' => '', 'email_alternativo' => ''],
        'endereco' => ['cep' => '', 'logradouro' => '', 'numero' => '', 'complemento' => '', 'bairro' => '', 'cidade' => '', 'estado' => ''],
        'estatisticas' => ['total_cashback' => 0, 'total_transacoes' => 0, 'total_compras' => 0, 'total_lojas_utilizadas' => 0]
    ];
}

// Calcular progresso do perfil
$profileCompletion = 0;
$totalSteps = 6;
$completedSteps = 0;

if (!empty($profileData['perfil']['nome'])) $completedSteps++;
if (!empty($profileData['perfil']['cpf'])) $completedSteps++;
if (!empty($profileData['contato']['telefone']) || !empty($profileData['contato']['celular'])) $completedSteps++;
if (!empty($profileData['contato']['email_alternativo'])) $completedSteps++;
if (!empty($profileData['endereco']['cep']) && !empty($profileData['endereco']['logradouro'])) $completedSteps++;
if (!empty($profileData['endereco']['cidade']) && !empty($profileData['endereco']['estado'])) $completedSteps++;

$profileCompletion = ($completedSteps / $totalSteps) * 100;

// Verificar se CPF está pendente
$cpfPendente = empty($profileData['perfil']['cpf']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Klube Cash</title>
    <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- CSS -->
    <link href="../../assets/css/client.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        /* Garantir que a página sempre carregue com o layout completo */
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
            margin-top: 80px; /* Espaço para navbar fixa */
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .profile-header h1 {
            color: #1a1a1a;
            margin-bottom: 0.5rem;
            font-size: 2rem;
        }
        
        .profile-header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .cpf-alert {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            align-items: start;
        }
        
        .profile-sidebar {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 100px;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 1rem;
        }
        
        .user-name {
            text-align: center;
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1a1a1a;
        }
        
        .user-email {
            text-align: center;
            color: #666;
            margin-bottom: 1rem;
        }
        
        .user-cpf {
            text-align: center;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        
        .progress-section {
            text-align: center;
            padding: 1rem 0;
            border-top: 1px solid #eee;
        }
        
        .progress-label {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .progress-bar {
            height: 8px;
            background: #eee;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff6b35, #f7931e);
            transition: width 0.3s ease;
        }
        
        .progress-percentage {
            font-weight: 600;
            color: #ff6b35;
            margin-top: 0.5rem;
        }
        
        .form-section {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .form-card-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .form-card-title {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a1a1a;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-card-body {
            padding: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #1a1a1a;
        }
        
        .required {
            color: #dc3545;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #ff6b35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }
        
        .form-control:disabled {
            background-color: #f8f9fa !important;
            color: #6c757d !important;
            cursor: not-allowed;
            border-color: #e9ecef;
            opacity: 0.8;
        }
        
        .form-help {
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.25rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .form-help.cpf-fixed {
            color: #28a745;
            font-weight: 500;
        }
        
        .cpf-verified {
            color: #28a745;
            font-weight: 600;
            font-size: 0.9rem;
            margin-left: 8px;
        }
        
        .cpf-verified i {
            margin-right: 4px;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
        }
        
        /* Grid responsivo */
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .profile-sidebar {
                position: static;
            }
            
            .profile-container {
                padding: 1rem;
                margin-top: 60px;
            }
        }
        
        /* Smooth scroll para ancoragem */
        html {
            scroll-behavior: smooth;
        }
        
        /* Destacar seção ativa temporariamente */
        .form-card:target {
            animation: highlight 2s ease-in-out;
        }
        
        @keyframes highlight {
            0% { background-color: rgba(255, 107, 53, 0.1); }
            100% { background-color: white; }
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

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php else: ?>

        <!-- Alerta de CPF pendente -->
        <?php if ($cpfPendente): ?>
            <div class="cpf-alert">
                <h3><i class="fas fa-exclamation-triangle"></i> Complete seu perfil</h3>
                <p>Para aproveitar todos os benefícios do Klube Cash, é necessário informar seu CPF.</p>
            </div>
        <?php endif; ?>

        <!-- Conteúdo principal -->
        <div class="main-content">
            <!-- Sidebar do perfil -->
            <div class="profile-sidebar">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($profileData['perfil']['nome'] ?: 'U', 0, 1)); ?>
                </div>
                <h2 class="user-name"><?php echo htmlspecialchars($profileData['perfil']['nome'] ?: 'Usuário'); ?></h2>
                <p class="user-email"><?php echo htmlspecialchars($profileData['perfil']['email'] ?: ''); ?></p>
                
                <!-- Mostrar CPF formatado se disponível -->
                <?php if (!empty($profileData['perfil']['cpf'])): ?>
                    <p class="user-cpf">
                        <i class="fas fa-id-card"></i> CPF: <?php echo Validator::formataCPF($profileData['perfil']['cpf']); ?>
                    </p>
                <?php endif; ?>
                
                <!-- Progresso do perfil -->
                <div class="progress-section">
                    <div class="progress-label">Perfil completo</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $profileCompletion; ?>%"></div>
                    </div>
                    <div class="progress-percentage"><?php echo round($profileCompletion); ?>%</div>
                </div>
            </div>

            <!-- Seção de formulários -->
            <div class="form-section">
                <!-- Formulário de informações pessoais -->
                <div class="form-card" id="personal-info">
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
                                       value="<?php echo htmlspecialchars($profileData['perfil']['nome'] ?: ''); ?>" 
                                       required placeholder="Digite seu nome completo">
                            </div>
                            
                            <!-- Campo CPF com verificação de edição -->
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
                                    value="<?php echo htmlspecialchars($profileData['perfil']['cpf'] ?: ''); ?>" 
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
                                       value="<?php echo htmlspecialchars($profileData['perfil']['email'] ?: ''); ?>" 
                                       disabled placeholder="Seu e-mail principal não pode ser alterado">
                                <p class="form-help">
                                    <i class="fas fa-info-circle"></i>
                                    O e-mail principal não pode ser alterado. Para alterar, entre em contato com o suporte.
                                </p>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="telefone">Telefone</label>
                                <input type="tel" id="telefone" name="telefone" class="form-control" 
                                       value="<?php echo htmlspecialchars($profileData['contato']['telefone'] ?: ''); ?>" 
                                       placeholder="(11) 99999-9999">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="celular">Celular</label>
                                <input type="tel" id="celular" name="celular" class="form-control" 
                                       value="<?php echo htmlspecialchars($profileData['contato']['celular'] ?: ''); ?>" 
                                       placeholder="(11) 99999-9999">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="email_alternativo">E-mail Alternativo</label>
                                <input type="email" id="email_alternativo" name="email_alternativo" class="form-control" 
                                       value="<?php echo htmlspecialchars($profileData['contato']['email_alternativo'] ?: ''); ?>" 
                                       placeholder="email@exemplo.com">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Salvar Informações
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Formulário de endereço -->
                <div class="form-card" id="address-info">
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
                                       value="<?php echo htmlspecialchars($profileData['endereco']['cep'] ?: ''); ?>" 
                                       placeholder="00000-000" maxlength="9">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="logradouro">Logradouro</label>
                                <input type="text" id="logradouro" name="logradouro" class="form-control" 
                                       value="<?php echo htmlspecialchars($profileData['endereco']['logradouro'] ?: ''); ?>" 
                                       placeholder="Rua, Avenida, etc.">
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1rem;">
                                <div class="form-group">
                                    <label class="form-label" for="numero">Número</label>
                                    <input type="text" id="numero" name="numero" class="form-control" 
                                           value="<?php echo htmlspecialchars($profileData['endereco']['numero'] ?: ''); ?>" 
                                           placeholder="123">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="complemento">Complemento</label>
                                    <input type="text" id="complemento" name="complemento" class="form-control" 
                                           value="<?php echo htmlspecialchars($profileData['endereco']['complemento'] ?: ''); ?>" 
                                           placeholder="Apto, Bloco, etc.">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="bairro">Bairro</label>
                                <input type="text" id="bairro" name="bairro" class="form-control" 
                                       value="<?php echo htmlspecialchars($profileData['endereco']['bairro'] ?: ''); ?>" 
                                       placeholder="Nome do bairro">
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label class="form-label" for="cidade">Cidade</label>
                                    <input type="text" id="cidade" name="cidade" class="form-control" 
                                           value="<?php echo htmlspecialchars($profileData['endereco']['cidade'] ?: ''); ?>" 
                                           placeholder="Nome da cidade">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label" for="estado">Estado</label>
                                    <select id="estado" name="estado" class="form-control">
                                        <option value="">Selecione...</option>
                                        <?php
                                        $estados = [
                                            'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
                                            'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',
                                            'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul',
                                            'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná',
                                            'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
                                            'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina',
                                            'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins'
                                        ];
                                        foreach ($estados as $sigla => $nome) {
                                            $selected = ($profileData['endereco']['estado'] ?? '') === $sigla ? 'selected' : '';
                                            echo "<option value='$sigla' $selected>$nome</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Salvar Endereço
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Formulário de senha -->
                <div class="form-card" id="password">
                    <div class="form-card-header">
                        <h3 class="form-card-title">
                            <i class="fas fa-lock"></i>
                            Alterar Senha
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
                                <label class="form-label" for="current_password">Senha Atual <span class="required">*</span></label>
                                <input type="password" id="current_password" name="current_password" class="form-control" 
                                       required placeholder="Digite sua senha atual">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="new_password">Nova Senha <span class="required">*</span></label>
                                <input type="password" id="new_password" name="new_password" class="form-control" 
                                       required placeholder="Digite a nova senha" minlength="8">
                                <p class="form-help">
                                    <i class="fas fa-info-circle"></i>
                                    A senha deve ter pelo menos 8 caracteres.
                                </p>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Confirmar Nova Senha <span class="required">*</span></label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                       required placeholder="Digite novamente a nova senha">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Alterar Senha
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <!-- JavaScript -->
    <script>
        // Máscaras para campos
        document.addEventListener('DOMContentLoaded', function() {
            // Máscara CPF
            const cpfInput = document.getElementById('cpf');
            if (cpfInput && !cpfInput.disabled) {
                cpfInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    value = value.replace(/(\d{3})(\d)/, '$1.$2');
                    value = value.replace(/(\d{3})(\d)/, '$1.$2');
                    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                    e.target.value = value;
                });
            }

            // Máscara CEP
            const cepInput = document.getElementById('cep');
            if (cepInput) {
                cepInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                    e.target.value = value;
                });

                // Buscar endereço por CEP
                cepInput.addEventListener('blur', function() {
                    const cep = this.value.replace(/\D/g, '');
                    if (cep.length === 8) {
                        fetch(`https://viacep.com.br/ws/${cep}/json/`)
                            .then(response => response.json())
                            .then(data => {
                                if (!data.erro) {
                                    document.getElementById('logradouro').value = data.logradouro || '';
                                    document.getElementById('bairro').value = data.bairro || '';
                                    document.getElementById('cidade').value = data.localidade || '';
                                    document.getElementById('estado').value = data.uf || '';
                                }
                            })
                            .catch(error => console.log('Erro ao buscar CEP:', error));
                    }
                });
            }

            // Máscara telefone
            ['telefone', 'celular'].forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    input.addEventListener('input', function(e) {
                        let value = e.target.value.replace(/\D/g, '');
                        if (value.length <= 10) {
                            value = value.replace(/(\d{2})(\d)/, '($1) $2');
                            value = value.replace(/(\d{4})(\d)/, '$1-$2');
                        } else {
                            value = value.replace(/(\d{2})(\d)/, '($1) $2');
                            value = value.replace(/(\d{5})(\d)/, '$1-$2');
                        }
                        e.target.value = value;
                    });
                }
            });

            // Validação de confirmação de senha
            const passwordForm = document.getElementById('passwordForm');
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    const newPassword = document.getElementById('new_password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    
                    if (newPassword !== confirmPassword) {
                        e.preventDefault();
                        alert('A confirmação da senha não confere.');
                        return;
                    }
                });
            }
        });
    </script>
</body>
</html>
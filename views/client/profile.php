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

// CORREÇÃO PRINCIPAL: Capturar e limpar mensagens de forma mais robusta
$feedbackMessages = [
    'personal_info' => [
        'message' => $_SESSION['personal_info_message'] ?? '',
        'success' => $_SESSION['personal_info_success'] ?? false
    ],
    'address' => [
        'message' => $_SESSION['address_message'] ?? '',
        'success' => $_SESSION['address_success'] ?? false  
    ],
    'password' => [
        'message' => $_SESSION['password_message'] ?? '',
        'success' => $_SESSION['password_success'] ?? false
    ]
];

// Limpar TODAS as mensagens de sessão imediatamente
unset($_SESSION['personal_info_message'], $_SESSION['personal_info_success']);
unset($_SESSION['address_message'], $_SESSION['address_success']); 
unset($_SESSION['password_message'], $_SESSION['password_success']);

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
        
        // CORREÇÃO: Simplificar redirecionamento
        header("Location: " . CLIENT_PROFILE_URL);
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
                    'estado' => $_POST['estado'] ?? '',
                    'principal' => 1
                ]
            ];
            
            $result = ClientController::updateProfile($userId, $updateData);
            
            // Armazenar mensagem na sessão
            $_SESSION['address_success'] = $result['status'];
            $_SESSION['address_message'] = $result['message'];
            
        } catch (Exception $e) {
            $_SESSION['address_success'] = false;
            $_SESSION['address_message'] = logError('Erro ao atualizar endereço', $e->getMessage());
        }
        
        header("Location: " . CLIENT_PROFILE_URL);
        exit;
    }
    
    // Formulário de alteração de senha
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
            
            // Armazenar mensagem na sessão
            $_SESSION['password_success'] = $passwordSuccess;
            $_SESSION['password_message'] = $passwordMessage;
            
        } catch (Exception $e) {
            $_SESSION['password_success'] = false;
            $_SESSION['password_message'] = logError('Erro ao atualizar senha', $e->getMessage());
        }
        
        header("Location: " . CLIENT_PROFILE_URL);
        exit;
    }
}

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
        
        // Garantir que as chaves existam
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
        
        if (!isset($profileData['perfil']) || !is_array($profileData['perfil'])) {
            $profileData['perfil'] = [
                'nome' => '',
                'email' => '',
                'cpf' => '',
                'cpf_editavel' => true
            ];
        }
    }
} catch (Exception $e) {
    $error = true;
    $errorMessage = logError('Erro ao carregar dados do perfil', $e->getMessage());
    $profileData = [
        'perfil' => ['nome' => '', 'email' => '', 'cpf' => '', 'cpf_editavel' => true],
        'contato' => [],
        'endereco' => [],
        'estatisticas' => ['total_cashback' => 0, 'total_transacoes' => 0, 'total_compras' => 0, 'total_lojas_utilizadas' => 0]
    ];
}

// Calcular progresso do perfil
$profileCompletion = 0;
$totalSteps = 6;
$completedSteps = 0;

if (!empty($profileData['perfil']['nome'])) $completedSteps++;

if (!empty($profileData['perfil']['cpf'])) {
    $completedSteps++;
    $cpfPendente = false;
} else {
    $cpfPendente = isset($profileData['perfil']['cpf_editavel']) ? $profileData['perfil']['cpf_editavel'] : true;
}

if (!empty($profileData['contato']['telefone']) || !empty($profileData['contato']['celular'])) $completedSteps++;
if (!empty($profileData['contato']['email_alternativo'])) $completedSteps++;
if (!empty($profileData['endereco']['cep']) && !empty($profileData['endereco']['logradouro'])) $completedSteps++;
if (!empty($profileData['endereco']['cidade']) && !empty($profileData['endereco']['estado'])) $completedSteps++;

$profileCompletion = $totalSteps > 0 ? ($completedSteps / $totalSteps) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/views/client/profile.css">
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                
                <!-- Mostrar CPF formatado se disponível -->
                <?php if (!empty($profileData['perfil']['cpf'])): ?>
                    <p class="user-cpf" style="color: var(--medium-gray); font-size: 0.9rem; margin-bottom: 25px;">
                        <i class="fas fa-id-card"></i> CPF: <?php 
                        $cpf = $profileData['perfil']['cpf'];
                        if (strlen($cpf) === 11) {
                            echo substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
                        } else {
                            echo $cpf;
                        }
                        ?>
                    </p>
                <?php endif; ?>
                
                <!-- Estatísticas do usuário 
                <div class="user-stats">
                    <div class="stat-item">
                        <div class="stat-value">R$ <?php echo number_format($profileData['estatisticas']['total_cashback'] ?? 0, 2, ',', '.'); ?></div>
                        <div class="stat-label">Total Cashback</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($profileData['estatisticas']['total_transacoes'] ?? 0); ?></div>
                        <div class="stat-label">Transações</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($profileData['estatisticas']['total_lojas_utilizadas'] ?? 0); ?></div>
                        <div class="stat-label">Lojas</div>
                    </div>
                </div> -->
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
                        <?php if (!empty($feedbackMessages['personal_info']['message'])): ?>
                            <div class="alert <?php echo $feedbackMessages['personal_info']['success'] ? 'alert-success' : 'alert-danger'; ?>">
                                <i class="fas <?php echo $feedbackMessages['personal_info']['success'] ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                                <?php echo htmlspecialchars($feedbackMessages['personal_info']['message']); ?>
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
                            
                            <!-- Campo CPF -->
                            <div class="form-group <?php echo ($cpfPendente && ($profileData['perfil']['cpf_editavel'] ?? true)) ? 'cpf-required' : ''; ?>">
                                <label class="form-label" for="cpf">
                                    CPF
                                    <?php if ($cpfPendente): ?>
                                        <span class="required">*</span>
                                    <?php endif; ?>
                                </label>
                                <input type="text" id="cpf" name="cpf" class="form-control" 
                                       value="<?php echo htmlspecialchars($profileData['perfil']['cpf'] ?? ''); ?>"
                                       placeholder="000.000.000-00"
                                       maxlength="14"
                                       <?php echo (!($profileData['perfil']['cpf_editavel'] ?? true)) ? 'readonly' : ''; ?>>
                                <?php if (!($profileData['perfil']['cpf_editavel'] ?? true)): ?>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-lock"></i> CPF já validado e não pode ser alterado
                                    </small>
                                <?php endif; ?>
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
                                       placeholder="seuemail@exemplo.com">
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Salvar Alterações
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Formulário de endereço -->
                <div class="form-card" id="address">
                    <div class="form-card-header">
                        <h3 class="form-card-title">
                            <i class="fas fa-map-marker-alt"></i>
                            Endereço
                        </h3>
                    </div>
                    <div class="form-card-body">
                        <?php if (!empty($feedbackMessages['address']['message'])): ?>
                            <div class="alert <?php echo $feedbackMessages['address']['success'] ? 'alert-success' : 'alert-danger'; ?>">
                                <i class="fas <?php echo $feedbackMessages['address']['success'] ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                                <?php echo htmlspecialchars($feedbackMessages['address']['message']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST" id="addressForm">
                            <input type="hidden" name="form_type" value="address">
                            
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="cep">CEP</label>
                                    <input type="text" id="cep" name="cep" class="form-control" 
                                           value="<?php echo htmlspecialchars($profileData['endereco']['cep'] ?? ''); ?>" 
                                           placeholder="00000-000" maxlength="9">
                                </div>
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="logradouro">Logradouro</label>
                                    <input type="text" id="logradouro" name="logradouro" class="form-control" 
                                           value="<?php echo htmlspecialchars($profileData['endereco']['logradouro'] ?? ''); ?>" 
                                           placeholder="Rua, Avenida, etc.">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label class="form-label" for="numero">Número</label>
                                    <input type="text" id="numero" name="numero" class="form-control" 
                                           value="<?php echo htmlspecialchars($profileData['endereco']['numero'] ?? ''); ?>" 
                                           placeholder="123">
                                </div>
                                <div class="form-group col-md-9">
                                    <label class="form-label" for="complemento">Complemento</label>
                                    <input type="text" id="complemento" name="complemento" class="form-control" 
                                           value="<?php echo htmlspecialchars($profileData['endereco']['complemento'] ?? ''); ?>" 
                                           placeholder="Apartamento, Bloco, etc.">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="bairro">Bairro</label>
                                <input type="text" id="bairro" name="bairro" class="form-control" 
                                       value="<?php echo htmlspecialchars($profileData['endereco']['bairro'] ?? ''); ?>" 
                                       placeholder="Nome do bairro">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group col-md-8">
                                    <label class="form-label" for="cidade">Cidade</label>
                                    <input type="text" id="cidade" name="cidade" class="form-control" 
                                           value="<?php echo htmlspecialchars($profileData['endereco']['cidade'] ?? ''); ?>" 
                                           placeholder="Nome da cidade">
                                </div>
                                <div class="form-group col-md-4">
                                    <label class="form-label" for="estado">Estado</label>
                                    <select id="estado" name="estado" class="form-control">
                                        <option value="">Selecione</option>
                                        <option value="AC" <?php echo ($profileData['endereco']['estado'] ?? '') === 'AC' ? 'selected' : ''; ?>>Acre</option>
                                        <option value="AL" <?php echo ($profileData['endereco']['estado'] ?? '') === 'AL' ? 'selected' : ''; ?>>Alagoas</option>
                                        <option value="AP" <?php echo ($profileData['endereco']['estado'] ?? '') === 'AP' ? 'selected' : ''; ?>>Amapá</option>
                                        <option value="AM" <?php echo ($profileData['endereco']['estado'] ?? '') === 'AM' ? 'selected' : ''; ?>>Amazonas</option>
                                        <option value="BA" <?php echo ($profileData['endereco']['estado'] ?? '') === 'BA' ? 'selected' : ''; ?>>Bahia</option>
                                        <option value="CE" <?php echo ($profileData['endereco']['estado'] ?? '') === 'CE' ? 'selected' : ''; ?>>Ceará</option>
                                        <option value="DF" <?php echo ($profileData['endereco']['estado'] ?? '') === 'DF' ? 'selected' : ''; ?>>Distrito Federal</option>
                                        <option value="ES" <?php echo ($profileData['endereco']['estado'] ?? '') === 'ES' ? 'selected' : ''; ?>>Espírito Santo</option>
                                        <option value="GO" <?php echo ($profileData['endereco']['estado'] ?? '') === 'GO' ? 'selected' : ''; ?>>Goiás</option>
                                        <option value="MA" <?php echo ($profileData['endereco']['estado'] ?? '') === 'MA' ? 'selected' : ''; ?>>Maranhão</option>
                                        <option value="MT" <?php echo ($profileData['endereco']['estado'] ?? '') === 'MT' ? 'selected' : ''; ?>>Mato Grosso</option>
                                        <option value="MS" <?php echo ($profileData['endereco']['estado'] ?? '') === 'MS' ? 'selected' : ''; ?>>Mato Grosso do Sul</option>
                                        <option value="MG" <?php echo ($profileData['endereco']['estado'] ?? '') === 'MG' ? 'selected' : ''; ?>>Minas Gerais</option>
                                        <option value="PA" <?php echo ($profileData['endereco']['estado'] ?? '') === 'PA' ? 'selected' : ''; ?>>Pará</option>
                                        <option value="PB" <?php echo ($profileData['endereco']['estado'] ?? '') === 'PB' ? 'selected' : ''; ?>>Paraíba</option>
                                        <option value="PR" <?php echo ($profileData['endereco']['estado'] ?? '') === 'PR' ? 'selected' : ''; ?>>Paraná</option>
                                        <option value="PE" <?php echo ($profileData['endereco']['estado'] ?? '') === 'PE' ? 'selected' : ''; ?>>Pernambuco</option>
                                        <option value="PI" <?php echo ($profileData['endereco']['estado'] ?? '') === 'PI' ? 'selected' : ''; ?>>Piauí</option>
                                        <option value="RJ" <?php echo ($profileData['endereco']['estado'] ?? '') === 'RJ' ? 'selected' : ''; ?>>Rio de Janeiro</option>
                                        <option value="RN" <?php echo ($profileData['endereco']['estado'] ?? '') === 'RN' ? 'selected' : ''; ?>>Rio Grande do Norte</option>
                                        <option value="RS" <?php echo ($profileData['endereco']['estado'] ?? '') === 'RS' ? 'selected' : ''; ?>>Rio Grande do Sul</option>
                                        <option value="RO" <?php echo ($profileData['endereco']['estado'] ?? '') === 'RO' ? 'selected' : ''; ?>>Rondônia</option>
                                        <option value="RR" <?php echo ($profileData['endereco']['estado'] ?? '') === 'RR' ? 'selected' : ''; ?>>Roraima</option>
                                        <option value="SC" <?php echo ($profileData['endereco']['estado'] ?? '') === 'SC' ? 'selected' : ''; ?>>Santa Catarina</option>
                                        <option value="SP" <?php echo ($profileData['endereco']['estado'] ?? '') === 'SP' ? 'selected' : ''; ?>>São Paulo</option>
                                        <option value="SE" <?php echo ($profileData['endereco']['estado'] ?? '') === 'SE' ? 'selected' : ''; ?>>Sergipe</option>
                                        <option value="TO" <?php echo ($profileData['endereco']['estado'] ?? '') === 'TO' ? 'selected' : ''; ?>>Tocantins</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Salvar Endereço
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Formulário de alteração de senha -->
                <div class="form-card" id="password">
                    <div class="form-card-header">
                        <h3 class="form-card-title">
                            <i class="fas fa-lock"></i>
                            Alterar Senha
                        </h3>
                    </div>
                    <div class="form-card-body">
                        <?php if (!empty($feedbackMessages['password']['message'])): ?>
                            <div class="alert <?php echo $feedbackMessages['password']['success'] ? 'alert-success' : 'alert-danger'; ?>">
                                <i class="fas <?php echo $feedbackMessages['password']['success'] ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                                <?php echo htmlspecialchars($feedbackMessages['password']['message']); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="" method="POST" id="passwordForm">
                            <input type="hidden" name="form_type" value="password">
                            
                            <div class="form-group">
                                <label class="form-label" for="senha_atual">
                                    Senha Atual <span class="required">*</span>
                                </label>
                                <input type="password" id="senha_atual" name="senha_atual" class="form-control" 
                                       required placeholder="Digite sua senha atual">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="nova_senha">
                                    Nova Senha <span class="required">*</span>
                                </label>
                                <input type="password" id="nova_senha" name="nova_senha" class="form-control" 
                                       required placeholder="Digite sua nova senha" 
                                       minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                                <small class="form-text text-muted">
                                    A senha deve ter pelo menos <?php echo PASSWORD_MIN_LENGTH; ?> caracteres
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="confirmar_senha">
                                    Confirmar Nova Senha <span class="required">*</span>
                                </label>
                                <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" 
                                       required placeholder="Confirme sua nova senha">
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i>
                                    Alterar Senha
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <!-- Scripts simplificados e corrigidos -->
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Manter todo o código de máscaras existente...
    
    // VERSÃO CORRIGIDA E MELHORADA da busca de CEP
    const cepField = document.getElementById('cep');
    if (cepField) {
        // Aplicar máscara de CEP
        cepField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });
        
        // Buscar endereço com tratamento de erro aprimorado
        cepField.addEventListener('blur', function(e) {
            const cep = e.target.value.replace(/\D/g, '');
            
            // Verificar se CEP tem 8 dígitos
            if (cep.length === 8) {
                // Mostrar carregamento
                mostrarCarregandoCEP(true);
                
                // Tentar múltiplas URLs caso uma falhe
                const urls = [
                    `https://viacep.com.br/ws/${cep}/json/`,
                    `https://brasilapi.com.br/api/cep/v1/${cep}` // API alternativa
                ];
                
                tentarConsultaCEP(urls, 0)
                    .then(data => {
                        if (data) {
                            preencherCamposEndereco(data);
                            mostrarSucessoCEP('✅ Endereço encontrado e preenchido!');
                        } else {
                            throw new Error('CEP não encontrado em nenhuma API');
                        }
                    })
                    .catch(error => {
                        console.error('Erro detalhado:', error);
                        // Mostrar erro específico baseado no tipo
                        if (error.message.includes('não encontrado')) {
                            mostrarErroCEP('❌ CEP não encontrado. Verifique se está correto.');
                        } else if (error.message.includes('rede') || error.message.includes('fetch')) {
                            mostrarErroCEP('🌐 Problema de conexão. Verifique sua internet.');
                        } else {
                            mostrarErroCEP('⚠️ Erro no serviço de CEP. Preencha manualmente.');
                        }
                        limparCamposEndereco();
                    })
                    .finally(() => {
                        mostrarCarregandoCEP(false);
                    });
            } else if (cep.length > 0) {
                mostrarErroCEP('📝 CEP deve ter exatamente 8 dígitos');
                limparCamposEndereco();
            }
        });
    }
    
    // Função que tenta consultar CEP em múltiplas APIs
    async function tentarConsultaCEP(urls, indice) {
        if (indice >= urls.length) {
            throw new Error('Todas as APIs de CEP falharam');
        }
        
        try {
            const response = await fetch(urls[indice], {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
                // Timeout de 5 segundos
                signal: AbortSignal.timeout ? AbortSignal.timeout(5000) : undefined
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            // Verificar se é ViaCEP (tem propriedade 'erro') ou BrasilAPI (diferente estrutura)
            if (data.erro || data.error) {
                throw new Error('CEP não encontrado nesta API');
            }
            
            // Normalizar dados para formato padrão
            return {
                logradouro: data.logradouro || data.street || '',
                bairro: data.bairro || data.district || '',
                localidade: data.localidade || data.city || '',
                uf: data.uf || data.state || ''
            };
            
        } catch (error) {
            console.warn(`API ${urls[indice]} falhou:`, error.message);
            // Tentar próxima API
            return tentarConsultaCEP(urls, indice + 1);
        }
    }
    
    // Função aprimorada para preencher campos
    function preencherCamposEndereco(data) {
        const campos = [
            { id: 'logradouro', valor: data.logradouro, nome: 'Logradouro' },
            { id: 'bairro', valor: data.bairro, nome: 'Bairro' },
            { id: 'cidade', valor: data.localidade, nome: 'Cidade' },
            { id: 'estado', valor: data.uf, nome: 'Estado' }
        ];
        
        let camposPreenchidos = 0;
        
        campos.forEach(campo => {
            const elemento = document.getElementById(campo.id);
            if (elemento && campo.valor) {
                elemento.value = campo.valor;
                elemento.style.backgroundColor = '#e8f5e8';
                elemento.style.transition = 'background-color 0.3s ease';
                camposPreenchidos++;
            }
        });
        
        // Remover destaque após 3 segundos
        setTimeout(() => {
            campos.forEach(campo => {
                const elemento = document.getElementById(campo.id);
                if (elemento) {
                    elemento.style.backgroundColor = '';
                }
            });
        }, 3000);
        
        // Focar no próximo campo lógico
        const numeroField = document.getElementById('numero');
        if (numeroField) {
            numeroField.focus();
            numeroField.select(); // Selecionar texto existente para facilitar substituição
        }
        
        console.log(`✅ ${camposPreenchidos} campos preenchidos automaticamente`);
    }
    
    // Função melhorada para limpar campos
    function limparCamposEndereco() {
        const campos = ['logradouro', 'bairro', 'cidade', 'estado'];
        
        campos.forEach(campoId => {
            const campo = document.getElementById(campoId);
            if (campo) {
                // Não limpar campos que já têm conteúdo válido
                if (!campo.value.trim()) {
                    campo.style.backgroundColor = '#ffe8e8';
                    campo.style.transition = 'background-color 0.3s ease';
                }
            }
        });
        
        // Remover destaque após 3 segundos
        setTimeout(() => {
            campos.forEach(campoId => {
                const campo = document.getElementById(campoId);
                if (campo) campo.style.backgroundColor = '';
            });
        }, 3000);
    }
    
    // Função de carregamento visual
    function mostrarCarregandoCEP(mostrar) {
        const cepField = document.getElementById('cep');
        if (!cepField) return;
        
        if (mostrar) {
            cepField.style.backgroundImage = 'url("data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTYiIHZpZXdCb3g9IjAgMCAxNiAxNiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTggMFY0TTggMTJWMTZNMTYgOEgxMk00IDhIME0xMy42NTY5IDIuMzQzMTVMMTEuMzEzNyA0LjY4NjI5TTQuNjg2MjkgMTEuMzEzN0wyLjM0MzE1IDEzLjY1NjlNMTMuNjU2OSAxMy42NTY5TDExLjMxMzcgMTEuMzEzN000LjY4NjI5IDQuNjg2MjlMMi4zNDMxNSAyLjM0MzE1IiBzdHJva2U9IiNGRjdBMDAiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIi8+CjxhbmltYXRlVHJhbnNmb3JtIGF0dHJpYnV0ZU5hbWU9InRyYW5zZm9ybSIgYXR0cmlidXRlVHlwZT0iWE1MIiB0eXBlPSJyb3RhdGUiIGZyb209IjAgOCA4IiB0bz0iMzYwIDggOCIgZHVyPSIxcyIgcmVwZWF0Q291bnQ9ImluZGVmaW5pdGUiLz4KPC9zdmc+")';
            cepField.style.backgroundRepeat = 'no-repeat';
            cepField.style.backgroundPosition = 'right 10px center';
            cepField.style.backgroundSize = '16px 16px';
            cepField.disabled = true;
            cepField.style.cursor = 'wait';
        } else {
            cepField.style.backgroundImage = '';
            cepField.disabled = false;
            cepField.style.cursor = '';
        }
    }
    
    // Funções de mensagem (reutilizar as mesmas do código anterior)
    function mostrarSucessoCEP(mensagem) {
        mostrarMensagemCEP(mensagem, 'success');
    }
    
    function mostrarErroCEP(mensagem) {
        mostrarMensagemCEP(mensagem, 'error');
    }
    
    function mostrarMensagemCEP(mensagem, tipo) {
        // Remover mensagem anterior
        const mensagemAnterior = document.querySelector('.cep-message');
        if (mensagemAnterior) {
            mensagemAnterior.remove();
        }
        
        // Criar nova mensagem
        const div = document.createElement('div');
        div.className = `cep-message cep-${tipo}`;
        div.innerHTML = mensagem;
        
        // Estilos aprimorados
        div.style.cssText = `
            padding: 10px 15px;
            margin-top: 8px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: slideIn 0.3s ease-out;
            ${tipo === 'success' 
                ? 'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;' 
                : 'background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'
            }
        `;
        
        // Inserir mensagem
        const cepField = document.getElementById('cep');
        if (cepField && cepField.parentNode) {
            cepField.parentNode.insertBefore(div, cepField.nextSibling);
        }
        
        // Auto-remover após 6 segundos
        setTimeout(() => {
            if (div && div.parentNode) {
                div.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => div.remove(), 300);
            }
        }, 6000);
    }
    
    // Manter o restante do código existente (validação de senhas, etc.)
    // ...
});

// CSS para animações (adicionar no <head> se necessário)
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideOut {
        from { opacity: 1; transform: translateY(0); }
        to { opacity: 0; transform: translateY(-10px); }
    }
`;
document.head.appendChild(style);
</script>
</body>
</html>
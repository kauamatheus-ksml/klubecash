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
    // Aplicar máscara no CPF
    const cpfField = document.getElementById('cpf');
    if (cpfField && !cpfField.readOnly) {
        cpfField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d)/, '$1.$2');
            value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            e.target.value = value;
        });
    }
    
    // Aplicar máscaras em telefones
    const telefoneField = document.getElementById('telefone');
    if (telefoneField) {
        telefoneField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{4})(\d)/, '$1-$2');
            e.target.value = value;
        });
    }
    
    const celularField = document.getElementById('celular');
    if (celularField) {
        celularField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{2})(\d)/, '($1) $2');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
        });
    }
    
    // SEÇÃO APRIMORADA: Aplicar máscara no CEP e buscar endereço automaticamente
    const cepField = document.getElementById('cep');
    if (cepField) {
        // Aplicar máscara de CEP enquanto digita
        cepField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é número
            value = value.replace(/(\d{5})(\d)/, '$1-$2'); // Adiciona o hífen após 5 dígitos
            e.target.value = value;
        });
        
        // Buscar endereço quando o usuário sair do campo CEP
        cepField.addEventListener('blur', function(e) {
            const cep = e.target.value.replace(/\D/g, ''); // Remove máscara para validação
            
            // Verificar se o CEP tem 8 dígitos
            if (cep.length === 8) {
                // Mostrar indicador de carregamento
                mostrarCarregandoCEP(true);
                
                // Fazer requisição para API do ViaCEP
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Erro na consulta do CEP');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Verificar se o CEP foi encontrado
                        if (data.erro) {
                            mostrarErroCEP('CEP não encontrado. Verifique se está correto.');
                            limparCamposEndereco();
                        } else {
                            // Preencher campos automaticamente
                            preencherCamposEndereco(data);
                            mostrarSucessoCEP('Endereço encontrado e preenchido automaticamente!');
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao buscar CEP:', error);
                        mostrarErroCEP('Erro ao consultar CEP. Tente novamente.');
                        limparCamposEndereco();
                    })
                    .finally(() => {
                        // Esconder indicador de carregamento
                        mostrarCarregandoCEP(false);
                    });
            } else if (cep.length > 0) {
                // CEP incompleto ou inválido
                mostrarErroCEP('CEP deve ter 8 dígitos');
                limparCamposEndereco();
            }
        });
    }
    
    // Função para preencher os campos de endereço com os dados da API
    function preencherCamposEndereco(data) {
        const logradouroField = document.getElementById('logradouro');
        const bairroField = document.getElementById('bairro');
        const cidadeField = document.getElementById('cidade');
        const estadoField = document.getElementById('estado');
        
        // Preencher cada campo se ele existir e se o dado da API não estiver vazio
        if (logradouroField && data.logradouro) {
            logradouroField.value = data.logradouro;
            logradouroField.style.backgroundColor = '#e8f5e8'; // Destaque verde claro
        }
        
        if (bairroField && data.bairro) {
            bairroField.value = data.bairro;
            bairroField.style.backgroundColor = '#e8f5e8';
        }
        
        if (cidadeField && data.localidade) {
            cidadeField.value = data.localidade;
            cidadeField.style.backgroundColor = '#e8f5e8';
        }
        
        if (estadoField && data.uf) {
            estadoField.value = data.uf;
            estadoField.style.backgroundColor = '#e8f5e8';
        }
        
        // Remover destaque após 3 segundos
        setTimeout(() => {
            [logradouroField, bairroField, cidadeField, estadoField].forEach(field => {
                if (field) field.style.backgroundColor = '';
            });
        }, 3000);
        
        // Focar no campo número para facilitar continuação do preenchimento
        const numeroField = document.getElementById('numero');
        if (numeroField) numeroField.focus();
    }
    
    // Função para limpar campos de endereço quando CEP é inválido
    function limparCamposEndereco() {
        const campos = ['logradouro', 'bairro', 'cidade'];
        campos.forEach(campoId => {
            const campo = document.getElementById(campoId);
            if (campo) {
                campo.value = '';
                campo.style.backgroundColor = '#ffe8e8'; // Destaque vermelho claro
            }
        });
        
        const estadoField = document.getElementById('estado');
        if (estadoField) {
            estadoField.value = '';
            estadoField.style.backgroundColor = '#ffe8e8';
        }
        
        // Remover destaque após 3 segundos
        setTimeout(() => {
            const campos = ['logradouro', 'bairro', 'cidade', 'estado'];
            campos.forEach(campoId => {
                const campo = document.getElementById(campoId);
                if (campo) campo.style.backgroundColor = '';
            });
        }, 3000);
    }
    
    // Função para mostrar indicador de carregamento
    function mostrarCarregandoCEP(mostrar) {
        const cepField = document.getElementById('cep');
        if (!cepField) return;
        
        if (mostrar) {
            cepField.style.backgroundImage = 'url("data:image/svg+xml;charset=UTF-8,%3csvg width=\'16\' height=\'16\' xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 100 100\' preserveAspectRatio=\'xMidYMid\' class=\'uil-ring\'%3e%3cpath fill=\'none\' class=\'bk\' d=\'M0 0h100v100H0z\'/%3e%3cdefs%3e%3cfilter id=\'uil-ring-shadow\' x=\'-100%25\' y=\'-100%25\' width=\'300%25\' height=\'300%25\'%3e%3cfeOffset result=\'offOut\' in=\'SourceGraphic\' dx=\'0\' dy=\'0\'/%3e%3cfeGaussianBlur result=\'blurOut\' in=\'offOut\' stdDeviation=\'0\'/%3e%3cfeBlend in=\'SourceGraphic\' in2=\'blurOut\' mode=\'normal\'/%3e%3c/filter%3e%3c/defs%3e%3cpath d=\'M10 50s0 .8.1 2.2c0 1.3.2 3.1.5 5.1.6 4 1.7 9.4 3.7 15.3 4 11.9 11.1 26.8 23.3 35.9 6.1 4.6 13.4 7.2 21.4 7.2s15.3-2.6 21.4-7.2c12.2-9.1 19.3-24 23.3-35.9 2-5.9 3.1-11.3 3.7-15.3.3-2 .5-3.8.5-5.1 0-1.4.1-2.2.1-2.2s0-.8-.1-2.2c0-1.3-.2-3.1-.5-5.1-.6-4-1.7-9.4-3.7-15.3-4-11.9-11.1-26.8-23.3-35.9-6.1-4.6-13.4-7.2-21.4-7.2S23.2 7.9 17.1 12.5C4.9 21.6-2.2 36.5-6.2 48.4c-2 5.9-3.1 11.3-3.7 15.3-.3 2-.5 3.8-.5 5.1 0 1.4-.1 2.2-.1 2.2z\' fill=\'%23ff7a00\' filter=\'url(%23uil-ring-shadow)\'%3e%3canimateTransform attributeName=\'transform\' type=\'rotate\' from=\'0 50 50\' to=\'360 50 50\' repeatCount=\'indefinite\' dur=\'1s\'/%3e%3c/path%3e%3c/svg%3e")';
            cepField.style.backgroundRepeat = 'no-repeat';
            cepField.style.backgroundPosition = 'right 10px center';
            cepField.style.backgroundSize = '16px 16px';
            cepField.disabled = true;
        } else {
            cepField.style.backgroundImage = '';
            cepField.disabled = false;
        }
    }
    
    // Função para mostrar mensagem de sucesso
    function mostrarSucessoCEP(mensagem) {
        mostrarMensagemCEP(mensagem, 'success');
    }
    
    // Função para mostrar mensagem de erro
    function mostrarErroCEP(mensagem) {
        mostrarMensagemCEP(mensagem, 'error');
    }
    
    // Função genérica para mostrar mensagens relacionadas ao CEP
    function mostrarMensagemCEP(mensagem, tipo) {
        // Remover mensagem anterior se existir
        const mensagemAnterior = document.querySelector('.cep-message');
        if (mensagemAnterior) {
            mensagemAnterior.remove();
        }
        
        // Criar nova mensagem
        const div = document.createElement('div');
        div.className = `cep-message cep-${tipo}`;
        div.innerHTML = `<i class="fas ${tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'}"></i> ${mensagem}`;
        
        // Estilos inline para a mensagem
        div.style.cssText = `
            padding: 8px 12px;
            margin-top: 5px;
            border-radius: 4px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            ${tipo === 'success' 
                ? 'background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb;' 
                : 'background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'
            }
        `;
        
        // Inserir após o campo CEP
        const cepField = document.getElementById('cep');
        if (cepField && cepField.parentNode) {
            cepField.parentNode.insertBefore(div, cepField.nextSibling);
        }
        
        // Remover mensagem após 5 segundos
        setTimeout(() => {
            if (div && div.parentNode) {
                div.remove();
            }
        }, 5000);
    }
    
    // Validação de senhas (mantida como estava)
    const novaSenhaField = document.getElementById('nova_senha');
    const confirmarSenhaField = document.getElementById('confirmar_senha');
    
    if (novaSenhaField && confirmarSenhaField) {
        function validatePasswords() {
            const novaSenha = novaSenhaField.value;
            const confirmarSenha = confirmarSenhaField.value;
            
            if (confirmarSenha && novaSenha !== confirmarSenha) {
                confirmarSenhaField.setCustomValidity('As senhas não coincidem');
            } else {
                confirmarSenhaField.setCustomValidity('');
            }
        }
        
        novaSenhaField.addEventListener('input', validatePasswords);
        confirmarSenhaField.addEventListener('input', validatePasswords);
    }
});
</script>
</body>
</html>
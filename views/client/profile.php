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
            
            // Aplicar máscara no CEP e buscar endereço
            const cepField = document.getElementById('cep');
            if (cepField) {
                cepField.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                    e.target.value = value;
                });
                
                cepField.addEventListener('blur', function(e) {
                    const cep = e.target.value.replace(/\D/g, '');
                    if (cep.length === 8) {
                        fetch(`https://viacep.com.br/ws/${cep}/json/`)
                            .then(response => response.json())
                            .then(data => {
                                if (!data.erro) {
                                    const logradouroField = document.getElementById('logradouro');
                                    const bairroField = document.getElementById('bairro');
                                    const cidadeField = document.getElementById('cidade');
                                    const estadoField = document.getElementById('estado');
                                    
                                    if (logradouroField) logradouroField.value = data.logradouro || '';
                                    if (bairroField) bairroField.value = data.bairro || '';
                                    if (cidadeField) cidadeField.value = data.localidade || '';
                                    if (estadoField) estadoField.value = data.uf || '';
                                }
                            })
                            .catch(error => console.log('Erro ao buscar CEP:', error));
                    }
                });
            }
            
            // Validação de senhas
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
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Manter todo o código de máscaras existente...
    
    // VERSÃO ULTRA-ROBUSTA da busca de CEP
    const cepField = document.getElementById('cep');
    if (cepField) {
        let timeoutBusca = null; // Para debounce
        
        // Aplicar máscara e validação em tempo real
        cepField.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{5})(\d)/, '$1-$2');
            e.target.value = value;
            
            // Limpar timeout anterior
            if (timeoutBusca) {
                clearTimeout(timeoutBusca);
            }
            
            // Feedback visual durante digitação
            const cep = value.replace(/\D/g, '');
            if (cep.length === 8) {
                if (validarFormatoCEP(cep)) {
                    e.target.style.borderColor = '#28a745'; // Verde
                    // Buscar automaticamente após 800ms de pausa na digitação
                    timeoutBusca = setTimeout(() => {
                        buscarEnderecoPorCEP(cep);
                    }, 800);
                } else {
                    e.target.style.borderColor = '#dc3545'; // Vermelho
                    mostrarErroCEP('❌ Este CEP não parece estar no formato válido para o Brasil');
                }
            } else if (cep.length > 0) {
                e.target.style.borderColor = '#ffc107'; // Amarelo para incompleto
            } else {
                e.target.style.borderColor = ''; // Reset
            }
        });
        
        // Buscar também quando sair do campo (comportamento original mantido)
        cepField.addEventListener('blur', function(e) {
            const cep = e.target.value.replace(/\D/g, '');
            if (cep.length === 8 && validarFormatoCEP(cep)) {
                // Se não foi buscado automaticamente, buscar agora
                if (timeoutBusca) {
                    clearTimeout(timeoutBusca);
                    buscarEnderecoPorCEP(cep);
                }
            }
        });
    }
    
    // Função para validar se o CEP está em faixas conhecidas do Brasil
    function validarFormatoCEP(cep) {
        // CEPs brasileiros válidos: 01000-000 a 99999-999
        // Mas vamos ser mais específicos com faixas conhecidas
        const cepNum = parseInt(cep);
        
        // Faixas principais de CEPs ativos no Brasil
        const faixasValidas = [
            [1000, 19999], // São Paulo
            [20000, 28999], // Rio de Janeiro
            [30000, 39999], // Minas Gerais
            [40000, 48999], // Bahia
            [50000, 56999], // Pernambuco
            [60000, 63999], // Ceará
            [64000, 64999], // Piauí
            [65000, 65999], // Maranhão
            [66000, 68999], // Pará
            [69000, 69999], // Amazonas/Acre/Rondônia/Roraima
            [70000, 72999], // Distrito Federal/Goiás
            [73000, 73999], // Tocantins
            [74000, 76999], // Goiás
            [77000, 78999], // Mato Grosso
            [79000, 79999], // Mato Grosso do Sul
            [80000, 87999], // Paraná
            [88000, 89999], // Santa Catarina
            [90000, 99999]  // Rio Grande do Sul
        ];
        
        return faixasValidas.some(faixa => cepNum >= faixa[0] && cepNum <= faixa[1]);
    }
    
    // Função principal de busca com múltiplas tentativas
    async function buscarEnderecoPorCEP(cep) {
        mostrarCarregandoCEP(true);
        
        const apis = [
            {
                nome: 'ViaCEP',
                url: `https://viacep.com.br/ws/${cep}/json/`,
                processar: (data) => ({
                    logradouro: data.logradouro || '',
                    bairro: data.bairro || '',
                    localidade: data.localidade || '',
                    uf: data.uf || ''
                }),
                validar: (data) => !data.erro
            },
            {
                nome: 'BrasilAPI',
                url: `https://brasilapi.com.br/api/cep/v1/${cep}`,
                processar: (data) => ({
                    logradouro: data.street || '',
                    bairro: data.district || '',
                    localidade: data.city || '',
                    uf: data.state || ''
                }),
                validar: (data) => !data.error && data.city
            },
            {
                nome: 'CEP Aberto',
                url: `https://www.cepaberto.com/api/v3/cep?cep=${cep}`,
                processar: (data) => ({
                    logradouro: data.address || '',
                    bairro: data.district || '',
                    localidade: data.city?.name || '',
                    uf: data.state?.name || ''
                }),
                validar: (data) => data.status === 200
            }
        ];
        
        let ultimoErro = null;
        
        for (let i = 0; i < apis.length; i++) {
            const api = apis[i];
            
            try {
                console.log(`Tentando API ${api.nome}...`);
                
                const response = await fetch(api.url, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                    signal: AbortSignal.timeout ? AbortSignal.timeout(5000) : undefined
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (api.validar(data)) {
                    const enderecoNormalizado = api.processar(data);
                    
                    if (enderecoNormalizado.localidade) { // Pelo menos cidade deve existir
                        preencherCamposEndereco(enderecoNormalizado);
                        mostrarSucessoCEP(`✅ Endereço encontrado via ${api.nome}!`);
                        return; // Sucesso, parar aqui
                    }
                }
                
                throw new Error(`CEP não encontrado na ${api.nome}`);
                
            } catch (error) {
                console.warn(`${api.nome} falhou:`, error.message);
                ultimoErro = error;
                
                // Se não é o último API, continuar tentando
                if (i < apis.length - 1) {
                    continue;
                }
            }
        }
        
        // Se chegou aqui, todas as APIs falharam
        console.error('Todas as APIs de CEP falharam:', ultimoErro);
        
        // Dar feedback específico baseado no tipo de erro
        if (ultimoErro?.message.includes('não encontrado')) {
            mostrarErroCEP(`❌ CEP ${cep.replace(/(\d{5})(\d{3})/, '$1-$2')} não foi encontrado. Verifique se está correto ou preencha manualmente.`);
            sugerirCEPsProximos(cep);
        } else if (ultimoErro?.message.includes('timeout') || ultimoErro?.name === 'AbortError') {
            mostrarErroCEP('⏱️ Tempo limite esgotado. Verifique sua conexão com a internet.');
        } else {
            mostrarErroCEP('⚠️ Serviços de CEP temporariamente indisponíveis. Preencha o endereço manualmente.');
        }
        
        limparCamposEndereco();
    }
    
    // Função para sugerir CEPs próximos
    function sugerirCEPsProximos(cep) {
        const cepNum = parseInt(cep);
        const sugestoes = [];
        
        // Gerar alguns CEPs próximos para teste
        for (let i = -5; i <= 5; i++) {
            if (i !== 0) {
                const novoCep = (cepNum + i).toString().padStart(8, '0');
                if (validarFormatoCEP(novoCep)) {
                    sugestoes.push(novoCep.replace(/(\d{5})(\d{3})/, '$1-$2'));
                }
            }
        }
        
        if (sugestoes.length > 0) {
            const sugestoesTexto = sugestoes.slice(0, 3).join(', ');
            mostrarInfoCEP(`💡 Tente CEPs próximos: ${sugestoesTexto}`);
        }
    }
    
    // Função para mostrar informações úteis (não erro)
    function mostrarInfoCEP(mensagem) {
        mostrarMensagemCEP(mensagem, 'info');
    }
    
    // Função aprimorada para mensagens
    function mostrarMensagemCEP(mensagem, tipo) {
        const mensagemAnterior = document.querySelector('.cep-message');
        if (mensagemAnterior) {
            mensagemAnterior.remove();
        }
        
        const div = document.createElement('div');
        div.className = `cep-message cep-${tipo}`;
        div.innerHTML = mensagem;
        
        const cores = {
            success: { bg: '#d4edda', color: '#155724', border: '#c3e6cb' },
            error: { bg: '#f8d7da', color: '#721c24', border: '#f5c6cb' },
            info: { bg: '#d1ecf1', color: '#0c5460', border: '#bee5eb' }
        };
        
        const estilo = cores[tipo] || cores.info;
        
        div.style.cssText = `
            padding: 12px 16px;
            margin-top: 8px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            line-height: 1.4;
            background-color: ${estilo.bg};
            color: ${estilo.color};
            border: 1px solid ${estilo.border};
            animation: slideIn 0.3s ease-out;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        `;
        
        const cepField = document.getElementById('cep');
        if (cepField?.parentNode) {
            cepField.parentNode.insertBefore(div, cepField.nextSibling);
        }
        
        // Auto-remover baseado no tipo
        const tempoRemocao = tipo === 'error' ? 8000 : tipo === 'info' ? 6000 : 4000;
        setTimeout(() => {
            if (div?.parentNode) {
                div.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => div.remove(), 300);
            }
        }, tempoRemocao);
    }
    
    // Manter as outras funções (preencherCamposEndereco, mostrarCarregandoCEP, etc.)
    // ... [resto do código anterior]
});
</script>
</body>
</html>
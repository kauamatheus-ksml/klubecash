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
$personalInfoMessage = $_SESSION['personal_info_message'] ?? '';
$personalInfoSuccess = $_SESSION['personal_info_success'] ?? false;
$addressMessage = $_SESSION['address_message'] ?? '';
$addressSuccess = $_SESSION['address_success'] ?? false;
$passwordMessage = $_SESSION['password_message'] ?? '';
$passwordSuccess = $_SESSION['password_success'] ?? false;

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
        
        // Redirecionar para evitar reenvio
        header("Location: " . $_SERVER['REQUEST_URI'] . "#personal-info");
        exit;
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
            
            // Armazenar mensagem na sessão
            $_SESSION['address_success'] = $result['status'];
            $_SESSION['address_message'] = $result['message'];
            
        } catch (Exception $e) {
            $_SESSION['address_success'] = false;
            $_SESSION['address_message'] = logError('Erro ao atualizar endereço', $e->getMessage());
        }
        
        // Redirecionar para evitar reenvio
        header("Location: " . $_SERVER['REQUEST_URI'] . "#address");
        exit;
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
            
            // Armazenar mensagem na sessão
            $_SESSION['password_success'] = $passwordSuccess;
            $_SESSION['password_message'] = $passwordMessage;
            
        } catch (Exception $e) {
            $_SESSION['password_success'] = false;
            $_SESSION['password_message'] = logError('Erro ao atualizar senha', $e->getMessage());
        }
        
        // Redirecionar para evitar reenvio
        header("Location: " . $_SERVER['REQUEST_URI'] . "#password");
        exit;
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
    <link rel="stylesheet" href="../assets/css/views/client/profile.css">
    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <
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
                
                
            </div>

            <!-- Seção de formulários -->
            <div class="form-section">
                <!-- Formulário de informações pessoais (ATUALIZADO) -->
                <div class="form-card" id="personal-info"><div class="form-card" id="personal-info">
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
                <div class="form-card" id="address">
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
                <div class="form-card" id="password">
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
        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash;
            if (hash) {
                const element = document.querySelector(hash);
                if (element) {
                    // Pequeno delay para garantir que a página carregou
                    setTimeout(() => {
                        element.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'center' 
                        });
                    }, 100);
                }
            }
        });
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
                if (submitBtn) {
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
                }
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
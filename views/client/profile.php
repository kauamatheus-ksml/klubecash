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
    return "Ocorreu um erro ao processar sua solicitação. Por favor, tente novamente.";
}

// Processamento de formulários
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Formulário de informações pessoais
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'personal_info') {
        try {
            $updateData = [
                'nome' => $_POST['nome'] ?? '',
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
            $addressSuccess = $result['status'];
            $addressMessage = $result['message'];
            
        } catch (Exception $e) {
            $addressSuccess = false;
            $addressMessage = logError('Erro ao atualizar endereço', $e->getMessage());
        }
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
                $passwordMessage = 'Todos os campos de senha são obrigatórios.';
            } else if ($novaSenha !== $confirmarSenha) {
                $passwordSuccess = false;
                $passwordMessage = 'As senhas não coincidem.';
            } else if (strlen($novaSenha) < PASSWORD_MIN_LENGTH) {
                $passwordSuccess = false;
                $passwordMessage = 'A nova senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres.';
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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/views/client/profile.css">
</head>
<body>
    <!-- Incluir navbar -->
    <?php include_once '../components/navbar.php'; ?>
    
    <div class="container" style="margin-top: 80px;">
        <div class="page-header">
            <h1>Meu Perfil</h1>
            <p class="page-subtitle">Gerencie suas informações pessoais e preferências</p>
        </div>
        
        <?php if (isset($error) && $error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php else: ?>
        
        <!-- Grade de Perfil -->
        <div class="profile-grid">
            <!-- Sidebar de Perfil -->
            <div class="profile-sidebar-container">
                <div class="card profile-sidebar">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($profileData['perfil']['nome'] ?? 'U', 0, 1)); ?>
                    </div>
                    <h2 class="profile-name"><?php echo htmlspecialchars($profileData['perfil']['nome'] ?? 'Usuário'); ?></h2>
                    <p class="profile-email"><?php echo htmlspecialchars($profileData['perfil']['email'] ?? 'email@exemplo.com'); ?></p>
                    <p style="color: var(--medium-gray); font-size: 14px;">
                        Membro desde: <?php echo date('d/m/Y', strtotime($profileData['perfil']['data_criacao'] ?? 'now')); ?>
                    </p>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <div class="stat-value">R$ <?php echo number_format($profileData['estatisticas']['total_cashback'] ?? 0, 2, ',', '.'); ?></div>
                            <div class="stat-label">Cashback Total</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $profileData['estatisticas']['total_transacoes'] ?? 0; ?></div>
                            <div class="stat-label">Transações</div>
                        </div>
                    </div>
                </div>
                
                <!-- Card de Detalhes da Conta -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Detalhes da Conta</h3>
                    </div>
                    
                    <div class="details-item">
                        <div class="details-label">Status</div>
                        <div class="details-value">
                            <span style="color: var(--success-color); background-color: #E6F7E6; padding: 3px 10px; border-radius: 20px; font-size: 14px;">
                                <?php echo ucfirst($profileData['perfil']['status'] ?? 'Ativo'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="details-item">
                        <div class="details-label">Último Login</div>
                        <div class="details-value">
                            <?php 
                            $ultimoLogin = $profileData['perfil']['ultimo_login'] ?? null;
                            echo $ultimoLogin 
                                ? date('d/m/Y H:i', strtotime($ultimoLogin . ' -3 hours')) 
                                : 'Não disponível';
                            
                            ?>
                        </div>
                    </div>
                    
                    <div class="details-item">
                        <div class="details-label">Lojas Utilizadas</div>
                        <div class="details-value"><?php echo $profileData['estatisticas']['total_lojas_utilizadas'] ?? 0; ?></div>
                    </div>
                    
                    <div class="details-item">
                        <div class="details-label">Total de Compras</div>
                        <div class="details-value">R$ <?php echo number_format($profileData['estatisticas']['total_compras'] ?? 0, 2, ',', '.'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Conteúdo Principal -->
            <div class="profile-content">
                <!-- Formulário de Informações Pessoais -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Informações Pessoais</h3>
                    </div>
                    
                    <?php if (!empty($personalInfoMessage)): ?>
                        <div class="alert <?php echo $personalInfoSuccess ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo htmlspecialchars($personalInfoMessage); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="POST">
                        <input type="hidden" name="form_type" value="personal_info">
                        
                        <div class="form-group">
                            <label class="form-label" for="nome">Nome Completo</label>
                            <input type="text" id="nome" name="nome" class="form-control" value="<?php echo htmlspecialchars($profileData['perfil']['nome'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="email">E-mail</label>
                            <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($profileData['perfil']['email'] ?? ''); ?>" disabled>
                            <small style="color: var(--medium-gray); font-size: 12px;">O e-mail não pode ser alterado.</small>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="telefone">Telefone</label>
                                <input type="tel" id="telefone" name="telefone" class="form-control" value="<?php echo htmlspecialchars($profileData['contato']['telefone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="celular">Celular</label>
                                <input type="tel" id="celular" name="celular" class="form-control" value="<?php echo htmlspecialchars($profileData['contato']['celular'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="email_alternativo">E-mail Alternativo</label>
                                <input type="email" id="email_alternativo" name="email_alternativo" class="form-control" value="<?php echo htmlspecialchars($profileData['contato']['email_alternativo'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Salvar Informações Pessoais</button>
                    </form>
                </div>
                
                <!-- Formulário de Endereço -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Endereço</h3>
                    </div>
                    
                    <?php if (!empty($addressMessage)): ?>
                        <div class="alert <?php echo $addressSuccess ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo htmlspecialchars($addressMessage); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="POST">
                        <input type="hidden" name="form_type" value="address">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="cep">CEP</label>
                                <input type="text" id="cep" name="cep" class="form-control" value="<?php echo htmlspecialchars($profileData['endereco']['cep'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label" for="logradouro">Logradouro</label>
                                <input type="text" id="logradouro" name="logradouro" class="form-control" value="<?php echo htmlspecialchars($profileData['endereco']['logradouro'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="numero">Número</label>
                                <input type="text" id="numero" name="numero" class="form-control" value="<?php echo htmlspecialchars($profileData['endereco']['numero'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="complemento">Complemento</label>
                            <input type="text" id="complemento" name="complemento" class="form-control" value="<?php echo htmlspecialchars($profileData['endereco']['complemento'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="bairro">Bairro</label>
                                <input type="text" id="bairro" name="bairro" class="form-control" value="<?php echo htmlspecialchars($profileData['endereco']['bairro'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="cidade">Cidade</label>
                                <input type="text" id="cidade" name="cidade" class="form-control" value="<?php echo htmlspecialchars($profileData['endereco']['cidade'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="estado">Estado</label>
                                <input type="text" id="estado" name="estado" class="form-control" value="<?php echo htmlspecialchars($profileData['endereco']['estado'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Salvar Endereço</button>
                    </form>
                </div>
                
                <!-- Formulário de Alteração de Senha -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Alterar Senha</h3>
                    </div>
                    
                    <?php if (!empty($passwordMessage)): ?>
                        <div class="alert <?php echo $passwordSuccess ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo htmlspecialchars($passwordMessage); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="POST">
                        <input type="hidden" name="form_type" value="password">
                        
                        <div class="form-group">
                            <label class="form-label" for="senha_atual">Senha Atual</label>
                            <input type="password" id="senha_atual" name="senha_atual" class="form-control" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="nova_senha">Nova Senha</label>
                                <input type="password" id="nova_senha" name="nova_senha" class="form-control" required>
                                <small style="color: var(--medium-gray); font-size: 12px;">Mínimo de <?php echo PASSWORD_MIN_LENGTH; ?> caracteres</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="confirmar_senha">Confirmar Nova Senha</label>
                                <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Alterar Senha</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Script para preenchimento automático de endereço via CEP
        document.getElementById('cep').addEventListener('blur', function() {
            const cep = this.value.replace(/\D/g, '');
            
            if (cep.length !== 8) {
                return;
            }
            
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        document.getElementById('logradouro').value = data.logradouro;
                        document.getElementById('bairro').value = data.bairro;
                        document.getElementById('cidade').value = data.localidade;
                        document.getElementById('estado').value = data.uf;
                        document.getElementById('numero').focus();
                    }
                })
                .catch(error => console.error('Erro:', error));
        });
    </script>
</body>
</html>
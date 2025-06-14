<?php
// views/stores/profile.php
// Incluir arquivos necessários
require_once '../../config/database.php';
require_once '../../config/constants.php';
require_once '../../controllers/AuthController.php';
require_once '../../utils/Validator.php';
require_once '../../utils/Security.php';

// Iniciar sessão
session_start();

// Verificar se o usuário está logado e é uma loja
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'loja') {
    header("Location: " . LOGIN_URL . "?error=acesso_restrito");
    exit;
}

// Obter ID do usuário logado
$userId = $_SESSION['user_id'];

// Obter dados da loja associada ao usuário
$db = Database::getConnection();
$storeQuery = $db->prepare("
    SELECT l.*, le.* 
    FROM lojas l
    LEFT JOIN lojas_endereco le ON l.id = le.loja_id
    WHERE l.usuario_id = :usuario_id
");
$storeQuery->bindParam(':usuario_id', $userId);
$storeQuery->execute();

// Verificar se o usuário tem uma loja associada
if ($storeQuery->rowCount() == 0) {
    header('Location: ' . LOGIN_URL . '?error=' . urlencode('Sua conta não está associada a nenhuma loja. Entre em contato com o suporte.'));
    exit;
}

// Obter dados da loja e endereço
$store = $storeQuery->fetch(PDO::FETCH_ASSOC);
$storeId = $store['id'];

// Obter dados do usuário
$userQuery = $db->prepare("SELECT nome, email FROM usuarios WHERE id = :id");
$userQuery->bindParam(':id', $userId);
$userQuery->execute();
$user = $userQuery->fetch(PDO::FETCH_ASSOC);

// Processar formulário se enviado
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_store_info':
            // Atualizar informações da loja
            try {
                $email = trim($_POST['email']);
                $telefone = preg_replace('/\D/', '', $_POST['telefone']); // Remover caracteres não numéricos
                $website = trim($_POST['website'] ?? '');
                $descricao = trim($_POST['descricao'] ?? '');
                
                // Validações
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Email inválido.';
                } elseif (strlen($telefone) < 10 || strlen($telefone) > 11) {
                    $error = 'Telefone inválido. Digite um telefone com DDD.';
                } else {
                    // Verificar se email já existe em outra loja
                    $checkEmailStmt = $db->prepare("SELECT id FROM lojas WHERE email = :email AND id != :loja_id");
                    $checkEmailStmt->bindParam(':email', $email);
                    $checkEmailStmt->bindParam(':loja_id', $storeId);
                    $checkEmailStmt->execute();
                    
                    if ($checkEmailStmt->rowCount() > 0) {
                        $error = 'Este email já está sendo usado por outra loja.';
                    } else {
                        // Atualizar informações
                        $updateStmt = $db->prepare("
                            UPDATE lojas 
                            SET email = :email, telefone = :telefone, website = :website, descricao = :descricao
                            WHERE id = :id
                        ");
                        $updateStmt->bindParam(':email', $email);
                        $updateStmt->bindParam(':telefone', $telefone);
                        $updateStmt->bindParam(':website', $website);
                        $updateStmt->bindParam(':descricao', $descricao);
                        $updateStmt->bindParam(':id', $storeId);
                        
                        if ($updateStmt->execute()) {
                            // Atualizar dados em memória
                            $store['email'] = $email;
                            $store['telefone'] = $telefone;
                            $store['website'] = $website;
                            $store['descricao'] = $descricao;
                            
                            $success = 'Informações da loja atualizadas com sucesso!';
                        } else {
                            $error = 'Erro ao atualizar informações. Tente novamente.';
                        }
                    }
                }
            } catch (PDOException $e) {
                $error = 'Erro ao atualizar informações. Tente novamente.';
                error_log('Erro ao atualizar loja: ' . $e->getMessage());
            }
            break;
            
        case 'update_address':
            // Atualizar endereço da loja - VERSÃO COM DEBUG
            try {
                // Log inicial para debug
                error_log("=== INÍCIO UPDATE ADDRESS ===");
                error_log("POST recebido: " . print_r($_POST, true));
                error_log("Store ID: " . $storeId);
                
                // Limpar e validar dados de entrada
                $cep = preg_replace('/\D/', '', $_POST['cep'] ?? '');
                $logradouro = trim($_POST['logradouro'] ?? '');
                $numero = trim($_POST['numero'] ?? '');
                $complemento = trim($_POST['complemento'] ?? '');
                $bairro = trim($_POST['bairro'] ?? '');
                $cidade = trim($_POST['cidade'] ?? '');
                $estado = trim($_POST['estado'] ?? '');
                
                // Log dos dados processados
                error_log("Dados processados:");
                error_log("CEP: '$cep' (length: " . strlen($cep) . ")");
                error_log("Logradouro: '$logradouro'");
                error_log("Número: '$numero'");
                error_log("Complemento: '$complemento'");
                error_log("Bairro: '$bairro'");
                error_log("Cidade: '$cidade'");
                error_log("Estado: '$estado'");
                
                // Validações obrigatórias
                if (strlen($cep) != 8) {
                    $error = 'CEP deve ter exatamente 8 dígitos.';
                    error_log("ERRO: CEP inválido - '$cep' tem " . strlen($cep) . " dígitos");
                } elseif (empty($logradouro)) {
                    $error = 'Logradouro é obrigatório.';
                    error_log("ERRO: Logradouro vazio");
                } elseif (empty($numero)) {
                    $error = 'Número é obrigatório.';
                    error_log("ERRO: Número vazio");
                } elseif (empty($bairro)) {
                    $error = 'Bairro é obrigatório.';
                    error_log("ERRO: Bairro vazio");
                } elseif (empty($cidade)) {
                    $error = 'Cidade é obrigatória.';
                    error_log("ERRO: Cidade vazia");
                } elseif (empty($estado)) {
                    $error = 'Estado é obrigatório.';
                    error_log("ERRO: Estado vazio");
                } elseif (strlen($estado) != 2) {
                    $error = 'Estado deve ter 2 caracteres (ex: MG, SP).';
                    error_log("ERRO: Estado com tamanho incorreto - '$estado' tem " . strlen($estado) . " caracteres");
                } else {
                    error_log("Validações OK - prosseguindo com o banco de dados");
                    
                    // Verificar se já existe um endereço para esta loja
                    $checkAddressStmt = $db->prepare("SELECT id FROM lojas_endereco WHERE loja_id = ? LIMIT 1");
                    $checkAddressStmt->execute([$storeId]);
                    
                    $addressExists = $checkAddressStmt->rowCount() > 0;
                    error_log("Endereço existe? " . ($addressExists ? 'SIM' : 'NÃO'));
                    
                    if ($addressExists) {
                        // Atualizar endereço existente
                        $sql = "UPDATE lojas_endereco SET cep = ?, logradouro = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ? WHERE loja_id = ?";
                        $params = [$cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado, $storeId];
                        error_log("SQL UPDATE: $sql");
                    } else {
                        // Inserir novo endereço
                        $sql = "INSERT INTO lojas_endereco (loja_id, cep, logradouro, numero, complemento, bairro, cidade, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                        $params = [$storeId, $cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado];
                        error_log("SQL INSERT: $sql");
                    }
                    
                    error_log("Parâmetros: " . print_r($params, true));
                    
                    // Executar a query
                    $stmt = $db->prepare($sql);
                    $result = $stmt->execute($params);
                    
                    error_log("Resultado da execução: " . ($result ? 'SUCESSO' : 'FALHA'));
                    
                    if ($result) {
                        // Atualizar dados em memória para exibir os valores corretos na tela
                        $store['cep'] = $cep;
                        $store['logradouro'] = $logradouro;
                        $store['numero'] = $numero;
                        $store['complemento'] = $complemento;
                        $store['bairro'] = $bairro;
                        $store['cidade'] = $cidade;
                        $store['estado'] = $estado;
                        
                        $success = 'Endereço atualizado com sucesso!';
                        error_log("SUCCESS: Endereço da loja {$storeId} atualizado com sucesso");
                    } else {
                        $error = 'Erro ao salvar endereço no banco de dados. Tente novamente.';
                        $errorInfo = $stmt->errorInfo();
                        error_log("ERRO SQL: " . print_r($errorInfo, true));
                    }
                }
            } catch (PDOException $e) {
                $error = 'Erro ao processar dados do endereço. Tente novamente.';
                error_log('ERRO PDO ao atualizar endereço da loja ' . $storeId . ': ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
            } catch (Exception $e) {
                $error = 'Erro inesperado ao atualizar endereço. Tente novamente.';
                error_log('ERRO GERAL ao atualizar endereço da loja ' . $storeId . ': ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
            }
            
            error_log("=== FIM UPDATE ADDRESS ===");
            break;
            
        case 'change_password':
            // Alterar senha
            try {
                $senhaAtual = $_POST['senha_atual'];
                $novaSenha = $_POST['nova_senha'];
                $confirmarSenha = $_POST['confirmar_senha'];
                
                // Validações básicas
                if (empty($senhaAtual) || empty($novaSenha) || empty($confirmarSenha)) {
                    $error = 'Todos os campos de senha são obrigatórios.';
                } elseif ($novaSenha !== $confirmarSenha) {
                    $error = 'A confirmação de senha não confere.';
                } elseif (strlen($novaSenha) < 8) {
                    $error = 'Nova senha deve ter pelo menos 8 caracteres.';
                } else {
                    // Verificar senha atual
                    $checkPasswordStmt = $db->prepare("SELECT senha_hash FROM usuarios WHERE id = :id");
                    $checkPasswordStmt->bindParam(':id', $userId);
                    $checkPasswordStmt->execute();
                    
                    if ($checkPasswordStmt->rowCount() > 0) {
                        $currentPasswordHash = $checkPasswordStmt->fetchColumn();
                        
                        // Verificar se a senha atual está correta
                        if (password_verify($senhaAtual, $currentPasswordHash)) {
                            // Gerar hash da nova senha
                            $newPasswordHash = password_hash($novaSenha, PASSWORD_DEFAULT, ['cost' => 12]);
                            
                            // Atualizar senha
                            $updatePasswordStmt = $db->prepare("UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :id");
                            $updatePasswordStmt->bindParam(':senha_hash', $newPasswordHash);
                            $updatePasswordStmt->bindParam(':id', $userId);
                            
                            if ($updatePasswordStmt->execute()) {
                                $success = 'Senha alterada com sucesso!';
                            } else {
                                $error = 'Erro ao alterar senha. Tente novamente.';
                            }
                        } else {
                            $error = 'Senha atual incorreta.';
                        }
                    } else {
                        $error = 'Usuário não encontrado.';
                    }
                }
            } catch (PDOException $e) {
                $error = 'Erro ao alterar senha. Tente novamente.';
                error_log('Erro ao alterar senha: ' . $e->getMessage());
            }
            break;
            
        default:
            $error = 'Ação inválida.';
            break;
    }
}
// === BOTÃO DE DEBUG TEMPORÁRIO ===
$showDebugButton = true; 
// Definir menu ativo
$activeMenu = 'profile';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil da Loja - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    
    <style>
        /* Variáveis CSS */
        :root {
            --primary-color: #FF7A00;
            --primary-dark: #E06E00;
            --primary-light: #FFF0E6;
            --secondary-color: #2A3F54;
            --success-color: #28A745;
            --warning-color: #FFC107;
            --danger-color: #DC3545;
            --info-color: #17A2B8;
            --light-gray: #F8F9FA;
            --medium-gray: #6C757D;
            --dark-gray: #343A40;
            --white: #FFFFFF;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.08);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F5F7FA;
            color: var(--dark-gray);
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        /* Layout principal */
        .main-content {
            margin-left: 250px;
            padding: 1.5rem;
            min-height: 100vh;
        }

        /* Cabeçalho */
        .dashboard-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .dashboard-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: var(--medium-gray);
            font-size: 1rem;
        }

        /* Alertas */
        .alert {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-md);
            margin-bottom: 2rem;
            border-left: 4px solid;
        }

        .alert.success {
            border-color: var(--success-color);
            background-color: #D4EDDA;
            color: #155724;
        }

        .alert.error {
            border-color: var(--danger-color);
            background-color: #F8D7DA;
            color: #721C24;
        }

        /* Cards */
        .card {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .card-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #E1E5E9;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--secondary-color);
            margin: 0;
        }

        /* Formulários */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .form-section {
            background-color: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-gray);
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.1);
        }

        .form-group small {
            display: block;
            margin-top: 0.25rem;
            color: var(--medium-gray);
            font-size: 0.875rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        /* Botões */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--medium-gray);
            color: var(--white);
        }

        .btn-secondary:hover {
            background-color: #5A6C7D;
        }

        /* Informações da loja */
        .store-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .info-item {
            background-color: var(--light-gray);
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }

        .info-label {
            font-size: 0.875rem;
            color: var(--medium-gray);
            margin-bottom: 0.5rem;
        }

        .info-value {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--dark-gray);
        }

        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-aprovado {
            background-color: #D4EDDA;
            color: #155724;
        }

        .status-pendente {
            background-color: #FFF3CD;
            color: #856404;
        }

        .status-rejeitado {
            background-color: #F8D7DA;
            color: #721C24;
        }

        /* Responsividade */
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767.98px) {
            .main-content {
                padding: 1rem;
            }

            .dashboard-title {
                font-size: 1.5rem;
            }

            .store-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Incluir sidebar -->
    <?php include_once '../components/sidebar-store.php'; ?>
    
    <div class="main-content" id="mainContent">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Perfil da Loja</h1>
            <p class="subtitle">Gerencie as informações da sua loja</p>
        </div>
        
        <?php if (!empty($success)): ?>
            <div class="alert success">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert error">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Informações básicas da loja -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Informações da Loja</h2>
            </div>
            
            <div class="store-info">
                <div class="info-item">
                    <div class="info-label">Nome Fantasia</div>
                    <div class="info-value"><?php echo htmlspecialchars($store['nome_fantasia']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Razão Social</div>
                    <div class="info-value"><?php echo htmlspecialchars($store['razao_social']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">CNPJ</div>
                    <div class="info-value">
                        <?php 
                        $cnpj = $store['cnpj'];
                        $formattedCnpj = preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "$1.$2.$3/$4-$5", $cnpj);
                        echo htmlspecialchars($formattedCnpj);
                        ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="status-badge status-<?php echo $store['status']; ?>">
                            <?php echo ucfirst($store['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Porcentagem de Cashback</div>
                    <div class="info-value"><?php echo number_format($store['porcentagem_cashback'], 2, ',', '.'); ?>%</div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Data de Cadastro</div>
                    <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($store['data_cadastro'])); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Formulários editáveis -->
        <div class="form-grid">
            <!-- Informações de contato -->
            <div class="form-section">
                <div class="card-header">
                    <h3 class="card-title">Informações de Contato</h3>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_store_info">
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($store['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefone">Telefone</label>
                        <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($store['telefone']); ?>" required>
                        <small>Formato: (XX) XXXXX-XXXX</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="website">Website</label>
                        <input type="url" id="website" name="website" value="<?php echo htmlspecialchars($store['website'] ?? ''); ?>" placeholder="https://www.suaempresa.com.br">
                    </div>
                    
                    <div class="form-group">
                        <label for="descricao">Descrição</label>
                        <textarea id="descricao" name="descricao" rows="4" placeholder="Descreva sua loja e produtos..."><?php echo htmlspecialchars($store['descricao'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Salvar Informações</button>
                </form>
            </div>
            
            <!-- Endereço -->
            <div class="form-section">
                <div class="card-header">
                    <h3 class="card-title">Endereço</h3>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_address">
                    
                    <div class="form-group">
                        <label for="cep">CEP</label>
                        <input type="text" id="cep" name="cep" value="<?php echo htmlspecialchars($store['cep'] ?? ''); ?>" required maxlength="9" placeholder="00000-000">
                    </div>
                    
                    <div class="form-group">
                        <label for="logradouro">Logradouro</label>
                        <input type="text" id="logradouro" name="logradouro" value="<?php echo htmlspecialchars($store['logradouro'] ?? ''); ?>" required placeholder="Rua, Avenida, etc.">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="numero">Número</label>
                            <input type="text" id="numero" name="numero" value="<?php echo htmlspecialchars($store['numero'] ?? ''); ?>" required placeholder="123">
                        </div>
                        
                        <div class="form-group">
                            <label for="complemento">Complemento</label>
                            <input type="text" id="complemento" name="complemento" value="<?php echo htmlspecialchars($store['complemento'] ?? ''); ?>" placeholder="Apto, Sala, etc.">
                        </div>
                    </div>
                    
                    <!-- CAMPOS QUE ESTAVAM FALTANDO -->
                    <div class="form-group">
                        <label for="bairro">Bairro</label>
                        <input type="text" id="bairro" name="bairro" value="<?php echo htmlspecialchars($store['bairro'] ?? ''); ?>" required placeholder="Nome do bairro">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="cidade">Cidade</label>
                            <input type="text" id="cidade" name="cidade" value="<?php echo htmlspecialchars($store['cidade'] ?? ''); ?>" required placeholder="Nome da cidade">
                        </div>
                        
                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado" required>
                                <option value="">Selecione</option>
                                <option value="AC" <?php echo ($store['estado'] ?? '') === 'AC' ? 'selected' : ''; ?>>Acre</option>
                                <option value="AL" <?php echo ($store['estado'] ?? '') === 'AL' ? 'selected' : ''; ?>>Alagoas</option>
                                <option value="AP" <?php echo ($store['estado'] ?? '') === 'AP' ? 'selected' : ''; ?>>Amapá</option>
                                <option value="AM" <?php echo ($store['estado'] ?? '') === 'AM' ? 'selected' : ''; ?>>Amazonas</option>
                                <option value="BA" <?php echo ($store['estado'] ?? '') === 'BA' ? 'selected' : ''; ?>>Bahia</option>
                                <option value="CE" <?php echo ($store['estado'] ?? '') === 'CE' ? 'selected' : ''; ?>>Ceará</option>
                                <option value="DF" <?php echo ($store['estado'] ?? '') === 'DF' ? 'selected' : ''; ?>>Distrito Federal</option>
                                <option value="ES" <?php echo ($store['estado'] ?? '') === 'ES' ? 'selected' : ''; ?>>Espírito Santo</option>
                                <option value="GO" <?php echo ($store['estado'] ?? '') === 'GO' ? 'selected' : ''; ?>>Goiás</option>
                                <option value="MA" <?php echo ($store['estado'] ?? '') === 'MA' ? 'selected' : ''; ?>>Maranhão</option>
                                <option value="MT" <?php echo ($store['estado'] ?? '') === 'MT' ? 'selected' : ''; ?>>Mato Grosso</option>
                                <option value="MS" <?php echo ($store['estado'] ?? '') === 'MS' ? 'selected' : ''; ?>>Mato Grosso do Sul</option>
                                <option value="MG" <?php echo ($store['estado'] ?? '') === 'MG' ? 'selected' : ''; ?>>Minas Gerais</option>
                                <option value="PA" <?php echo ($store['estado'] ?? '') === 'PA' ? 'selected' : ''; ?>>Pará</option>
                                <option value="PB" <?php echo ($store['estado'] ?? '') === 'PB' ? 'selected' : ''; ?>>Paraíba</option>
                                <option value="PR" <?php echo ($store['estado'] ?? '') === 'PR' ? 'selected' : ''; ?>>Paraná</option>
                                <option value="PE" <?php echo ($store['estado'] ?? '') === 'PE' ? 'selected' : ''; ?>>Pernambuco</option>
                                <option value="PI" <?php echo ($store['estado'] ?? '') === 'PI' ? 'selected' : ''; ?>>Piauí</option>
                                <option value="RJ" <?php echo ($store['estado'] ?? '') === 'RJ' ? 'selected' : ''; ?>>Rio de Janeiro</option>
                                <option value="RN" <?php echo ($store['estado'] ?? '') === 'RN' ? 'selected' : ''; ?>>Rio Grande do Norte</option>
                                <option value="RS" <?php echo ($store['estado'] ?? '') === 'RS' ? 'selected' : ''; ?>>Rio Grande do Sul</option>
                                <option value="RO" <?php echo ($store['estado'] ?? '') === 'RO' ? 'selected' : ''; ?>>Rondônia</option>
                                <option value="RR" <?php echo ($store['estado'] ?? '') === 'RR' ? 'selected' : ''; ?>>Roraima</option>
                                <option value="SC" <?php echo ($store['estado'] ?? '') === 'SC' ? 'selected' : ''; ?>>Santa Catarina</option>
                                <option value="SP" <?php echo ($store['estado'] ?? '') === 'SP' ? 'selected' : ''; ?>>São Paulo</option>
                                <option value="SE" <?php echo ($store['estado'] ?? '') === 'SE' ? 'selected' : ''; ?>>Sergipe</option>
                                <option value="TO" <?php echo ($store['estado'] ?? '') === 'TO' ? 'selected' : ''; ?>>Tocantins</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Salvar Endereço</button>
                </form>
            </div>
        </div>
        
        <!-- Alteração de senha -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Alterar Senha</h3>
            </div>
            
            <form method="POST" action="" style="max-width: 500px;">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label for="senha_atual">Senha Atual</label>
                    <input type="password" id="senha_atual" name="senha_atual" required>
                </div>
                
                <div class="form-group">
                    <label for="nova_senha">Nova Senha</label>
                    <input type="password" id="nova_senha" name="nova_senha" required>
                    <small>Mínimo de 8 caracteres</small>
                </div>
                
                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Nova Senha</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Alterar Senha</button>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Máscara para CEP
            const cepInput = document.getElementById('cep');
            if (cepInput) {
                cepInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                    e.target.value = value;
                });
                
                // Buscar endereço por CEP
                cepInput.addEventListener('blur', function(e) {
                    const cep = e.target.value.replace(/\D/g, '');
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
            
            // Máscara para telefone
            const telefoneInput = document.getElementById('telefone');
            if (telefoneInput) {
                telefoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                    e.target.value = value;
                });
            }
            
            // Validação de confirmação de senha
            const passwordForm = document.querySelector('input[name="action"][value="change_password"]').closest('form');
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    const novaSenha = document.getElementById('nova_senha').value;
                    const confirmarSenha = document.getElementById('confirmar_senha').value;
                    
                    if (novaSenha !== confirmarSenha) {
                        e.preventDefault();
                        alert('A confirmação de senha não confere.');
                        return false;
                    }
                    
                    if (novaSenha.length < 8) {
                        e.preventDefault();
                        alert('A nova senha deve ter pelo menos 8 caracteres.');
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>
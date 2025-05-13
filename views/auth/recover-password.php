<?php
// Incluir arquivos de configuração
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../config/email.php';

// Verificar se já existe uma sessão ativa
session_start();
if (isset($_SESSION['user_id'])) {
    // Redirecionar com base no tipo de usuário
    if ($_SESSION['user_type'] == USER_TYPE_ADMIN) {
        header('Location: ' . ADMIN_DASHBOARD_URL);
    } else {
        header('Location: ' . CLIENT_DASHBOARD_URL);
    }
    exit;
}

$error = '';
$success = '';
$token = '';
$validToken = false;
$userInfo = null;

// Verificar se é uma solicitação de redefinição (com token)
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        $db = Database::getConnection();
        
        // Verificar se o token é válido
        $stmt = $db->prepare("
            SELECT rs.*, u.nome, u.email 
            FROM recuperacao_senha rs
            JOIN usuarios u ON rs.usuario_id = u.id
            WHERE rs.token = :token 
            AND rs.usado = 0 
            AND rs.data_expiracao > NOW()
        ");
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $tokenInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tokenInfo) {
            $validToken = true;
            $userInfo = $tokenInfo;
            
            // Processar o formulário de redefinição de senha
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset') {
                $newPassword = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                // Validar senha
                if (empty($newPassword)) {
                    $error = 'Por favor, informe a nova senha.';
                } else if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
                    $error = 'A senha deve ter no mínimo ' . PASSWORD_MIN_LENGTH . ' caracteres.';
                } else if ($newPassword !== $confirmPassword) {
                    $error = 'As senhas não coincidem.';
                } else {
                    // Atualizar a senha do usuário
                    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updateStmt = $db->prepare("UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :id");
                    $updateStmt->bindParam(':senha_hash', $passwordHash);
                    $updateStmt->bindParam(':id', $tokenInfo['usuario_id']);
                    
                    if ($updateStmt->execute()) {
                        // Marcar o token como usado
                        $usedStmt = $db->prepare("UPDATE recuperacao_senha SET usado = 1 WHERE id = :id");
                        $usedStmt->bindParam(':id', $tokenInfo['id']);
                        $usedStmt->execute();
                        
                        $success = 'Sua senha foi atualizada com sucesso! Você já pode fazer login.';
                    } else {
                        $error = 'Erro ao atualizar a senha. Por favor, tente novamente.';
                    }
                }
            }
        } else {
            $error = 'Token inválido ou expirado. Por favor, solicite uma nova recuperação de senha.';
        }
    } catch (PDOException $e) {
        $error = 'Erro ao processar a solicitação. Tente novamente.';
        error_log('Erro na validação do token: ' . $e->getMessage());
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request') {
    // Processar o formulário de solicitação de recuperação
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, informe um email válido.';
    } else {
        try {
            $db = Database::getConnection();
            
            // Verificar se o email existe
            $stmt = $db->prepare("SELECT id, nome, status FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                // Não informar ao usuário que o email não existe (segurança)
                $success = 'Se o email estiver cadastrado, enviaremos instruções para recuperar sua senha.';
            } else if ($user['status'] !== USER_ACTIVE) {
                $error = 'Sua conta está ' . $user['status'] . '. Entre em contato com o suporte.';
            } else {
                // Gerar token único
                $token = bin2hex(random_bytes(32));
                $expiry = date('Y-m-d H:i:s', strtotime('+2 hours'));
                
                // Salvar token no banco de dados
                // Primeiro excluir tokens antigos deste usuário
                $deleteStmt = $db->prepare("DELETE FROM recuperacao_senha WHERE usuario_id = :user_id");
                $deleteStmt->bindParam(':user_id', $user['id']);
                $deleteStmt->execute();
                
                // Inserir novo token
                $insertStmt = $db->prepare("INSERT INTO recuperacao_senha (usuario_id, token, data_expiracao) VALUES (:user_id, :token, :expiry)");
                $insertStmt->bindParam(':user_id', $user['id']);
                $insertStmt->bindParam(':token', $token);
                $insertStmt->bindParam(':expiry', $expiry);
                
                if ($insertStmt->execute()) {
                    // Enviar email de recuperação
                    if (Email::sendPasswordRecovery($email, $user['nome'], $token)) {
                        $success = 'Enviamos instruções para recuperar sua senha para o email informado.';
                    } else {
                        $error = 'Não foi possível enviar o email. Por favor, tente novamente mais tarde.';
                    }
                } else {
                    $error = 'Erro ao gerar token de recuperação. Por favor, tente novamente.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Erro ao processar a solicitação. Tente novamente.';
            error_log('Erro na recuperação de senha: ' . $e->getMessage());
        } catch (Exception $e) {
            $error = 'Erro ao processar a solicitação. Tente novamente.';
            error_log('Erro na recuperação de senha: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $validToken ? 'Redefinir Senha' : 'Recuperar Senha'; ?> - Klube Cash</title>
    <link rel="stylesheet" href="../../assets/css/auth.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <style>
        /* Estilos específicos para a página de recuperação de senha */
        :root {
            --primary-color: #FF7A00;
            --white: #FFFFFF;
            --light-gray: #F5F5F5;
            --dark-gray: #333333;
            --medium-gray: #666666;
            --border-radius: 20px;
            --shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--primary-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .logo-container {
            text-align: center;
            margin: 20px 0;
            padding: 10px;
        }

        .logo-container img {
            height: 50px;
        }

        .recover-container {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            width: 90%;
            max-width: 450px;
            box-shadow: var(--shadow);
            margin: 0 auto;
        }

        .recover-header {
            margin-bottom: 30px;
        }

        .recover-header h1 {
            font-size: 42px;
            margin-bottom: 10px;
            color: var(--dark-gray);
        }
        
        .recover-header h2 {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--dark-gray);
        }

        .login-link {
            text-align: right;
            margin-bottom: 20px;
        }

        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: bold;
        }

        .input-group {
            margin-bottom: 25px;
        }

        .input-group label {
            display: block;
            font-size: 16px;
            margin-bottom: 10px;
            color: var(--dark-gray);
        }

        .input-group input {
            width: 100%;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
            outline: none;
            transition: border-color 0.3s;
        }

        .input-group input:focus {
            border-color: var(--primary-color);
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
        }

        .recover-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 30px;
        }

        .recover-btn:hover {
            background-color: #E86E00;
        }

        .error-message {
            background-color: #ffdddd;
            color: #ff0000;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .success-message {
            background-color: #ddffdd;
            color: #008800;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    /* Adicione este código no seu arquivo responsive.css ou diretamente no estilo da página de recuperação de senha */
@media (max-width: 991px) {
    /* Oculta toda a estrutura de painéis usada no desktop */
    .recover-page .left-panel {
        display: none;
    }
    
    /* Oculta o painel direito como estrutura separada */
    .recover-page .right-panel {
        width: 100%;
    }
    
    /* Oculta a ilustração à direita explicitamente */
    .illustration-right {
        display: none !important;
    }
    
    /* Garante que o logo mobile fique visível */
    .logo-container {
        display: block;
    }
    
    /* Ajusta o container de recuperação para preencher a tela disponível */
    .recover-container {
        width: 90%;
        margin: 0 auto;
        max-width: 450px;
    }
    
    /* Faz o body usar o layout mobile */
    body {
        background-color: var(--primary-color);
        flex-direction: column;
    }
}

/* Ajustes para telas muito pequenas */
@media (max-width: 576px) {
    /* Reduz o tamanho dos títulos para melhor visualização em telas pequenas */
    .recover-header h1 {
        font-size: 32px;
    }
    
    .recover-header h2 {
        font-size: 20px;
    }
}
        /* Estilos para desktop */
        @media (min-width: 992px) {
            body {
                background-color: var(--white);
                flex-direction: row;
            }

            .recover-page {
                display: flex;
                width: 100%;
                height: 100vh;
            }

            .left-panel {
                background-color: var(--primary-color);
                width: 50%;
                display: flex;
                flex-direction: column;
                padding: 20px;
                position: relative;
            }

            .right-panel {
                width: 50%;
                display: flex;
                justify-content: center;
                align-items: center;
                background-color: var(--white);
            }

            .logo-container-desktop {
                text-align: left;
                margin: 20px;
            }

            .illustration-right {
                position: absolute;
                width: 200px;
                right: -80px;
                bottom: 100px;
                z-index: 2;
            }

            .recover-container {
                margin: 0;
                width: 400px;
                max-width: 90%;
            }

            .logo-container {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Versão Mobile -->
    <div class="logo-container">
        <img src="../../assets/images/logobranco.png" alt="KlubeCash">
    </div>

    <!-- Versão Desktop -->
    <div class="recover-page">
        <div class="left-panel">
            <div class="logo-container-desktop">
                <img src="../../assets/images/logobranco.png" alt="KlubeCash">
            </div>
        </div>

        <div class="right-panel">
            <div class="recover-container">
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success); ?>
                        <p><a href="login.php">Voltar para o login</a></p>
                    </div>
                <?php endif; ?>

                <div class="recover-header">
                    <div class="login-link">
                        <span>Lembra da senha?</span>
                        <a href="login.php">Login</a>
                    </div>
                    
                    <?php if ($validToken): ?>
                        <h1>Nova senha</h1>
                        <?php if ($userInfo): ?>
                            <h2>Para <?php echo htmlspecialchars($userInfo['email']); ?></h2>
                        <?php endif; ?>
                    <?php else: ?>
                        <h1>Esqueceu sua senha?</h1>
                    <?php endif; ?>
                </div>

                <?php if ($validToken): ?>
                <!-- Formulário de redefinição de senha -->
                <form method="post" action="" id="reset-form">
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="input-group">
                        <label for="password">Digite sua nova senha</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" placeholder="Nova senha" required>
                            <span class="password-toggle" onclick="togglePassword('password')">
                                <img src="../../assets/images/icons/eye.svg" alt="Mostrar senha">
                            </span>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="confirm_password">Confirme sua nova senha</label>
                        <div class="password-field">
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirme a senha" required>
                            <span class="password-toggle" onclick="togglePassword('confirm_password')">
                                <img src="../../assets/images/icons/eye.svg" alt="Mostrar senha">
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="recover-btn">Alterar Senha</button>
                </form>
                
                <?php else: ?>
                <!-- Formulário de solicitação de recuperação -->
                <form method="post" action="" id="recover-form">
                    <input type="hidden" name="action" value="request">
                    
                    <div class="input-group">
                        <label for="email">Digite seu email</label>
                        <input type="email" id="email" name="email" placeholder="Email" required>
                    </div>

                    <button type="submit" class="recover-btn">Enviar</button>
                </form>
                <?php endif; ?>
            </div>
            
            <div class="illustration-right">
                <img src="../../assets/images/illustrations/forgot-password.svg" alt="">
            </div>
        </div>
    </div>

    <script>
        // Função para alternar a visibilidade da senha
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const passwordToggle = document.querySelector(`#${fieldId} + .password-toggle img`);
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordToggle.src = '../../assets/images/icons/eye-slash.svg';
            } else {
                passwordField.type = 'password';
                passwordToggle.src = '../../assets/images/icons/eye.svg';
            }
        }

        // Validação do formulário de recuperação
        document.getElementById('recover-form')?.addEventListener('submit', function(event) {
            const email = document.getElementById('email').value;
            
            let isValid = true;
            let errorMessage = '';
            
            if (!email) {
                errorMessage = 'Por favor, informe seu email.';
                isValid = false;
            } else if (!isValidEmail(email)) {
                errorMessage = 'Por favor, informe um email válido.';
                isValid = false;
            }
            
            if (!isValid) {
                event.preventDefault();
                alert(errorMessage);
            }
        });
        
        // Validação do formulário de redefinição de senha
        document.getElementById('reset-form')?.addEventListener('submit', function(event) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            let isValid = true;
            let errorMessage = '';
            
            if (!password) {
                errorMessage = 'Por favor, informe sua nova senha.';
                isValid = false;
            } else if (password.length < <?php echo PASSWORD_MIN_LENGTH; ?>) {
                errorMessage = 'A senha deve ter no mínimo <?php echo PASSWORD_MIN_LENGTH; ?> caracteres.';
                isValid = false;
            } else if (password !== confirmPassword) {
                errorMessage = 'As senhas não coincidem.';
                isValid = false;
            }
            
            if (!isValid) {
                event.preventDefault();
                alert(errorMessage);
            }
        });
        
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    </script>
</body>
</html>
<?php
// Arquivo: views/auth/register.php
// Incluir arquivos de configuração
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../config/email.php';
require_once '../../utils/Validator.php';

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

// Processar o formulário de registro
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar e sanitizar dados do formulário
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_STRING);
    $senha = $_POST['senha'] ?? '';
    
    // Validar campos
    $errors = [];
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido';
    }
    
    if (empty($nome) || strlen($nome) < 3) {
        $errors[] = 'Nome precisa ter pelo menos 3 caracteres';
    }
    
    if (empty($telefone) || strlen($telefone) < 10) {
        $errors[] = 'Telefone inválido';
    }
    
    if (empty($senha) || strlen($senha) < PASSWORD_MIN_LENGTH) {
        $errors[] = 'A senha deve ter no mínimo ' . PASSWORD_MIN_LENGTH . ' caracteres';
    }
    
    // Se não houver erros, prosseguir com o registro
    if (empty($errors)) {
        try {
            $db = Database::getConnection();
            
            // Verificar se o email já existe
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $error = 'Este email já está cadastrado. Por favor, use outro ou faça login.';
            } else {
                // Hash da senha
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                
                // Inserir novo usuário
                $stmt = $db->prepare("INSERT INTO usuarios (nome, email, senha_hash, tipo, telefone) VALUES (:nome, :email, :senha_hash, :tipo, :telefone)");
                $stmt->bindParam(':nome', $nome);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':senha_hash', $senha_hash);
                $tipo = USER_TYPE_CLIENT;
                $stmt->bindParam(':tipo', $tipo);
                $stmt->bindParam(':telefone', $telefone);
                
                if ($stmt->execute()) {
                    $user_id = $db->lastInsertId();
                    
                    // Enviar email de boas-vindas
                    Email::sendWelcome($email, $nome);
                    
                    // Redirecionar para página de sucesso ou login
                    $success = 'Cadastro realizado com sucesso! Você já pode fazer login.';
                    
                    // Opcionalmente, fazer login automático
                    // $_SESSION['user_id'] = $user_id;
                    // $_SESSION['user_name'] = $nome;
                    // $_SESSION['user_type'] = $tipo;
                    // header('Location: ' . CLIENT_DASHBOARD_URL);
                    // exit;
                } else {
                    $error = 'Erro ao cadastrar. Por favor, tente novamente.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Erro ao processar o cadastro. Tente novamente.';
            // Log do erro para debug
            error_log('Erro no registro: ' . $e->getMessage());
        }
    } else {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Klube Cash</title>
    <link rel="stylesheet" href="../../assets/css/auth.css">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <style>
        /* Estilos específicos para a página de registro */
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

        .register-container {
            background-color: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            width: 90%;
            max-width: 450px;
            box-shadow: var(--shadow);
            margin: 0 auto;
        }

        .register-header {
            margin-bottom: 30px;
        }

        .register-header h1 {
            font-size: 18px;
            color: var(--dark-gray);
            margin-bottom: 5px;
        }

        .register-header span {
            color: var(--primary-color);
            font-weight: bold;
        }

        .register-header h2 {
            font-size: 32px;
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
            margin-bottom: 20px;
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

        .input-row {
            display: flex;
            gap: 15px;
        }

        .input-row .input-group {
            flex: 1;
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

        .register-btn {
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

        .register-btn:hover {
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
        /* Adicione este código no seu arquivo responsive.css ou diretamente no estilo da página de registro */
@media (max-width: 991px) {
    /* Oculta toda a estrutura de painéis usada no desktop */
    .register-page .left-panel {
        display: none;
    }
    
    /* Oculta o painel direito como estrutura separada */
    .register-page .right-panel {
        width: 100%;
    }
    
    /* Oculta as ilustrações explicitamente */
    .illustrations, 
    .illustration-left, 
    .illustration-right {
        display: none !important;
    }
    
    /* Garante que o logo mobile fique visível */
    .logo-container {
        display: block;
    }
    
    /* Ajusta o container de registro para preencher a tela disponível */
    .register-container {
        width: 90%;
        margin: 0 auto;
        max-width: 450px;
    }
    
    /* Faz o body usar o layout mobile */
    body {
        background-color: var(--primary-color);
        flex-direction: column;
    }
    
    /* Garante que a ilustração à direita não seja exibida em mobile */
    .right-panel .illustration-right {
        display: none;
    }
}

/* Ajuste para dispositivos realmente pequenos */
@media (max-width: 576px) {
    /* Ajusta a disposição dos campos em linha para ficarem em coluna */
    .input-row {
        flex-direction: column;
        gap: 5px;
    }
}
        @media (min-width: 992px) {
            body {
                background-color: var(--white);
                flex-direction: row;
            }

            .register-page {
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

            .illustrations {
                display: flex;
                justify-content: space-between;
                margin-top: auto;
            }

            .illustration-left, .illustration-right {
                position: absolute;
                bottom: 30px;
            }

            .illustration-left {
                width: 180px;
                left: 50px;
            }

            .illustration-right {
                width: 200px;
                right: -120px;
                bottom: 50px;
                z-index: 2;
            }

            .register-container {
                margin: 0;
                width: 400px;
                max-width: 90%;
            }

            .logo-container {
                display: none;
            }

            .form-title {
                margin-top: 0;
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
    <div class="register-page">
        <div class="left-panel">
            <div class="logo-container-desktop">
                <img src="../../assets/images/logobranco.png" alt="KlubeCash">
            </div>
            <div class="illustrations">
                <img src="../../assets/images/illustrations/businessman-coin.svg" alt="" class="illustration-left">
            </div>
        </div>

        <div class="right-panel">
            <div class="register-container">
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success); ?>
                        <p><a href="login.php">Clique aqui para fazer login</a></p>
                    </div>
                <?php endif; ?>

                <div class="register-header">
                    <div class="login-link">
                        <span>Já tem conta?</span>
                        <a href="login.php">Login</a>
                    </div>
                    <h1>Seja <span>BEM VINDO</span></h1>
                    <h2>Registro</h2>
                </div>

                <form method="post" action="" id="register-form">
                    <div class="input-group">
                        <label for="email">Entre com seu email</label>
                        <input type="email" id="email" name="email" placeholder="Email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label for="nome">Nome</label>
                            <input type="text" id="nome" name="nome" placeholder="Nome" required value="<?php echo isset($nome) ? htmlspecialchars($nome) : ''; ?>">
                        </div>

                        <div class="input-group">
                            <label for="telefone">Telefone</label>
                            <input type="tel" id="telefone" name="telefone" placeholder="Telefone" required value="<?php echo isset($telefone) ? htmlspecialchars($telefone) : ''; ?>">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="senha">Digite sua senha</label>
                        <div class="password-field">
                            <input type="password" id="senha" name="senha" placeholder="Senha" required>
                            <span class="password-toggle" onclick="togglePassword()">
                                <img src="../../assets/images/icons/eye.svg" alt="Mostrar senha">
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="register-btn">Registre-se</button>
                </form>
            </div>
            
            <div class="illustration-right">
                <img src="../../assets/images/illustrations/woman-phone.svg" alt="">
            </div>
        </div>
    </div>

    <script>
        // Função para alternar a visibilidade da senha
        function togglePassword() {
            const passwordField = document.getElementById('senha');
            const passwordToggle = document.querySelector('.password-toggle img');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordToggle.src = '../../assets/images/icons/eye-slash.svg';
            } else {
                passwordField.type = 'password';
                passwordToggle.src = '../../assets/images/icons/eye.svg';
            }
        }

        // Máscara para o campo de telefone
        document.getElementById('telefone').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove não-dígitos
            
            // Aplicar máscara (XX) XXXXX-XXXX
            if (value.length <= 11) {
                if (value.length > 2) {
                    value = '(' + value.substring(0, 2) + ') ' + value.substring(2);
                }
                if (value.length > 10) {
                    value = value.substring(0, 10) + '-' + value.substring(10);
                }
            }
            
            e.target.value = value;
        });

        // Validação do formulário no lado do cliente
        document.getElementById('register-form').addEventListener('submit', function(event) {
            const email = document.getElementById('email').value;
            const nome = document.getElementById('nome').value;
            const telefone = document.getElementById('telefone').value;
            const senha = document.getElementById('senha').value;
            
            let isValid = true;
            let errorMessage = '';
            
            if (!email) {
                errorMessage = 'Por favor, informe seu email.';
                isValid = false;
            } else if (!isValidEmail(email)) {
                errorMessage = 'Por favor, informe um email válido.';
                isValid = false;
            }
            
            if (!nome || nome.length < 3) {
                errorMessage = 'Por favor, informe seu nome completo (mínimo 3 caracteres).';
                isValid = false;
            }
            
            if (!telefone || telefone.replace(/\D/g, '').length < 10) {
                errorMessage = 'Por favor, informe um telefone válido.';
                isValid = false;
            }
            
            if (!senha || senha.length < <?php echo PASSWORD_MIN_LENGTH; ?>) {
                errorMessage = 'A senha deve ter no mínimo <?php echo PASSWORD_MIN_LENGTH; ?> caracteres.';
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
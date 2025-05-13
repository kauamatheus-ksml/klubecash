<?php
// Incluir arquivos de configuração
require_once '../../config/constants.php';
require_once '../../config/database.php';

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

// Processar o formulário de login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            $db = Database::getConnection();
            
            // Buscar usuário pelo email
            $stmt = $db->prepare("SELECT id, nome, senha_hash, tipo, status FROM usuarios WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['senha_hash'])) {
                // Verificar status do usuário
                if ($user['status'] !== USER_ACTIVE) {
                    $error = 'Sua conta está ' . $user['status'] . '. Entre em contato com o suporte.';
                } else {
                    // Login bem-sucedido
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nome'];
                    $_SESSION['user_type'] = $user['tipo'];
                    
                    // Atualizar último login
                    $updateStmt = $db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id");
                    $updateStmt->bindParam(':id', $user['id']);
                    $updateStmt->execute();
                    
                    // Redirecionar com base no tipo de usuário
                    if ($user['tipo'] == USER_TYPE_ADMIN) {
                        header('Location: ' . ADMIN_DASHBOARD_URL);
                    } else {
                        header('Location: ' . CLIENT_DASHBOARD_URL);
                    }
                    exit;
                }
            } else {
                $error = 'Email ou senha incorretos.';
            }
        } catch (PDOException $e) {
            $error = 'Erro ao processar o login. Tente novamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Klube Cash</title>
    <link rel="stylesheet" href="../../assets/css/auth.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link rel="stylesheet" href="../../assets/css/login.css">
</head>
<body>
    <!-- Versão Mobile -->
    <div class="logo-container">
        <img src="../../assets/images/logobranco.png" alt="KlubeCash">
    </div>

    <!-- Versão Desktop -->
    <div class="login-page">
        <div class="left-panel">
            <div class="logo-container-desktop">
                <img src="../../assets/images/logobranco.png" alt="KlubeCash">
            </div>
            <div class="illustrations">
                <img src="../../assets/images/illustrations/businessman-coin.svg" alt="" class="illustration-left">
                <img src="../../assets/images/illustrations/man-money.svg" alt="" class="illustration-right">
            </div>
        </div>

        <div class="right-panel">
            <div class="login-container">
                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="login-header">
                    <h1>Seja <span>BEM VINDO</span></h1>
                    <h2>Login</h2>
                </div>

                <form method="post" action="" id="login-form">
                    <div class="input-group">
                        <label for="email">Entre com seu email</label>
                        <input type="email" id="email" name="email" placeholder="Email" required>
                    </div>

                    <div class="input-group">
                        <label for="password">Entre com sua senha</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" placeholder="Senha" required>
                            <span class="password-toggle" onclick="togglePassword()">
                                <img src="../../assets/images/icons/eye.svg" alt="Mostrar senha">
                            </span>
                        </div>
                    </div>

                    <div class="forgot-password">
                    <a href="<?php echo RECOVER_PASSWORD_URL; ?>">Esqueci minha senha</a>
                    </div>

                    <button type="submit" class="login-btn">Entrar</button>
                </form>

                <div class="or-divider">Ou</div>

                <div class="social-login">
                    <button class="social-btn google-btn" onclick="loginWithGoogle()">
                        <img src="../../assets/images/icons/google.svg" alt="Google">
                        <span class="hide-on-mobile">Entre com Google</span>
                    </button>
                    <button class="social-btn facebook-btn" onclick="loginWithFacebook()">
                        <img src="../../assets/images/icons/facebook.svg" alt="Facebook">
                    </button>
                    <button class="social-btn apple-btn" onclick="loginWithApple()">
                        <img src="../../assets/images/icons/apple.svg" alt="Apple">
                    </button>
                </div>

                <div class="register-link">
                    <p>Não tem conta? <a href="<?php echo REGISTER_URL; ?>">Registre-se</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Função para alternar a visibilidade da senha
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const passwordToggle = document.querySelector('.password-toggle img');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordToggle.src = '../../assets/images/icons/eye-slash.svg';
            } else {
                passwordField.type = 'password';
                passwordToggle.src = '../../assets/images/icons/eye.svg';
            }
        }

        // Funções para login social (seriam implementadas com as respectivas APIs)
        function loginWithGoogle() {
            // Implementação da API do Google
            alert('Login com Google será implementado com a API do Google.');
        }

        function loginWithFacebook() {
            // Implementação da API do Facebook
            alert('Login com Facebook será implementado com a API do Facebook.');
        }

        function loginWithApple() {
            // Implementação da API da Apple
            alert('Login com Apple será implementado com a API da Apple.');
        }

        // Validação do formulário no lado do cliente
        document.getElementById('login-form').addEventListener('submit', function(event) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            let isValid = true;
            let errorMessage = '';
            
            if (!email) {
                errorMessage = 'Por favor, informe seu email.';
                isValid = false;
            } else if (!isValidEmail(email)) {
                errorMessage = 'Por favor, informe um email válido.';
                isValid = false;
            }
            
            if (!password) {
                errorMessage = 'Por favor, informe sua senha.';
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

        // Verifica o tamanho da tela e aplica ajustes específicos
        function checkScreenSize() {
            const socialBtns = document.querySelectorAll('.social-btn');
            const googleBtn = document.querySelector('.google-btn span');
            
            if (window.innerWidth < 768) {
                socialBtns.forEach(btn => {
                    if (!btn.classList.contains('google-btn')) {
                        btn.querySelector('span')?.classList.add('hide');
                    }
                });
                
                if (googleBtn) {
                    googleBtn.textContent = 'Login com Google';
                }
            } else {
                socialBtns.forEach(btn => {
                    btn.querySelector('span')?.classList.remove('hide');
                });
                
                if (googleBtn) {
                    googleBtn.textContent = 'Entre com Google';
                }
            }
        }

        // Executa verificação no carregamento e redimensionamento
        window.addEventListener('load', checkScreenSize);
        window.addEventListener('resize', checkScreenSize);
    </script>
</body>
</html>
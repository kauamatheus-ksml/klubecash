<?php
// Incluir arquivos de configuração
require_once '../../config/constants.php';
require_once '../../config/database.php';

// Verificar se já existe uma sessão ativa
session_start();
if (isset($_SESSION['user_id']) && !isset($_GET['force_login'])) {
    // Redirecionar com base no tipo de usuário
    if ($_SESSION['user_type'] == USER_TYPE_ADMIN) {
        header('Location: ' . ADMIN_DASHBOARD_URL);
        exit;
    } else if ($_SESSION['user_type'] == USER_TYPE_STORE) {
        header('Location: ' . STORE_DASHBOARD_URL);
        exit;
    } else {
        header('Location: ' . CLIENT_DASHBOARD_URL);
        exit;
    }
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

// Verificar mensagens de URL
$urlError = $_GET['error'] ?? '';
$urlSuccess = $_GET['success'] ?? '';
if (!empty($urlError)) {
    $error = $urlError;
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
    <link rel="stylesheet" href="../../assets/css/views/auth/login.css">
</head>
<body>
    <!-- Container para Toast Messages -->
    <div class="toast-container" id="toast-container"></div>
    
    <!-- Spinner Overlay -->
    <div class="spinner-overlay" id="spinner-overlay">
        <span class="loader"></span>
    </div>

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

                    <button type="submit" class="login-btn" id="login-btn">Entrar</button>
                </form>
                <!--
                <div class="or-divider">Ou</div>

                <div class="social-login">
                    <button class="social-btn google-btn" onclick="loginWithGoogle()" id="google-btn">
                        <img src="../../assets/images/icons/google.svg" alt="Google">
                        <span class="hide-on-mobile">Entre com Google</span>
                    </button>
                    <button class="social-btn facebook-btn" onclick="loginWithFacebook()">
                        <img src="../../assets/images/icons/facebook.svg" alt="Facebook">
                    </button>
                    <button class="social-btn apple-btn" onclick="loginWithApple()">
                        <img src="../../assets/images/icons/apple.svg" alt="Apple">
                    </button>
                </div>-->

                <div class="register-link">
                    <p>Não tem conta? <a href="<?php echo REGISTER_URL; ?>">Registre-se</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sistema de Toast Messages
        class ToastManager {
            constructor() {
                this.container = document.getElementById('toast-container');
                this.toasts = new Map();
            }

            show(message, type = 'info', title = '', duration = 5000) {
                const toast = this.createToast(message, type, title, duration);
                this.container.appendChild(toast);
                
                // Forçar reflow para animação
                toast.offsetHeight;
                toast.classList.add('show');
                
                // Auto remove
                setTimeout(() => {
                    this.hide(toast);
                }, duration);
                
                return toast;
            }

            createToast(message, type, title, duration) {
                const toast = document.createElement('div');
                toast.className = `toast ${type}`;
                
                const icons = {
                    success: '✓',
                    error: '✕',
                    warning: '⚠',
                    info: 'ℹ'
                };

                const titles = {
                    success: title || 'Sucesso!',
                    error: title || 'Erro!',
                    warning: title || 'Atenção!',
                    info: title || 'Informação'
                };

                toast.innerHTML = `
                    <div class="toast-icon">${icons[type] || icons.info}</div>
                    <div class="toast-content">
                        <div class="toast-title">${titles[type]}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                    <button class="toast-close" onclick="toastManager.hide(this.parentElement)">×</button>
                    <div class="toast-progress"></div>
                `;

                return toast;
            }

            hide(toast) {
                if (toast && toast.parentElement) {
                    toast.classList.remove('show');
                    toast.classList.add('hide');
                    setTimeout(() => {
                        if (toast.parentElement) {
                            toast.parentElement.removeChild(toast);
                        }
                    }, 400);
                }
            }

            success(message, title) {
                return this.show(message, 'success', title);
            }

            error(message, title) {
                return this.show(message, 'error', title);
            }

            warning(message, title) {
                return this.show(message, 'warning', title);
            }

            info(message, title) {
                return this.show(message, 'info', title);
            }
        }

        // Instanciar o gerenciador de toast
        const toastManager = new ToastManager();

        // Sistema de Spinner
        class SpinnerManager {
            constructor() {
                this.overlay = document.getElementById('spinner-overlay');
            }

            show() {
                this.overlay.classList.add('show');
            }

            hide() {
                this.overlay.classList.remove('show');
            }
        }

        const spinnerManager = new SpinnerManager();

        // Verificar mensagens na URL e do PHP
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const errorParam = urlParams.get('error');
            const successParam = urlParams.get('success');

            // Mensagens do PHP
            <?php if (!empty($error)): ?>
                toastManager.error('<?php echo addslashes($error); ?>');
            <?php endif; ?>

            <?php if (!empty($urlSuccess)): ?>
                toastManager.success('<?php echo addslashes($urlSuccess); ?>');
            <?php endif; ?>

            // Mensagens da URL
            if (errorParam) {
                toastManager.error(decodeURIComponent(errorParam));
            }
            
            if (successParam) {
                toastManager.success(decodeURIComponent(successParam));
            }

            // Limpar URL após mostrar as mensagens
            if (errorParam || successParam) {
                const url = new URL(window.location);
                url.searchParams.delete('error');
                url.searchParams.delete('success');
                window.history.replaceState({}, document.title, url);
            }
        });

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

        // Função para login com Google
        function loginWithGoogle() {
            const googleBtn = document.getElementById('google-btn');
            const originalHTML = googleBtn.innerHTML;
            
            // Mostrar loading no botão
            googleBtn.innerHTML = '<span class="loader" style="width: 20px; height: 20px; margin-right: 10px;"></span>Conectando...';
            googleBtn.disabled = true;
            
            // Mostrar spinner geral
            spinnerManager.show();
            
            fetch('<?php echo SITE_URL; ?>/auth/google/auth', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na requisição: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.status && data.auth_url) {
                    toastManager.info('Redirecionando para o Google...', 'Aguarde');
                    setTimeout(() => {
                        window.location.href = data.auth_url;
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Erro desconhecido');
                }
            })
            .catch(error => {
                console.error('Erro no login Google:', error);
                toastManager.error('Erro ao conectar com o Google: ' + error.message);
                
                // Restaurar botão
                googleBtn.innerHTML = originalHTML;
                googleBtn.disabled = false;
                spinnerManager.hide();
            });
        }

        // Funções placeholder para outros provedores
        function loginWithFacebook() {
            toastManager.info('Login com Facebook será implementado em breve.', 'Em desenvolvimento');
        }

        function loginWithApple() {
            toastManager.info('Login com Apple será implementado em breve.', 'Em desenvolvimento');
        }

        // Validação e envio do formulário
        document.getElementById('login-form').addEventListener('submit', function(event) {
            event.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('login-btn');
            
            // Validação básica
            if (!email) {
                toastManager.error('Por favor, informe seu email.');
                return;
            }
            
            if (!isValidEmail(email)) {
                toastManager.error('Por favor, informe um email válido.');
                return;
            }
            
            if (!password) {
                toastManager.error('Por favor, informe sua senha.');
                return;
            }
            
            // Mostrar loading
            const originalText = loginBtn.innerHTML;
            loginBtn.innerHTML = '<span class="loader" style="width: 20px; height: 20px; margin-right: 10px;"></span>Entrando...';
            loginBtn.disabled = true;
            spinnerManager.show();
            
            // Simular delay para mostrar o loading (remover em produção)
            setTimeout(() => {
                this.submit();
            }, 1000);
        });
        
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Verificar tamanho da tela para ajustes responsivos
        function checkScreenSize() {
            const socialBtns = document.querySelectorAll('.social-btn');
            const googleBtn = document.querySelector('.google-btn span');
            
            if (window.innerWidth < 768) {
                socialBtns.forEach(btn => {
                    if (!btn.classList.contains('google-btn')) {
                        btn.querySelector('span')?.classList.add('hide');
                    }
                });
                
                if (googleBtn && !googleBtn.textContent.includes('Conectando')) {
                    googleBtn.textContent = 'Login com Google';
                }
            } else {
                socialBtns.forEach(btn => {
                    btn.querySelector('span')?.classList.remove('hide');
                });
                
                if (googleBtn && !googleBtn.textContent.includes('Conectando')) {
                    googleBtn.textContent = 'Entre com Google';
                }
            }
        }

        window.addEventListener('load', checkScreenSize);
        window.addEventListener('resize', checkScreenSize);
    </script>
</body>
</html>
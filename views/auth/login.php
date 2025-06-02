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
    <title>Entrar na Sua Conta - Klube Cash</title>
    <link rel="stylesheet" href="../../assets/css/auth.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    
    <!-- Fonte Google mais amigável -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Reset e base mais amigável */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #FF7A00 0%, #FF9A3D 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Elementos decorativos de fundo */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        /* Container principal mais amigável */
        .login-main-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 480px;
            padding: 40px;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* Header mais acolhedor */
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-container {
            margin-bottom: 24px;
        }

        .logo-container img {
            height: 60px;
            width: auto;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
        }

        .welcome-text {
            color: #2D3748;
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .welcome-subtitle {
            color: #718096;
            font-size: 16px;
            font-weight: 400;
            margin-bottom: 8px;
        }

        .login-title {
            color: #FF7A00;
            font-size: 18px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Formulário mais intuitivo */
        .login-form {
            margin-bottom: 30px;
        }

        .input-wrapper {
            margin-bottom: 24px;
            position: relative;
        }

        .input-label {
            display: block;
            color: #4A5568;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            transition: color 0.2s ease;
        }

        .input-field {
            width: 100%;
            padding: 16px 20px;
            background: #F7FAFC;
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            font-size: 16px;
            color: #2D3748;
            transition: all 0.3s ease;
            position: relative;
        }

        .input-field:focus {
            outline: none;
            border-color: #FF7A00;
            background: #FFFFFF;
            box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.1);
        }

        .input-field:focus + .input-label {
            color: #FF7A00;
        }

        /* Campo de senha melhorado */
        .password-container {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
            color: #A0AEC0;
            transition: color 0.2s ease;
            border-radius: 6px;
        }

        .password-toggle:hover {
            color: #FF7A00;
            background: rgba(255, 122, 0, 0.1);
        }

        .password-toggle img {
            width: 20px;
            height: 20px;
        }

        /* Link esqueceu senha */
        .forgot-password {
            text-align: right;
            margin-bottom: 32px;
        }

        .forgot-password a {
            color: #FF7A00;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .forgot-password a:hover {
            color: #E86E00;
            text-decoration: underline;
        }

        /* Botão de login mais atrativo */
        .login-button {
            width: 100%;
            padding: 18px 24px;
            background: linear-gradient(135deg, #FF7A00, #FF9A3D);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(255, 122, 0, 0.3);
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 122, 0, 0.4);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .login-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Link de registro */
        .register-section {
            text-align: center;
            padding-top: 24px;
            border-top: 1px solid #E2E8F0;
        }

        .register-text {
            color: #718096;
            font-size: 14px;
            margin-bottom: 16px;
        }

        .register-link {
            display: inline-block;
            padding: 12px 24px;
            background: transparent;
            color: #FF7A00;
            border: 2px solid #FF7A00;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .register-link:hover {
            background: #FF7A00;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 122, 0, 0.3);
        }

        /* Toast Messages - Sistema mais amigável */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            width: 100%;
        }

        .toast {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            margin-bottom: 12px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .toast.show {
            transform: translateX(0);
            opacity: 1;
        }

        .toast.hide {
            transform: translateX(100%);
            opacity: 0;
        }

        .toast.success {
            background: linear-gradient(135deg, rgba(72, 187, 120, 0.95), rgba(72, 187, 120, 0.9));
            color: white;
            border-left: 4px solid #48BB78;
        }

        .toast.error {
            background: linear-gradient(135deg, rgba(245, 101, 101, 0.95), rgba(245, 101, 101, 0.9));
            color: white;
            border-left: 4px solid #F56565;
        }

        .toast.warning {
            background: linear-gradient(135deg, rgba(237, 137, 54, 0.95), rgba(237, 137, 54, 0.9));
            color: white;
            border-left: 4px solid #ED8936;
        }

        .toast.info {
            background: linear-gradient(135deg, rgba(66, 153, 225, 0.95), rgba(66, 153, 225, 0.9));
            color: white;
            border-left: 4px solid #4299E1;
        }

        .toast-icon {
            font-size: 20px;
            margin-right: 12px;
            flex-shrink: 0;
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .toast-message {
            font-size: 14px;
            opacity: 0.9;
            line-height: 1.4;
        }

        .toast-close {
            background: none;
            border: none;
            color: inherit;
            font-size: 18px;
            cursor: pointer;
            opacity: 0.7;
            margin-left: 12px;
            padding: 4px;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .toast-close:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.2);
        }

        /* Spinner overlay mais elegante */
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            backdrop-filter: blur(8px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .spinner-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .loader {
            width: 50px;
            height: 50px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #FF7A00;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsividade melhorada */
        @media (max-width: 768px) {
            body {
                padding: 16px;
            }

            .login-main-container {
                padding: 32px 24px;
                border-radius: 20px;
            }

            .welcome-text {
                font-size: 28px;
            }

            .welcome-subtitle {
                font-size: 15px;
            }

            .input-field {
                padding: 14px 16px;
                font-size: 16px; /* Importante para iOS não dar zoom */
            }

            .login-button {
                padding: 16px 20px;
                font-size: 15px;
            }

            .toast-container {
                top: 10px;
                right: 10px;
                left: 10px;
                max-width: none;
            }

            .toast {
                transform: translateY(-100%);
            }

            .toast.show {
                transform: translateY(0);
            }

            .toast.hide {
                transform: translateY(-100%);
            }
        }

        @media (max-width: 480px) {
            .login-main-container {
                padding: 24px 20px;
            }

            .welcome-text {
                font-size: 24px;
            }

            .logo-container img {
                height: 50px;
            }
        }

        /* Estados de carregamento para UX melhor */
        .loading .login-button {
            position: relative;
        }

        .loading .login-button::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            margin: auto;
            border: 2px solid transparent;
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
        }

        /* Acessibilidade */
        .input-field:focus-visible {
            outline: 2px solid #FF7A00;
            outline-offset: 2px;
        }

        .login-button:focus-visible {
            outline: 2px solid #FF7A00;
            outline-offset: 4px;
        }

        /* Animações suaves */
        .login-main-container {
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <!-- Container para Toast Messages -->
    <div class="toast-container" id="toast-container"></div>
    
    <!-- Spinner Overlay -->
    <div class="spinner-overlay" id="spinner-overlay">
        <div class="loader"></div>
    </div>

    <!-- Container Principal de Login -->
    <div class="login-main-container">
        <!-- Cabeçalho Amigável -->
        <div class="login-header">
            <div class="logo-container">
                <img src="../../assets/images/logolaranja.png" alt="Klube Cash - Seu dinheiro de volta">
            </div>
            <h1 class="welcome-text">Bem-vindo de volta!</h1>
            <p class="welcome-subtitle">Acesse sua conta para continuar economizando</p>
            <div class="login-title">Fazer Login</div>
        </div>

        <!-- Formulário de Login -->
        <form method="post" action="" id="login-form" class="login-form">
            <div class="input-wrapper">
                <label for="email" class="input-label">Seu email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="input-field"
                    placeholder="Digite seu email aqui"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="input-wrapper">
                <label for="password" class="input-label">Sua senha</label>
                <div class="password-container">
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="input-field"
                        placeholder="Digite sua senha"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Mostrar/esconder senha">
                        <img src="../../assets/images/icons/eye.svg" alt="Ver senha" id="password-icon">
                    </button>
                </div>
            </div>

            <div class="forgot-password">
                <a href="<?php echo RECOVER_PASSWORD_URL; ?>">Esqueci minha senha</a>
            </div>

            <button type="submit" class="login-button" id="login-btn">
                <span id="login-btn-text">Entrar na Minha Conta</span>
            </button>
        </form>

        <!-- Seção de Registro -->
        <div class="register-section">
            <p class="register-text">Ainda não tem uma conta?</p>
            <a href="<?php echo REGISTER_URL; ?>" class="register-link">Criar Conta Grátis</a>
        </div>
    </div>

    <script>
        // Sistema de Toast Messages mais intuitivo
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
                    success: '✅',
                    error: '❌',
                    warning: '⚠️',
                    info: 'ℹ️'
                };

                const titles = {
                    success: title || 'Tudo certo!',
                    error: title || 'Ops, algo deu errado!',
                    warning: title || 'Atenção!',
                    info: title || 'Informação'
                };

                toast.innerHTML = `
                    <div class="toast-icon">${icons[type] || icons.info}</div>
                    <div class="toast-content">
                        <div class="toast-title">${titles[type]}</div>
                        <div class="toast-message">${message}</div>
                    </div>
                    <button class="toast-close" onclick="toastManager.hide(this.parentElement)" aria-label="Fechar notificação">×</button>
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

        // Função para alternar a visibilidade da senha - mais intuitiva
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordIcon.src = '../../assets/images/icons/eye-slash.svg';
                passwordIcon.alt = 'Esconder senha';
            } else {
                passwordField.type = 'password';
                passwordIcon.src = '../../assets/images/icons/eye.svg';
                passwordIcon.alt = 'Ver senha';
            }
        }

        // Validação e envio do formulário - mais amigável
        document.getElementById('login-form').addEventListener('submit', function(event) {
            event.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('login-btn');
            const loginBtnText = document.getElementById('login-btn-text');
            
            // Validação básica mais amigável
            if (!email) {
                toastManager.warning('Por favor, digite seu email para continuar.');
                document.getElementById('email').focus();
                return;
            }
            
            if (!isValidEmail(email)) {
                toastManager.warning('O email digitado não parece estar correto. Verifique e tente novamente.');
                document.getElementById('email').focus();
                return;
            }
            
            if (!password) {
                toastManager.warning('Não esqueça de digitar sua senha!');
                document.getElementById('password').focus();
                return;
            }
            
            // Mostrar loading de forma mais elegante
            loginBtn.disabled = true;
            loginBtn.classList.add('loading');
            loginBtnText.textContent = 'Entrando...';
            spinnerManager.show();
            
            // Simular delay para UX mais suave
            setTimeout(() => {
                this.submit();
            }, 800);
        });
        
        // Validação de email mais robusta
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Melhorar acessibilidade - permitir Enter nos campos
        document.getElementById('email').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('password').focus();
            }
        });

        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('login-form').dispatchEvent(new Event('submit'));
            }
        });

        // Feedback visual nos campos
        document.querySelectorAll('.input-field').forEach(field => {
            field.addEventListener('input', function() {
                if (this.value.length > 0) {
                    this.style.borderColor = '#48BB78';
                } else {
                    this.style.borderColor = '#E2E8F0';
                }
            });
        });
    </script>
</body>
</html>
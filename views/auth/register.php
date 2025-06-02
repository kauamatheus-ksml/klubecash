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
    <title>Cadastre-se Grátis - Klube Cash</title>
    <link rel="shortcut icon" type="image/jpg" href="../../assets/images/icons/KlubeCashLOGO.ico"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* === VARIÁVEIS CSS === */
        :root {
            --primary-color: #FF7A00;
            --primary-dark: #E86E00;
            --primary-light: #FFF1E6;
            --secondary-color: #1A1A1A;
            --white: #FFFFFF;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-400: #9CA3AF;
            --gray-500: #6B7280;
            --gray-600: #4B5563;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
            --success-color: #10B981;
            --error-color: #EF4444;
            --warning-color: #F59E0B;
            --info-color: #3B82F6;
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            --gradient-primary: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            --gradient-success: linear-gradient(135deg, #10B981 0%, #059669 100%);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* === RESET E BASE === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: var(--gray-800);
            background: var(--gradient-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        /* === CONTAINER PRINCIPAL === */
        .register-wrapper {
            width: 100%;
            max-width: 1300px;
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr;
            min-height: 650px;
        }

        /* === SEÇÃO DE BENEFÍCIOS === */
        .benefits-section {
            background: var(--gradient-primary);
            padding: 3rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }

        .benefits-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.05)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
            animation: float 30s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateX(0px) translateY(0px) rotate(0deg); }
            33% { transform: translateX(30px) translateY(-30px) rotate(120deg); }
            66% { transform: translateX(-20px) translateY(20px) rotate(240deg); }
        }

        .logo-container {
            margin-bottom: 2rem;
            z-index: 1;
            position: relative;
        }

        .logo-container img {
            height: 60px;
            width: auto;
            filter: brightness(0) invert(1);
        }

        .benefits-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            z-index: 1;
            position: relative;
        }

        .benefits-subtitle {
            font-size: 1.125rem;
            opacity: 0.9;
            margin-bottom: 2.5rem;
            max-width: 400px;
            z-index: 1;
            position: relative;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            max-width: 350px;
            z-index: 1;
            position: relative;
        }

        .benefit-card {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            backdrop-filter: blur(10px);
            transition: var(--transition);
        }

        .benefit-card:hover {
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.15);
        }

        .benefit-icon {
            font-size: 2rem;
            margin-bottom: 0.75rem;
        }

        .benefit-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .benefit-description {
            font-size: 0.875rem;
            opacity: 0.9;
            line-height: 1.5;
        }

        /* === SEÇÃO DO FORMULÁRIO === */
        .form-section {
            padding: 3rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
        }

        .form-subtitle {
            color: var(--gray-500);
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        .form-subtitle a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .form-subtitle a:hover {
            color: var(--primary-dark);
        }

        .security-badge {
            display: inline-flex;
            align-items: center;
            background: var(--primary-light);
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .security-badge svg {
            width: 16px;
            height: 16px;
            margin-right: 0.5rem;
        }

        /* === FORMULÁRIO === */
        .register-form {
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
        }

        .input-group {
            margin-bottom: 1.25rem;
        }

        .input-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .input-label {
            display: block;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .input-label.required::after {
            content: ' *';
            color: var(--error-color);
        }

        .input-wrapper {
            position: relative;
        }

        .input-field {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background: var(--white);
        }

        .input-field:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 122, 0, 0.1);
        }

        .input-field:invalid:not(:placeholder-shown) {
            border-color: var(--error-color);
        }

        .input-field.valid {
            border-color: var(--success-color);
        }

        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray-400);
            padding: 0.25rem;
            border-radius: 4px;
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: var(--gray-600);
            background: var(--gray-100);
        }

        .password-toggle svg {
            width: 20px;
            height: 20px;
        }

        .input-help {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-top: 0.25rem;
        }

        .password-strength {
            margin-top: 0.5rem;
        }

        .strength-bar {
            height: 4px;
            background: var(--gray-200);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 0.25rem;
        }

        .strength-fill {
            height: 100%;
            transition: var(--transition);
            border-radius: 2px;
        }

        .strength-fill.weak { background: var(--error-color); width: 25%; }
        .strength-fill.fair { background: var(--warning-color); width: 50%; }
        .strength-fill.good { background: var(--info-color); width: 75%; }
        .strength-fill.strong { background: var(--success-color); width: 100%; }

        .strength-text {
            font-size: 0.75rem;
            font-weight: 500;
        }

        .strength-text.weak { color: var(--error-color); }
        .strength-text.fair { color: var(--warning-color); }
        .strength-text.good { color: var(--info-color); }
        .strength-text.strong { color: var(--success-color); }

        /* === BOTÕES === */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.875rem 1.5rem;
            border-radius: var(--border-radius);
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            min-height: 48px;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
            width: 100%;
            margin-bottom: 1.5rem;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: var(--shadow-lg);
        }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* === LOADING === */
        .loading-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* === TOAST MESSAGES === */
        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 9999;
            max-width: 400px;
            width: 100%;
        }

        .toast {
            display: flex;
            align-items: flex-start;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
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
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.95), rgba(5, 150, 105, 0.95));
            color: var(--white);
            border-left: 4px solid var(--success-color);
        }

        .toast.error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.95), rgba(220, 38, 38, 0.95));
            color: var(--white);
            border-left: 4px solid var(--error-color);
        }

        .toast.warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.95), rgba(217, 119, 6, 0.95));
            color: var(--white);
            border-left: 4px solid var(--warning-color);
        }

        .toast.info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.95), rgba(37, 99, 235, 0.95));
            color: var(--white);
            border-left: 4px solid var(--info-color);
        }

        .toast-icon {
            font-size: 1.25rem;
            margin-right: 0.75rem;
            flex-shrink: 0;
        }

        .toast-content {
            flex: 1;
        }

        .toast-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .toast-message {
            font-size: 0.875rem;
            opacity: 0.9;
            line-height: 1.4;
        }

        .toast-close {
            background: none;
            border: none;
            color: inherit;
            font-size: 1.25rem;
            cursor: pointer;
            opacity: 0.7;
            margin-left: 0.75rem;
            padding: 0.25rem;
            border-radius: 4px;
            transition: var(--transition);
        }

        .toast-close:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.2);
        }

        /* === SPINNER OVERLAY === */
        .spinner-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
            backdrop-filter: blur(5px);
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .spinner-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .spinner {
            width: 48px;
            height: 48px;
            border: 3px solid var(--gray-200);
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* === RESPONSIVIDADE === */
        @media (min-width: 768px) {
            .register-wrapper {
                grid-template-columns: 1fr 1fr;
                max-height: 750px;
            }

            .benefits-section {
                padding: 4rem 3rem;
            }

            .form-section {
                padding: 4rem 3rem;
            }

            .benefits-title {
                font-size: 3rem;
            }

            .benefits-subtitle {
                font-size: 1.25rem;
            }

            .benefits-grid {
                grid-template-columns: 1fr;
                max-width: 400px;
            }
        }

        @media (max-width: 767px) {
            body {
                padding: 0.5rem;
            }

            .register-wrapper {
                border-radius: var(--border-radius);
            }

            .benefits-section {
                padding: 2rem 1.5rem;
            }

            .form-section {
                padding: 2rem 1.5rem;
            }

            .benefits-title {
                font-size: 2rem;
            }

            .benefits-subtitle {
                font-size: 1rem;
            }

            .form-title {
                font-size: 1.75rem;
            }

            .input-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .benefits-grid {
                gap: 1rem;
            }

            .benefit-card {
                padding: 1rem;
            }

            .toast-container {
                top: 0.5rem;
                right: 0.5rem;
                left: 0.5rem;
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
            .input-field {
                font-size: 16px; /* Previne zoom no iOS */
            }
        }
    </style>
</head>
<body>
    <!-- Container para Toast Messages -->
    <div class="toast-container" id="toast-container"></div>
    
    <!-- Spinner Overlay -->
    <div class="spinner-overlay" id="spinner-overlay">
        <div class="spinner"></div>
    </div>

    <!-- Container Principal -->
    <div class="register-wrapper">
        <!-- Seção de Benefícios -->
        <div class="benefits-section">
            <div class="logo-container">
                <img src="../../assets/images/logobranco.png" alt="Klube Cash">
            </div>
            <h1 class="benefits-title">Junte-se ao Klube Cash!</h1>
            <p class="benefits-subtitle">
                Transforme suas compras em dinheiro de volta. É gratuito e você pode começar hoje mesmo.
            </p>
            
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">💰</div>
                    <h3 class="benefit-title">Cashback Real</h3>
                    <p class="benefit-description">Receba dinheiro de verdade, não pontos que expiram</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">🏪</div>
                    <h3 class="benefit-title">500+ Lojas</h3>
                    <p class="benefit-description">Centenas de lojas parceiras em todas as categorias</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">🔒</div>
                    <h3 class="benefit-title">100% Seguro</h3>
                    <p class="benefit-description">Seus dados protegidos com criptografia avançada</p>
                </div>
            </div>
        </div>

        <!-- Seção do Formulário -->
        <div class="form-section">
            <div class="form-header">
                <h2 class="form-title">Crie sua conta</h2>
                <p class="form-subtitle">
                    Já tem conta? <a href="<?php echo LOGIN_URL; ?>">Faça login aqui</a>
                </p>
                <div class="security-badge">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    Cadastro 100% Gratuito e Seguro
                </div>
            </div>

            <form method="post" action="" class="register-form" id="register-form">
                <div class="input-group">
                    <label for="email" class="input-label required">E-mail</label>
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="input-field" 
                            placeholder="Digite seu melhor e-mail"
                            required 
                            autocomplete="email"
                            value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                        >
                    </div>
                    <div class="input-help">Usaremos este e-mail para enviar suas notificações de cashback</div>
                </div>

                <div class="input-row">
                    <div class="input-group">
                        <label for="nome" class="input-label required">Nome completo</label>
                        <div class="input-wrapper">
                            <input 
                                type="text" 
                                id="nome" 
                                name="nome" 
                                class="input-field" 
                                placeholder="Seu nome completo"
                                required 
                                autocomplete="name"
                                value="<?php echo isset($nome) ? htmlspecialchars($nome) : ''; ?>"
                            >
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="telefone" class="input-label required">Telefone</label>
                        <div class="input-wrapper">
                            <input 
                                type="tel" 
                                id="telefone" 
                                name="telefone" 
                                class="input-field" 
                                placeholder="(00) 00000-0000"
                                required 
                                autocomplete="tel"
                                value="<?php echo isset($telefone) ? htmlspecialchars($telefone) : ''; ?>"
                            >
                        </div>
                    </div>
                </div>

                <div class="input-group">
                    <label for="senha" class="input-label required">Senha</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="senha" 
                            name="senha" 
                            class="input-field" 
                            placeholder="Crie uma senha segura"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Mostrar/ocultar senha">
                            <svg id="eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="password-strength" id="password-strength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strength-fill"></div>
                        </div>
                        <div class="strength-text" id="strength-text">Digite pelo menos <?php echo PASSWORD_MIN_LENGTH; ?> caracteres</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" id="register-btn">
                    <span id="btn-text">Criar Minha Conta Grátis</span>
                </button>

                <div style="text-align: center; font-size: 0.75rem; color: var(--gray-500); line-height: 1.4;">
                    Ao criar sua conta, você concorda com nossos 
                    <a href="#" style="color: var(--primary-color);">Termos de Uso</a> e 
                    <a href="#" style="color: var(--primary-color);">Política de Privacidade</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // === SISTEMA DE TOAST MESSAGES ===
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
                    error: title || 'Ops!',
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

        // === SISTEMA DE SPINNER ===
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

        // === INSTANCIAR GERENCIADORES ===
        const toastManager = new ToastManager();
        const spinnerManager = new SpinnerManager();

        // === FUNÇÃO PARA ALTERNAR VISIBILIDADE DA SENHA ===
        function togglePassword() {
            const passwordField = document.getElementById('senha');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                passwordField.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }

        // === VALIDADOR DE FORÇA DA SENHA ===
        function checkPasswordStrength(password) {
            const strengthFill = document.getElementById('strength-fill');
            const strengthText = document.getElementById('strength-text');
            
            let score = 0;
            const checks = {
                length: password.length >= 8,
                lowercase: /[a-z]/.test(password),
                uppercase: /[A-Z]/.test(password),
                numbers: /\d/.test(password),
                special: /[^A-Za-z0-9]/.test(password)
            };
            
            score = Object.values(checks).filter(Boolean).length;
            
            // Remover classes anteriores
            strengthFill.className = 'strength-fill';
            strengthText.className = 'strength-text';
            
            if (password.length === 0) {
                strengthText.textContent = `Digite pelo menos <?php echo PASSWORD_MIN_LENGTH; ?> caracteres`;
                return;
            }
            
            if (score <= 2) {
                strengthFill.classList.add('weak');
                strengthText.classList.add('weak');
                strengthText.textContent = 'Senha fraca - adicione mais caracteres e variações';
            } else if (score === 3) {
                strengthFill.classList.add('fair');
                strengthText.classList.add('fair');
                strengthText.textContent = 'Senha razoável - pode melhorar';
            } else if (score === 4) {
                strengthFill.classList.add('good');
                strengthText.classList.add('good');
                strengthText.textContent = 'Senha boa - quase lá!';
            } else {
                strengthFill.classList.add('strong');
                strengthText.classList.add('strong');
                strengthText.textContent = 'Senha forte - perfeita!';
            }
        }

        // === MÁSCARA PARA TELEFONE ===
        function formatPhone(value) {
            // Remove tudo que não é dígito
            const numbers = value.replace(/\D/g, '');
            
            // Aplica a máscara
            if (numbers.length <= 11) {
                return numbers.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3')
                             .replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3')
                             .replace(/(\d{2})(\d{1,5})/, '($1) $2')
                             .replace(/(\d{2})/, '($1');
            }
            return value;
        }

        // === VALIDAÇÃO DE EMAIL ===
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // === VALIDAÇÃO DO FORMULÁRIO ===
        document.getElementById('register-form').addEventListener('submit', function(event) {
            event.preventDefault();
            
            const email = document.getElementById('email').value.trim();
            const nome = document.getElementById('nome').value.trim();
            const telefone = document.getElementById('telefone').value.trim();
            const senha = document.getElementById('senha').value;
            const registerBtn = document.getElementById('register-btn');
            const btnText = document.getElementById('btn-text');
            
            // Validação completa
            if (!email) {
                toastManager.error('Por favor, informe seu e-mail.');
                return;
            }
            
            if (!isValidEmail(email)) {
                toastManager.error('Por favor, informe um e-mail válido.');
                return;
            }
            
            if (!nome || nome.length < 3) {
                toastManager.error('Por favor, informe seu nome completo (mínimo 3 caracteres).');
                return;
            }
            
            if (!telefone || telefone.replace(/\D/g, '').length < 10) {
                toastManager.error('Por favor, informe um telefone válido.');
                return;
            }
            
            if (!senha || senha.length < <?php echo PASSWORD_MIN_LENGTH; ?>) {
                toastManager.error('A senha deve ter no mínimo <?php echo PASSWORD_MIN_LENGTH; ?> caracteres.');
                return;
            }
            
            // Mostrar loading
            const originalHTML = btnText.innerHTML;
            btnText.innerHTML = '<div class="loading-spinner"></div>Criando sua conta...';
            registerBtn.disabled = true;
            spinnerManager.show();
            
            // Simular delay para mostrar o loading
            setTimeout(() => {
                this.submit();
            }, 1500);
        });

        // === EVENT LISTENERS ===
        document.addEventListener('DOMContentLoaded', function() {
            const senhaField = document.getElementById('senha');
            const telefoneField = document.getElementById('telefone');
            const emailField = document.getElementById('email');
            const nomeField = document.getElementById('nome');

            // Verificador de força da senha
            senhaField.addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });

            // Máscara de telefone
            telefoneField.addEventListener('input', function() {
                this.value = formatPhone(this.value);
            });

            // Validação visual em tempo real
            emailField.addEventListener('blur', function() {
                if (this.value && isValidEmail(this.value)) {
                    this.classList.add('valid');
                } else {
                    this.classList.remove('valid');
                }
            });

            nomeField.addEventListener('input', function() {
                if (this.value.length >= 3) {
                    this.classList.add('valid');
                } else {
                    this.classList.remove('valid');
                }
            });

            // Verificar mensagens do PHP
            <?php if (!empty($error)): ?>
                toastManager.error('<?php echo addslashes($error); ?>');
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                toastManager.success('<?php echo addslashes($success); ?>', 'Bem-vindo!');
                setTimeout(() => {
                    window.location.href = '<?php echo LOGIN_URL; ?>';
                }, 2000);
            <?php endif; ?>

            // Verificar mensagens da URL
            const urlParams = new URLSearchParams(window.location.search);
            const errorParam = urlParams.get('error');
            const successParam = urlParams.get('success');

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

        // === PREVENÇÃO DE DUPLO SUBMIT ===
        let formSubmitted = false;
        document.getElementById('register-form').addEventListener('submit', function(event) {
            if (formSubmitted) {
                event.preventDefault();
                return;
            }
            formSubmitted = true;
        });

        // === MELHORIAS DE ACESSIBILIDADE ===
        document.addEventListener('keydown', function(event) {
            // Permitir fechar toast com ESC
            if (event.key === 'Escape') {
                const toasts = document.querySelectorAll('.toast.show');
                toasts.forEach(toast => toastManager.hide(toast));
            }
        });
    </script>
</body>
</html>
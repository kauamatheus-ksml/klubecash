<?php
// views/auth/login.php - VERSÃO CORRIGIDA
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php'; // ADICIONADO

// Verificar se já existe uma sessão ativa
session_start();
if (isset($_SESSION['user_id']) && !isset($_GET['force_login'])) {
    // Redirecionar com base no tipo de usuário
    if ($_SESSION['user_type'] == 'admin') {
        header('Location: ' . ADMIN_DASHBOARD_URL);
        exit;
    } else if ($_SESSION['user_type'] == 'loja') {
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
        // === USAR O MÉTODO AuthController::login() ===
        $result = AuthController::login($email, $password);
        
        if ($result['status']) {
            // Login bem-sucedido - Redirecionar com base no tipo de usuário
            $userType = $_SESSION['user_type'] ?? '';
            
            if ($userType == 'admin' || (defined('USER_TYPE_ADMIN') && $userType == USER_TYPE_ADMIN)) {
                header('Location: ' . ADMIN_DASHBOARD_URL);
            } else if ($userType == 'loja' || (defined('USER_TYPE_STORE') && $userType == USER_TYPE_STORE)) {
                header('Location: ' . STORE_DASHBOARD_URL);
            } else if ($userType == 'funcionario' || (defined('USER_TYPE_EMPLOYEE') && $userType == USER_TYPE_EMPLOYEE)) {
                header('Location: ' . STORE_DASHBOARD_URL);
            } else {
                header('Location: ' . CLIENT_DASHBOARD_URL);
            }
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Verificar mensagens de URL
$urlError = $_GET['error'] ?? '';
$urlSuccess = $_GET['success'] ?? '';
if (!empty($urlError)) {
    $error = urldecode($urlError);
}
if (!empty($urlSuccess)) {
    $success = urldecode($urlSuccess);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/auth.css">
    <link rel="stylesheet" href="../../assets/css/main.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="../../assets/images/logo.png" alt="<?php echo SYSTEM_NAME; ?>" class="logo">
                <h1>Entrar na sua conta</h1>
                <p>Bem-vindo de volta ao Klube Cash</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="icon-alert-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="icon-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           placeholder="seu@email.com">
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required
                           placeholder="Sua senha">
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" value="1">
                        <span class="checkbox-custom"></span>
                        Lembrar-me
                    </label>
                    <a href="recover-password.php" class="forgot-link">Esqueceu sua senha?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Entrar
                </button>
            </form>

            <div class="auth-footer">
                <p>Não tem uma conta? <a href="register.php">Cadastre-se</a></p>
                <p><a href="../../index.php">Voltar ao início</a></p>
            </div>
        </div>
    </div>

    <script src="../../assets/js/auth.js"></script>
</body>
</html>
<?php
// views/auth/login.php - VERSÃO COMPLETAMENTE CORRIGIDA
require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';

// Verificar se já existe uma sessão ativa
session_start();
if (isset($_SESSION['user_id']) && !isset($_GET['force_login'])) {
    // CORREÇÃO LINHA 18 - Redirecionar corretamente baseado no tipo
    $userType = $_SESSION['user_type'] ?? '';
    if ($userType == 'admin') {
        header('Location: ' . ADMIN_DASHBOARD_URL);
        exit;
    } else if ($userType == 'loja') {
        header('Location: ' . STORE_DASHBOARD_URL);
        exit;
    } else if ($userType == 'funcionario') {
        // CORREÇÃO CRÍTICA: FUNCIONÁRIO VAI PARA STORE
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
        // Usar AuthController::login()
        $result = AuthController::login($email, $password);
        
        if ($result['status']) {
            // CORREÇÃO LINHA 48 - Login bem-sucedido
            $userType = $_SESSION['user_type'] ?? '';
            
            if ($userType == 'admin') {
                header('Location: ' . ADMIN_DASHBOARD_URL);
            } else if ($userType == 'loja') {
                header('Location: ' . STORE_DASHBOARD_URL);
            } else if ($userType == 'funcionario') {
                // CORREÇÃO CRÍTICA: FUNCIONÁRIO VAI PARA STORE
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
                <h1>Entrar na sua conta</h1>
                <p>Bem-vindo de volta ao Klube Cash</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
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
</body>
</html>
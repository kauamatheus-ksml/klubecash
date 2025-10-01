<?php
// auth/google/callback.php - VERSÃO ATUALIZADA

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../utils/GoogleAuth.php';

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log da requisição para debug
error_log('Google OAuth Callback: ' . json_encode($_GET));

// Verificar se houve erro na autorização
if (isset($_GET['error'])) {
    $error = $_GET['error'];
    $errorDescription = $_GET['error_description'] ?? 'Erro desconhecido';
    
    error_log('Google OAuth: Erro na autorização - ' . $error . ': ' . $errorDescription);
    
    if ($error === 'access_denied') {
        $message = 'Autorização cancelada pelo usuário.';
    } else {
        $message = 'Erro na autorização: ' . $errorDescription;
    }
    
    // Redirecionar para a página apropriada baseado na ação
    $isRegister = isset($_SESSION['google_action']) && $_SESSION['google_action'] === 'register';
    $redirectUrl = $isRegister ? REGISTER_URL : LOGIN_URL;
    
    header('Location: ' . $redirectUrl . '?error=' . urlencode($message));
    exit;
}

// Verificar se recebeu o código de autorização
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    error_log('Google OAuth: Código ou state não recebido');
    
    $isRegister = isset($_SESSION['google_action']) && $_SESSION['google_action'] === 'register';
    $redirectUrl = $isRegister ? REGISTER_URL : LOGIN_URL;
    
    header('Location: ' . $redirectUrl . '?error=' . urlencode('Parâmetros de autorização incompletos'));
    exit;
}

$code = $_GET['code'];
$state = $_GET['state'];

// Verificar se é registro ou login
$isRegister = isset($_SESSION['google_action']) && $_SESSION['google_action'] === 'register';

error_log('Google OAuth: Processando callback - Ação: ' . ($isRegister ? 'REGISTRO' : 'LOGIN'));

// Processar baseado na ação
if ($isRegister) {
    $result = AuthController::googleRegister($code, $state);
    
    if ($result['status']) {
        // Registro bem-sucedido
        error_log('Google OAuth: Registro bem-sucedido, redirecionando para dashboard');
        
        // Para novos usuários, sempre redirecionar para cliente dashboard
        $redirectUrl = CLIENT_DASHBOARD_URL;
        $successMessage = 'Registro realizado com sucesso! Bem-vindo ao Klube Cash!';
        
        header('Location: ' . $redirectUrl . '?success=' . urlencode($successMessage));
    } else {
        // Erro no registro
        error_log('Google OAuth: Erro no registro - ' . $result['message']);
        
        // Se usuário já existe, redirecionar para login
        if (isset($result['redirect_to_login']) && $result['redirect_to_login']) {
            header('Location: ' . LOGIN_URL . '?error=' . urlencode($result['message']));
        } else {
            header('Location: ' . REGISTER_URL . '?error=' . urlencode($result['message']));
        }
    }
} else {
    // Processar como login
    $result = AuthController::googleLogin($code, $state);
    
    if ($result['status']) {
        // Login bem-sucedido
        error_log('Google OAuth: Login bem-sucedido, redirecionando usuário tipo: ' . $_SESSION['user_type']);
        
        switch ($_SESSION['user_type']) {
            case USER_TYPE_ADMIN:
                $redirectUrl = ADMIN_DASHBOARD_URL;
                break;
            case USER_TYPE_STORE:
                $redirectUrl = STORE_DASHBOARD_URL;
                break;
            default:
                $redirectUrl = CLIENT_DASHBOARD_URL;
                break;
        }
        
        $successMessage = isset($result['is_new_user']) && $result['is_new_user'] 
                         ? 'Conta criada e login realizado com sucesso!' 
                         : 'Login realizado com sucesso!';
        
        header('Location: ' . $redirectUrl . '?success=' . urlencode($successMessage));
    } else {
        // Erro no login
        error_log('Google OAuth: Erro no login - ' . $result['message']);
        header('Location: ' . LOGIN_URL . '?error=' . urlencode($result['message']));
    }
}

exit;
?>
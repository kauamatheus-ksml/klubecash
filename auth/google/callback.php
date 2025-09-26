<?php
// auth/google/callback.php - VERSAO ATUALIZADA

require_once '../../config/constants.php';
require_once '../../config/database.php';
require_once '../../controllers/AuthController.php';
require_once '../../utils/GoogleAuth.php';

// Iniciar sessao se nao estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log da requisicao para debug
error_log('Google OAuth Callback: ' . json_encode($_GET));

// Verificar se houve erro na autorizacao
if (isset($_GET['error'])) {
    $error = $_GET['error'];
    $errorDescription = $_GET['error_description'] ?? 'Erro desconhecido';
    
    error_log('Google OAuth: Erro na autorizacao - ' . $error . ': ' . $errorDescription);
    
    if ($error === 'access_denied') {
        $message = 'Autorizacao cancelada pelo usuario.';
    } else {
        $message = 'Erro na autorizacao: ' . $errorDescription;
    }
    
    // Redirecionar para a pagina apropriada baseado na acao
    $isRegister = isset($_SESSION['google_action']) && $_SESSION['google_action'] === 'register';
    $redirectUrl = $isRegister ? REGISTER_URL : LOGIN_URL;
    
    header('Location: ' . $redirectUrl . '?error=' . urlencode($message));
    exit;
}

// Verificar se recebeu o codigo de autorizacao
if (!isset($_GET['code']) || !isset($_GET['state'])) {
    error_log('Google OAuth: Codigo ou state nao recebido');
    
    $isRegister = isset($_SESSION['google_action']) && $_SESSION['google_action'] === 'register';
    $redirectUrl = $isRegister ? REGISTER_URL : LOGIN_URL;
    
    header('Location: ' . $redirectUrl . '?error=' . urlencode('Parametros de autorizacao incompletos'));
    exit;
}

$code = $_GET['code'];
$state = $_GET['state'];

// Verificar se e registro ou login
$isRegister = isset($_SESSION['google_action']) && $_SESSION['google_action'] === 'register';

error_log('Google OAuth: Processando callback - Acao: ' . ($isRegister ? 'REGISTRO' : 'LOGIN'));

// Processar baseado na acao
if ($isRegister) {
    $result = AuthController::googleRegister($code, $state);
    
    if ($result['status']) {
        // Registro bem-sucedido
        error_log('Google OAuth: Registro bem-sucedido, redirecionando para dashboard');
        
        // Para novos usuarios, sempre redirecionar para cliente dashboard
        $redirectUrl = CLIENT_DASHBOARD_URL;
        $successMessage = 'Registro realizado com sucesso! Bem-vindo ao Klube Cash!';
        
        header('Location: ' . $redirectUrl . '?success=' . urlencode($successMessage));
    } else {
        // Erro no registro
        error_log('Google OAuth: Erro no registro - ' . $result['message']);
        
        // Se usuario ja existe, redirecionar para login
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
        error_log('Google OAuth: Login bem-sucedido, redirecionando usuario tipo: ' . $_SESSION['user_type']);
        
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

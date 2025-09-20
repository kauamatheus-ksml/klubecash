<?php
// auth/google/register.php

require_once '../../config/constants.php';
require_once '../../utils/GoogleAuth.php';

// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar se é uma requisição GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => false, 'message' => 'Método não permitido']);
    exit;
}

// Definir cabeçalho JSON
header('Content-Type: application/json');

try {
    // Marcar na sessão que é um registro (não login)
    $_SESSION['google_action'] = 'register';
    
    // Gerar URL de autorização do Google (mesma do login)
    $authUrl = GoogleAuth::getAuthUrl();
    
    error_log('Google OAuth Register: URL de autorização gerada - ' . $authUrl);
    
    // Retornar URL para o JavaScript redirecionar
    echo json_encode([
        'status' => true,
        'auth_url' => $authUrl,
        'message' => 'URL de registro gerada com sucesso'
    ]);
    
} catch (Exception $e) {
    error_log('Erro ao gerar URL do Google para registro: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Erro ao conectar com Google. Tente novamente.'
    ]);
}
?>
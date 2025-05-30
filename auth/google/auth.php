<?php
// auth/google/auth.php

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
    // Gerar URL de autorização do Google
    $authUrl = GoogleAuth::getAuthUrl();
    
    error_log('Google OAuth: URL de autorização gerada - ' . $authUrl);
    
    // Retornar URL para o JavaScript redirecionar
    echo json_encode([
        'status' => true,
        'auth_url' => $authUrl,
        'message' => 'URL de autorização gerada com sucesso'
    ]);
    
} catch (Exception $e) {
    error_log('Erro ao gerar URL do Google: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => false,
        'message' => 'Erro ao conectar com Google. Tente novamente.'
    ]);
}
?>
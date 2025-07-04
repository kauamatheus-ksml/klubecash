<?php
// public_html/api2/login.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Em produção, restrinja isso ao seu domínio do app Flutter
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Exemplo para public_html/api2/login.php
require_once __DIR__ . '/../controllers/AuthController.php'; // Vai de api2/ para public_html/controllers/
require_once __DIR__ . '/../config/constants.php'; // Vai de api2/ para public_html/config/
// E assim por diante para todos os require_once em TODOS os seus arquivos dentro de public_html/api2/

if ($_SERVER['REQUEST_METHOD'] === 'POST' && file_get_contents('php://input')) {
    $data = json_decode(file_get_contents('php://input'), true);

    $email = $data['email'] ?? '';
    $senha = $data['senha'] ?? '';

    $result = AuthController::login($email, $senha);

    if ($result['status']) {
        $user = [
            'id' => $_SESSION['user_id'] ?? null,
            'nome' => $_SESSION['user_name'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'tipo' => $_SESSION['user_type'] ?? null,
        ];
        echo json_encode([
            'message' => $result['message'],
            'user' => $user,
            'token' => 'seu_super_token_secreto_aqui_para_simulacao'
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['message' => $result['message']]);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Método não permitido ou dados ausentes.']);
}
?>
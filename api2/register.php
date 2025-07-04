<?php
// public_html/api2/register.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../controllers/AuthController.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && file_get_contents('php://input')) {
    $data = json_decode(file_get_contents('php://input'), true);

    $nome = $data['nome'] ?? '';
    $email = $data['email'] ?? '';
    $telefone = $data['telefone'] ?? '';
    $senha = $data['senha'] ?? '';
    $tipo = $data['tipo'] ?? 'cliente';

    $result = AuthController::register($nome, $email, $telefone, $senha, $tipo);

    if ($result['status']) {
        http_response_code(201);
        echo json_encode(['message' => $result['message']]);
    } else {
        http_response_code(400);
        echo json_encode(['message' => $result['message']]);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Método não permitido ou dados ausentes.']);
}
?>
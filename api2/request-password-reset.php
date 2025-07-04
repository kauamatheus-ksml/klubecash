<?php
// public_html/api2/request-password-reset.php

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

    $email = $data['email'] ?? '';

    $result = AuthController::recoverPassword($email);

    if ($result['status']) {
        http_response_code(200);
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
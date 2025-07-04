<?php
// public_html/api2/change-password.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$authenticatedUserId = null;
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = null;
if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
}
$simulatedAuthToken = 'seu_super_token_secreto_aqui_para_simulacao'; 
$simulatedUserId = 9; 

if ($token === $simulatedAuthToken) {
    $authenticatedUserId = $simulatedUserId;
}

if ($authenticatedUserId === null) {
    http_response_code(401);
    echo json_encode(['message' => 'Não autorizado. Token ausente ou inválido.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $currentPassword = $data['currentPassword'] ?? '';
    $newPassword = $data['newPassword'] ?? '';

    if (empty($currentPassword) || empty($newPassword)) {
        http_response_code(400);
        echo json_encode(['message' => 'Senha atual e nova senha são obrigatórias.']);
        exit();
    }

    if (strlen($newPassword) < 8) {
        http_response_code(400);
        echo json_encode(['message' => 'A nova senha deve ter no mínimo 8 caracteres.']);
        exit();
    }
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).*$/', $newPassword)) {
        http_response_code(400);
        echo json_encode(['message' => 'A nova senha deve conter pelo menos uma letra maiúscula, uma minúscula e um número.']);
        exit();
    }

    try {
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT senha_hash FROM usuarios WHERE id = ?");
        $stmt->execute([$authenticatedUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['message' => 'Usuário não encontrado.']);
            exit();
        }

        if (password_verify($currentPassword, $user['senha_hash'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $db->prepare("UPDATE usuarios SET senha_hash = ? WHERE id = ?");
            $updateStmt->execute([$hashedPassword, $authenticatedUserId]);

            echo json_encode(['message' => 'Senha alterada com sucesso!']);
        } else {
            http_response_code(401);
            echo json_encode(['message' => 'Senha atual incorreta.']);
        }

    } catch (PDOException $e) {
        error_log('Erro em change-password.php: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['message' => 'Erro interno do servidor ao alterar senha.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Método não permitido.']);
}
?>
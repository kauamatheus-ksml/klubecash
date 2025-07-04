<?php
// public_html/api2/profile/update.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS'); // PUT e OPTIONS
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

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);

    $nome = $data['nome'] ?? null;
    $telefone = $data['telefone'] ?? null;
    $email_alternativo = $data['email_alternativo'] ?? null;
    $cep = $data['cep'] ?? null;
    $logradouro = $data['logradouro'] ?? null;
    $numero = $data['numero'] ?? null;
    $complemento = $data['complemento'] ?? null;
    $bairro = $data['bairro'] ?? null;
    $cidade = $data['cidade'] ?? null;
    $estado = $data['estado'] ?? null;

    try {
        $db = Database::getConnection();
        $db->beginTransaction();

        $stmtUser = $db->prepare("UPDATE usuarios SET nome = ?, telefone = ? WHERE id = ?");
        $stmtUser->execute([$nome, $telefone, $authenticatedUserId]);

        $stmtContactCheck = $db->prepare("SELECT id FROM usuarios_contato WHERE usuario_id = ?");
        $stmtContactCheck->execute([$authenticatedUserId]);
        if ($stmtContactCheck->fetch(PDO::FETCH_ASSOC)) {
            $stmtContact = $db->prepare("UPDATE usuarios_contato SET email_alternativo = ? WHERE usuario_id = ?");
            $stmtContact->execute([$email_alternativo, $authenticatedUserId]);
        } else {
            $stmtContact = $db->prepare("INSERT INTO usuarios_contato (usuario_id, telefone, email_alternativo) VALUES (?, ?, ?)");
            $stmtContact->execute([$authenticatedUserId, $telefone, $email_alternativo]);
        }

        $stmtAddressCheck = $db->prepare("SELECT id FROM usuarios_endereco WHERE usuario_id = ? AND principal = 1");
        $stmtAddressCheck->execute([$authenticatedUserId]);
        if ($stmtAddressCheck->fetch(PDO::FETCH_ASSOC)) {
            $stmtAddress = $db->prepare("UPDATE usuarios_endereco SET cep = ?, logradouro = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, estado = ? WHERE usuario_id = ? AND principal = 1");
            $stmtAddress->execute([$cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado, $authenticatedUserId]);
        } else {
            $stmtAddress = $db->prepare("INSERT INTO usuarios_endereco (usuario_id, cep, logradouro, numero, complemento, bairro, cidade, estado, principal) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
            $stmtAddress->execute([$authenticatedUserId, $cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado]);
        }

        $db->commit();
        echo json_encode(['message' => 'Perfil atualizado com sucesso!']);

    } catch (PDOException $e) {
        $db->rollBack();
        error_log('Erro em profile_update.php: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['message' => 'Erro interno do servidor ao atualizar perfil.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Método não permitido.']);
}
?>
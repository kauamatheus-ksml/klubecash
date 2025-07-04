<?php
// public_html/api2/profile.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Para testar, depois restrinja
header('Access-Control-Allow-Methods: GET, OPTIONS'); // Adicionado OPTIONS
header('Access-Control-Allow-Headers: Content-Type, Authorization'); // Essencial para CORS com headers personalizados

// Para requisições OPTIONS (preflight CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';

// --- SIMULAÇÃO BÁSICA DE AUTENTICAÇÃO VIA TOKEN (NÃO SEGURA PARA PROD) ---
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

try {
    $db = Database::getConnection();

    $stmt = $db->prepare(
        "SELECT
             u.nome, u.cpf, u.email, u.telefone, uc.email_alternativo,
             ue.cep, ue.logradouro, ue.numero, ue.complemento, ue.bairro, ue.cidade, ue.estado
           FROM usuarios u
           LEFT JOIN usuarios_contato uc ON u.id = uc.usuario_id
           LEFT JOIN usuarios_endereco ue ON u.id = ue.usuario_id AND ue.principal = 1
           WHERE u.id = ?"
    );
    $stmt->execute([$authenticatedUserId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
        echo json_encode(['user' => $userData]);
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'Usuário não encontrado.']);
    }

} catch (PDOException $e) {
    error_log('Erro no profile.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'Erro interno do servidor.']);
}
?>
<?php
// public_html/api2/profile.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();
function api_log($message) { file_put_contents(__DIR__ . '/api_debug.log', "[" . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND); } api_log("PONTO 1: Script profile.php iniciado.");

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); ob_end_flush(); exit();
}

api_log("PONTO 2: Headers CORS definidos. Iniciando bloco try-catch principal.");

try {
    api_log("PONTO 3: Tentando incluir dependências...");
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/constants.php';
    // require_once __DIR__ . '/../utils/Email.php'; // Geralmente não necessário para profile GET
    api_log("PONTO 4: Dependências incluídas com sucesso.");

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
        api_log("PONTO 5: Autenticação falhou. Token ausente ou inválido.");
        http_response_code(401); echo json_encode(['message' => 'Não autorizado. Token ausente ou inválido.']); ob_end_flush(); exit();
    }

    api_log("PONTO 6: Usuário autenticado (ID: $authenticatedUserId). Processando requisição GET.");

    $db = Database::getConnection(); // Tenta conectar ao banco de dados

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

    api_log("PONTO 7: Dados do usuário do banco: " . json_encode($userData));

    if ($userData) {
        http_response_code(200); // OK
        echo json_encode(['user' => $userData]);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['message' => 'Usuário não encontrado.']);
    }

} catch (Throwable $e) {
    $error_message = "PONTO 9: ERRO CATASTRÓFICO: " . $e->getMessage() . " em " . $e->getFile() . " na linha " . $e->getLine() . "\nStack trace:\n" . $e->getTraceAsString();
    api_log($error_message);
    http_response_code(500); echo json_encode(['message' => 'Erro interno do servidor. Detalhes em api_debug.log']);
}
ob_end_flush();
?>
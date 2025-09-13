<?php
// public_html/api2/change-password.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();
function api_log($message) { file_put_contents(__DIR__ . '/api_debug.log', "[" . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND); } api_log("PONTO 1: Script change-password.php iniciado.");

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); ob_end_flush(); exit();
}

api_log("PONTO 2: Headers CORS definidos. Iniciando bloco try-catch principal.");

try {
    api_log("PONTO 3: Tentando incluir dependências...");
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/constants.php';
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

    api_log("PONTO 6: Usuário autenticado (ID: $authenticatedUserId). Processando requisição POST.");

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input_data = file_get_contents('php://input');
        $data = json_decode($input_data, true);
        api_log("PONTO 7: Dados POST recebidos: " . $input_data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            api_log("ERRO: JSON Decode falhou: " . json_last_error_msg());
            http_response_code(400); echo json_encode(['message' => 'JSON inválido na requisição.']); ob_end_flush(); exit();
        }

        $currentPassword = $data['currentPassword'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        if (empty($currentPassword) || empty($newPassword)) {
            api_log("PONTO 8: Senha atual ou nova senha ausentes.");
            http_response_code(400); echo json_encode(['message' => 'Senha atual e nova senha são obrigatórias.']); ob_end_flush(); exit();
        }

        if (strlen($newPassword) < 8) {
            api_log("PONTO 8.1: Nova senha muito curta.");
            http_response_code(400); echo json_encode(['message' => 'A nova senha deve ter no mínimo 8 caracteres.']); ob_end_flush(); exit();
        }
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).*$/', $newPassword)) {
            api_log("PONTO 8.2: Nova senha não atende aos requisitos de complexidade.");
            http_response_code(400); echo json_encode(['message' => 'A nova senha deve conter pelo menos uma letra maiúscula, uma minúscula e um número.']); ob_end_flush(); exit();
        }

        $db = Database::getConnection(); // Conecta ao banco de dados

        api_log("PONTO 9: Conexão com DB estabelecida. Buscando senha hash do usuário.");

        $stmt = $db->prepare("SELECT senha_hash FROM usuarios WHERE id = ?");
        $stmt->execute([$authenticatedUserId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            api_log("PONTO 10: Usuário não encontrado para ID: $authenticatedUserId.");
            http_response_code(404); echo json_encode(['message' => 'Usuário não encontrado.']); ob_end_flush(); exit();
        }

        if (password_verify($currentPassword, $user['senha_hash'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            api_log("PONTO 11: Senha atual verificada. Atualizando senha no DB.");
            $updateStmt = $db->prepare("UPDATE usuarios SET senha_hash = ? WHERE id = ?");
            $updateStmt->execute([$hashedPassword, $authenticatedUserId]);

            api_log("PONTO 12: Senha alterada com sucesso no DB.");
            echo json_encode(['message' => 'Senha alterada com sucesso!']);
        } else {
            api_log("PONTO 13: Senha atual incorreta.");
            http_response_code(401); echo json_encode(['message' => 'Senha atual incorreta.']);
        }

    } else {
        api_log("PONTO 14: Método não permitido ou dados POST ausentes.");
        http_response_code(405); echo json_encode(['message' => 'Método não permitido.']);
    }

} catch (Throwable $e) {
    $error_message = "PONTO 15: ERRO CATASTRÓFICO: " . $e->getMessage() . " em " . $e->getFile() . " na linha " . $e->getLine() . "\nStack trace:\n" . $e->getTraceAsString();
    api_log($error_message);
    http_response_code(500); echo json_encode(['message' => 'Erro interno do servidor. Detalhes em api_debug.log']);
}
ob_end_flush();
?>
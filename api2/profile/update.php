<?php
// public_html/api2/profile/update.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();
function api_log($message) { file_put_contents(__DIR__ . '/../api_debug.log', "[" . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND); } // Loga um nível acima
api_log("PONTO 1: Script profile/update.php iniciado.");

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200); ob_end_flush(); exit();
}

api_log("PONTO 2: Headers CORS definidos. Iniciando bloco try-catch principal.");

try {
    api_log("PONTO 3: Tentando incluir dependências...");
    require_once __DIR__ . '/../../config/database.php'; // Dois níveis acima
    require_once __DIR__ . '/../../config/constants.php'; // Dois níveis acima
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

    api_log("PONTO 6: Usuário autenticado (ID: $authenticatedUserId). Processando requisição PUT.");

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $input_data = file_get_contents('php://input');
        $data = json_decode($input_data, true);
        api_log("PONTO 7: Dados PUT recebidos: " . $input_data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            api_log("ERRO: JSON Decode falhou: " . json_last_error_msg());
            http_response_code(400); echo json_encode(['message' => 'JSON inválido na requisição.']); ob_end_flush(); exit();
        }

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

        $db = Database::getConnection();
        $db->beginTransaction();

        api_log("PONTO 8: Atualizando tabelas: usuarios, usuarios_contato, usuarios_endereco.");

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
        api_log("PONTO 9: Perfil atualizado com sucesso no DB.");
        echo json_encode(['message' => 'Perfil atualizado com sucesso!']);

    } else {
        api_log("PONTO 10: Método não permitido ou dados PUT ausentes.");
        http_response_code(405); echo json_encode(['message' => 'Método não permitido.']);
    }

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    $error_message = "PONTO 11: ERRO CATASTRÓFICO: " . $e->getMessage() . " em " . $e->getFile() . " na linha " . $e->getLine() . "\nStack trace:\n" . $e->getTraceAsString();
    api_log($error_message);
    http_response_code(500); echo json_encode(['message' => 'Erro interno do servidor. Detalhes em api_debug.log']);
}
ob_end_flush();
?>
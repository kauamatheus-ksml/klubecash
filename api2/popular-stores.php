<?php
// public_html/api2/popular-stores.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();
function api_log($message) { file_put_contents(__DIR__ . '/api_debug.log', "[" . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND); } api_log("PONTO 1: Script popular-stores.php iniciado.");

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

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;

        api_log("PONTO 7: Buscando lojas para Limit: $limit.");
        $db = Database::getConnection();

        $stmt = $db->prepare(
            "SELECT
                 id,
                 nome_fantasia,
                 porcentagem_cashback,
                 logo
               FROM lojas
               WHERE status = 'aprovado'
               ORDER BY data_cadastro DESC
               LIMIT ?"
        );
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);

        api_log("PONTO 8: Lojas do DB: " . json_encode($stores));

        $formattedStores = array_map(function($s) {
            $s['porcentagem_cashback'] = (float)$s['porcentagem_cashback'];
            return $s;
        }, $stores);
        api_log("PONTO 9: Lojas formatadas: " . json_encode($formattedStores));

        echo json_encode(['stores' => $formattedStores]);

    } else {
        api_log("PONTO 10: Método não permitido ou dados GET ausentes.");
        http_response_code(405); echo json_encode(['message' => 'Método não permitido.']);
    }

} catch (Throwable $e) {
    $error_message = "PONTO 11: ERRO CATASTRÓFICO: " . $e->getMessage() . " em " . $e->getFile() . " na linha " . $e->getLine() . "\nStack trace:\n" . $e->getTraceAsString();
    api_log($error_message);
    http_response_code(500); echo json_encode(['message' => 'Erro interno do servidor. Detalhes em api_debug.log']);
}
ob_end_flush();
?>
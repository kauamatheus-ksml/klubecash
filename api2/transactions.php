<?php
// public_html/api2/transactions.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();
function api_log($message) { file_put_contents(__DIR__ . '/api_debug.log', "[" . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND); } api_log("PONTO 1: Script transactions.php iniciado.");

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
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        api_log("PONTO 7: Buscando transações para User ID: $authenticatedUserId com Limit: $limit, Offset: $offset.");
        $db = Database::getConnection();

        $stmt = $db->prepare(
            "SELECT
                 tc.id,
                 COALESCE(tc.valor_total, 0) AS valor_total,
                 COALESCE(tc.valor_cashback, 0) AS valor_cashback,
                 tc.data_transacao,
                 tc.status,
                 l.nome_fantasia AS loja_nome,
                 COALESCE(tsu.valor_usado, 0) AS valor_usado,
                 COALESCE(tc.valor_cliente, 0) AS valor_cashback_cliente
               FROM transacoes_cashback tc
               JOIN lojas l ON tc.loja_id = l.id
               LEFT JOIN transacoes_saldo_usado tsu ON tc.id = tsu.transacao_id
               WHERE tc.usuario_id = ?
               ORDER BY tc.data_transacao DESC
               LIMIT ? OFFSET ?"
        );
        $stmt->bindParam(1, $authenticatedUserId, PDO::PARAM_INT);
        $stmt->bindParam(2, $limit, PDO::PARAM_INT);
        $stmt->bindParam(3, $offset, PDO::PARAM_INT);
        $stmt->execute();
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        api_log("PONTO 8: Transações do DB: " . json_encode($transactions));

        $formattedTransactions = array_map(function($t) {
            $t['valor_total'] = (float)$t['valor_total'];
            $t['valor_cashback'] = (float)$t['valor_cashback_cliente'];
            $t['valor_usado'] = (float)$t['valor_usado'];
            unset($t['valor_cashback_cliente']);
            return $t;
        }, $transactions);
        api_log("PONTO 9: Transações formatadas: " . json_encode($formattedTransactions));

        echo json_encode(['transactions' => $formattedTransactions]);

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
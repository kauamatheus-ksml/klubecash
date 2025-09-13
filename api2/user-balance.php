<?php
// public_html/api2/user-balance.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();
function api_log($message) { file_put_contents(__DIR__ . '/api_debug.log', "[" . date('Y-m-d H:i:s') . "] $message\n", FILE_APPEND); } api_log("PONTO 1: Script user-balance.php iniciado.");

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
        api_log("PONTO 7: Buscando saldo para User ID: $authenticatedUserId.");
        $db = Database::getConnection();

        $stmt = $db->prepare(
            "SELECT
                 COALESCE(SUM(cs.saldo_disponivel), 0) AS saldo_disponivel,
                 COALESCE(SUM(cs.total_creditado), 0) AS total_creditado,
                 COALESCE(SUM(cs.total_usado), 0) AS total_usado,
                 COALESCE(SUM(CASE WHEN tc.status = 'pendente' AND tc.usuario_id = ? THEN tc.valor_cliente ELSE 0 END), 0) AS saldo_pendente
               FROM cashback_saldos cs
               LEFT JOIN transacoes_cashback tc ON cs.usuario_id = tc.usuario_id AND tc.status = 'pendente' -- Apenas transações pendentes para o cálculo
               WHERE cs.usuario_id = ?"
        );
        $stmt->execute([$authenticatedUserId, $authenticatedUserId]);
        $balanceData = $stmt->fetch(PDO::FETCH_ASSOC);

        api_log("PONTO 8: Saldo do DB: " . json_encode($balanceData));

        $formattedBalance = [
            'saldo_disponivel' => (float)$balanceData['saldo_disponivel'],
            'total_creditado' => (float)$balanceData['total_creditado'],
            'total_usado' => (float)$balanceData['total_usado'],
            'saldo_pendente' => (float)$balanceData['saldo_pendente'],
        ];
        api_log("PONTO 9: Saldo formatado: " . json_encode($formattedBalance));

        echo json_encode(['balance' => $formattedBalance]);

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
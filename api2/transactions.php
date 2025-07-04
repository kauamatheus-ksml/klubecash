<?php
// public_html/api2/transactions.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    try {
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

        // Formatar para float, pois o PDO pode retornar strings para DECIMAL
        $formattedTransactions = array_map(function($t) {
            $t['valor_total'] = (float)$t['valor_total'];
            $t['valor_cashback'] = (float)$t['valor_cashback_cliente']; // Usar valor_cashback_cliente
            $t['valor_usado'] = (float)$t['valor_usado'];
            unset($t['valor_cashback_cliente']); // Remover o campo auxiliar
            return $t;
        }, $transactions);

        echo json_encode(['transactions' => $formattedTransactions]);

    } catch (PDOException $e) {
        error_log('Erro em transactions.php: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['message' => 'Erro interno do servidor ao buscar histórico de transações.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Método não permitido.']);
}
?>